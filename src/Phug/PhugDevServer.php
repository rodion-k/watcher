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
        if (ctype_digit(strval($file)) && !file_exists($file)) {
            $__server = $server;
            $server = $file;
            $file = $__server;
        }

        $this->server = $server;
        $this->file = $file;
        $this->browserReloadPort = intval($browserReloadPort);
    }

    /**
     * @return int|string
     */
    public function getServer()
    {
        $serverPort = intval($this->server);
        if ($serverPort) {
            return "localhost:$serverPort";
        }

        return $this->server;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return int
     */
    public function getBrowserReloadPort()
    {
        return $this->browserReloadPort;
    }

    /**
     * Start browser-reload and dev-server listening.
     *
     * @return bool
     */
    public function listen()
    {
        $browserReloadPort = $this->browserReloadPort;
        $serverPort = intval($this->server);
        if ($serverPort) {
            $server = "localhost:$serverPort";
        }
        $php = PHP_BINARY;
        $file = escapeshellarg($this->file);
        $watcher = realpath(file_exists('vendor/phug/watcher/watcher') ? 'vendor/phug/watcher/watcher' : 'watcher');
        if (!$watcher) {
            throw new \RuntimeException('No watcher program found in the vendor/bin directory.');
        }

        // @codeCoverageIgnoreStart
        if (strtolower(substr(php_uname(), 0, 3)) === 'win') {
            echo "Opening new window for browser-reload watching on port $browserReloadPort (or superior if not available).\n";
            pclose(popen("start $php $watcher --browser-reload=$browserReloadPort ".
                "> watcher-output.log 2> watcher-error.log", 'r'));
            echo "Listening $file on $server.\n";
            echo shell_exec("set BROWSER_RELOAD_PORT=$browserReloadPort && $php -S $server $file");

            return true;
        }

        echo "Browser-reload watching listening on port $browserReloadPort (or superior if not available).\n";
        shell_exec("$php $watcher --browser-reload=$browserReloadPort ".
            "> watcher-output.log 2> watcher-error.log &");
        echo "Listening $file on $server.\n";
        echo shell_exec("BROWSER_RELOAD_PORT=$browserReloadPort $php -S $server $file");

        return true;
        // @codeCoverageIgnoreEnd
    }
}
