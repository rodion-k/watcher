<?php

namespace Phug\Test;

use PHPUnit\Framework\TestCase;

abstract class AbstractWatcherTestCase extends TestCase
{
    protected static function execInBackground($cmd)
    {
        if (substr(php_uname(), 0, 7) === 'Windows') {
            pclose(popen("start /B $cmd", 'r'));

            return;
        }

        exec("$cmd > /dev/null &");
    }

    protected static function emptyDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $file) {
            if ($file !== '.' && $file !== '..') {
                $path = $dir.'/'.$file;
                if (is_dir($path)) {
                    static::emptyDirectory($path);
                    rmdir($path);

                    continue;
                }

                unlink($path);
            }
        }
    }

    protected static function removeDirectory($dir)
    {
        static::emptyDirectory($dir);
        rmdir($dir);
    }

    protected static function addEmptyDirectory($dir)
    {
        if (file_exists($dir)) {
            is_dir($dir) ? static::emptyDirectory($dir) : unlink($dir);

            return;
        }

        mkdir($dir, 0777, true);
    }
}
