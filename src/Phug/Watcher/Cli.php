<?php

namespace Phug\Watcher;

use Phug\Watcher;

class Cli
{
    public function run(array $cliArguments)
    {
        $watcher = new Watcher();

        $arguments = [];
        $count = count($cliArguments);
        $options = [];

        for ($i = 0; $i < $count; $i++) {
            $argument = $cliArguments[$i];

            if ($argument === '--exit-on-change') {
                $watcher->setChangeEventCallback(function () {
                    exit(0);
                });

                continue;
            }

            if (substr($argument, 0, 20) === '--execute-on-change=') {
                $options['file'] = substr($argument, 20);

                continue;
            }

            if ($argument === '--execute-on-change') {
                if (isset($cliArguments[$i + 1])) {
                    $options['file'] = $cliArguments[$i + 1];
                }

                continue;
            }

            $arguments[] = $argument;
        }

        if (isset($options['file'])) {
            $file = $options['file'];
            $watcher->setChangeEventCallback(function () use ($file) {
                include $file;
            });
        }

        return $watcher->watch($arguments);
    }
}
