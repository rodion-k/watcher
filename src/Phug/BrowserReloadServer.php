<?php

namespace Phug;

class BrowserReloadServer
{
    /**
     * @var string|int
     */
    private $port;

    /**
     * @var array
     */
    private $directories;

    public function __construct($port, array $directories)
    {
        $this->port = $port;
        $this->directories = $directories;
    }

    public function listen()
    {
        $port = $this->port;
        $host = 'localhost';
        if (is_string($port)) {
            list($host, $port) = explode(':', $port.':');
        }
        $port = intval($port);
        if ($port < 80 || $port > 65535) {
            $port = 80;
        }
        while ($port < 65535) {
            echo "Browser reloading listening on http://$host:$port\n";
            echo 'Important note: you should ensure this host/port pair'.
                " cannot be reachable by non-authorized people over your network.\n";
            shell_exec(PHP_BINARY." -S $host:$port ".
                escapeshellarg(realpath(__DIR__.'/../reloadBrowser.php')).
                ' 2>&1'
            );
            echo "The port $port seems busy, trying an other one...\n";
            $port++;
        }

        echo "No port available above the minimal one you asked.\n";

        return false;
    }
}
