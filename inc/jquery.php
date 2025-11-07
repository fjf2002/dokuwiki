<?php

use dokuwiki\Cache\Cache;

/**
 * Delivers the jQuery JavaScript
 *
 * We do absolutely nothing fancy here but concatenating the different files
 * and handling conditional and gzipped requests
 *
 * uses cache or fills it
 */
function jquery_out()
{
    $cache = new Cache('jquery', '.js');
    $files = [
        DOKU_INC . 'lib/scripts/jquery/jquery.min.js',
        DOKU_INC . 'lib/scripts/jquery/jquery-ui.min.js'
    ];
    $cache_files = $files;
    $cache_files[] = __FILE__;

    // check cache age & handle conditional request
    // This may exit if a cache can be used
    $cache_ok = $cache->useCache(['files' => $cache_files]);
    http_cached($cache->cache, $cache_ok);

    $js = '';
    foreach ($files as $file) {
        $js .= file_get_contents($file) . "\n";
    }
    stripsourcemaps($js);

    http_cached_finish($cache->cache, $js);
}
