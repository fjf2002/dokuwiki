<?php

use dokuwiki\Manifest;

if (!defined('DOKU_INC')) {
    define('DOKU_INC', __DIR__ . '/../../');
}
require_once(DOKU_INC . 'inc/init.php');
init_request(noSession: true); // no session or auth required here

if (!actionOK('manifest')) {
    http_status(404, 'Manifest has been disabled in DokuWiki configuration.');
    exit();
}

$manifest = new Manifest();
$manifest->sendManifest();
