<?php
/**
 * PRISMA-SLR — config/ai.php
 * AI-assisted screening (optional).
 *
 * To enable it, create the file config/ai.local.php (ignored by git) with:
 *
 *   <?php
 *   define('AI_PROVIDER', 'gemini');           // 'gemini' (free) or 'anthropic'
 *   define('AI_API_KEY',  'AQ.Ab8...');        // API key
 *
 * Without a configured key, the feature stays disabled and the system works normally.
 */

@include __DIR__ . '/ai.local.php';

if (!defined('AI_API_KEY'))  define('AI_API_KEY',  '');
if (!defined('AI_PROVIDER')) define('AI_PROVIDER', 'groq');
// Default models per provider
if (!defined('AI_MODEL')) {
    $defaults = [
        'groq'      => 'openai/gpt-oss-20b',
        'gemini'    => 'gemini-2.0-flash',
        'anthropic' => 'claude-haiku-4-5',
    ];
    define('AI_MODEL', $defaults[AI_PROVIDER] ?? 'openai/gpt-oss-20b');
}
// Global daily limit of suggestions (0 = no limit). Adjust in ai.local.php.
if (!defined('AI_DAILY_LIMIT')) define('AI_DAILY_LIMIT', 100);

function aiEnabled(): bool
{
    return AI_API_KEY !== '';
}
