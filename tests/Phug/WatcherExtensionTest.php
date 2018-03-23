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

        self::assertSame(['watch', 'browserReload', 'listen'], $extension->getOptions()['commands']);
        self::assertSame(['watch', 'browserReload', 'listen'], array_keys($extension->getOptions()['macros']));
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

    /**
     * @covers ::browserReload
     * @covers \Phug\BrowserReloadServer::<public>
     */
    public function testBrowserReloadCommand()
    {
        $extension = new WatcherExtension();

        ob_start();
        $reload = $extension->browserReload(1, 1);
        $message = trim(ob_get_clean());

        self::assertFalse($reload);
        self::assertSame('No port available above the minimal one you asked.', $message);

        ob_start();
        $reload = $extension->browserReload('localhost:12', 1);
        $message = trim(ob_get_clean());

        self::assertFalse($reload);
        self::assertSame('No port available above the minimal one you asked.', $message);
    }

    /**
     * @covers ::listen
     * @covers \Phug\PhugDevServer::<public>
     */
    public function testListenCommand()
    {
        $extension = new WatcherExtension();
        $id = mt_rand(0, 999999);
        $baseDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pug-base'.$id;
        static::addEmptyDirectory($baseDir);
        $cwd = getcwd();
        chdir($baseDir);

        try {
            $extension->listen('index.php');
        } catch (\RuntimeException $exception) {
            $message = $exception->getMessage();
        }

        static::removeDirectory($baseDir);
        chdir($cwd);

        self::assertSame('No watcher program found in the vendor/bin directory.', $message);
    }

    /**
     * @covers ::getDocumentEvents
     */
    public function testBrowserReloadScriptAppend()
    {
        Phug::reset();
        $extension = new WatcherExtension();

        self::assertSame([], $extension->getDocumentEvents(0));
        $events = $extension->getDocumentEvents(9876);
        self::assertCount(1, $events);
        Phug::setOption('on_document', $events);
        $html = Phug::render("html\n  body\n    h1 Title");
        Phug::reset();

        self::assertContains('<script', $html);
        self::assertContains('//localhost:9876', $html);
    }
}
