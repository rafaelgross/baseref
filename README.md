# BaseRef

> Free, open-source web platform for conducting and reporting Systematic Literature Reviews under the **PRISMA 2020** protocol.

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)
![PRISMA](https://img.shields.io/badge/Protocol-PRISMA%202020-6c63ff)

---

## About the project

**BaseRef** is a complete platform for managing every stage of a systematic literature review, from importing bibliographic references to generating the PRISMA 2020 flow diagram and the 27-item reporting checklist. It runs on any web server with PHP and MySQL, with no build step and no paid external dependencies.

The distinguishing property of BaseRef is that **every count reported in the PRISMA 2020 flow diagram is aggregated from the individually stored screening decisions, rather than typed in by the reviewer**. Two SQL views pre-aggregate the counters in real time, which guarantees that the number of records entering each stage equals the number leaving the preceding one, and keeps the origin of any excluded study auditable down to the record and to the exclusion reason applied to it.

The system supports multiple users via Google account login, each with their own projects and isolated data.

Live demonstration instance: [baseref.com.br](https://baseref.com.br)

---

## Features

- **Project management** — create and manage multiple systematic reviews
- **Reference import** — BibTeX (`.bib`), RIS (`.ris`), PubMed XML (`.xml`) and plain text (`.txt`), with automatic format and provenance detection
- **Metadata enrichment via OpenAlex** — fills gaps in imported records by DOI, in batches of up to 50, using the public OpenAlex API with no key and no account required. Strictly non-destructive: abstracts are written only when empty and reconstructed from the inverted index, citation counts only updated when higher, open access flags only set when confirmed
- **Automatic deduplication** — two-phase detection combining exact normalized DOI matching and configurable fuzzy title similarity (default 85%), with mandatory human confirmation of every similarity-flagged pair before removal
- **Title and abstract screening** — include/exclude interface with a controlled list of exclusion reasons defined per project, so no exclusion can be registered without a reason
- **Blinded multi-reviewer screening with Cohen's kappa** — when more than one reviewer is assigned, assessments are blinded and Cohen's kappa is computed continuously from the stored decisions and interpreted on the Landis and Koch scale, so agreement is monitored while screening is still in progress
- **Full-text eligibility** — second assessment phase with its own customizable reason list
- **PRISMA 2020 flow diagram** — automatic generation with PNG and SVG export, derived from the stored decisions
- **PRISMA 2020 checklist** — all 27 mandatory items, with the values already recorded in the system populated automatically
- **Bibliometrics** — analyses computed over the review corpus itself, with no export and re-import cycle: Lotka's law of author productivity, Bradford's zoning of source dispersion, the H, G and M indices, Callon thematic mapping through co-word analysis, temporal series, geographic distribution, keyword co-occurrence and co-authorship networks
- **Formatted references** — automatic generation in **ABNT NBR 6023:2018** and **APA 7th edition**
- **Export** — CSV (19 fields, UTF-8 with BOM), BibTeX and JSON, in three scopes: all records, screened records, or included records only
- **Multi-language** — interface translatable via gtranslate.io (PT / EN / ES)
- **Light/dark theme** — per-user theme toggle
- **Google OAuth 2.0 authentication** — no passwords stored locally; each user has isolated projects

---

## Supported bibliographic databases

| Database | Export format |
| --- | --- |
| Scopus | BibTeX, RIS |
| Web of Science | BibTeX, RIS |
| PubMed | XML, RIS |
| Embase | RIS |
| Mendeley / Zotero / EndNote | RIS, BibTeX |
| IEEE Xplore | BibTeX |
| Others | BibTeX, RIS |

Format detection is performed from file content rather than declared by the user: the `EID` field identifies Scopus exports and the `Unique-ID` field identifies Web of Science exports in BibTeX files, the `TY -` header pattern identifies RIS, and the record structure identifies PubMed XML. Detecting provenance automatically matters because PRISMA 2020 requires records identified to be disaggregated by search source.

---

## Technologies

- **Back-end:** PHP 8.1+ (no frameworks)
- **Database:** MySQL 5.7+ / MariaDB 10.4+
- **Front-end:** HTML5, CSS3, vanilla JavaScript
- **JS libraries:** Chart.js, vis.js Network, WordCloud2.js, FileSaver.js
- **External services:** Google OAuth 2.0 (authentication), OpenAlex REST API (metadata enrichment, optional), MyMemory (abstract translation, optional)

---

## Requirements

- PHP 8.1 or higher with extensions: `pdo_mysql`, `curl`, `simplexml`, `mbstring`
- MySQL 5.7+ or MariaDB 10.4+
- Web server: Apache or Nginx (or MAMP/XAMPP for local use)
- A [Google Cloud Console](https://console.cloud.google.com/) account to configure OAuth

There is no build step and no external dependency manager.

---

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/rafaelgross/baseref.git
cd baseref
```

### 2. Set up the database

Create the database and run the SQL scripts in order:

```bash
mysql -u root -p < sql/schema.sql
mysql -u root -p baseref < sql/add_users_table.sql
mysql -u root -p baseref < sql/add_user_id_to_projects.sql
```

### 3. Configure the database connection

Edit `config/database.php` with your credentials:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');   // 8889 for MAMP
define('DB_NAME', 'baseref');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');
```

### 4. Configure Google OAuth 2.0

#### 4.1 Create credentials in the Google Cloud Console

1. Go to [console.cloud.google.com](https://console.cloud.google.com)
2. Create or select a project
3. Go to **APIs & Services** → **Credentials** → **+ Create Credentials** → **OAuth 2.0 Client ID**
4. Application type: **Web application**
5. Under **Authorized redirect URIs**, add:
   - `http://localhost/baseref/auth/callback.php` (local environment)
   - `https://yourdomain.com/baseref/auth/callback.php` (production)
6. Copy the **Client ID** and the **Client Secret**

#### 4.2 Paste the credentials into the system

Edit `config/auth.php`:

```php
define('GOOGLE_CLIENT_ID',     'YOUR_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'YOUR_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI',  'http://localhost/baseref/auth/callback.php');
```

### 5. Configure abstract translation (optional)

The **Translate** button in screening uses the free [MyMemory](https://mymemory.translated.net/) API. Without an email the limit is 1,000 words/day; with a registered email it rises to 10,000 words/day.

Add to the end of your `config/database.php`:

```php
// Email to raise the translation limit (free at mymemory.translated.net)
define('MYMEMORY_EMAIL', 'you@email.com');
```

> This file is in `.gitignore` — your email is never pushed to the repository.

Metadata enrichment via OpenAlex requires no configuration: the public API is queried without a key.

### 6. Access the system

Point your web server to the project folder and open it in the browser. On first launch you will be redirected to the Google login screen.

> **First access:** the first user to log in will have all existing projects in the database automatically assigned to their account.

---

## Local use with MAMP (macOS)

1. Copy the `baseref` folder to `/Applications/MAMP/htdocs/`
2. Start MAMP and open `http://localhost/baseref/`
3. Use phpMyAdmin (`http://localhost:8888/phpMyAdmin`) to run the SQL scripts
4. Set `DB_PORT` to `8889` in `config/database.php`

---

## Project structure

```
baseref/
├── api/                 # REST endpoints (PHP)
│   ├── articles.php
│   ├── bibliometrics.php
│   ├── duplicates.php
│   ├── enrich.php       # OpenAlex metadata enrichment
│   ├── export.php
│   ├── import.php
│   ├── prisma-flow.php
│   ├── projects.php
│   ├── references.php
│   └── screening.php
├── assets/
│   ├── css/style.css    # Global styles
│   ├── js/              # Per-page scripts
│   └── img/             # Logo and icons
├── auth/                # OAuth 2.0 flow
│   ├── callback.php
│   ├── google.php
│   └── logout.php
├── config/
│   ├── auth.php         # OAuth config and session helpers
│   └── database.php     # PDO connection
├── lib/                 # Reference parsers
│   ├── BibTexParser.php
│   ├── RisParser.php
│   └── PubMedXmlParser.php
├── pages/               # PHP views per page
├── sql/                 # Database scripts
│   ├── schema.sql
│   ├── add_users_table.sql
│   └── add_user_id_to_projects.sql
├── index.php            # Main router
└── login.php            # Login page
```

---

## Data model

The relational model comprises the normalized tables `projects`, `search_sources`, `articles`, `authors`, `article_authors` (N:N), `article_keywords`, `article_affiliations`, `duplicates`, `exclusion_reasons`, `screening_decisions` and `prisma_checklist`.

Two SQL views, `vw_prisma_counts` and `vw_project_stats`, pre-aggregate the flow diagram counters in real time. These views are the mechanism that enforces the conservation property of the PRISMA chain: the counts entering and leaving each stage are derived from the same underlying decision records rather than maintained independently.

---

## Multi-user

The system supports multiple researchers in isolation:

- Each user logs in with their own Google account
- Each user only sees and manages their own projects
- New users start with an empty project list
- No user limit

---

## Security

- Authentication via Google OAuth 2.0 (no passwords stored locally)
- CSRF protection with a state token in all OAuth flows
- Sessions with automatic expiration (8 hours)
- Parameterized queries via PDO in all endpoints
- Each user can only access projects they own

---

## License

Distributed under the **MIT** license. See the [LICENSE](LICENSE) file for details.

---

## Contributing

Contributions are welcome. To contribute:

1. Fork the project
2. Create a branch for your feature (`git checkout -b feature/my-feature`)
3. Commit your changes (`git commit -m 'feat: add my feature'`)
4. Push to the branch (`git push origin feature/my-feature`)
5. Open a Pull Request

---

## Citation

If you use BaseRef in your research, please cite the archived software release:

```
Gross R, Gonçalves RF. BaseRef: an open-source platform for systematic literature
reviews. Version 1.0.0 [software]. Zenodo; 2026.
https://doi.org/10.5281/zenodo.21119457
```

Validation data for the pilot review is separately archived:

```
Gross R, Gonçalves RF. Validation data for the BaseRef method: pilot systematic
review on blockchain and smart contracts in the circular economy [dataset].
Zenodo. 2026. https://doi.org/10.5281/zenodo.21133086
```

---

## References

- Page MJ, McKenzie JE, Bossuyt PM, Boutron I, Hoffmann TC, Mulrow CD, et al. The PRISMA 2020 statement: an updated guideline for reporting systematic reviews. BMJ 2021;372:n71. https://doi.org/10.1136/bmj.n71
- Cohen J. A coefficient of agreement for nominal scales. Educ Psychol Meas 1960;20(1):37-46.
- Landis JR, Koch GG. The measurement of observer agreement for categorical data. Biometrics 1977;33(1):159-74.
- ABNT NBR 6023:2018 — Information and documentation: References
- APA 7th edition — Publication Manual of the American Psychological Association
