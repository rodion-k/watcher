<?php

namespace Phug\Test;

use Phug\PhugDevServer;

/**
 * @coversDefaultClass \Phug\PhugDevServer
 */
class PhugDevServerTest extends AbstractWatcherTestCase
{
    /**
     * @covers ::<public>
     */
    public function testCreate()
    {
        $server = new PhugDevServer('localhost:9000', 'index.php', '3600');

        self::assertSame('index.php', $server->getFile());
        self::assertSame('localhost:9000', $server->getServer());
        self::assertSame(3600, $server->getBrowserReloadPort());

        $server = new PhugDevServer('7500', 'script', '3600');

        self::assertSame('script', $server->getFile());
        self::assertSame('localhost:7500', $server->getServer());
        self::assertSame(3600, $server->getBrowserReloadPort());
    }

    /**
     * @covers ::<public>
     */
    public function testCreateArgumentsSwap()
    {
        $server = new PhugDevServer('script.php', '3600', '3000');

        self::assertSame('script.php', $server->getFile());
        self::assertSame('localhost:3600', $server->getServer());
        self::assertSame(3000, $server->getBrowserReloadPort());
    }
}
