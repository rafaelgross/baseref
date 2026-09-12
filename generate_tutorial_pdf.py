"""
Gera o PDF do Tutorial PRISMA-SLR com capa, sumário e formatação profissional.
"""
from reportlab.lib.pagesizes import A4
from reportlab.lib import colors
from reportlab.lib.units import cm
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_JUSTIFY
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, PageBreak, Table, TableStyle,
    HRFlowable, KeepTogether
)
from reportlab.platypus.tableofcontents import TableOfContents
from reportlab.pdfgen import canvas
from reportlab.lib.colors import HexColor
import os

OUTPUT = os.path.join(os.path.dirname(__file__), "TUTORIAL.pdf")

# ── Paleta de cores ──────────────────────────────────────────────────────────
C_PRIMARY   = HexColor("#1d4ed8")   # azul PRISMA
C_PRIMARY_L = HexColor("#eff6ff")   # azul claro
C_ACCENT    = HexColor("#0ea5e9")   # azul céu
C_GREEN     = HexColor("#16a34a")
C_GRAY      = HexColor("#6b7280")
C_DARK      = HexColor("#111827")
C_BORDER    = HexColor("#e5e7eb")
C_CODE_BG   = HexColor("#f3f4f6")
C_TIP_BG    = HexColor("#eff6ff")
C_WARN_BG   = HexColor("#fefce8")
C_WARN_BORDER = HexColor("#eab308")
WHITE       = colors.white

PAGE_W, PAGE_H = A4
MARGIN = 2.2 * cm

# ── Estilos ──────────────────────────────────────────────────────────────────
base_styles = getSampleStyleSheet()

def make_styles():
    s = {}

    s["cover_title"] = ParagraphStyle(
        "cover_title", fontSize=28, leading=34, alignment=TA_CENTER,
        textColor=WHITE, fontName="Helvetica-Bold", spaceAfter=10
    )
    s["cover_subtitle"] = ParagraphStyle(
        "cover_subtitle", fontSize=13, leading=18, alignment=TA_CENTER,
        textColor=HexColor("#bfdbfe"), fontName="Helvetica", spaceAfter=6
    )
    s["cover_badge"] = ParagraphStyle(
        "cover_badge", fontSize=10, leading=14, alignment=TA_CENTER,
        textColor=HexColor("#93c5fd"), fontName="Helvetica"
    )

    s["h1"] = ParagraphStyle(
        "h1", fontSize=18, leading=24, spaceBefore=18, spaceAfter=8,
        textColor=C_PRIMARY, fontName="Helvetica-Bold",
        borderPad=(0, 0, 6, 0)
    )
    s["h2"] = ParagraphStyle(
        "h2", fontSize=13, leading=18, spaceBefore=14, spaceAfter=6,
        textColor=C_DARK, fontName="Helvetica-Bold"
    )
    s["h3"] = ParagraphStyle(
        "h3", fontSize=11, leading=16, spaceBefore=10, spaceAfter=4,
        textColor=C_PRIMARY, fontName="Helvetica-BoldOblique"
    )

    s["body"] = ParagraphStyle(
        "body", fontSize=10.5, leading=16, spaceAfter=6,
        textColor=C_DARK, fontName="Helvetica", alignment=TA_JUSTIFY
    )
    s["bullet"] = ParagraphStyle(
        "bullet", fontSize=10.5, leading=15, spaceAfter=3,
        textColor=C_DARK, fontName="Helvetica",
        leftIndent=14, bulletIndent=0
    )
    s["sub_bullet"] = ParagraphStyle(
        "sub_bullet", fontSize=10, leading=14, spaceAfter=2,
        textColor=C_GRAY, fontName="Helvetica",
        leftIndent=28, bulletIndent=14
    )
    s["numbered"] = ParagraphStyle(
        "numbered", fontSize=10.5, leading=15, spaceAfter=3,
        textColor=C_DARK, fontName="Helvetica",
        leftIndent=20, bulletIndent=0
    )

    s["code"] = ParagraphStyle(
        "code", fontSize=9, leading=13, spaceAfter=2, spaceBefore=2,
        fontName="Courier", textColor=HexColor("#374151"),
        leftIndent=12, rightIndent=12, backColor=C_CODE_BG
    )

    s["tip"] = ParagraphStyle(
        "tip", fontSize=10, leading=15, spaceAfter=4, spaceBefore=4,
        fontName="Helvetica", textColor=HexColor("#1e40af"),
        leftIndent=10, rightIndent=6
    )
    s["warn"] = ParagraphStyle(
        "warn", fontSize=10, leading=15, spaceAfter=4, spaceBefore=4,
        fontName="Helvetica", textColor=HexColor("#78350f"),
        leftIndent=10, rightIndent=6
    )

    s["toc_h1"] = ParagraphStyle(
        "toc_h1", fontSize=11, leading=16, textColor=C_PRIMARY,
        fontName="Helvetica-Bold", leftIndent=0
    )
    s["toc_h2"] = ParagraphStyle(
        "toc_h2", fontSize=10, leading=14, textColor=C_DARK,
        fontName="Helvetica", leftIndent=14
    )

    s["footer"] = ParagraphStyle(
        "footer", fontSize=8, leading=10, textColor=C_GRAY,
        fontName="Helvetica", alignment=TA_CENTER
    )

    s["table_header"] = ParagraphStyle(
        "table_header", fontSize=9.5, leading=13, textColor=WHITE,
        fontName="Helvetica-Bold", alignment=TA_CENTER
    )
    s["table_cell"] = ParagraphStyle(
        "table_cell", fontSize=9.5, leading=13, textColor=C_DARK,
        fontName="Helvetica", alignment=TA_LEFT
    )

    return s

ST = make_styles()

# ── Canvas com capa e cabeçalho/rodapé ───────────────────────────────────────
class PRISMACanvas(canvas.Canvas):
    def __init__(self, filename, **kwargs):
        super().__init__(filename, **kwargs)
        self._saved_page_states = []

    def showPage(self):
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        num_pages = len(self._saved_page_states)
        for state in self._saved_page_states:
            self.__dict__.update(state)
            self._draw_page(num_pages)
            super().showPage()
        super().save()

    def _draw_page(self, total):
        page = self._pageNumber
        if page == 1:
            self._draw_cover()
            return
        # Linha topo
        self.setStrokeColor(C_BORDER)
        self.setLineWidth(0.5)
        self.line(MARGIN, PAGE_H - 1.4*cm, PAGE_W - MARGIN, PAGE_H - 1.4*cm)
        # Header
        self.setFont("Helvetica-Bold", 8)
        self.setFillColor(C_PRIMARY)
        self.drawString(MARGIN, PAGE_H - 1.15*cm, "PRISMA-SLR")
        self.setFont("Helvetica", 8)
        self.setFillColor(C_GRAY)
        self.drawRightString(PAGE_W - MARGIN, PAGE_H - 1.15*cm, "Tutorial do Usuário")
        # Linha rodapé
        self.line(MARGIN, 1.4*cm, PAGE_W - MARGIN, 1.4*cm)
        # Página (descontando capa e sumário)
        self.setFont("Helvetica", 8)
        self.setFillColor(C_GRAY)
        self.drawCentredString(PAGE_W / 2, 0.85*cm, f"Página {page - 1} de {total - 1}")

    def _draw_cover(self):
        # Fundo azul sólido
        self.setFillColor(C_PRIMARY)
        self.rect(0, 0, PAGE_W, PAGE_H, fill=1, stroke=0)

        # Faixa inferior mais escura
        self.setFillColor(HexColor("#1e3a8a"))
        self.rect(0, 0, PAGE_W, 4*cm, fill=1, stroke=0)

        # Título principal
        self.setFillColor(WHITE)
        self.setFont("Helvetica-Bold", 36)
        self.drawCentredString(PAGE_W / 2, PAGE_H - 7*cm, "PRISMA-SLR")

        # Subtítulo
        self.setFont("Helvetica-Bold", 16)
        self.setFillColor(HexColor("#bfdbfe"))
        self.drawCentredString(PAGE_W / 2, PAGE_H - 8.5*cm, "Tutorial do Usuário")

        # Linha separadora
        self.setStrokeColor(HexColor("#3b82f6"))
        self.setLineWidth(1.5)
        self.line(MARGIN + 2*cm, PAGE_H - 9.3*cm, PAGE_W - MARGIN - 2*cm, PAGE_H - 9.3*cm)

        # Descrição
        self.setFont("Helvetica", 12)
        self.setFillColor(HexColor("#93c5fd"))
        self.drawCentredString(
            PAGE_W / 2, PAGE_H - 10.4*cm,
            "Sistema web para Revisões Sistemáticas da Literatura"
        )
        self.drawCentredString(
            PAGE_W / 2, PAGE_H - 11.1*cm,
            "baseado no protocolo PRISMA 2020"
        )

        # Badge
        bx = PAGE_W/2 - 4.5*cm
        self.setFillColor(HexColor("#1e40af"))
        self.roundRect(bx, PAGE_H - 13.5*cm, 9*cm, 1.2*cm, 8, fill=1, stroke=0)
        self.setFont("Helvetica-Bold", 10)
        self.setFillColor(HexColor("#93c5fd"))
        self.drawCentredString(PAGE_W / 2, PAGE_H - 12.9*cm, "Open Source  ·  PRISMA 2020  ·  v1.0")

        # Ano no rodapé
        self.setFont("Helvetica", 10)
        self.setFillColor(HexColor("#93c5fd"))
        self.drawCentredString(PAGE_W / 2, 1.8*cm, "2025")


# ── Utilitários de formatação ────────────────────────────────────────────────
def bold(text):
    return f"<b>{text}</b>"

def inline_code(text):
    return f'<font face="Courier" size="9" color="#374151">{text}</font>'

def parse_inline(text):
    """Converte **bold** e `code` para tags ReportLab."""
    import re
    text = text.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")
    # restore our own tags after escaping (workaround: process before escaping)
    # Simpler: process on raw text
    return text

def parse_inline_raw(text):
    """Converte markdown inline sem escapar HTML primeiro."""
    import re
    # bold
    text = re.sub(r'\*\*(.+?)\*\*', r'<b>\1</b>', text)
    # inline code
    text = re.sub(r'`(.+?)`', lambda m: inline_code(m.group(1)), text)
    # escape remaining & < > outside tags
    return text

def tip_box(text, warn=False):
    """Caixa de dica ou aviso."""
    style = ST["warn"] if warn else ST["tip"]
    bg    = C_WARN_BG if warn else C_TIP_BG
    border= C_WARN_BORDER if warn else C_PRIMARY

    prefix = "<b>Importante:</b> " if warn else "<b>Dica:</b> "
    # strip leading "> **Dica:**" or "> **Importante:**"
    import re
    text = re.sub(r'^\*\*(Dica|Importante):\*\*\s*', '', text)
    text = parse_inline_raw(text)

    inner = Paragraph(prefix + text, style)
    t = Table([[inner]], colWidths=[PAGE_W - 2*MARGIN - 1.2*cm])
    t.setStyle(TableStyle([
        ("BACKGROUND", (0,0), (-1,-1), bg),
        ("BOX",        (0,0), (-1,-1), 1, border),
        ("LEFTPADDING",  (0,0), (-1,-1), 10),
        ("RIGHTPADDING", (0,0), (-1,-1), 10),
        ("TOPPADDING",   (0,0), (-1,-1), 8),
        ("BOTTOMPADDING",(0,0), (-1,-1), 8),
        ("ROUNDEDCORNERS", [6]),
    ]))
    return t

def code_box(lines):
    """Bloco de código com fundo cinza."""
    content = "<br/>".join(
        line.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")
        for line in lines
    )
    inner = Paragraph(content, ST["code"])
    t = Table([[inner]], colWidths=[PAGE_W - 2*MARGIN - 1.2*cm])
    t.setStyle(TableStyle([
        ("BACKGROUND", (0,0), (-1,-1), C_CODE_BG),
        ("BOX",        (0,0), (-1,-1), 0.5, C_BORDER),
        ("LEFTPADDING",  (0,0), (-1,-1), 12),
        ("RIGHTPADDING", (0,0), (-1,-1), 12),
        ("TOPPADDING",   (0,0), (-1,-1), 8),
        ("BOTTOMPADDING",(0,0), (-1,-1), 8),
    ]))
    return t

def make_table(headers, rows):
    """Cria tabela estilizada."""
    col_count = len(headers)
    avail = PAGE_W - 2*MARGIN - 1.2*cm

    # Larguras dinâmicas baseadas no conteúdo
    col_w = avail / col_count

    header_row = [Paragraph(h, ST["table_header"]) for h in headers]
    data = [header_row]
    for row in rows:
        data.append([Paragraph(str(c), ST["table_cell"]) for c in row])

    t = Table(data, colWidths=[col_w]*col_count, repeatRows=1)
    t.setStyle(TableStyle([
        ("BACKGROUND",    (0,0), (-1,0), C_PRIMARY),
        ("TEXTCOLOR",     (0,0), (-1,0), WHITE),
        ("FONTNAME",      (0,0), (-1,0), "Helvetica-Bold"),
        ("FONTSIZE",      (0,0), (-1,0), 9.5),
        ("ROWBACKGROUNDS",(0,1), (-1,-1), [WHITE, C_PRIMARY_L]),
        ("GRID",          (0,0), (-1,-1), 0.4, C_BORDER),
        ("VALIGN",        (0,0), (-1,-1), "MIDDLE"),
        ("TOPPADDING",    (0,0), (-1,-1), 6),
        ("BOTTOMPADDING", (0,0), (-1,-1), 6),
        ("LEFTPADDING",   (0,0), (-1,-1), 8),
        ("RIGHTPADDING",  (0,0), (-1,-1), 8),
    ]))
    return t

# ── Capa ─────────────────────────────────────────────────────────────────────
def build_cover():
    # A capa é desenhada pelo PRISMACanvas._draw_cover() para a página 1.
    # Aqui apenas garantimos que a primeira flowable force uma quebra de página.
    story = [PageBreak()]
    return story

# ── Sumário ───────────────────────────────────────────────────────────────────
def build_toc():
    story = []
    story.append(Paragraph("Sumário", ST["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=C_BORDER, spaceAfter=10))

    toc_entries = [
        ("1", "O que é o PRISMA-SLR?"),
        ("2", "Visão geral do fluxo de trabalho"),
        ("3", "Projetos"),
        ("4", "Importação de referências"),
        ("5", "Deduplicação"),
        ("6", "Triagem (Título / Resumo)"),
        ("7", "Elegibilidade (Texto Completo)"),
        ("8", "Diagrama PRISMA 2020"),
        ("9", "Bibliometria"),
        ("10", "Exportação"),
        ("11", "Checklist PRISMA 2020"),
        ("12", "Perguntas frequentes"),
    ]

    for num, title in toc_entries:
        story.append(Paragraph(f"{num}. {title}", ST["toc_h1"]))
        story.append(Spacer(1, 3))

    story.append(PageBreak())
    return story

# ── Seções de conteúdo ────────────────────────────────────────────────────────
def build_content():
    import re
    story = []

    # ── SEÇÃO 1 ───────────────────────────────────────────────────────────────
    story.append(Paragraph("1. O que é o PRISMA-SLR?", ST["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=C_BORDER, spaceAfter=6))

    story.append(Paragraph(
        "O <b>PRISMA-SLR</b> é uma ferramenta web de código aberto que apoia pesquisadores na "
        "condução de <b>Revisões Sistemáticas da Literatura (RSL)</b> seguindo o protocolo "
        "<b>PRISMA 2020</b> — o padrão internacional mais adotado para relato de revisões sistemáticas.",
        ST["body"]
    ))
    story.append(Paragraph("Com ele você pode:", ST["body"]))

    bullets_1 = [
        "Importar referências das principais bases (Scopus, Web of Science, PubMed, Embase)",
        "Detectar e remover artigos duplicados automaticamente",
        "Avaliar títulos/resumos e textos completos de forma organizada",
        "Gerar o Diagrama PRISMA 2020 automaticamente",
        "Analisar bibliometria com 12 gráficos e redes de coautoria",
        "Exportar os dados em CSV, BibTeX ou JSON",
        "Verificar conformidade com o Checklist PRISMA 2020 (27 itens)",
    ]
    for b in bullets_1:
        story.append(Paragraph(f"• {b}", ST["bullet"]))

    story.append(Spacer(1, 0.5*cm))

    # ── SEÇÃO 2 ───────────────────────────────────────────────────────────────
    story.append(Paragraph("2. Visão geral do fluxo de trabalho", ST["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=C_BORDER, spaceAfter=6))

    story.append(Paragraph(
        "O processo de uma RSL no PRISMA-SLR segue as etapas abaixo, na ordem indicada:",
        ST["body"]
    ))

    steps = [
        ("[1]", "Criar Projeto"),
        ("[2]", "Importar referências (.bib / .ris / .xml)"),
        ("[3]", "Detectar e resolver duplicatas"),
        ("[4]", "Triagem — avaliar título e resumo"),
        ("[5]", "Elegibilidade — avaliar texto completo"),
        ("[6]", "Visualizar Diagrama PRISMA 2020"),
        ("[7]", "Analisar Bibliometria"),
        ("[8]", "Exportar dados finais"),
        ("[9]", "Preencher Checklist PRISMA 2020"),
    ]

    flow_data = []
    for num, label in steps:
        flow_data.append([
            Paragraph(f"<b>{num}</b>", ParagraphStyle(
                "step_num", fontSize=10, fontName="Helvetica-Bold",
                textColor=WHITE, alignment=TA_CENTER
            )),
            Paragraph(label, ParagraphStyle(
                "step_label", fontSize=10.5, fontName="Helvetica",
                textColor=C_DARK, leading=14
            )),
        ])

    flow_table = Table(flow_data, colWidths=[1.4*cm, PAGE_W - 2*MARGIN - 2.6*cm])
    flow_table.setStyle(TableStyle([
        ("BACKGROUND",    (0,0), (0,-1), C_PRIMARY),
        ("ROWBACKGROUNDS",(1,0), (1,-1), [C_PRIMARY_L, WHITE]),
        ("VALIGN",        (0,0), (-1,-1), "MIDDLE"),
        ("TOPPADDING",    (0,0), (-1,-1), 6),
        ("BOTTOMPADDING", (0,0), (-1,-1), 6),
        ("LEFTPADDING",   (0,0), (0,-1), 4),
        ("RIGHTPADDING",  (0,0), (0,-1), 4),
        ("LEFTPADDING",   (1,0), (1,-1), 10),
        ("GRID",          (0,0), (-1,-1), 0.3, C_BORDER),
    ]))
    story.append(flow_table)
    story.append(Spacer(1, 6))
    story.append(Paragraph(
        "O <b>Dashboard</b> exibe o progresso em tempo real de cada etapa.",
        ST["body"]
    ))
    story.append(Spacer(1, 0.5*cm))

    # ── SEÇÃO 3 ───────────────────────────────────────────────────────────────
    story.append(Paragraph("3. Projetos", ST["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=C_BORDER, spaceAfter=6))

    story.append(Paragraph("Criar um projeto", ST["h2"]))
    for i, step in enumerate([
        "Acesse <b>Projetos</b> no menu lateral.",
        "Clique em <b>Novo Projeto</b>.",
        "Preencha o título da revisão e uma descrição opcional.",
        "Clique em <b>Criar</b>.",
    ], 1):
        story.append(Paragraph(f"{i}. {step}", ST["numbered"]))

    story.append(Paragraph("Selecionar um projeto existente", ST["h2"]))
    for b in [
        "No menu lateral, o nome do projeto ativo aparece no topo.",
        "Clique em <b>Projetos</b> e depois no nome do projeto desejado para ativá-lo.",
    ]:
        story.append(Paragraph(f"• {b}", ST["bullet"]))

    story.append(Paragraph("Por que usar projetos separados?", ST["h2"]))
    story.append(Paragraph(
        "Cada projeto mantém suas próprias referências, decisões de triagem e configurações. "
        "Isso permite gerenciar múltiplas revisões simultâneas sem conflito.",
        ST["body"]
    ))
    story.append(Spacer(1, 0.5*cm))

    # ── SEÇÃO 4 ───────────────────────────────────────────────────────────────
    story.append(Paragraph("4. Importação de referências", ST["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=C_BORDER, spaceAfter=6))

    story.append(Paragraph("Formatos suportados", ST["h2"]))
    story.append(make_table(
        ["Formato", "Extensão", "Bases compatíveis"],
        [
            ["BibTeX",     ".bib", "Scopus, Web of Science, qualquer exportação BibTeX"],
            ["RIS",        ".ris", "Mendeley, Zotero, EndNote, PubMed, Embase"],
            ["PubMed XML", ".xml", "PubMed (exportação MEDLINE XML)"],
        ]
    ))
    story.append(Spacer(1, 10))

    story.append(Paragraph("Como importar", ST["h2"]))
    for i, step in enumerate([
        "Acesse <b>Importar</b> no menu lateral.",
        "Selecione o formato do arquivo clicando nas abas: <b>BibTeX</b>, <b>RIS</b> ou <b>PubMed XML</b>.",
        "Arraste o arquivo para a área de upload, ou clique nela para selecionar.",
        "Preencha o <b>Nome da Fonte</b> (ex.: Scopus 2024, WoS — Dezembro 2023).",
        "Selecione a <b>Base de dados</b> de origem na lista.",
        "Informe a <b>Data da busca</b> (importante para o Diagrama PRISMA).",
        "Clique em <b>Importar</b>.",
    ], 1):
        story.append(Paragraph(f"{i}. {step}", ST["numbered"]))

    story.append(Spacer(1, 8))
    story.append(Paragraph("Exportando da base de dados", ST["h2"]))

    db_info = [
        ("Scopus",          "Selecione os resultados → Export → BibTeX → marque todos os campos."),
        ("Web of Science",  "Selecione os registros → Export → BibTeX → exportar todos os registros."),
        ("PubMed",          "Clique em Save → Format: PubMed → Create file (ou Send to → File → Format XML)."),
        ("Embase / Outros", "Exporte no formato RIS (.ris)."),
    ]
    for db, instruction in db_info:
        story.append(Paragraph(f"<b>{db}</b>", ST["h3"]))
        story.append(Paragraph(instruction, ST["body"]))

    story.append(Spacer(1, 8))
    story.append(tip_box(
        "Você pode importar arquivos de múltiplas bases no mesmo projeto. "
        "O sistema registra a origem de cada referência."
    ))
    story.append(Spacer(1, 0.5*cm))

    # ── SEÇÃO 5 ───────────────────────────────────────────────────────────────
    story.append(Paragraph("5. Deduplicação", ST["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=C_BORDER, spaceAfter=6))

    story.append(Paragraph(
        "Após importar referências de diferentes bases, é comum haver artigos duplicados. "
        "Esta etapa identifica e remove esses registros.",
        ST["body"]
    ))

    story.append(Paragraph("Como detectar duplicatas", ST["h2"]))
    for i, step in enumerate([
        "Acesse <b>Duplicatas</b> no menu lateral.",
        "Clique em <b>Detectar Duplicatas</b>.",
        "Aguarde o processamento (pode levar alguns segundos em bases grandes).",
    ], 1):
        story.append(Paragraph(f"{i}. {step}", ST["numbered"]))

    story.append(Paragraph("O sistema detecta duplicatas por dois critérios:", ST["body"]))
    story.append(Paragraph("• <b>DOI idêntico</b> — correspondência exata.", ST["bullet"]))
    story.append(Paragraph("• <b>Similaridade de título</b> — comparação textual com threshold configurável.", ST["bullet"]))

    story.append(Paragraph("Revisando os pares encontrados", ST["h2"]))
    story.append(Paragraph(
        "Para cada par suspeito, o sistema mostra os dois registros lado a lado com o grau de "
        "similaridade. Você pode:",
        ST["body"]
    ))
    story.append(Paragraph("• <b>Confirmar como duplicata</b> — o registro secundário é marcado como duplicado e removido das etapas seguintes.", ST["bullet"]))
    story.append(Paragraph("• <b>Rejeitar</b> — os dois registros são mantidos separados.", ST["bullet"]))
    story.append(Paragraph("• <b>Confirmar todos pendentes</b> — aceita todos os pares detectados de uma vez (use com cautela).", ST["bullet"]))

    story.append(Paragraph("Redefinir detecção", ST["h2"]))
    story.append(Paragraph(
        "O botão <b>Redefinir</b> apaga todos os resultados de deduplicação e permite rodar o "
        "processo novamente, útil após ajustar o threshold de similaridade.",
        ST["body"]
    ))
    story.append(Spacer(1, 8))
    story.append(tip_box(
        "Artigos marcados como duplicata <b>não aparecem</b> na Triagem, Elegibilidade nem nos "
        "gráficos bibliométricos. Eles são contabilizados corretamente no Diagrama PRISMA.",
        warn=True
    ))
    story.append(Spacer(1, 0.5*cm))

    # ── SEÇÃO 6 ───────────────────────────────────────────────────────────────
    story.append(Paragraph("6. Triagem (Título / Resumo)", ST["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=C_BORDER, spaceAfter=6))

    story.append(Paragraph(
        "Nesta etapa você avalia cada artigo lendo <b>título e resumo</b>, decidindo se ele "
        "passa para a fase de elegibilidade.",
        ST["body"]
    ))

    story.append(Paragraph("Como triar artigos", ST["h2"]))
    story.append(Paragraph("1. Acesse <b>Triagem</b> no menu lateral.", ST["numbered"]))
    story.append(Paragraph("2. Para cada artigo, clique em:", ST["numbered"]))
    story.append(Paragraph("• <b>Incluir</b> — artigo passa para a Elegibilidade.", ST["sub_bullet"]))
    story.append(Paragraph("• <b>Excluir</b> — artigo é descartado; informe o <b>motivo de exclusão</b>.", ST["sub_bullet"]))
    story.append(Paragraph("3. O progresso é exibido na barra no topo da página.", ST["numbered"]))

    story.append(Paragraph("Modos de visualização", ST["h2"]))
    story.append(Paragraph("• <b>Lista</b> — visualização compacta, ideal para triagem rápida.", ST["bullet"]))
    story.append(Paragraph("• <b>Detalhado</b> — exibe título, autores, resumo completo e metadados.", ST["bullet"]))

    story.append(Paragraph("Filtros rápidos", ST["h2"]))
    story.append(Paragraph("Clique nos chips coloridos para filtrar artigos por status:", ST["body"]))
    for b in ["<b>Pendentes</b> — ainda não avaliados.", "<b>Incluídos</b> — marcados para prosseguir.",
              "<b>Excluídos</b> — descartados com motivo.", "<b>Todos</b> — lista completa."]:
        story.append(Paragraph(f"• {b}", ST["bullet"]))

    story.append(Spacer(1, 8))
    story.append(tip_box("Você pode alterar a decisão de qualquer artigo a qualquer momento, mesmo após ele ter sido avaliado."))
    story.append(Spacer(1, 0.5*cm))

    # ── SEÇÃO 7 ───────────────────────────────────────────────────────────────
    story.append(Paragraph("7. Elegibilidade (Texto Completo)", ST["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=C_BORDER, spaceAfter=6))

    story.append(Paragraph(
        "Os artigos incluídos na Triagem passam para esta fase, onde você avalia o "
        "<b>texto completo</b> com mais profundidade.",
        ST["body"]
    ))

    story.append(Paragraph("Como avaliar elegibilidade", ST["h2"]))
    story.append(Paragraph("1. Acesse <b>Elegibilidade</b> no menu lateral.", ST["numbered"]))
    story.append(Paragraph("2. Para cada artigo:", ST["numbered"]))
    story.append(Paragraph("• Leia o texto completo (acesse o DOI ou PDF externamente).", ST["sub_bullet"]))
    story.append(Paragraph("• Clique em <b>Incluir</b> — artigo integra a revisão final.", ST["sub_bullet"]))
    story.append(Paragraph("• Clique em <b>Excluir</b> — artigo é descartado; informe o <b>motivo</b> (critério de exclusão de texto completo).", ST["sub_bullet"]))

    story.append(Paragraph(
        "O funcionamento é idêntico à Triagem. Os mesmos filtros e modos de visualização estão disponíveis.",
        ST["body"]
    ))
    story.append(Spacer(1, 0.5*cm))

    # ── SEÇÃO 8 ───────────────────────────────────────────────────────────────
    story.append(Paragraph("8. Diagrama PRISMA 2020", ST["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=C_BORDER, spaceAfter=6))

    story.append(Paragraph(
        "O diagrama é gerado <b>automaticamente</b> com base nos números de cada etapa do processo.",
        ST["body"]
    ))

    story.append(Paragraph("Como visualizar", ST["h2"]))
    story.append(Paragraph("1. Acesse <b>PRISMA Flow</b> no menu lateral.", ST["numbered"]))
    story.append(Paragraph("2. O diagrama é exibido imediatamente com os números atuais.", ST["numbered"]))

    story.append(Paragraph("O que o diagrama mostra", ST["h2"]))
    story.append(Paragraph("O Diagrama PRISMA 2020 segue a estrutura oficial:", ST["body"]))

    prisma_rows = [
        ["Identificação",  "Registros identificados por base + registros removidos antes da triagem (duplicatas)"],
        ["Triagem",        "Registros triados + registros excluídos (com motivos)"],
        ["Elegibilidade",  "Textos completos avaliados + textos excluídos (com motivos)"],
        ["Incluídos",      "Estudos incluídos na revisão final"],
    ]
    story.append(make_table(["Fase", "Conteúdo"], prisma_rows))
    story.append(Spacer(1, 10))

    story.append(Paragraph("Exportar o diagrama", ST["h2"]))
    story.append(Paragraph("• <b>PNG</b> — imagem de alta resolução para inserção em artigos.", ST["bullet"]))
    story.append(Paragraph("• <b>SVG</b> — formato vetorial editável em Inkscape, Illustrator etc.", ST["bullet"]))
    story.append(Spacer(1, 0.5*cm))

    # ── SEÇÃO 9 ───────────────────────────────────────────────────────────────
    story.append(Paragraph("9. Bibliometria", ST["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=C_BORDER, spaceAfter=6))

    story.append(Paragraph(
        "A seção de bibliometria oferece <b>12 análises visuais</b> dos artigos incluídos na revisão.",
        ST["body"]
    ))

    story.append(Paragraph("Como acessar", ST["h2"]))
    story.append(Paragraph("1. Acesse <b>Bibliometria</b> no menu lateral.", ST["numbered"]))
    story.append(Paragraph("2. Os gráficos são carregados automaticamente.", ST["numbered"]))

    story.append(Paragraph("Análises disponíveis", ST["h2"]))
    biblio_rows = [
        ["1", "Publicações por ano",        "Evolução temporal da produção científica"],
        ["2", "Artigos por base de dados",  "Distribuição por fonte de busca"],
        ["3", "Top periódicos",             "Revistas com mais publicações"],
        ["4", "Top autores",                "Autores mais produtivos"],
        ["5", "Top instituições",           "Afiliações mais frequentes"],
        ["6", "Top países",                 "Mapa e ranking por país"],
        ["7", "Distribuição de citações",   "Histograma de citações por artigo"],
        ["8", "Top artigos citados",        "Artigos mais referenciados"],
        ["9", "Nuvem de palavras-chave",    "Keywords mais frequentes"],
        ["10","Co-ocorrência de keywords",  "Rede de keywords relacionadas"],
        ["11","Rede de coautoria",          "Colaboração entre autores"],
        ["12","Top áreas temáticas",        "Classificação por subject area"],
    ]
    story.append(make_table(["#", "Gráfico", "Descrição"], biblio_rows))
    story.append(Spacer(1, 10))
    story.append(tip_box("Os gráficos usam apenas os artigos com status <b>incluído</b> (pós-elegibilidade)."))
    story.append(Spacer(1, 0.5*cm))

    # ── SEÇÃO 10 ──────────────────────────────────────────────────────────────
    story.append(Paragraph("10. Exportação", ST["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=C_BORDER, spaceAfter=6))

    story.append(Paragraph(
        "Exporte os dados do projeto em diferentes formatos para análise externa ou publicação.",
        ST["body"]
    ))

    story.append(Paragraph("Como exportar", ST["h2"]))
    story.append(Paragraph("1. Acesse <b>Exportar</b> no menu lateral.", ST["numbered"]))
    story.append(Paragraph("2. Escolha o <b>escopo</b> de exportação:", ST["numbered"]))
    story.append(Paragraph("• <b>Todos os artigos</b> — todos os registros importados (exceto duplicatas).", ST["sub_bullet"]))
    story.append(Paragraph("• <b>Triados</b> — artigos que passaram pela triagem.", ST["sub_bullet"]))
    story.append(Paragraph("• <b>Elegíveis</b> — artigos avaliados por texto completo.", ST["sub_bullet"]))
    story.append(Paragraph("• <b>Incluídos</b> — artigos aceitos na revisão final.", ST["sub_bullet"]))
    story.append(Paragraph("3. Clique no formato desejado: <b>CSV</b>, <b>BibTeX</b> ou <b>JSON</b>.", ST["numbered"]))

    story.append(Paragraph("Quando usar cada formato", ST["h2"]))
    story.append(make_table(
        ["Formato", "Ideal para"],
        [
            ["CSV",    "Análises em Excel, Google Sheets, R, Python"],
            ["BibTeX", "Importar no Mendeley, Zotero, LaTeX"],
            ["JSON",   "Processamento programático, integração com outros sistemas"],
        ]
    ))
    story.append(Spacer(1, 0.5*cm))

    # ── SEÇÃO 11 ──────────────────────────────────────────────────────────────
    story.append(Paragraph("11. Checklist PRISMA 2020", ST["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=C_BORDER, spaceAfter=6))

    story.append(Paragraph(
        "O checklist contém os <b>27 itens obrigatórios</b> que seu relatório de revisão "
        "sistemática deve conter segundo o protocolo PRISMA 2020.",
        ST["body"]
    ))

    story.append(Paragraph("Como usar", ST["h2"]))
    for i, step in enumerate([
        "Acesse <b>Checklist</b> no menu lateral.",
        "Para cada item, marque se está <b>cumprido</b>, <b>parcialmente cumprido</b> ou <b>não aplicável</b>.",
        "Adicione notas explicando como cada item foi abordado no seu manuscrito.",
    ], 1):
        story.append(Paragraph(f"{i}. {step}", ST["numbered"]))

    story.append(Paragraph("Para que serve", ST["h2"]))
    story.append(Paragraph(
        "O checklist é exigido por muitos periódicos científicos ao submeter uma revisão sistemática. "
        "Ele garante que seu artigo contenha todas as informações metodológicas necessárias para que "
        "outros pesquisadores possam reproduzir e avaliar sua revisão.",
        ST["body"]
    ))
    story.append(Spacer(1, 0.5*cm))

    # ── SEÇÃO 12 ──────────────────────────────────────────────────────────────
    story.append(Paragraph("12. Perguntas frequentes", ST["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=C_BORDER, spaceAfter=6))

    faqs = [
        (
            "Posso importar o mesmo arquivo duas vezes?",
            "Sim, mas o sistema não impede duplicação de arquivos. Reimporte apenas se necessário e execute a deduplicação novamente."
        ),
        (
            "O que acontece se eu alterar a decisão de triagem de um artigo já avaliado na elegibilidade?",
            "Se você excluir um artigo na triagem que já havia sido incluído na elegibilidade, ele sairá do escopo de elegibilidade. Todos os números do Diagrama PRISMA são recalculados automaticamente."
        ),
        (
            "Como faço para que dois avaliadores trabalhem na mesma revisão?",
            "O sistema atual não possui controle de múltiplos avaliadores por artigo. Para revisões com dois avaliadores, recomenda-se combinar as decisões externamente e usar o sistema para registrar o resultado final."
        ),
        (
            "O diagrama PRISMA pode ser editado manualmente?",
            "Não. Os números são calculados automaticamente a partir das decisões registradas no sistema. Edite os dados na triagem/elegibilidade para alterar os números."
        ),
        (
            "Quais bases de dados são suportadas na importação?",
            "Scopus, Web of Science, PubMed, Embase, e qualquer base que exporte nos formatos BibTeX (.bib) ou RIS (.ris)."
        ),
        (
            "Posso usar o sistema sem conexão com a internet?",
            "Sim, se estiver rodando localmente (MAMP/XAMPP). O único recurso que requer conexão é a tradução automática de abstracts (quando disponível)."
        ),
        (
            "Como faço backup dos dados?",
            "Exporte os artigos incluídos em JSON (que contém todos os metadados) e salve. Para backup completo do banco de dados, use o phpMyAdmin para exportar o banco MySQL."
        ),
    ]

    for q, a in faqs:
        block = [
            Paragraph(f"<b>{q}</b>", ST["h3"]),
            Paragraph(a, ST["body"]),
            Spacer(1, 6),
        ]
        story.append(KeepTogether(block))

    return story


# ── Montagem do documento ─────────────────────────────────────────────────────
def build_pdf():
    doc = SimpleDocTemplate(
        OUTPUT,
        pagesize=A4,
        topMargin=1.8*cm,
        bottomMargin=1.8*cm,
        leftMargin=MARGIN,
        rightMargin=MARGIN,
        title="Tutorial PRISMA-SLR",
        author="PRISMA-SLR",
        subject="Tutorial do Usuário — PRISMA 2020",
    )

    story = []
    story += build_cover()
    story += build_toc()
    story += build_content()

    doc.build(story, canvasmaker=PRISMACanvas)
    print(f"PDF gerado com sucesso: {OUTPUT}")


if __name__ == "__main__":
    build_pdf()
