<?php
/**
 * BaseRef — Classificação de motivos de exclusão para o diagrama PRISMA 2020
 *
 * No modelo oficial PRISMA 2020, um texto completo que não pôde ser obtido NÃO é
 * uma exclusão após leitura: ele sai na caixa "Reports not retrieved", entre
 * "Reports sought for retrieval" e "Reports assessed for eligibility".
 *
 * A marcação fica na coluna exclusion_reasons.not_retrieved:
 *   1    → o motivo conta como "não recuperado"
 *   0    → o motivo é uma exclusão comum após leitura
 *   NULL → automático: reconhece pelo texto do motivo (ver notRetrievedPattern)
 * Assim projetos antigos funcionam sem alterar nenhum registro, e qualquer motivo
 * personalizado pode ser marcado explicitamente.
 */

/** Cria a coluna de marcação, se ainda não existir. Não altera dados existentes. */
function ensureReasonFlagColumn(PDO $pdo): void
{
    static $ensured = false;
    if ($ensured) return;
    $has = $pdo->query("SHOW COLUMNS FROM exclusion_reasons LIKE 'not_retrieved'")->fetchColumn();
    if (!$has) {
        $pdo->exec("ALTER TABLE exclusion_reasons
            ADD COLUMN not_retrieved TINYINT(1) NULL DEFAULT NULL
            COMMENT 'PRISMA 2020: 1 = conta como Reports not retrieved; NULL = detectar pelo texto'");
    }
    $ensured = true;
}

/** Expressão que reconhece motivos de texto completo indisponível (PT, EN, ES). */
function notRetrievedPattern(): string
{
    return '/texto (completo|integral) (indispon|n[aã]o (dispon|encontrad|obtid|recuperad|acess))'
         . '|n[aã]o (foi )?(poss[ií]vel )?(recupera[rd]|obter|acessar o texto)'
         . '|sem acesso ao texto|acesso ao texto (completo )?(indispon|negado)'
         . '|full[- ]?text (not |un)(available|retrieved|obtainable|accessible)'
         . '|(report|full[- ]?text)s? not retrieved|could not (be )?(retrieve|obtain|access)'
         . '|texto completo no disponible|no se (pudo|ha podido) (recuperar|obtener)/iu';
}

/** Decide se um motivo conta como "não recuperado" (marcação explícita tem prioridade). */
function isNotRetrievedReason(?string $reason, $flag): bool
{
    if ($flag !== null && $flag !== '') return (int) $flag === 1;
    return $reason !== null && preg_match(notRetrievedPattern(), $reason) === 1;
}
