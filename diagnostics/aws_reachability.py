#!/usr/bin/env python3
"""
Can this host reach the marketplace APIs at all?

Run before committing to an AWS region: the open question is not whether the code works,
it is whether the marketplaces answer *this* source address. MoySklad already blocks one of
our Russian IPs while allowing another, so the block is per-IP reputation, not geography -
which is exactly the risk when egressing from shared cloud ranges.

No credentials required, by design. An HTTP 401 proves reachability just as well as a 200,
so this is safe to run in any account with no secrets configured.

Stages are reported separately - DNS -> TCP -> TLS -> HTTP - because that is what tells a
blocked route apart from a poisoned resolver apart from a TLS middlebox.

    local:   python3 diagnostics/aws_reachability.py
    lambda:  handler = aws_reachability.lambda_handler   (python3.x, no dependencies, 30s timeout)

Exit code is 0 only if every endpoint answered.

IMPORTANT: run it twice, in both egress configurations, because that is the whole question -
  1. plain Lambda (no VPC)         -> shared AWS egress, rotating addresses
  2. Lambda in VPC + NAT + EIP     -> one stable, allowlistable address
Configuration 2 is the only one you can ask a marketplace to unblock.
"""

import json
import socket
import ssl
import sys
import time
import urllib.error
import urllib.request

CONNECT_TIMEOUT = 8
HTTP_TIMEOUT = 15

# endpoint, method, and what a reachable host looks like. 401/403 is a *success* here:
# it means the request arrived and was understood.
TARGETS = [
    {
        "name": "MoySklad",
        "host": "api.moysklad.ru",
        "url": "https://api.moysklad.ru/api/remap/1.2/context/employee",
        "method": "GET",
        "expect": "401 (or 415) - unauthenticated but reachable",
    },
    {
        "name": "Wildberries marketplace",
        "host": "marketplace-api.wildberries.ru",
        "url": "https://marketplace-api.wildberries.ru/ping",
        "method": "GET",
        "expect": "401 unauthorized - reachable",
    },
    {
        "name": "Wildberries content",
        "host": "content-api.wildberries.ru",
        "url": "https://content-api.wildberries.ru/ping",
        "method": "GET",
        "expect": "401 unauthorized - reachable",
    },
    {
        "name": "Ozon seller",
        "host": "api-seller.ozon.ru",
        "url": "https://api-seller.ozon.ru/v2/warehouse/list",
        "method": "POST",
        "expect": "401/403 - reachable",
    },
    {
        "name": "Yandex.Market partner",
        "host": "api.partner.market.yandex.ru",
        "url": "https://api.partner.market.yandex.ru/v2/campaigns",
        "method": "GET",
        "expect": "401 unauthorized - reachable",
    },
]


def egress_ip():
    """The address the marketplaces actually see. The crux of the whole exercise."""
    for url in ("https://api.ipify.org", "https://checkip.amazonaws.com"):
        try:
            req = urllib.request.Request(url, headers={"User-Agent": "reachability-probe"})
            with urllib.request.urlopen(req, timeout=6) as r:
                return r.read().decode().strip()
        except Exception:
            continue
    return None


def probe_dns(host):
    t0 = time.time()
    try:
        infos = socket.getaddrinfo(host, 443, socket.AF_INET, socket.SOCK_STREAM)
        ips = sorted({i[4][0] for i in infos})
        return {"ok": True, "ms": round((time.time() - t0) * 1000), "ips": ips}
    except Exception as e:
        return {"ok": False, "ms": round((time.time() - t0) * 1000), "error": str(e)}


def probe_tcp(ip, port=443):
    t0 = time.time()
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.settimeout(CONNECT_TIMEOUT)
    try:
        s.connect((ip, port))
        return {"ok": True, "ms": round((time.time() - t0) * 1000)}
    except Exception as e:
        return {"ok": False, "ms": round((time.time() - t0) * 1000), "error": str(e)}
    finally:
        try:
            s.close()
        except Exception:
            pass


def probe_tls(host, ip, port=443):
    """Also surfaces the certificate issuer - a surprise issuer means something is
    intercepting TLS, which breaks these APIs in ways that look like random errors."""
    t0 = time.time()
    ctx = ssl.create_default_context()
    raw = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    raw.settimeout(CONNECT_TIMEOUT)
    try:
        raw.connect((ip, port))
        with ctx.wrap_socket(raw, server_hostname=host) as tls:
            cert = tls.getpeercert() or {}
            issuer = ""
            for part in cert.get("issuer", ()):
                for k, v in part:
                    if k == "organizationName":
                        issuer = v
            return {
                "ok": True,
                "ms": round((time.time() - t0) * 1000),
                "version": tls.version(),
                "issuer": issuer or "?",
            }
    except Exception as e:
        return {"ok": False, "ms": round((time.time() - t0) * 1000), "error": str(e)}
    finally:
        try:
            raw.close()
        except Exception:
            pass


def probe_http(target):
    t0 = time.time()
    data = b"{}" if target["method"] == "POST" else None
    req = urllib.request.Request(
        target["url"],
        data=data,
        method=target["method"],
        headers={
            "Content-Type": "application/json",
            # identity: no credentials means no need to decode, and it avoids depending on
            # whichever compression this runtime's ssl/zlib happens to support
            "Accept-Encoding": "identity",
            "User-Agent": "reachability-probe",
        },
    )
    try:
        with urllib.request.urlopen(req, timeout=HTTP_TIMEOUT) as r:
            return {"reached": True, "status": r.status, "ms": round((time.time() - t0) * 1000)}
    except urllib.error.HTTPError as e:
        # an HTTP status - however unhappy - means the API answered us
        return {"reached": True, "status": e.code, "ms": round((time.time() - t0) * 1000)}
    except Exception as e:
        return {"reached": False, "status": None, "ms": round((time.time() - t0) * 1000),
                "error": "%s: %s" % (type(e).__name__, e)}


def run():
    started = time.strftime("%Y-%m-%d %H:%M:%S UTC", time.gmtime())
    ip = egress_ip()
    results = []

    for t in TARGETS:
        r = {"name": t["name"], "host": t["host"], "expect": t["expect"]}
        r["dns"] = probe_dns(t["host"])
        if r["dns"]["ok"]:
            first = r["dns"]["ips"][0]
            r["tcp"] = probe_tcp(first)
            r["tls"] = probe_tls(t["host"], first) if r["tcp"]["ok"] else {"ok": False, "skipped": True}
        else:
            r["tcp"] = {"ok": False, "skipped": True}
            r["tls"] = {"ok": False, "skipped": True}
        r["http"] = probe_http(t)

        if r["http"]["reached"]:
            r["verdict"] = "REACHABLE"
        elif not r["dns"]["ok"]:
            r["verdict"] = "DNS FAIL"
        elif not r["tcp"]["ok"]:
            r["verdict"] = "TCP BLOCKED"
        elif not r["tls"]["ok"]:
            r["verdict"] = "TLS FAIL"
        else:
            r["verdict"] = "HTTP FAIL"
        results.append(r)

    blocked = [r["name"] for r in results if r["verdict"] != "REACHABLE"]
    return {
        "started": started,
        "egress_ip": ip,
        "results": results,
        "blocked": blocked,
        "all_reachable": not blocked,
    }


def render(report):
    lines = []
    lines.append("marketplace reachability probe - %s" % report["started"])
    lines.append("egress ip seen by the internet: %s" % (report["egress_ip"] or "UNKNOWN (outbound http blocked?)"))
    lines.append("")
    for r in report["results"]:
        lines.append("%-28s %s" % (r["name"], r["verdict"]))
        d = r["dns"]
        lines.append("   dns   %s" % ("%s (%d ms)" % (", ".join(d["ips"]), d["ms"]) if d["ok"]
                                      else "FAILED %s" % d.get("error")))
        for stage in ("tcp", "tls"):
            s = r[stage]
            if s.get("skipped"):
                lines.append("   %s   skipped" % stage)
            elif s["ok"]:
                extra = ""
                if stage == "tls":
                    extra = "  %s  issuer=%s" % (s.get("version"), s.get("issuer"))
                lines.append("   %s   ok (%d ms)%s" % (stage, s["ms"], extra))
            else:
                lines.append("   %s   FAILED after %d ms - %s" % (stage, s["ms"], s.get("error")))
        h = r["http"]
        if h["reached"]:
            lines.append("   http  HTTP %s (%d ms)   expected: %s" % (h["status"], h["ms"], r["expect"]))
        else:
            lines.append("   http  NO RESPONSE after %d ms - %s" % (h["ms"], h.get("error")))
        lines.append("")

    if report["all_reachable"]:
        lines.append("ALL REACHABLE from %s - this egress address is not being blocked." % report["egress_ip"])
    else:
        lines.append("BLOCKED: %s" % ", ".join(report["blocked"]))
        lines.append("")
        lines.append("  TCP BLOCKED on one host only -> that API is dropping traffic from this address.")
        lines.append("     Only fixable with a stable EIP you can ask them to allowlist.")
        lines.append("  TCP BLOCKED on every host    -> this subnet has no working egress (NAT/SG/route).")
        lines.append("  DNS FAIL                     -> resolver, not reachability.")
        lines.append("  TLS FAIL / odd issuer        -> something is intercepting TLS.")
    return "\n".join(lines)


def lambda_handler(event, context):
    report = run()
    print(render(report))
    return {"statusCode": 200 if report["all_reachable"] else 503,
            "headers": {"Content-Type": "application/json"},
            "body": json.dumps(report, ensure_ascii=False)}


if __name__ == "__main__":
    report = run()
    if "--json" in sys.argv:
        print(json.dumps(report, indent=2, ensure_ascii=False))
    else:
        print(render(report))
    sys.exit(0 if report["all_reachable"] else 1)
