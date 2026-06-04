import csv
from openpyxl import Workbook
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side


COLUMNS = [
    ("Nome", "name"),
    ("Email", "email"),
    ("Telefone", "phone"),
    ("WhatsApp", "whatsapp"),
    ("Website", "website"),
    ("Score", "score"),
    ("Tier", "tier"),
    ("Status", "status"),
    ("Valor Negócio", "deal_value"),
    ("Notas", "deal_notes"),
    ("Origem", "source"),
    ("Categoria", "category"),
    ("Site Verificado", "site_verified"),
]


def export_csv(leads, filepath):
    with open(filepath, "w", newline="", encoding="utf-8-sig") as f:
        writer = csv.writer(f, delimiter=";")
        writer.writerow([col[0] for col in COLUMNS])

        for lead in leads:
            row = []
            for _, key in COLUMNS:
                val = lead.get(key, "")
                if key == "site_verified":
                    val = "Sim" if val else "Não"
                row.append(val)
            writer.writerow(row)


def export_xlsx(leads, filepath):
    wb = Workbook()
    ws = wb.active
    ws.title = "Leads"

    header_font = Font(name="Inter", bold=True, color="FFFFFF", size=11)
    header_fill = PatternFill(start_color="1F2937", end_color="1F2937", fill_type="solid")
    header_alignment = Alignment(horizontal="center", vertical="center")
    thin_border = Border(
        left=Side(style="thin", color="E5E7EB"),
        right=Side(style="thin", color="E5E7EB"),
        top=Side(style="thin", color="E5E7EB"),
        bottom=Side(style="thin", color="E5E7EB"),
    )

    for col_idx, (header, _) in enumerate(COLUMNS, 1):
        cell = ws.cell(row=1, column=col_idx, value=header)
        cell.font = header_font
        cell.fill = header_fill
        cell.alignment = header_alignment
        cell.border = thin_border

    data_font = Font(name="Inter", size=10)
    even_fill = PatternFill(start_color="F9FAFB", end_color="F9FAFB", fill_type="solid")

    hot_fill = PatternFill(start_color="FEF3C7", end_color="FEF3C7", fill_type="solid")
    warm_fill = PatternFill(start_color="FFEDD5", end_color="FFEDD5", fill_type="solid")

    for row_idx, lead in enumerate(leads, 2):
        for col_idx, (_, key) in enumerate(COLUMNS, 1):
            val = lead.get(key, "")
            if key == "site_verified":
                val = "Sim" if val else "Não"

            cell = ws.cell(row=row_idx, column=col_idx, value=val)
            cell.font = data_font
            cell.border = thin_border

            if key == "tier":
                tier = lead.get("tier", "")
                if tier == "hot":
                    cell.fill = hot_fill
                elif tier == "warm":
                    cell.fill = warm_fill
            elif row_idx % 2 == 0:
                cell.fill = even_fill

    col_widths = [30, 35, 20, 20, 40, 8, 10, 12, 15, 25, 14]
    for i, width in enumerate(col_widths, 1):
        ws.column_dimensions[chr(64 + i)].width = width

    ws.freeze_panes = "A2"
    ws.auto_filter.ref = ws.dimensions

    wb.save(filepath)
