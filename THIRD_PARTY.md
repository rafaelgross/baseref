# Third-Party Dependencies and External Services

This document details all bundled front-end third-party libraries, licenses, local file paths, and external online services integrated with BaseRef.

---

## 1. Bundled Front-End Libraries (Vendored)

All core front-end libraries used for charting, network visualization, word clouds, file export, and iconography are **served locally** from `assets/vendor/` with pinned versions. 

> **CDN Verification Note:**  
> A scan of every PHP, HTML, CSS and JavaScript file confirms that **no library, stylesheet or font is loaded from a public CDN** (cdnjs, jsDelivr, unpkg, Google Fonts, etc.). All `<script>` and `<link>` tags for libraries and fonts point to relative local paths (`assets/vendor/...`). The only third-party resources fetched by the browser are the **GTranslate language-switcher widget** and the **YouTube** demo video on the landing page, both listed in section 3.

| Library | Version | License | Local File Path | Original Distribution / Source |
|---|---|---|---|---|
| **Chart.js** | 4.4.3 | MIT | `assets/vendor/chart.js-4.4.3/chart.umd.min.js` | npm `chart.js@4.4.3/dist` |
| **vis-network** | 9.1.9 | Apache-2.0 / MIT (dual) | `assets/vendor/vis-network-9.1.9/vis-network.min.js` | npm `vis-network@9.1.9/standalone/umd` |
| **WordCloud2.js** | 1.2.2 | MIT | `assets/vendor/wordcloud2-1.2.2/wordcloud2.min.js` | cdnjs `wordcloud2.js/1.2.2` |
| **FileSaver.js** | 2.0.5 | MIT | `assets/vendor/filesaver-2.0.5/FileSaver.min.js` | cdnjs `FileSaver.js/2.0.5` |
| **Font Awesome Free** | 6.5.1 | Icons: CC BY 4.0<br>Fonts: SIL OFL 1.1<br>Code: MIT | `assets/vendor/fontawesome-6.5.1/css/all.min.css`<br>`assets/vendor/fontawesome-6.5.1/webfonts/*` | cdnjs `font-awesome/6.5.1` |
| **Inter** (variable font, weights 300-800; Latin and Latin-Extended subsets) | v20 (Google Fonts build) | SIL OFL 1.1 | `assets/vendor/inter-gfonts-v20/inter.css`<br>`assets/vendor/inter-gfonts-v20/*.woff2`<br>`assets/vendor/inter-gfonts-v20/OFL.txt` | Google Fonts (`fonts.gstatic.com`), files downloaded once and stored locally |
| **Lora** (regular and italic, weight 400; Latin and Latin-Extended subsets) | v37 (Google Fonts build) | SIL OFL 1.1 | `assets/vendor/lora-gfonts-v37/lora.css`<br>`assets/vendor/lora-gfonts-v37/*.woff2`<br>`assets/vendor/lora-gfonts-v37/OFL.txt` | Google Fonts (`fonts.gstatic.com`), files downloaded once and stored locally |
| **D3.js** | 7.9.0 | ISC | `assets/vendor/d3-7.9.0/d3.min.js` | npm `d3@7.9.0/dist` |
| **topojson-client** | 3.1.0 | ISC | `assets/vendor/topojson-client-3.1.0/topojson-client.min.js` | npm `topojson-client@3.1.0/dist` |
| **SheetJS (xlsx)** | 0.18.5 | Apache-2.0 | `assets/vendor/xlsx-0.18.5/xlsx.full.min.js` | npm `xlsx@0.18.5/dist` |
| **QRCode.js** | 1.0.0 | MIT | `assets/vendor/qrcodejs-1.0.0/qrcode.min.js` | cdnjs `qrcodejs/1.0.0` |

---

## 2. Bundled Datasets & Backend Libraries

* **World Map Boundaries**: `assets/data/countries-110m.json` (TopoJSON dataset for the country production map, served locally).

---

## 3. External Online Services & Integrations

BaseRef relies on or optionally integrates with the following external web services:

| Service | Provider / URL | Purpose | Required or Optional | Data Transmitted |
|---|---|---|---|---|
| **Google OAuth 2.0** | Google (`accounts.google.com`) | Secure authentication / user sign-in | **Required** (for standard login) | OAuth 2.0 handshake tokens, user email, profile name, and picture. |
| **GTranslate (gtranslate.io)** | GTranslate Inc. (`cdn.gtranslate.net/widgets/latest/flags.js`) | Client-side interface language switcher (flags and dynamic translation of UI labels) | **External script** (loaded in `index.php`; the only script loaded from a CDN) | Browser language selection and translated DOM text strings. |
| **OpenAlex API** | OurResearch (`api.openalex.org`) | Metadata enrichment (abstracts, citations, open access status) via DOI | **Optional** (user-triggered via button) | List of bibliographic DOIs. Contact email sent via `mailto` for the polite pool. |
| **MyMemory Translation** | Translated S.r.l. (`api.mymemory.translated.net`) | Server-side translation proxy for abstracts and exclusion reasons | **Optional** (user-triggered via button) | Text snippets (up to 450 characters) to be translated. |
| **AI Providers (Groq / Gemini / Anthropic)** | Groq Inc., Google AI Studio, or Anthropic | Optional automated title/abstract screening suggestions | **Optional** (requires API key) | Article title, year, journal, abstract, and project criteria. Never sends full papers. |
| **YouTube** | Google / YouTube (`i.ytimg.com`, `www.youtube-nocookie.com`, `www.youtube.com`) | Demo video: on the landing page (`landing.php`, thumbnail from `i.ytimg.com`) and in the help modal of the application (`index.php`). In both places the player iframe is created only after the visitor clicks | **Optional** (shown only when opened) | Standard HTTP/iframe headers and client IP address. |
