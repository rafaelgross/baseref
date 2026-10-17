# Third-party front-end libraries (vendored)

All JavaScript/CSS libraries are served locally from this folder, pinned to exact
versions, so BaseRef does not depend on public CDNs at runtime. Each file is
byte-identical to the official distribution listed below (verified by SHA-256
against the CDN copy when vendored, 2026-10-07).

| Library | Version | File(s) | Original source | License |
|---|---|---|---|---|
| Font Awesome Free | 6.5.1 | `fontawesome-6.5.1/css/all.min.css`, `webfonts/*` | cdnjs `font-awesome/6.5.1` | Icons CC BY 4.0, fonts SIL OFL 1.1, code MIT (`LICENSE.txt`) |
| Chart.js | 4.4.3 | `chart.js-4.4.3/chart.umd.min.js` | npm `chart.js@4.4.3/dist` | MIT |
| vis-network | 9.1.9 | `vis-network-9.1.9/vis-network.min.js` (standalone UMD) | npm `vis-network@9.1.9/standalone/umd` | Apache-2.0 or MIT (dual) |
| D3 | 7.9.0 | `d3-7.9.0/d3.min.js` | npm `d3@7.9.0/dist` | ISC |
| topojson-client | 3.1.0 | `topojson-client-3.1.0/topojson-client.min.js` | npm `topojson-client@3.1.0/dist` | ISC |
| SheetJS Community Edition | 0.18.5 | `xlsx-0.18.5/xlsx.full.min.js` | npm `xlsx@0.18.5/dist` | Apache-2.0 |
| wordcloud2.js | 1.2.2 | `wordcloud2-1.2.2/wordcloud2.min.js` | cdnjs `wordcloud2.js/1.2.2` | MIT |
| FileSaver.js | 2.0.5 | `filesaver-2.0.5/FileSaver.min.js` | cdnjs `FileSaver.js/2.0.5` | MIT |
| QRCode.js (davidshimjs) | 1.0.0 | `qrcodejs-1.0.0/qrcode.min.js` | cdnjs `qrcodejs/1.0.0` | MIT |
| Inter (variable font, 300-800) | Google Fonts v20 | `inter-gfonts-v20/inter.css`, `*.woff2` (Latin, Latin-Extended) | Google Fonts (`fonts.gstatic.com`), downloaded 2026-10-16 | SIL OFL 1.1 (`OFL.txt`) |
| Lora (regular and italic, 400) | Google Fonts v37 | `lora-gfonts-v37/lora.css`, `*.woff2` (Latin, Latin-Extended) | Google Fonts (`fonts.gstatic.com`), downloaded 2026-10-16 | SIL OFL 1.1 (`OFL.txt`) |

The world map used by the bibliometrics page (`assets/data/countries-110m.json`)
is also local, and so are the web fonts (Inter and Lora). The only resources still
fetched from third parties by the browser are the gtranslate.io language widget
and the YouTube tutorial video (thumbnail and player, created only after a click).

To upgrade a library, download the new exact version into a new
`<name>-<version>/` folder and update the corresponding `<script>`/`<link>` tag
in `index.php` (and `login.php` for Font Awesome and the Inter font).
