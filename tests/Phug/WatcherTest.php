<?php

namespace Phug\Test;

use Phug\Watcher;

/**
 * @coversDefaultClass \Phug\Watcher
 */
class WatcherTest extends AbstractWatcherTestCase
{
    /**
     * @covers ::__construct
     * @covers ::getWatcher
     */
    public function testGetWatcher()
    {
        $watcher = new Watcher();

        self::assertInstanceOf(\JasonLewis\ResourceWatcher\Watcher::class, $watcher->getWatcher());
    }

    /**
     * @covers ::watch
     * @covers ::getListeners
     */
    public function testWatch()
    {
        $watcher = new Watcher();

        self::assertNull($watcher->getListeners());
        self::assertFalse($watcher->watch([]));
    }
}
