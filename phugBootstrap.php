<?php

$templatesDirectories = [
    // Replace with your templates directories locations (could be one or more directories)
    'views',
    'templates',
];

// Replace with the directory you want to store in cached templates
$cacheDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'phug-cache';

if (!file_exists($cacheDirectory) && !@mkdir($cacheDirectory, 0777, true)) {
    throw new \RuntimeException(
        "$cacheDirectory cache directory could not be created."
    );
}

\Phug\Phug::addExtension(\Phug\WatcherExtension::class);

\Phug\Phug::setOptions([
    'cache_dir' => $cacheDirectory,
    'paths'     => $templatesDirectories,
]);
