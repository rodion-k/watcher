<?php

namespace Phug;

class PhugDevServer
{
    /**
     * @var string|int
     */
    private $server;

    /**
     * @var string
     */
    private $file;

    /**
     * @var int
     */
    private $browserReloadPort;

    public function __construct($server, $file, $browserReloadPort)
    {
        $this->server = $server;
        $this->file = $file;
        $this->browserReloadPort = $browserReloadPort;
    }

    public function listen()
    {
        $browserReloadPort = $this->browserReloadPort;
        $serverPort = intval($this->server);
        if ($serverPort) {
            $server = "localhost:$serverPort";
        }
        $php = PHP_BINARY;
        $file = escapeshellarg($this->file);
        $watcher = realpath(file_exists('vendor/bin/watcher') ? 'vendor/bin/watcher' : 'watcher');
        if (!$watcher) {
            throw new \RuntimeException('No watcher program found in the vendor/bin directory.');
        }

        // @codeCoverageIgnoreStart
        if (strtolower(substr(php_uname(), 0, 3)) === 'win') {
            pclose(popen("start $php $watcher --browser-reload=$browserReloadPort > watcher-output.log 2> watcher-error.log", 'r'));
            echo shell_exec("set BROWSER_RELOAD_PORT=$browserReloadPort && $php -S $server $file");

            return true;
        }

        shell_exec("$php $watcher --browser-reload=$browserReloadPort > watcher-output.log 2> watcher-error.log &");
        echo shell_exec("BROWSER_RELOAD_PORT=$browserReloadPort $php -S $server $file");

        return true;
        // @codeCoverageIgnoreEnd
    }
}
