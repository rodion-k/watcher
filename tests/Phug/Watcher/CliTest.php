<?php

namespace Phug\Test\Watcher;

use Phug\Test\AbstractWatcherTestCase;
use Phug\Watcher\Cli;

/**
 * @coversDefaultClass \Phug\Watcher\Cli
 */
class CliTest extends AbstractWatcherTestCase
{
    /**
     * @covers ::run
     * @covers \Phug\Watcher::logEventChange
     * @covers \Phug\Watcher::watch
     */
    public function testRun()
    {
        $id = mt_rand(0, 999999);
        $baseDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pug-base'.$id;
        static::addEmptyDirectory($baseDir);
        $view = $baseDir.DIRECTORY_SEPARATOR.'view.pug';

        $cli = new Cli();

        static::execInBackground('php '.
            escapeshellarg(realpath(__DIR__.'/../../createFileOneSecondLater.php')).' '.
            escapeshellarg($view));
        static::execInBackground('php '.
            escapeshellarg(realpath(__DIR__.'/../../editFileTwoSecondLater.php')).' '.
            escapeshellarg($view));
        static::execInBackground('php '.
            escapeshellarg(realpath(__DIR__.'/../../deleteFileThreeSecondLater.php')).' '.
            escapeshellarg($view));
        ob_start();
        $cli->run([$baseDir], 1000000, 10 * 1000000);
        $events = explode("\n", trim(ob_get_clean()));
        static::removeDirectory($baseDir);

        self::assertCount(3, $events);
        self::assertContains('view.pug was created.', $events[0]);
        self::assertContains('view.pug was modified.', $events[1]);
        self::assertContains('view.pug was deleted.', $events[2]);
    }

    /**
     * @covers ::addOption
     * @covers ::removeOption
     * @covers ::run
     */
    public function testExecuteOnChange()
    {
        $id = mt_rand(0, 999999);
        $baseDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pug-base'.$id;
        static::addEmptyDirectory($baseDir);
        $view = $baseDir.DIRECTORY_SEPARATOR.'view.pug';

        $cli = new Cli();
        $cli
            ->addOption('foo', Cli::FLAG_OPTION)
            ->addOption('bar', Cli::FLAG_OPTION)
            ->removeOption('foo')
            ->addOption('biz', Cli::VALUE_OPTION)
        ;

        static::execInBackground('php '.
            escapeshellarg(realpath(__DIR__.'/../../createFileOneSecondLater.php')).' '.
            escapeshellarg($view));
        ob_start();
        $cli->run([
            '--execute-on-change='.realpath(__DIR__.'/../../callback.php'),
            '--bar',
            $baseDir,
            '--biz',
            'bizValue',
        ], 1000000, 3 * 1000000);
        ob_end_clean();
        static::removeDirectory($baseDir);

        self::assertTrue(isset($GLOBALS['options']));
        $options = $GLOBALS['options'];
        self::assertFalse(isset($options['foo']));
        self::assertSame(true, $options['bar']);
        self::assertSame('bizValue', $options['biz']);
    }

    /**
     * @covers ::addOption
     * @covers ::removeOption
     * @covers ::run
     */
    public function testInitCommand()
    {
        $id = mt_rand(0, 999999);
        $baseDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pug-base'.$id;
        static::addEmptyDirectory($baseDir);
        chdir($baseDir);
        $cli = new Cli();

        ob_start();
        $cli->run(['--init'], 1000000, 3 * 1000000);
        $firstContents = ob_get_clean();

        ob_start();
        $cli->run(['--init'], 1000000, 3 * 1000000);
        $secondContents = ob_get_clean();

        static::removeDirectory($baseDir);

        self::assertContains('phugBootstrap.php initialized in ', $firstContents);
        self::assertContains('pug-base'.$id, $firstContents);
        self::assertContains('phugBootstrap.php already exists in ', $secondContents);
    }
}
