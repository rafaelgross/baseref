<?php
/**
 * BaseRef — config/ui.php
 * Interface options.
 *
 * GTRANSLATE_ENABLED: page translation widget from gtranslate.io (PT / EN / ES flags). OFF by default.
 * When ON, every page load fetches a script from cdn.gtranslate.net, and when a visitor picks a language
 * other than Portuguese the widget sends the text shown on the page (menus, labels and the record data on
 * screen, including titles and abstracts) to the external translation service. See INSTALL.md.
 * When OFF, nothing is loaded from gtranslate.io; a local selector still switches the language of the
 * PRISMA 2020 labels (flow diagram, dashboard counters and exports), which BaseRef translates itself.
 *
 * To turn it on, create config/ui.local.php (ignored by git, blocked from the web):
 *     <?php define('GTRANSLATE_ENABLED', true);
 * or set the environment variable GTRANSLATE_ENABLED=true (1, yes and on also work; anything else keeps it off).
 */

@include __DIR__ . '/ui.local.php';

if (!defined('GTRANSLATE_ENABLED')) {
    define('GTRANSLATE_ENABLED', filter_var(getenv('GTRANSLATE_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN));
}
