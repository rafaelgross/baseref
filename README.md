# PRISMA-SLR

**Sistema web open source para gestão de Revisões Sistemáticas da Literatura** baseado no protocolo **PRISMA 2020**.

![PRISMA-SLR Screenshot](assets/img/logo.svg)

## Funcionalidades

- **Projetos** — Gerencie múltiplas revisões simultaneamente
- **Importação** — Suporte a arquivos `.bib` do Scopus e Web of Science
- **Deduplicação** — Detecção automática de artigos duplicados por DOI e similaridade de título
- **Triagem** — Avaliação de título/resumo com decisões de inclusão/exclusão e motivos configuráveis
- **Elegibilidade** — Avaliação de texto completo
- **Diagrama PRISMA 2020** — Gerado automaticamente com exportação PNG/SVG
- **Bibliometria** — 12 gráficos + redes de co-autoria e co-ocorrência de keywords
- **Exportação** — CSV, BibTeX e JSON para todos os escopos
- **Checklist PRISMA 2020** — 27 itens para garantir conformidade do relatório

## Tecnologias

- **Backend**: PHP 8+ / MySQL
- **Frontend**: HTML5, CSS3, JavaScript (sem framework)
- **Gráficos**: Chart.js 4, vis.js 9, wordcloud2.js
- **Servidor local**: MAMP (macOS/Windows), XAMPP ou qualquer servidor PHP+MySQL

## Instalação (MAMP)

### Pré-requisitos
- [MAMP](https://www.mamp.info/) instalado e rodando
- PHP 8.0+ e MySQL 8.0+

### Passo a passo

1. **Clone o repositório** na pasta `htdocs` do MAMP:
   ```bash
   cd /Applications/MAMP/htdocs
   git clone https://github.com/SEU_USUARIO/prisma-slr.git
   cd prisma-slr
   ```

2. **Configure o banco de dados** em `config/database.php`:
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_PORT', '8889');      // Padrão MAMP = 8889
   define('DB_NAME', 'prisma_slr');
   define('DB_USER', 'root');
   define('DB_PASS', 'root');
   ```

3. **Crie o banco de dados** via phpMyAdmin (MAMP → Open WebStart → phpMyAdmin):
   - Crie o banco `prisma_slr` (charset: `utf8mb4`)
   - Importe o arquivo `sql/schema.sql`

   **Ou via terminal:**
   ```bash
   /Applications/MAMP/Library/bin/mysql -u root -proot -P 8889 -h 127.0.0.1 \
     -e "CREATE DATABASE IF NOT EXISTS prisma_slr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   /Applications/MAMP/Library/bin/mysql -u root -proot -P 8889 -h 127.0.0.1 prisma_slr \
     < sql/schema.sql
   ```

4. **Acesse no navegador:**
   ```
   http://localhost:8888/prisma-slr/
   ```
   > Porta padrão do MAMP = 8888. Ajuste conforme sua configuração.

## Instalação (XAMPP / outros)

Configure `config/database.php` com:
```php
define('DB_PORT', '3306');    // porta padrão MySQL
define('DB_USER', 'root');    // ou seu usuário
define('DB_PASS', '');        // ou sua senha
```

## Uso Básico

1. **Criar projeto** → Projetos → Novo Projeto
2. **Importar referências** → Importar → arraste arquivos `.bib` do Scopus ou WoS
3. **Dedupliquem** → Duplicatas → Detectar (ajuste threshold se necessário)
4. **Triagem** → Triagem → avalie título/resumo artigo por artigo
5. **Elegibilidade** → Elegibilidade → avalie texto completo
6. **Visualizar resultados** → Diagrama PRISMA / Bibliometria
7. **Exportar** → Exportar → CSV, BibTeX ou JSON
8. **Checklist** → Checklist PRISMA 2020 → 27 itens para o relatório

## Formatos Suportados

### Scopus (`.bib`)
- Campos: `EID`, `affiliations`, `author_keywords`, `note` (cited by), `source`
- Auto-detectado por padrão

### Web of Science (`.bib`)
- Campos: `Unique-ID`, `Times-Cited`, `Keywords-Plus`, `Affiliation`, `Web-of-Science-Index`
- Auto-detectado por padrão

## Estrutura do Projeto

```
prisma-slr/
├── api/               # Endpoints REST (PHP)
│   ├── projects.php
│   ├── import.php
│   ├── articles.php
│   ├── duplicates.php
│   ├── screening.php
│   ├── prisma-flow.php
│   ├── bibliometrics.php
│   └── export.php
├── assets/
│   ├── css/style.css  # Design system completo
│   ├── js/            # Scripts
│   └── img/           # Imagens
├── config/
│   └── database.php   # Configuração do banco
├── lib/
│   └── BibTexParser.php  # Parser BibTeX customizado
├── pages/             # Páginas PHP (inclusas via index.php)
├── sql/
│   └── schema.sql     # Esquema do banco de dados
└── index.php          # Roteador principal
```

## Licença

MIT © 2024 — Veja [LICENSE](LICENSE)

## Citação

Se usar este software em sua pesquisa, por favor cite:

```
PRISMA-SLR: Sistema web para gestão de Revisões Sistemáticas da Literatura.
GitHub: https://github.com/SEU_USUARIO/prisma-slr
```

---

Desenvolvido para apoiar pesquisadores na condução de revisões sistemáticas da literatura de acordo com o protocolo PRISMA 2020.
