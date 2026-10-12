# BaseRef

**Open-source web system for managing Systematic Literature Reviews** based on the **PRISMA 2020** protocol.

![BaseRef](assets/img/baseref_light-2.png)

## Features

- **Projects** — Manage multiple reviews simultaneously
- **Import** — Supports `.bib` (BibTeX), `.ris`, PubMed XML, and Scopus CSV files from Scopus and Web of Science
- **Deduplication** — Automatic duplicate detection by DOI and title similarity
- **Screening** — Title/abstract assessment with include/exclude decisions and configurable exclusion reasons
- **Eligibility** — Full-text assessment
- **Multi-reviewer agreement** — Dual-blind screening mode with Cohen's kappa and consensus resolution for disagreements
- **AI-assisted screening** — Optional include/exclude/uncertain suggestions (Groq, Gemini, or Anthropic) to support — never replace — the human reviewer's decision
- **OpenAlex enrichment** — Fills missing abstracts, citation counts, and open-access status from imported DOIs
- **PRISMA 2020 diagram** — Automatically generated with PNG/SVG export
- **Bibliometrics** — 30+ charts and networks (Lotka's and Bradford's laws, co-authorship, keyword co-occurrence, thematic maps, country collaboration, h/g/m indices, and more), filterable by dataset and exportable to XLSX per chart
- **PICO(C) extraction** — Structured data extraction for included studies
- **Quality/risk-of-bias assessment** — CASP, JBI, or a custom instrument
- **Export** — CSV, BibTeX, and JSON for every scope
- **PRISMA 2020 checklist** — 27 items to ensure reporting compliance
- **Admin panel** — Usage overview and "view as user" support for troubleshooting

## Technology

- **Backend**: PHP 8.0+ (tested on 8.3) with PDO, MariaDB 10.4+ or MySQL 8.0+ (tested on MariaDB 10.11)
- **Frontend**: HTML5, CSS3 and plain JavaScript (no framework, no build step)
- **Charts and networks**: Chart.js 4.4.3, vis-network 9.1.9, D3 7.9.0 + topojson-client 3.1.0, wordcloud2.js 1.2.2, SheetJS 0.18.5 — all served locally from `assets/vendor/` with pinned versions (see `assets/vendor/README.md`)

## Installation

BaseRef can be quickly launched using **Docker Compose** or deployed manually on Windows (XAMPP), macOS (MAMP), or Linux (Apache/Nginx).

👉 **For comprehensive step-by-step instructions across all platforms, see [INSTALL.md](INSTALL.md).**

### Quick Start with Docker

```bash
git clone https://github.com/rafaelgross/baseref.git
cd baseref
cp .env.example .env
# Edit .env and enter your Google OAuth credentials
docker compose up -d
```
The application will be available at **`http://localhost:8080/`**. The database schema is imported automatically on startup.

---

### Quick Manual Setup

1. **Requirements**: PHP >= 8.0 (`pdo_mysql`, `mbstring`, `curl`, `json`, `xml`, `openssl`), MySQL >= 8.0 or MariaDB >= 10.4, Apache or Nginx.
2. **Clone the repository**:
   ```bash
   git clone https://github.com/rafaelgross/baseref.git
   ```
3. **Database initialization**:
   Create the database and import the unified installation script:
   ```bash
   mysql -u baseref -p prisma_slr < sql/install.sql
   ```
4. **Configuration**:
   ```bash
   cp config/database.example.php config/database.php
   cp config/auth.example.php     config/auth.php
   ```
   Edit both files to provide your database and Google OAuth credentials.
5. **Detailed platform guides**: Please refer to [INSTALL.md](INSTALL.md) for XAMPP, MAMP, Linux virtual hosts, Nginx rules, and OAuth configuration.

## Verification & Automated Testing

BaseRef includes continuous integration and verification scripts:
- **CI Workflow**: `.github/workflows/ci.yml` runs full syntax checking (`php -l`) across all PHP files on PHP 8.0, 8.1, 8.2, and 8.3.
- **Verification Tests**: `php tests/run_tests.php` validates Cohen's Kappa calculation accuracy (including 3-decimal rounding) and PRISMA reason categorization logic without external dependencies.

## Third-Party Libraries & External Services

All core front-end charting, network, and export libraries (Chart.js, vis-network, WordCloud2.js, FileSaver.js, SheetJS, Font Awesome) are **served locally** from `assets/vendor/` without CDN dependencies. For complete licenses, file paths, and external services (Google OAuth, gtranslate.io, OpenAlex, MyMemory), see [THIRD_PARTY.md](THIRD_PARTY.md).

## Basic Usage

1. **Create a project** → Projects → New Project
2. **Import references** → Import → drop `.bib`/`.ris`/`.xml`/`.csv` files from Scopus, Web of Science, or PubMed
3. **Deduplicate** → Duplicates → Detect (adjust the threshold if needed)
4. **Screening** → Screening → assess title/abstract article by article
5. **Eligibility** → Eligibility → assess full text
6. **View results** → PRISMA Diagram / Bibliometrics
7. **Export** → Export → CSV, BibTeX, or JSON
8. **Checklist** → PRISMA 2020 Checklist → 27 items for the report

## Supported Formats

### Scopus (`.bib` or `.csv`)
- Fields: `EID`, `affiliations`, `author_keywords`, `note` (cited by), `source`
- Auto-detected by default

### Web of Science (`.bib`)
- Fields: `Unique-ID`, `Times-Cited`, `Keywords-Plus`, `Affiliation`, `Web-of-Science-Index`
- Auto-detected by default

### PubMed (`.xml`)
- Standard PubMed/MEDLINE XML export

## Project Structure

```
baseref/
├── api/                 # JSON endpoints (projects, import, duplicates, screening, kappa,
│                        #   prisma-flow, bibliometrics, export, enrich, translate, ai-screening, ...)
├── assets/
│   ├── css/             # Design system
│   ├── js/              # Application scripts
│   ├── vendor/          # Third-party JS/CSS libraries, pinned versions (see its README)
│   ├── data/            # World map (TopoJSON) for the country-production map
│   └── img/
├── auth/                # Google OAuth callback and logout
├── config/
│   ├── database.example.php   # → copy to database.php
│   ├── auth.example.php       # → copy to auth.php
│   ├── ai.php                 # AI provider defaults (override in ai.local.php)
│   └── api_guard.php          # Authentication/authorization helpers for the API
├── lib/                 # Parsers (BibTeX, RIS, PubMed XML, Scopus CSV) and shared helpers
├── pages/               # Pages rendered inside index.php
├── sql/                 # Database scripts (see Installation, step 4)
├── .htaccess            # Blocks web access to config/, sql/, lib/ and sensitive files
├── index.php            # Main router (authenticated app)
├── landing.php          # Public landing page
└── login.php            # Sign-in page
```

## License

MIT © 2024 — See [LICENSE](LICENSE)

## Citation

If you use BaseRef in your research, please cite:

```
GROSS, Rafael; GONÇALVES, Rodrigo Franco. BaseRef: plataforma de apoio à pesquisa e gestão
de referências bibliográficas. Version 1.0.0 [software]. Zenodo, 2026.
DOI: 10.5281/zenodo.21119457. Available at: https://baseref.com.br/
```

Source code: https://github.com/rafaelgross/baseref

---

Built to support researchers conducting systematic literature reviews in accordance with the PRISMA 2020 protocol.
