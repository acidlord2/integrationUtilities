# Wildberries Kosmos — orders arriving without stickers

| | |
|---|---|
| **Date** | 2026-07-25 |
| **Operator** | georgy.polyan@myant.ca |
| **Repo** | `Integration helper` (branch `master`) |
| **Shops touched** | Wildberries **Kosmos** (write), Wildberries **Ullo** (read-only check) |
| **Environments** | local `docker compose` stack (`integration-helper-app`, PHP 7.4-apache) talking to **production** WB + MoySklad APIs |
| **Reference** | WB FBS OpenAPI spec (`specs/03-orders-fbs.yaml` from `github.com/eslazarev/wildberries-sdk`, mirrors dev.wildberries.ru — the docs site itself returns HTTP 498 to non-browser clients) |

## Objective

Kosmos WB orders were landing in MoySklad without stickers; the log showed a mix of `429 too many requests`
and empty `{"stickers":[]}` responses. Find the real cause, fix it, keep the Kosmos and Ullo scripts
symmetric, and clear the backlog.

## Actions taken (chronological)

1. **Read the existing flow** — `wildberries/kosmos/getNewOrders.php`, `wildberries/ullo/getNewOrders.php`,
   `classes/Wildberries/{Orders,Supplies,Api}.php`, `wildberries/order.php`.
   Found stickers were requested **one order per HTTP request** inside the per-order loop, and an
   uncommitted `getStickerWithRetry()` helper that retried that same single-order call.
2. **First fix (later revised)** — batched `getStickersMap()`, plus a backfill pass, plus dedupe of sticker
   attributes on re-write. Mirrored kosmos → ullo.
3. **Checked the originals for hidden divergence** (user pushed back on "identical"): the committed
   kosmos/ullo files differed only in statement order and whitespace — nothing behavioural was lost by
   unifying them.
4. **Brought up the local stack** (`docker compose up -d --build`); the `settings` table in the local
   MySQL volume `integrationhelper_mysql_data` already held `WBApiTokenKosmos` / `WBApiTokenUllo`.
5. **Read-only probes against production WB** (scratchpad scripts, no writes):
   - `GET /api/v3/supplies/{id}/orders` → **404, endpoint no longer exists** (my first backfill guess).
   - `GET /api/v3/orders` → returns `supplyId`; used briefly, then dropped.
   - Mapped orders to supplies: **1 open supply, `isB2b: true`, holding 1 order**, while **233 regular
     orders sat with no supply** — the daily script-created supplies (`WB2026-07-xx …`, `isB2b: false`)
     had all been closed each morning.
6. **Checked the WB API docs on request** before changing anything further. Key rules found:
   - Stickers exist **only for orders in status `confirm` or `complete`**; adding to a supply is what
     moves `new → confirm`.
   - Max **100** ids per stickers request and per supply-add.
   - Rate limit for all FBS order/supply/pass methods: **300 req/min per seller, 200 ms interval,
     burst 20 — and one 4XX counts as 10 requests**.
   - A supply takes the `cargoType`/`crossBorderType` of its first order and cannot mix warehouses.
   - The supported way to list a supply's contents is
     **`GET /api/marketplace/v3/supplies/{supplyId}/order-ids`**.
7. **Confirmed the diagnosis live**: all 100 sampled waiting orders were `supplierStatus: new`, and the
   waiting orders were homogeneous (warehouse `1073256`, cargoType 1, crossBorderType 0) — so the only
   mismatch was the **B2B supply**. The script's "first supply with `closedAt == null`" was picking it,
   every add failed silently, orders stayed `new`, and no sticker could exist. Jammed since 23.07 13:18 UTC.
8. **Rewrote the fix** around the documented behaviour (see *Generated materials*).
9. **Single-order smoke test** (approved write): skipped the B2B supply → created `WB-GI-258340153` →
   added order `5369736171` → status flipped `new → confirm` → sticker returned on the first attempt.
10. **MoySklad half of the smoke test**: attached the sticker to `WB5369736171` via the real transformer
    + upsert, then downloaded it back from MoySklad (13 484 bytes, valid PNG signature).
11. **Verified the status question** — WB side bumps `new → confirm` via the supply add; MoySklad state
    stays `MS_MPNEW_STATE` «новый (маркетплейс)» and is preserved by the sticker write.
12. **Full Kosmos run** (approved): `Processed 209, created 0, stickers updated 209` in 322 s.
13. **Caught a second failure mode from the run**: of 2 newly arrived orders only 1 got a sticker — the
    other, `5374685014`, is `isB2B: true`. WB accepted the regular order from the chunk and rejected the
    B2B one, which would then be retried and fail on every future run. Added the B2B-order skip and
    extended the backfill to scan **all** open supplies (B2B included).
14. **Re-ran via CLI with logging** to verify: B2B order skipped and logged; backfill self-healed the
    order missed in step 13 (`sticker.backfill orders - [5374666371]`).
15. **Checked for 429s** (user request): none anywhere. Re-proved batching at scale read-only —
    212 stickers in 3 requests (100/100/12), 12.9 s, `getStickersMap.received - 212 of 212`.

## Generated materials / resources

### Code changed (all linted with `php -l` on **php:7.4-cli**, matching the Dockerfile)

| File | Change |
|---|---|
| `classes/Wildberries/Orders.php` | `getStickersMap($ids, $maxAttempts)` — chunks of 100, indexes by `orderId`, re-requests only missing ids with 2→4→8→15 s backoff; `stripStickerFiles()` keeps base64 out of the log |
| `classes/Wildberries/Supplies.php` | `getOpenSupplies()` (all open, logs `isB2b`); `getSupplyOrderIds()` via `order-ids`; `addOrdersToSupply()` checks HTTP 204, logs `addOrdersToSupply.FAILED`, returns accepted ids; `createSupply()` returns `name`/`closedAt`/`done` with the id |
| `classes/Wildberries/Api.php` | records HTTP status on every call; `getLastHttpCode()` |
| `wildberries/order.php` | `transformWildberriesStickerToMS()` strips existing copies of the 4 sticker attributes before adding them |
| `wildberries/kosmos/getNewOrders.php`<br>`wildberries/ullo/getNewOrders.php` | non-B2B supply pick; B2B **orders** excluded from the add; stickers only for ids WB accepted; backfill across every open supply; removed dead `$changeStatus`. **Byte-identical apart from `$shop`** |

`python/qr-sticker-generator.py` was already modified before this session — untouched here.

### WB resources created (production)

- Supply **`WB-GI-258340153`** «WB2026-07-25 11:03:39» — now holds **211** orders, all with stickers.
- 210 Kosmos orders moved `new → confirm` (209 in the bulk run + 1 in the smoke test).
- Untouched: B2B supply `WB-GI-257783013` (1 order) — left for handling in the seller UI.

### Scratchpad probes (not in the repo)

`wb-sticker-probe.php`, `wb-endpoint-probe.php`, `wb-orders-probe.php`, `wb-supply-map.php`,
`wb-b2b-check.php`, `wb-status-check.php`, `wb-smoke-test.php`, `ms-order-check.php`,
`ms-backfill-one.php`, `ms-file-verify.php`, `ms-state-check.php`, `wb-health.php`,
`wb-batch-proof.php`, `run-as-cli.php` — under the session scratchpad. `wb-health.php` and
`run-as-cli.php` are the two worth keeping if this recurs.

## Outcome

✅ **Root cause identified and doc-confirmed**: the only open supply was a **B2B** supply; the script
picked it, `addOrdersToSupply` failed silently (`patchData` discarded the HTTP status), orders stayed in
status `new`, and per the WB spec a `new` order **has no sticker** — hence `{"stickers":[]}`. The 429s were
a separate, compounding symptom of one-request-per-order (each 4XX costing 10 requests against 300/min).

✅ **Backlog cleared**: Kosmos `/orders/new` went 209 → 1; the open supply holds 211 orders, **211/211 with
sticker files in MoySklad**, 0 missing.

✅ **Verified end-to-end** for `WB5369736171`: WB `new` → supply add (204) → `confirm` → sticker →
MoySklad attributes (`Номер доставки 5628685-8777`, `Штрихкод *DRt…`, `Служба доставки`, `QR WB`) →
file downloaded back as a valid PNG.

✅ **Rate limiting resolved**: 0 occurrences of `too many requests`; 212 stickers now cost 3 requests.

✅ **Backfill proven self-healing** on a real miss (`5374666371`).

✅ **Ullo checked**: 0 waiting orders, no B2B supply, no open supply — healthy, nothing to process. Its
script is identical to Kosmos apart from `$shop`.

⚠️ **Ullo's fixed script has not been exercised against its own data** — there was nothing to process.

⚠️ **Order `5374685014` (B2B) remains in `/orders/new`** by design. It's already in MoySklad without a
sticker; it will keep appearing in the `Processed N` count until WB places it into a B2B supply, at which
point the backfill attaches its sticker automatically.

⚠️ **Not yet deployed** — changes are uncommitted in the working tree; the user intended to merge and ship.

❌ **Not verified**: behaviour when `addOrdersToSupply` returns a hard 409 for a whole chunk (today WB
applied the chunk partially, accepting the regular order and rejecting the B2B one).

## Open issues / follow-ups

1. **Ship it** — the fix is uncommitted. Both shop scripts plus 4 class files.
2. **`max_execution_time = 300`** — the 209-order run took **322 s wall clock** and did not abort, because
   the limit counts CPU time, not `curl` wait. If cron invokes the script over HTTP on the server, check
   the web server / proxy timeout; the daily first run is ~200 orders. Most of the time is the MoySklad
   upsert, not WB.
3. **Watch for a recurrence** of the jam: `getOpenSupplies.open … isB2b=true` followed by
   `addOrdersToSupply.FAILED` in `logs/classes - Wildberries - Supplies.log` is now the signature.
4. **Shared class log** — `classes - Wildberries - Orders.log` is written by *both* shops, which made the
   original log confusing (the run that looked healthy was Ullo, the 429 storm was Kosmos). Consider a
   per-shop log name.
5. **Local logging quirk** — under Apache in the container, `www-data` cannot write the class logs on the
   Windows bind mount. Use the CLI wrapper locally. Not an issue on the server.
6. Consider whether B2B orders should be created in MoySklad at all, or handled by a separate flow.

## Key commands (reference)

```powershell
# local stack (tokens live in the settings table of the mysql volume — not in the repo)
docker compose up -d --build

# run a site script as root so the bind-mounted logs are writable
docker cp scratchpad\run-as-cli.php integration-helper-app:/tmp/run.php
docker exec integration-helper-app php /tmp/run.php /var/www/html/wildberries/kosmos/getNewOrders.php

# or over HTTP (logs partially unwritable locally)
docker exec integration-helper-app curl -s -m 900 http://localhost/wildberries/kosmos/getNewOrders.php

# health check for either shop
docker exec integration-helper-app php /tmp/health.php Kosmos
docker exec integration-helper-app php /tmp/health.php Ullo

# lint against the production PHP version
docker run --rm -v "<repo>:/app" -w //app php:7.4-cli php -l wildberries/kosmos/getNewOrders.php

# keep the shops symmetric
sed "s/\$shop = 'Kosmos';/\$shop = 'Ullo';/" wildberries/kosmos/getNewOrders.php > wildberries/ullo/getNewOrders.php
diff <(sed "s/Ullo/SHOP/g" wildberries/ullo/getNewOrders.php) <(sed "s/Kosmos/SHOP/g" wildberries/kosmos/getNewOrders.php)

# 429 / batching audit
grep -c "too many requests" "logs/classes - Wildberries - Orders.log"
grep "getStickersMap" "logs/classes - Wildberries - Orders.log" | tail -3
```

### WB endpoints used

| Method | Path | Note |
|---|---|---|
| GET | `/api/v3/orders/new` | only orders **not** in a supply |
| GET | `/api/v3/supplies?limit&next` | `isB2b` flag lives here |
| POST | `/api/v3/supplies` | body `{name}` only — API-created supplies are always non-B2B |
| PATCH | `/api/marketplace/v3/supplies/{id}/orders` | ≤100 ids; **204** = accepted; moves `new → confirm` |
| GET | `/api/marketplace/v3/supplies/{id}/order-ids` | replaces the removed `…/{id}/orders` GET (404 now) |
| POST | `/api/v3/orders/stickers?type=png&width=58&height=40` | ≤100 ids; only for `confirm`/`complete` |
| POST | `/api/v3/orders/status` | read-only status check |
