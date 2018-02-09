<?php

namespace Phug\Test;

use Phug\Phug;
use Phug\WatcherExtension;

/**
 * @coversDefaultClass \Phug\WatcherExtension
 */
class WatcherExtensionTest extends AbstractWatcherTestCase
{
    /**
     * @throws \Phug\PhugException
     */
    public function setUp()
    {
        Phug::addExtension(WatcherExtension::class);
    }

    public function tearDown()
    {
        Phug::removeExtension(WatcherExtension::class);
    }

    /**
     * @covers ::getOption
     * @covers ::getTemplatesDirectories
     */
    public function testGetTemplatesDirectories()
    {
        Phug::setOption('paths', [__DIR__]);

        $extension = new WatcherExtension();

        self::assertSame([__DIR__], $extension->getTemplatesDirectories());

        $directory = realpath(__DIR__.'/..');
        Phug::setOption('base_dir', $directory);

        self::assertSame([__DIR__, $directory], $extension->getTemplatesDirectories());
    }

    /**
     * @covers ::getOptions
     */
    public function testGetOptions()
    {
        $extension = new WatcherExtension();

        self::assertSame(['watch'], $extension->getOptions()['commands']);
        self::assertSame(['watch'], array_keys($extension->getOptions()['macros']));
    }

    /**
     * @covers \Phug\Watcher::setChangeEventCallback
     * @covers \Phug\Watcher::watch
     * @covers ::watch
     */
    public function testWatch()
    {
        Phug::reset();

        $extension = new WatcherExtension();
        ob_start();
        $watch = $extension->watch();
        $contents = ob_get_clean();

        self::assertFalse($watch);
        self::assertSame('Directories to watch must exist before to be watched, none given.', trim($contents));

        $id = mt_rand(0, 999999);
        $cacheDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pug-cache'.$id;
        $baseDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pug-base'.$id;
        static::addEmptyDirectory($cacheDir);
        static::addEmptyDirectory($baseDir);
        $view = $baseDir.DIRECTORY_SEPARATOR.'view.pug';
        Phug::reset();
        Phug::setOption('cache_dir', $cacheDir);
        Phug::setOption('base_dir', $baseDir);
        clearstatcache();
        $extension = new WatcherExtension();
        static::execInBackground('php '.
            escapeshellarg(realpath(__DIR__.'/../createFileOneSecondLater.php')).' '.
            escapeshellarg($view));
        ob_start();
        $watch = $extension->watch(1000000, 3 * 1000000);
        $contents = ob_get_clean();
        $cachedFiles = glob($cacheDir.'/*.php');
        $cachedFile = count($cachedFiles) ? file_get_contents($cachedFiles[0]) : '';
        static::removeDirectory($cacheDir);
        static::removeDirectory($baseDir);

        self::assertFalse($watch);
        self::assertContains('Start watching', $contents);
        self::assertContains('pug-base'.$id, $contents);
        self::assertContains('view.pug', $contents);
        self::assertContains('template cached successfully', $contents);
        self::assertContains('Hello world!', $cachedFile);
    }
}
