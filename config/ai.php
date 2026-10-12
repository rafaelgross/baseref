<?php
/**
 * PRISMA-SLR — config/ai.php
 * Triagem assistida por IA (opcional).
 *
 * Para ativar, crie o arquivo config/ai.local.php (ignorado pelo git) com:
 *
 *   <?php
 *   define('AI_PROVIDER', 'gemini');           // 'gemini' (gratuito) ou 'anthropic'
 *   define('AI_API_KEY',  'AQ.Ab8...');        // chave da API
 *
 * Sem chave configurada, o recurso fica desativado e o sistema funciona normalmente.
 */

@include __DIR__ . '/ai.local.php';

// Permite configuração via variáveis de ambiente quando não definidas em ai.local.php
if (!defined('AI_API_KEY') && getenv('AI_API_KEY')) define('AI_API_KEY', getenv('AI_API_KEY'));
if (!defined('AI_PROVIDER') && getenv('AI_PROVIDER')) define('AI_PROVIDER', getenv('AI_PROVIDER'));
if (!defined('AI_MODEL') && getenv('AI_MODEL')) define('AI_MODEL', getenv('AI_MODEL'));
if (!defined('AI_DAILY_LIMIT') && getenv('AI_DAILY_LIMIT') !== false) define('AI_DAILY_LIMIT', (int) getenv('AI_DAILY_LIMIT'));

if (!defined('AI_API_KEY'))  define('AI_API_KEY',  '');
if (!defined('AI_PROVIDER')) define('AI_PROVIDER', 'groq');
// Modelos padrão por provedor
if (!defined('AI_MODEL')) {
    $defaults = [
        'groq'      => 'openai/gpt-oss-20b',
        'gemini'    => 'gemini-2.0-flash',
        'anthropic' => 'claude-haiku-4-5',
    ];
    define('AI_MODEL', $defaults[AI_PROVIDER] ?? 'openai/gpt-oss-20b');
}
// Limite diário global de sugestões (0 = sem limite). Ajuste em ai.local.php.
if (!defined('AI_DAILY_LIMIT')) define('AI_DAILY_LIMIT', 100);

function aiEnabled(): bool
{
    return AI_API_KEY !== '';
}
