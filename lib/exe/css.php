<?php

/**
 * DokuWiki StyleSheet creator
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */

if (!defined('DOKU_INC')) define('DOKU_INC', __DIR__ . '/../../');
if (!defined('NOSESSION')) define('NOSESSION', true); // we do not use a session or authentication here (better caching)
if (!defined('DOKU_DISABLE_GZIP_OUTPUT')) define('DOKU_DISABLE_GZIP_OUTPUT', 1); // we gzip ourself here
if (!defined('NL')) define('NL', "\n");
require_once(DOKU_INC . 'inc/init.php');
require_once(DOKU_INC . 'inc/css.php');

// Main (don't run when UNIT test)
if (!defined('SIMPLE_TEST')) {
    header('Content-Type: text/css; charset=utf-8');
    css_out();
}
