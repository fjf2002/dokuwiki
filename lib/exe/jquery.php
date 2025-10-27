<?php

if (!defined('DOKU_INC')) define('DOKU_INC', __DIR__ . '/../../');
if (!defined('NOSESSION')) define('NOSESSION', true); // we do not use a session or authentication here (better caching)
if (!defined('NL')) define('NL', "\n");
if (!defined('DOKU_DISABLE_GZIP_OUTPUT')) define('DOKU_DISABLE_GZIP_OUTPUT', 1); // we gzip ourself here
require_once(DOKU_INC . 'inc/init.php');
require_once(DOKU_INC . 'inc/jquery.php');

// MAIN
doku_header('Content-Type: application/javascript; charset=utf-8');
jquery_out();
