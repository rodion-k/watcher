<?php

namespace Phug;

$baseDir = __DIR__.'/..';

for($i = 0; $i < 5 && !file_exists($baseDir.'/vendor/autoload.php'); $i++) {
    $baseDir .= '/..';
}

require $baseDir.'/vendor/autoload.php';

$watcher = new Watcher();

$watcher->setChangeEventCallback(function () {
    echo 'window.reload();';

    exit(0);
});

$watcher->watch((array) $_GET['directories']);

exit(1);
