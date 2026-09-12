# BaseRef User Manual

**A web platform for conducting Systematic Literature Reviews following the PRISMA 2020 protocol**

Version 1.0 · https://baseref.com.br

---

## Table of Contents

1. [What is BaseRef?](#1-what-is-baseref)
2. [Getting Started](#2-getting-started)
3. [Workflow Overview](#3-workflow-overview)
4. [Projects](#4-projects)
5. [PICO Extraction](#5-pico-extraction)
6. [Importing References](#6-importing-references)
7. [Deduplication](#7-deduplication)
8. [Screening (Title and Abstract)](#8-screening-title-and-abstract)
9. [AI-Assisted Screening](#9-ai-assisted-screening)
10. [Eligibility (Full Text)](#10-eligibility-full-text)
11. [Reviewer Agreement](#11-reviewer-agreement)
12. [Quality and Risk-of-Bias Assessment](#12-quality-and-risk-of-bias-assessment)
13. [PRISMA 2020 Flow Diagram](#13-prisma-2020-flow-diagram)
14. [Bibliometrics](#14-bibliometrics)
15. [Formatted References](#15-formatted-references)
16. [Export](#16-export)
17. [PRISMA 2020 Checklist](#17-prisma-2020-checklist)
18. [Frequently Asked Questions](#18-frequently-asked-questions)
19. [Support and How to Cite](#19-support-and-how-to-cite)

---

## 1. What is BaseRef?

**BaseRef** is a free, open-source (MIT license) web platform that supports researchers in conducting **Systematic Literature Reviews (SLRs)** following the **PRISMA 2020** protocol — the most widely adopted international standard for reporting systematic reviews.

With BaseRef you can:

- Import references from major databases (Scopus, Web of Science, PubMed, Embase, IEEE Xplore, and others)
- Automatically detect and resolve duplicate articles
- Screen titles and abstracts, with AI-assisted include/exclude suggestions
- Assess full-text eligibility using standardized exclusion reasons
- Work with two or more reviewers and measure agreement (Cohen's kappa)
- Assess methodological quality and risk of bias (CASP, JBI, or a custom instrument)
- Automatically generate the PRISMA 2020 flow diagram and export it as SVG or PNG
- Analyze bibliometrics with charts, maps, and co-authorship networks
- Generate formatted references in ABNT NBR 6023:2018 and APA 7th edition
- Export data as CSV, BibTeX, or JSON
- Track the 27 items of the PRISMA 2020 checklist

All work is saved in the cloud and is user-isolated: each researcher sees only their own projects and those they've been invited to collaborate on.

---

## 2. Getting Started

### Access

1. Go to **https://baseref.com.br**.
2. Click **Sign in with Google**.
3. Authorize access to your Google account.

There is no password registration: authentication is handled via **Google OAuth 2.0**, and no password is stored by the platform. The session expires automatically after a few hours of inactivity — simply sign in again.

### The system screen

- **Side menu (left)** — active project selector and all review stages, grouped into *Overview*, *PRISMA Process*, *Analysis*, *Data*, and *Support*.
- **Header (top)** — active project name, language selector (PT / EN / ES), and light/dark theme toggle.
- **Main area** — the stage you're currently working on.

### In-app help

In the side menu, under **Support → Help**, you'll find:

- **Video Tutorial** — opens the platform's demonstration video in an on-screen window.
- **PDF Tutorial** — opens this manual.

> **Tip:** the interface language (PT / EN / ES) and the light/dark theme are saved in your browser and reapplied the next time you visit.

---

## 3. Workflow Overview

A systematic review in BaseRef follows the steps below, in this order:

```
[1]  Create the project and define criteria
      ↓
[2]  Structure the question (PICO)
      ↓
[3]  Import references (.bib / .ris / .xml / .csv / .txt)
      ↓
[4]  Detect and resolve duplicates
      ↓
[5]  Screening — title and abstract
      ↓
[6]  Eligibility — full text
      ↓
[7]  Reviewer agreement (kappa)
      ↓
[8]  Quality / risk-of-bias assessment
      ↓
[9]  PRISMA 2020 flow diagram
      ↓
[10] Bibliometrics
      ↓
[11] References and export
      ↓
[12] PRISMA 2020 checklist
```

The **Dashboard** shows, in real time, how many articles have been identified, how many duplicates have been removed, and how many articles are in each phase.

> **Tip:** steps 7, 8, and 10 are optional but strongly recommended — most journals require reporting of reviewer agreement and quality assessment.

---

## 4. Projects

Each project is an independent review: it has its own references, decisions, criteria, and collaborators. You can keep several reviews in progress at the same time without any risk of mixing data.

### Creating a project

1. Go to **Projects** in the side menu.
2. Click **New Project**.
3. Fill in the fields:
   - **Title** (required) — the name of your review.
   - **Description** — additional information about the study.
   - **Objective / Research Question** — the question the review aims to answer.
   - **Search Period — Start / End** — the time frame of the search (e.g., 2013 to 2024).
   - **Inclusion criteria** and **exclusion criteria** — write them clearly and objectively.
4. Click **Create**.

> **Important:** the inclusion and exclusion criteria are not just documentation — they are what the **AI-assisted screening** uses to suggest decisions. The more specific they are, the better the suggestions.

### Selecting the active project

At the top of the side menu is the **Active Project** selector. All stages (import, screening, diagram, bibliometrics) always refer to the project selected there.

### Collaborators

For reviews with more than one reviewer:

1. Open the **Dashboard** (or the project's collaborators area).
2. Enter the **collaborator's email** and confirm.
3. The collaborator will then see the project in their own account.

> **Important:** the collaborator needs to have logged into BaseRef at least once with that email before being added.

---

## 5. PICO Extraction

The **PICO** framework helps structure the research question and standardize data extraction from included studies.

1. Go to **PICO Extraction** in the side menu.
2. Select the active project.
3. For each article, record:
   - **P — Population** — who was studied.
   - **I — Intervention** — what was applied, analyzed, or proposed.
   - **C — Comparison** — the comparator or control group, if any.
   - **O — Outcome** — the results measured.
   - **Context** — the setting, country, sector, or scenario of the study.

The PICO data travels with the article and can be reviewed alongside the project's other information, serving as the basis for synthesizing results in the manuscript.

---

## 6. Importing References

### Supported formats

| Format | Extension | Compatible databases |
|---------|----------|-------------------|
| BibTeX | `.bib` | Scopus, Web of Science, IEEE Xplore, Mendeley, Zotero |
| RIS | `.ris` | Mendeley, Zotero, EndNote, PubMed, Embase |
| PubMed XML | `.xml` | PubMed (MEDLINE XML export) |
| CSV / text | `.csv`, `.txt` | Tabular exports and simple lists |

The maximum file size is **50 MB**.

### How to import

1. Go to **Import** in the side menu.
2. Choose the file format from the tabs: **BibTeX**, **RIS**, **PubMed XML**, or **CSV/Text**.
3. Drag the file into the indicated area, or click it to select a file.
4. Fill in the **Source Name** (e.g., `Scopus 2024`, `WoS — December 2023`).
5. Select the source **Database**.
6. Enter the **Search Date** — this is required by PRISMA reporting.
7. Click **Import**.

When finished, the system reports how many records were found, how many were imported, and whether any records had reading errors.

### How to export from each database

**Scopus** — run the search, select the results → `Export` → `BibTeX` → check all fields.

**Web of Science** — select the records → `Export` → `BibTeX` (or `RIS Format`) → export all fields.

**PubMed** — run the search, select the results → `Send to` → `File` → format `PubMed` or `XML` → `Create file`.

**Embase and other databases** — export as `RIS`.

**Mendeley / Zotero / EndNote** — export the desired folder as `BibTeX` or `RIS`.

> **Tip:** import each database into a separate file, with its own source name. BaseRef records the origin of each reference, and the PRISMA diagram needs this information to report how many records came from each database.

### Import history

The import screen itself lists the files already processed, with name, database, search date, number of articles, and the date they were imported.

---

## 7. Deduplication

When importing from multiple databases, it's normal for the same article to appear more than once. This step identifies and resolves those cases.

### How to detect

1. Go to **Duplicates** in the side menu.
2. Click **Detect Duplicates**.
3. Wait for processing (this can take a few seconds for large databases).

Detection uses two criteria:

- **Identical DOI** — an exact match of the identifier.
- **Title similarity** — textual comparison, with a similarity score shown for each pair.

### Reviewing pairs

Each suspected pair is shown side by side. For each one you can:

- **Confirm as duplicate** — the secondary record is marked as a duplicate and removed from subsequent steps.
- **Reject** — both records are kept as distinct studies.
- **Confirm all pending** — accepts all detected pairs at once (use with caution, especially when similarity is low).

### Reset

The **Reset** button clears the deduplication results and lets you run the process again — useful after importing new databases.

> **Important:** articles marked as duplicates do not appear in Screening, Eligibility, or the bibliometric charts, but they are correctly counted in the PRISMA diagram as "records removed before screening."

---

## 8. Screening (Title and Abstract)

In this phase you assess each article by reading its **title and abstract** and decide whether it proceeds to full-text assessment.

### How to screen

1. Go to **Screening** in the side menu.
2. For each article, choose:
   - **Include** — the article moves on to Eligibility.
   - **Exclude** — provide the **reason for exclusion** (required) and, optionally, a comment.
   - **Undo** — returns the article to the pending list.
3. The progress bar at the top shows screening progress.

### Screen features

- **Search** — find articles by title or abstract.
- **Quick filters** — *Pending*, *Included*, *Excluded*, and *All*.
- **Full abstract** — click the article title to read the full abstract.
- **Abstract translation** — a button that translates the abstract into Portuguese, useful for articles in other languages.
- **Relevance rating** — assign 1 to 5 stars (*low relevance* to *highly relevant*) to prioritize full-text reading.
- **AI suggestion** — view the automatic recommendation before deciding (detailed in the next section).

> **Tip:** exclusion reasons are reused in the PRISMA diagram and in exports. Standardize your list of reasons at the start of the review and keep it stable until the end.

---

## 9. AI-Assisted Screening

BaseRef can analyze an article's title and abstract and suggest **include** or **exclude**, always based on the **inclusion and exclusion criteria registered in the project** and the review's objective.

### How to use it

1. Fill in the project's objective and inclusion/exclusion criteria.
2. In Screening, trigger the **AI suggestion** for the article.
3. Read the recommendation and the rationale provided.
4. **The final decision is always yours** — the suggestion does not automatically change the article's status.

> **Important:** the AI suggestion is a reading aid, not a replacement for the reviewer. For publication purposes, report in the manuscript that AI assistance was used during screening and that all decisions were verified by human reviewers.

---

## 10. Eligibility (Full Text)

Articles included during screening move on to this phase, in which you assess the **full text**.

1. Go to **Eligibility** in the side menu.
2. Obtain the article's full text (via DOI, the journal's website, or your institution's repository).
3. Choose:
   - **Include** — the article becomes part of the review's final corpus.
   - **Exclude** — provide the reason (full-text exclusion criterion).

The interface is identical to Screening, with the same filters, search, and comments. Exclusion reasons recorded here appear in the "full-text articles excluded, with reasons" block of the PRISMA diagram — exactly as the protocol requires.

---

## 11. Reviewer Agreement

High-quality systematic reviews are typically conducted by **two or more independent reviewers**. This screen measures the degree of agreement between them.

### Review modes

- **Single reviewer (default)** — only one reviewer decides; no agreement is calculated.
- **Double-blind (2 or more independent reviewers)** — each reviewer assesses without seeing the other's decision.

### How to configure

1. Go to **Agreement (κ)** in the side menu.
2. Select **Double-blind** mode.
3. Add reviewers by email (the reviewer must have already logged into BaseRef).
4. Choose the phase to analyze: **Screening (title/abstract)** or **Eligibility (full text)**.

### What the screen shows

- **Cohen's kappa (κ)** — an index of agreement between two reviewers, discounting agreement by chance. The calculation requires **at least two reviewers with recorded decisions** in the chosen phase.
- **Agreement matrix** — a cross-tabulation of decisions by Reviewer 1 (R1) and Reviewer 2 (R2).
- **Pending conflicts** — articles on which reviewers disagreed, for resolution by consensus or by a third reviewer.

### How to interpret kappa

| κ value | Typical interpretation |
|------------|---------------------|
| < 0.00 | No agreement |
| 0.00 – 0.20 | Slight agreement |
| 0.21 – 0.40 | Fair agreement |
| 0.41 – 0.60 | Moderate agreement |
| 0.61 – 0.80 | Substantial agreement |
| 0.81 – 1.00 | Almost perfect agreement |

> **Tip:** report in the manuscript the κ value obtained, the phase in which it was calculated, and how conflicts were resolved. This is frequently required by journal reviewers.

---

## 12. Quality and Risk-of-Bias Assessment

After defining the final corpus, assess the methodological quality of each included study.

### Choosing the instrument

1. Go to **Quality/Bias** in the side menu.
2. Select the instrument:
   - **CASP** — 10 items (*Critical Appraisal Skills Programme*).
   - **JBI** — 8 items (*Joanna Briggs Institute*).
   - **Custom** — create your own questions using the **New question...** field.
3. Confirm.

> **Important:** switching instruments **erases all previously recorded answers**. Choose the instrument before you start assessing.

### Assessing the studies

For each included article, answer item by item. The possible answers are **yes**, **no**, and **unclear**, and the screen consolidates the total for each category per study.

### Export

The export button generates a **CSV** with all answers by article and by item — ready to become the quality assessment table in your paper or as supplementary material.

---

## 13. PRISMA 2020 Flow Diagram

The flow diagram is generated **automatically** from the project's actual numbers — it is not filled in by hand.

### How to view it

1. Go to **PRISMA Diagram** in the side menu.
2. The flowchart is displayed with the project's current numbers already in place.

### Diagram structure

```
Identification
  └── Records identified from databases
  └── Records removed before screening (duplicates)
Screening
  └── Records screened
  └── Records excluded (with reasons)
Eligibility
  └── Full-text articles assessed
  └── Full-text articles excluded (with reasons)
Included
  └── Studies included in the review
```

### Language

The diagram follows the language selected in the header (**PT / EN / ES**) — switch the flag and the flowchart is redrawn with translated labels, ready for submission to a national or international journal.

### Export

- **SVG** — a vector format, editable in Inkscape, Illustrator, or Figma; the best option for submission.
- **PNG** — an image at 150 dpi or 300 dpi. Exporting at **300 dpi** meets the requirements of most journals.

> **Tip:** if the numbers don't match what you expect, check the deduplication and pending screening decisions — the diagram always reflects the current state of the data.

---

## 14. Bibliometrics

The bibliometrics area visually analyzes the review's corpus.

1. Go to **Bibliometrics** in the side menu.
2. The charts load automatically.

### Available analyses

| # | Analysis | What it shows |
|---|---------|--------------|
| 1 | Publications by year | Temporal evolution of output |
| 2 | Articles by database | Distribution by search source |
| 3 | Top journals | Journals with the most publications |
| 4 | Top authors | Most productive authors |
| 5 | Top institutions | Most frequent affiliations |
| 6 | Top countries | Ranking and world map |
| 7 | Citation distribution | Histogram of citations per article |
| 8 | Most-cited articles | Studies with the greatest impact |
| 9 | Keyword cloud | Most frequent terms |
| 10 | Keyword co-occurrence | Network of related terms |
| 11 | Co-authorship network | Collaboration between authors |
| 12 | Thematic areas | Classification by field of knowledge |

Charts can be saved as images for direct use in the manuscript.

> **Note:** the analyses consider the articles **included** in the review. Metadata missing from the source database (such as affiliation or citations) may limit some charts.

---

## 15. Formatted References

BaseRef generates a reference list already formatted from the articles' metadata.

1. Go to **References** in the side menu.
2. Choose the scope: **Included only** or **All (except duplicates)**.
3. Click **Select Articles** and check the ones you want.
4. Click **Generate References**.
5. Choose the style:
   - **ABNT NBR 6023:2018**
   - **APA 7th edition**
6. Copy the ready-made list into your text editor.

> **Important:** always check the generated references. Formatting depends on the quality of the metadata exported from the source database — abbreviated author names or all-caps titles in the source require manual adjustment.

---

## 16. Export

1. Go to **Export** in the side menu.
2. Choose the **scope**:
   - **All articles** — everything imported, except duplicates.
   - **Approved in screening** — articles that moved on to eligibility.
   - **Included articles** — the review's final corpus.
   - **Excluded articles** — discarded articles, with their reasons.
3. Choose the format: **CSV**, **BibTeX**, or **JSON**.

### When to use each format

| Format | Best for |
|---------|------------|
| CSV | Analysis in Excel, Google Sheets, R, or Python; supplementary material |
| BibTeX | Importing into Mendeley, Zotero, or LaTeX |
| JSON | Full metadata backup and integration with other systems |

> **Tip:** exporting the excluded articles as CSV, with reasons, is the simplest way to build the "excluded studies and reasons" appendix required by many journals.

---

## 17. PRISMA 2020 Checklist

The checklist covers the **27 items** that the report of a systematic review must address according to PRISMA 2020.

1. Go to **PRISMA Checklist** in the side menu.
2. For each item, record its status and indicate the **page or section** of your manuscript where it is addressed (e.g., `p. 5, Section 2.1`).
3. Use the **comments** field for additional notes.
4. Track the **Checklist Progress** bar.

The completed checklist is often requested as a submission document by journals, along with the flow diagram.

---

## 18. Frequently Asked Questions

**Is BaseRef free?**
Yes. It is 100% free and open source, under the MIT license.

**Do I need to install anything?**
No. BaseRef runs entirely in the browser. Your data is saved in the cloud, isolated per user.

**Can I import the same file twice?**
You can, but the system does not block re-importing. If this happens, run duplicate detection again.

**I changed a screening decision for an article that was already in eligibility. What happens?**
The article is removed from the eligibility scope, and all the PRISMA diagram numbers are automatically recalculated.

**How do I conduct a review with two independent reviewers?**
Add the second reviewer as a project collaborator, enable **Double-blind** mode on the Agreement screen, and resolve any flagged conflicts. Cohen's kappa is calculated automatically.

**Can the PRISMA diagram be edited manually?**
No. The numbers come from the decisions recorded in the system. To change them, adjust the data in screening, eligibility, or deduplication.

**What resolution should I use to export the diagram?**
Prefer **SVG**. If the journal requires a rasterized image, use **PNG at 300 dpi**.

**Does the AI decide on its own which articles are included?**
No. It only suggests, based on the criteria you registered. The decision is always the reviewer's.

**How do I back up my data?**
Export the project as **JSON** (contains all metadata) and, if you'd like, also as CSV and BibTeX. Keep the files together with your research materials.

**What languages does the system support?**
Portuguese, English, and Spanish — including the PRISMA diagram.

---

## 19. Support and How to Cite

### Support

Questions about the platform, about systematic reviews, or about the PRISMA 2020 protocol:
**rafaelgross123@gmail.com**

### How to cite BaseRef

> GROSS, Rafael; GONÇALVES, Rodrigo Franco. **BaseRef**: a platform supporting research and bibliographic reference management. Version 1.0.0 [software]. Zenodo, 2026. DOI: 10.5281/zenodo.21119457. Available at: <https://baseref.com.br/>.

### Protocol reference

> PAGE, M. J. et al. The PRISMA 2020 statement: an updated guideline for reporting systematic reviews. **BMJ**, v. 372, n. 71, 2021. DOI: 10.1136/bmj.n71.

---

*BaseRef · Distributed under the MIT license · https://baseref.com.br*
