<?php
/**
 * PRISMA-SLR - BibTeX Parser
 *
 * Supports the two detected formats:
 *   - Scopus: indentation with \t, fields like affiliations, author_keywords,
 *             note with "Cited by: N", EID, etc.
 *   - Web of Science: indentation with spaces, fields Unique-ID, Times-Cited,
 *                     Keywords-Plus, Affiliation, Web-of-Science-Index, etc.
 */

class BibTexParser
{
    /** @var array Already-parsed entries */
    private array $entries = [];

    /** @var array Log of warnings and errors */
    private array $log = [];

    // -----------------------------------------------------------------
    // Public interface
    // -----------------------------------------------------------------

    /**
     * Parses a .bib file and returns an array of normalized entries.
     *
     * @param  string $content  Raw file content
     * @param  string $sourceType 'scopus'|'wos'|'auto'
     * @return array ['entries' => [...], 'log' => [...]]
     */
    public function parse(string $content, string $sourceType = 'auto'): array
    {
        $this->entries = [];
        $this->log     = [];

        // Remove UTF-8 BOM
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Auto-detect type if necessary
        if ($sourceType === 'auto') {
            $sourceType = $this->detectSourceType($content);
        }

        // Extract @TYPE{...} blocks
        $blocks = $this->extractBlocks($content);
        $this->log[] = ['type' => 'info', 'msg' => sprintf('%d blocos encontrados (fonte: %s)', count($blocks), $sourceType)];

        foreach ($blocks as $index => $block) {
            try {
                $entry = $this->parseBlock($block, $sourceType);
                if ($entry !== null) {
                    $this->entries[] = $entry;
                }
            } catch (Throwable $e) {
                $this->log[] = [
                    'type' => 'warning',
                    'msg'  => "Erro no bloco #{$index}: " . $e->getMessage()
                ];
            }
        }

        $this->log[] = ['type' => 'info', 'msg' => sprintf('%d entradas parseadas com sucesso', count($this->entries))];

        return [
            'entries'     => $this->entries,
            'log'         => $this->log,
            'source_type' => $sourceType,
        ];
    }

    // -----------------------------------------------------------------
    // Format detection
    // -----------------------------------------------------------------

    private function detectSourceType(string $content): string
    {
        // Scopus uses EID and lowercase affiliations
        if (preg_match('/\bEID\s*=|affiliations\s*=|author_keywords\s*=/i', $content)) {
            return 'scopus';
        }
        // WoS uses Unique-ID and Times-Cited
        if (preg_match('/Unique-ID\s*=|Times-Cited\s*=|Web-of-Science-Index\s*=/i', $content)) {
            return 'wos';
        }
        return 'other';
    }

    // -----------------------------------------------------------------
    // Block extraction
    // -----------------------------------------------------------------

    private function extractBlocks(string $content): array
    {
        $blocks = [];
        $len    = strlen($content);
        $i      = 0;

        while ($i < $len) {
            // Look for the start of an @ entry
            $pos = strpos($content, '@', $i);
            if ($pos === false) break;

            // Ignore @comment, @preamble, @string
            $rest = substr($content, $pos + 1, 20);
            if (preg_match('/^(comment|preamble|string)\b/i', $rest)) {
                // Skip to the next @
                $i = $pos + 1;
                continue;
            }

            // Find the opening {
            $openPos = strpos($content, '{', $pos);
            if ($openPos === false) break;

            // Walk through with a brace counter to find the closing one
            $depth  = 0;
            $end    = $openPos;
            $inStr  = false;

            for ($j = $openPos; $j < $len; $j++) {
                $ch = $content[$j];
                if ($ch === '{') $depth++;
                elseif ($ch === '}') {
                    $depth--;
                    if ($depth === 0) { $end = $j; break; }
                }
            }

            if ($depth !== 0) {
                // Malformed block - try to recover
                $this->log[] = ['type' => 'warning', 'msg' => "Bloco mal formado iniciado na posição {$pos}"];
                $i = $pos + 1;
                continue;
            }

            $blocks[] = substr($content, $pos, $end - $pos + 1);
            $i = $end + 1;
        }

        return $blocks;
    }

    // -----------------------------------------------------------------
    // Parsing an individual block
    // -----------------------------------------------------------------

    private function parseBlock(string $block, string $sourceType): ?array
    {
        // Extract type and key: @ARTICLE{KeyHere,
        if (!preg_match('/^@(\w+)\s*\{\s*([^,\n]*)/s', $block, $m)) {
            return null;
        }

        $entryType = strtolower(trim($m[1]));
        $citeKey   = trim($m[2]);

        // Ignore entries with unrecognized type
        $validTypes = ['article','inproceedings','proceedings','conference','book',
                       'incollection','phdthesis','mastersthesis','techreport','misc','review'];
        // Proceed even if not recognized
        if (empty($citeKey)) return null;

        // Extract the content inside the braces (everything after the key's comma)
        $inner = substr($block, strpos($block, ',') + 1);
        // Remove the final closing brace
        $inner = rtrim($inner, " \t\n\r}");

        // Parse fields
        $rawFields = $this->parseFields($inner);

        // Normalize into a unified structure
        $entry = $this->normalize($rawFields, $entryType, $citeKey, $sourceType, $block);

        return $entry;
    }

    // -----------------------------------------------------------------
    // Parsing all fields of a block
    // -----------------------------------------------------------------

    private function parseFields(string $inner): array
    {
        $fields = [];
        $len    = strlen($inner);
        $i      = 0;

        while ($i < $len) {
            // Skip spaces and commas between fields
            while ($i < $len && in_array($inner[$i], [' ', "\t", "\n", "\r", ','])) {
                $i++;
            }
            if ($i >= $len) break;

            // Read the field name (up to the =)
            $eqPos = strpos($inner, '=', $i);
            if ($eqPos === false) break;

            $fieldName = strtolower(trim(substr($inner, $i, $eqPos - $i)));
            $fieldName = str_replace(['-', ' '], '_', $fieldName); // normalize name
            $i = $eqPos + 1;

            // Skip spaces after the =
            while ($i < $len && in_array($inner[$i], [' ', "\t"])) $i++;

            if ($i >= $len) break;

            // Read value: can be {content}, "content", or a number
            $value = '';
            if ($inner[$i] === '{') {
                // Value between braces
                $depth = 0;
                $start = $i;
                for ($j = $i; $j < $len; $j++) {
                    if ($inner[$j] === '{') $depth++;
                    elseif ($inner[$j] === '}') {
                        $depth--;
                        if ($depth === 0) {
                            $value = substr($inner, $start + 1, $j - $start - 1);
                            $i = $j + 1;
                            break;
                        }
                    }
                }
            } elseif ($inner[$i] === '"') {
                // Value between quotes
                $i++;
                $start = $i;
                while ($i < $len && $inner[$i] !== '"') {
                    if ($inner[$i] === '\\') $i++; // escape
                    $i++;
                }
                $value = substr($inner, $start, $i - $start);
                $i++; // close quotes
            } else {
                // Number or @string reference
                $start = $i;
                while ($i < $len && !in_array($inner[$i], [',', "\n", "\r"])) $i++;
                $value = trim(substr($inner, $start, $i - $start));
            }

            if (!empty($fieldName)) {
                $fields[$fieldName] = trim($value);
            }
        }

        return $fields;
    }

    // -----------------------------------------------------------------
    // Normalization into a unified structure
    // -----------------------------------------------------------------

    private function normalize(array $f, string $type, string $key, string $src, string $raw): array
    {
        $e = [
            'source_type'    => $src,
            'source_key'     => $key,
            'document_type'  => $this->normalizeDocType($type, $f['type'] ?? ''),
            'title'          => $this->cleanBraces($f['title'] ?? ''),
            'abstract'       => $this->cleanBraces($f['abstract'] ?? ''),
            'year'           => (int) ($f['year'] ?? 0) ?: null,
            'language'       => $this->cleanBraces($f['language'] ?? 'English'),

            // Journal
            'journal'        => $this->cleanBraces(
                $f['journal'] ?? $f['booktitle'] ?? $f['source'] ?? ''
            ),
            'volume'         => $this->cleanBraces($f['volume'] ?? ''),
            'issue'          => $this->cleanBraces($f['number'] ?? $f['issue'] ?? ''),
            'pages'          => $this->cleanBraces($f['pages'] ?? ''),
            'article_number' => $this->cleanBraces($f['art_no'] ?? $f['article_number'] ?? ''),
            'issn'           => $this->normalizeISSN($f['issn'] ?? ''),
            'eissn'          => $this->normalizeISSN($f['eissn'] ?? ''),
            'isbn'           => $this->cleanBraces($f['isbn'] ?? ''),

            // Identifiers
            'doi'            => $this->normalizeDOI($f['doi'] ?? ''),
            'url'            => $this->cleanBraces($f['url'] ?? $f['link'] ?? ''),
            'eid'            => $this->cleanBraces($f['eid'] ?? ''),
            'wos_id'         => $this->extractWosId($f),
            'pubmed_id'      => $this->cleanBraces($f['pubmed_id'] ?? $f['pmid'] ?? ''),

            // Publisher
            'publisher'      => $this->cleanBraces($f['publisher'] ?? ''),

            // Metrics
            'cited_by'       => $this->extractCitedBy($f),
            'open_access'    => $this->detectOpenAccess($f),

            // Authors, keywords, and affiliations (arrays)
            'authors'        => $this->parseAuthors($f['author'] ?? ''),
            'keywords_author'=> $this->parseKeywordList($f['author_keywords'] ?? $f['keywords'] ?? ''),
            'keywords_plus'  => $this->parseKeywordList($f['keywords_plus'] ?? ''),
            'affiliations'   => $this->parseAffiliations($f, $src),

            'raw_bibtex'     => $raw,
        ];

        // Remove empty fields (keeps 0 and false)
        foreach ($e as $k => $v) {
            if ($v === '' || $v === [] || $v === null) {
                $e[$k] = null;
            }
        }

        return $e;
    }

    // -----------------------------------------------------------------
    // Normalization helpers
    // -----------------------------------------------------------------

    /** Remove nested braces and extra line breaks */
    private function cleanBraces(string $s): string
    {
        $s = preg_replace('/\{+([^{}]*)\}+/', '$1', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

    private function normalizeDocType(string $type, string $rawType): string
    {
        $map = [
            'article'         => 'Article',
            'review'          => 'Review',
            'inproceedings'   => 'Conference Paper',
            'proceedings'     => 'Conference Paper',
            'conference'      => 'Conference Paper',
            'book'            => 'Book',
            'incollection'    => 'Book Chapter',
            'phdthesis'       => 'Thesis',
            'mastersthesis'   => 'Thesis',
            'techreport'      => 'Report',
            'misc'            => 'Other',
        ];

        // Scopus/WoS may have type= inside the field
        if (!empty($rawType)) {
            $rt = strtolower(trim($rawType));
            foreach ($map as $k => $v) {
                if (str_contains($rt, $k)) return $v;
            }
            return ucfirst($rawType);
        }

        return $map[$type] ?? ucfirst($type);
    }

    private function normalizeDOI(string $doi): string
    {
        $doi = trim($doi);
        // Remove prefixes like https://doi.org/
        $doi = preg_replace('#^https?://(dx\.)?doi\.org/#i', '', $doi);
        return $doi;
    }

    private function normalizeISSN(string $issn): string
    {
        $issn = trim($issn);
        // Take the first ISSN if there are several separated by ; or ,
        $parts = preg_split('/[;,\s]+/', $issn);
        $first = trim($parts[0] ?? '');
        // Format as XXXX-XXXX
        $digits = preg_replace('/\D/', '', $first);
        if (strlen($digits) === 8) {
            return substr($digits, 0, 4) . '-' . substr($digits, 4);
        }
        return $first;
    }

    private function extractWosId(array $f): ?string
    {
        // WoS UT or Unique-ID
        $uid = $f['unique_id'] ?? $f['ut'] ?? $f['wos_id'] ?? '';
        $uid = $this->cleanBraces($uid);
        // Remove WOS: prefix
        $uid = preg_replace('/^WOS:/i', '', $uid);
        return $uid ?: null;
    }

    private function extractCitedBy(array $f): int
    {
        // WoS: Times-Cited
        if (isset($f['times_cited'])) {
            return (int) preg_replace('/\D/', '', $f['times_cited']);
        }
        // Scopus: note = {Cited by: N; ...}
        if (isset($f['note'])) {
            if (preg_match('/Cited by:\s*(\d+)/i', $f['note'], $m)) {
                return (int) $m[1];
            }
        }
        return 0;
    }

    private function detectOpenAccess(array $f): bool
    {
        $oa = strtolower($f['open_access'] ?? $f['oa'] ?? '');
        if (in_array($oa, ['true', '1', 'yes', 'gold', 'green', 'diamond'])) return true;

        // Scopus: publication_stage = Final + a few fields
        if (isset($f['note']) && str_contains(strtolower($f['note']), 'open access')) return true;

        return false;
    }

    // -----------------------------------------------------------------
    // Authors
    // -----------------------------------------------------------------

    /**
     * Parses the author field = {Lastname, Firstname and Lastname2, Firstname2 and ...}
     * Returns an array of strings with full names
     */
    private function parseAuthors(string $authorStr): array
    {
        if (empty($authorStr)) return [];

        $authorStr = $this->cleanBraces($authorStr);

        // Split by " and " (case-insensitive)
        $parts = preg_split('/\s+and\s+/i', $authorStr);
        $authors = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            // Format "Lastname, Firstname" → "Firstname Lastname"
            if (str_contains($part, ',')) {
                [$last, $first] = array_map('trim', explode(',', $part, 2));
                $full = trim("$first $last");
            } else {
                $full = $part;
            }

            // Remove double punctuation and extra spaces
            $full = preg_replace('/\s+/', ' ', $full);
            if (!empty($full)) {
                $authors[] = $full;
            }
        }

        return $authors;
    }

    // -----------------------------------------------------------------
    // Keywords
    // -----------------------------------------------------------------

    /**
     * Parses keyword lists separated by ; or ,
     */
    private function parseKeywordList(string $kwStr): array
    {
        if (empty($kwStr)) return [];

        $kwStr = $this->cleanBraces($kwStr);

        // Scopus uses ; WoS uses ;  but sometimes ,
        $parts = preg_split('/\s*[;]\s*/', $kwStr);
        if (count($parts) === 1) {
            // Try splitting by ,
            $parts = preg_split('/\s*,\s*/', $kwStr);
        }

        $keywords = [];
        foreach ($parts as $kw) {
            $kw = trim($kw, " \t\n\r\"'{}");
            if (strlen($kw) > 1) {
                $keywords[] = $kw;
            }
        }

        return array_unique($keywords);
    }

    // -----------------------------------------------------------------
    // Affiliations
    // -----------------------------------------------------------------

    private function parseAffiliations(array $f, string $src): array
    {
        $raw = '';

        if ($src === 'scopus') {
            $raw = $f['affiliations'] ?? '';
        } elseif ($src === 'wos') {
            $raw = $f['affiliation'] ?? '';
        } else {
            $raw = $f['affiliations'] ?? $f['affiliation'] ?? '';
        }

        $raw = $this->cleanBraces($raw);
        if (empty($raw)) return [];

        $affs = [];
        // Scopus: separated by ; between full affiliations
        $parts = preg_split('/;\s*(?=[A-Z])/u', $raw);
        if (count($parts) === 1) {
            $parts = explode(';', $raw);
        }

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            $country = $this->extractCountry($part);
            $affs[] = [
                'institution' => $part,
                'country'     => $country,
            ];
        }

        return $affs;
    }

    /**
     * Tries to extract the country from the affiliation text
     * (usually the last comma-separated element)
     */
    private function extractCountry(string $affiliation): string
    {
        $parts = array_map('trim', explode(',', $affiliation));
        $last  = end($parts);

        // Partial list of countries for basic validation
        $countries = [
            'Brazil', 'Brasil', 'United States', 'USA', 'United Kingdom', 'UK',
            'China', 'Germany', 'France', 'Italy', 'Spain', 'Portugal', 'Australia',
            'Canada', 'Japan', 'India', 'Netherlands', 'Sweden', 'Norway', 'Denmark',
            'Switzerland', 'Belgium', 'Austria', 'Poland', 'Russia', 'South Korea',
            'Korea', 'Taiwan', 'Singapore', 'Malaysia', 'Indonesia', 'Mexico',
            'Argentina', 'Colombia', 'Chile', 'South Africa', 'Turkey', 'Iran',
            'Saudi Arabia', 'Egypt', 'Israel', 'Greece', 'Czech Republic', 'Finland',
        ];

        foreach ($countries as $c) {
            if (stripos($last, $c) !== false) return $c;
        }

        // If the last fragment looks like a country (short, no digits)
        if (strlen($last) < 50 && !preg_match('/\d/', $last)) {
            return $last;
        }

        return '';
    }

    // -----------------------------------------------------------------
    // Getters
    // -----------------------------------------------------------------

    public function getEntries(): array { return $this->entries; }
    public function getLog(): array     { return $this->log; }
}
