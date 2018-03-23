<?php

namespace Phug\Watcher;

use Phug\BrowserReloadServer;
use Phug\PhugDevServer;
use Phug\Watcher;

class Cli
{
    const VALUE_OPTION = 1;
    const FLAG_OPTION = 2;

    private $optionsMap = [
        'init'              => true,
        'exit-on-change'    => true,
        'browser-reload'    => 'browser-reload',
        'execute-on-change' => 'execute-on-change',
        'listen'            => 'listen',
    ];

    /**
     * Add an available option to the CLI.
     *
     * @param string $optionName
     * @param int    $type       option type (default Phug\Watcher\Cli::VALUE_OPTION)
     *
     * @return $this
     */
    public function addOption($optionName, $type = null)
    {
        $type = $type ?: static::VALUE_OPTION;
        $this->optionsMap[$optionName] = $type === static::FLAG_OPTION ? true : $optionName;

        return $this;
    }

    /**
     * Remove an option by name.
     *
     * @return $this
     */
    public function removeOption($optionName)
    {
        unset($this->optionsMap[$optionName]);

        return $this;
    }

    /**
     * Take CLI arguments and returns true if the command succeed.
     *
     * @param array $cliArguments
     *
     * @return bool
     */
    public function run(array $cliArguments, $interval = 1000000, $timeout = null, callable $callback = null)
    {
        $watcher = new Watcher();

        $arguments = [];
        $count = count($cliArguments);
        $options = [];

        for ($i = 0; $i < $count; $i++) {
            $argument = $cliArguments[$i];

            // @codeCoverageIgnoreStart
            if ($argument === '--exit-on-change') {
                $watcher->setChangeEventCallback(function () {
                    exit(0);
                });

                continue;
            }
            // @codeCoverageIgnoreEnd

            foreach ($this->optionsMap as $optionName => $value) {
                if ($value === true) {
                    if ($argument === "--$optionName") {
                        $options[$optionName] = true;

                        continue 2;
                    }

                    continue;
                }

                $length = strlen($optionName) + 3;
                if (substr($argument, 0, $length) === "--$optionName=") {
                    $options[$value] = substr($argument, $length);

                    continue 2;
                }

                if ($argument === "--$optionName") {
                    $options[$value] = isset($cliArguments[++$i]) ? $cliArguments[$i] : true;
                    if (is_string($options[$value]) && substr($options[$value], 0, 2) === '--') {
                        $i--;
                        $options[$value] = true;
                    }

                    continue 2;
                }
            }

            $arguments[] = $argument;
        }

        if (isset($options['browser-reload'])) {
            $reloadServer = new BrowserReloadServer(intval($options['browser-reload']), $arguments);

            return $reloadServer->listen();
        }

        if (isset($options['listen'])) {
            $devServer = new PhugDevServer($options['listen'], $arguments[1], isset($arguments[2]) ? $arguments[2] : 8066); //

            return $devServer->listen();
        }

        if (isset($options['init'])) {
            $source = __DIR__.'/../../../phugBootstrap.php';
            $destination = 'phugBootstrap.php';
            if (file_exists($destination)) {
                echo "$destination already exists in ".getcwd()."\n".
                    "Please add manually in it the following code:\n".
                    trim(preg_replace('/^<\?(php)?/', '', file_get_contents($source)));

                return false;
            }

            $success = copy($source, $destination);
            $message = $success ? "$destination initialized" : "Unable to write $destination";

            echo "$message in ".getcwd()."\n";

            return $success;
        }

        if (isset($options['execute-on-change'])) {
            $file = $options['execute-on-change'];
            $watcher->setChangeEventCallback(function ($event, $resource, $path) use ($file, $options) {
                include $file;
            });
        }

        return $watcher->watch($arguments, $interval, $timeout, $callback);
    }
}
