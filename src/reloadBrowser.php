<?php

namespace Phug;

// @codeCoverageIgnoreStart
if (isset($_GET['directories'])) {
    set_time_limit(-1);

    $baseDir = __DIR__.'/..';

    for ($i = 0; $i < 5 && !file_exists($baseDir.'/vendor/autoload.php'); $i++) {
        $baseDir .= '/..';
    }

    require $baseDir.'/vendor/autoload.php';

    $watcher = new Watcher();

    $watcher->setChangeEventCallback(function () {
        header('Content-type: text/javascript');
        echo 'location.reload()';

        exit(0);
    });

    $watcher->watch((array) $_GET['directories']);

    exit(1);
}
// @codeCoverageIgnoreEnd
