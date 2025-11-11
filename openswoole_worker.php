<?php

require_once(__DIR__ . '/worker-env.php');
require_once(__DIR__ . '/inc/workers/OpenswooleWorker.php');

new OpenswooleWorker()->run();
