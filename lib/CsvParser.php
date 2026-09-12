<?php
/**
 * PRISMA-SLR — Scopus CSV parser
 *
 * CSV is the Scopus export format that preserves the most fields
 * (full affiliations, funding, Open Access). Maps the standard columns
 * from Scopus's "CSV / Excel" export to the same input format
 * used by the other parsers (BibTexParser, RisParser, PubMedXmlParser).
 */

class CsvParser
{
    private array $entries = [];
    private array $log     = [];

    public function parse(string $content, string $sourceType = 'auto'): array
    {
        // Remove BOM and normalize line breaks
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $header = fgetcsv($stream);
        if ($header === false || count($header) < 3) {
            fclose($stream);
            $this->log[] = 'Cabeçalho CSV inválido ou ausente';
            return ['entries' => [], 'log' => $this->log];
        }

        // Index by normalized column name
        $col = [];
        foreach ($header as $i => $name) {
            $col[strtolower(trim($name))] = $i;
        }

        // Detection heuristic: Scopus CSV includes the EID column
        $isScopus = isset($col['eid']);
        if (!$isScopus) {
            $this->log[] = 'Aviso: coluna EID ausente — o arquivo pode não ser um export padrão do Scopus';
        }

        $get = function (array $row, string ...$names) use ($col): ?string {
            foreach ($names as $n) {
                $i = $col[strtolower($n)] ?? null;
                if ($i !== null && isset($row[$i]) && trim($row[$i]) !== '') {
                    return trim($row[$i]);
                }
            }
            return null;
        };

        $line = 1;
        while (($row = fgetcsv($stream)) !== false) {
            $line++;
            if (count($row) === 1 && trim((string) $row[0]) === '') continue;

            $title = $get($row, 'Title', 'Document Title');
            if (!$title) {
                $this->log[] = "Linha $line ignorada: título ausente";
                continue;
            }

            // Authors: "Author full names" provides full names with ID; otherwise "Authors"
            $authors = [];
            $authorsFull = $get($row, 'Author full names');
            if ($authorsFull) {
                foreach (explode(';', $authorsFull) as $a) {
                    // Format: "Lastname, Firstname (12345678900)"
                    $a = trim(preg_replace('/\s*\(\d+\)\s*$/', '', trim($a)));
                    if ($a !== '') $authors[] = $a;
                }
            } else {
                foreach (explode(';', $get($row, 'Authors') ?? '') as $a) {
                    $a = trim($a);
                    if ($a !== '' && strtolower($a) !== '[no author name available]') $authors[] = $a;
                }
            }

            // Pages
            $pages = null;
            $ps = $get($row, 'Page start');
            $pe = $get($row, 'Page end');
            if ($ps && $pe)    $pages = "$ps-$pe";
            elseif ($ps)       $pages = $ps;

            // Keywords
            $kwAuthor = $this->splitList($get($row, 'Author Keywords'));
            $kwIndex  = $this->splitList($get($row, 'Index Keywords'));

            // Affiliations: "Inst A, City, Country; Inst B, City, Country"
            $affiliations = [];
            foreach (explode(';', $get($row, 'Affiliations') ?? '') as $aff) {
                $aff = trim($aff);
                if ($aff === '') continue;
                $parts   = array_map('trim', explode(',', $aff));
                $country = count($parts) >= 2 ? end($parts) : null;
                $affiliations[] = ['institution' => $aff, 'country' => $country];
            }

            // Open Access: free-text field ("All Open Access; Gold Open Access...")
            $oa = $get($row, 'Open Access');
            $openAccess = ($oa && stripos($oa, 'open access') !== false) ? 1 : 0;

            $abstract = $get($row, 'Abstract');
            if ($abstract && strtolower($abstract) === '[no abstract available]') $abstract = null;

            $this->entries[] = [
                'source_type'     => 'scopus',
                'source_key'      => $get($row, 'EID'),
                'title'           => $title,
                'abstract'        => $abstract,
                'year'            => $get($row, 'Year'),
                'document_type'   => $get($row, 'Document Type'),
                'language'        => $get($row, 'Language of Original Document') ?? 'English',
                'journal'         => $get($row, 'Source title'),
                'volume'          => $get($row, 'Volume'),
                'issue'           => $get($row, 'Issue'),
                'pages'           => $pages,
                'article_number'  => $get($row, 'Art. No.'),
                'issn'            => $get($row, 'ISSN'),
                'isbn'            => $get($row, 'ISBN'),
                'doi'             => $get($row, 'DOI'),
                'url'             => $get($row, 'Link'),
                'eid'             => $get($row, 'EID'),
                'pubmed_id'       => $get($row, 'PubMed ID'),
                'publisher'       => $get($row, 'Publisher'),
                'cited_by'        => $get($row, 'Cited by'),
                'open_access'     => $openAccess,
                'authors'         => $authors,
                'keywords_author' => $kwAuthor,
                'keywords_indexed'=> $kwIndex,
                'affiliations'    => $affiliations,
                'raw_bibtex'      => null,
            ];
        }
        fclose($stream);

        $this->log[] = count($this->entries) . ' registros lidos do CSV';
        return ['entries' => $this->entries, 'log' => $this->log];
    }

    private function splitList(?string $value): array
    {
        if (!$value) return [];
        return array_values(array_filter(array_map('trim', explode(';', $value))));
    }
}
