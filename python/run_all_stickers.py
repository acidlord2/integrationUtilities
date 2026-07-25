"""
Driver: batch-generate stickers for the July 2026 Stickers folder.
Reuses the validated functions from qr-sticker-generator.py.
Each entry: (gtin_used_to_filter, codes_xlsx_path). GEL products use their OLD GTIN
(ШК NEW mapping from 'заведение ЧЗ...'), and their template was renamed to that old GTIN.
"""
import os
import importlib.util

# Load qr-sticker-generator.py (hyphenated filename) as a module
_spec = importlib.util.spec_from_file_location("qsg", "qr-sticker-generator.py")
qsg = importlib.util.module_from_spec(_spec)
_spec.loader.exec_module(qsg)

EX = "Stickers/extracted"
JOBS = [
    ("04901301299031", f"{EX}/file-9b3f8be9-919a-4c16-a7d2-bac8b67ad1f8.xlsx"),  # Biore Perfect oil Refill 240
    ("04901301451149", f"{EX}/file-da98c375-d415-4928-8c08-f16767f2b98a.xlsx"),  # Attack Multiaction GEL ->467430  800
    ("04901301451170", f"{EX}/file-da98c375-d415-4928-8c08-f16767f2b98a.xlsx"),  # Attack BioEx GEL ->467485  800
    ("04901301453976", f"{EX}/file-ec429da3-a780-48b2-b512-4b9ff7b1ba0c.xlsx"),  # Biore gel dush 1260
    ("04901301453983", f"{EX}/file-ec429da3-a780-48b2-b512-4b9ff7b1ba0c.xlsx"),  # Biore gel dush 1260
    ("04901301453990", f"{EX}/file-ec429da3-a780-48b2-b512-4b9ff7b1ba0c.xlsx"),  # Biore gel dush 1260
    ("04901301452429", f"{EX}/file-9b3f8be9-919a-4c16-a7d2-bac8b67ad1f8.xlsx"),  # Attack BioEx Powder Refill 3000
    ("04901301453334", f"{EX}/file-da98c375-d415-4928-8c08-f16767f2b98a.xlsx"),  # Attack Multiaction Powder Refill 3000
    ("04901301452412", f"{EX}/file-9b3f8be9-919a-4c16-a7d2-bac8b67ad1f8.xlsx"),  # Attack BioEx Powder 8600
    ("04901301452436", f"{EX}/file-da98c375-d415-4928-8c08-f16767f2b98a.xlsx"),  # Attack Multiaction Powder 8600
]

INPUT_FOLDER = "Stickers"
summary = []

for gtin, input_file in JOBS:
    print("\n" + "=" * 70)
    print(f"GTIN {gtin}  <-  {input_file}")
    print("=" * 70)
    template = f"{gtin}.docx"
    if not os.path.exists(os.path.join(INPUT_FOLDER, template)):
        print(f"!! template {template} missing, skipping")
        summary.append((gtin, "NO TEMPLATE", 0, 0))
        continue
    if not os.path.exists(input_file):
        print(f"!! codes file missing, skipping")
        summary.append((gtin, "NO CODES FILE", 0, 0))
        continue

    matched_rows = qsg.read_xlsx_with_gtin(input_file, gtin)
    if not matched_rows:
        summary.append((gtin, "NO MATCHES", 0, 0))
        continue

    qsg.generate_barcodes_for_rows(matched_rows, gtin, output_dir="barcodes", size=qsg.sticker_size)

    total_rows = len(matched_rows)
    num_batches = (total_rows + qsg.MAX_STICKERS_PER_FILE - 1) // qsg.MAX_STICKERS_PER_FILE
    print(f"Splitting {total_rows} stickers into {num_batches} file(s)")
    pdfs = 0
    for batch_idx in range(num_batches):
        start_idx = batch_idx * qsg.MAX_STICKERS_PER_FILE
        end_idx = min(start_idx + qsg.MAX_STICKERS_PER_FILE, total_rows)
        batch_rows = matched_rows[start_idx:end_idx]
        batch_num = batch_idx + 1 if num_batches > 1 else None
        print(f"  batch {batch_idx + 1}/{num_batches} (stickers {start_idx + 1}-{end_idx})")
        doc_file = qsg.append_codes_to_template(
            batch_rows, barcodes_dir="barcodes", gtin=gtin, result_dir="result",
            template_path=template, template_folder=INPUT_FOLDER, batch_num=batch_num,
        )
        if doc_file:
            pdf_suffix = f"_part{batch_num}" if batch_num else ""
            pdf_path = os.path.join("result", f"output_{gtin}{pdf_suffix}.pdf")
            print(f"  -> PDF {pdf_path}")
            qsg.convert(doc_file, pdf_path)
            pdfs += 1
    summary.append((gtin, "OK", total_rows, pdfs))

print("\n" + "#" * 70)
print("SUMMARY")
for gtin, status, n, pdfs in summary:
    print(f"  {gtin}  {status:14s} stickers={n:6d} pdfs={pdfs}")
