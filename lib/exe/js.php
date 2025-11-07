<?php

/**
 * DokuWiki JavaScript creator
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */

if (!defined('DOKU_INC')) define('DOKU_INC', __DIR__ . '/../../');
if (!defined('NL')) define('NL', "\n");
if (!defined('DOKU_DISABLE_GZIP_OUTPUT')) define('DOKU_DISABLE_GZIP_OUTPUT', 1); // we gzip ourself here
require_once(DOKU_INC . 'inc/init.php');
init_request(noSession: true); // we do not use a session or authentication here (better caching)
require_once(DOKU_INC . 'inc/js.php');

// Main (don't run when UNIT test)
if (!defined('SIMPLE_TEST')) {
    header('Content-Type: application/javascript; charset=utf-8');
    js_out();
}
