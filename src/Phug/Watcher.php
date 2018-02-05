<?php

namespace Phug;

use Illuminate\Filesystem\Filesystem;
use JasonLewis\ResourceWatcher\Event as JLEvent;
use JasonLewis\ResourceWatcher\Tracker;
use JasonLewis\ResourceWatcher\Watcher as JLWatcher;

class Watcher
{
    /**
     * @var JLWatcher watcher resource.
     */
    private $watcher;

    /**
     * Watcher constructor. Create a new watcher with tracker and filesystem.
     */
    public function __construct()
    {
        $this->watcher = new JLWatcher(
            new Tracker(),
            new Filesystem()
        );
    }

    /**
     * Watch directories and returns true if watcher is running.
     *
     * @param $directories
     *
     * @return bool
     */
    public function watch($directories)
    {
        if (empty($directories) && !count($directories)) {
            return false;
        }

        $this->listeners = array_map(function ($directory) {
            $listener = $this->watcher->watch($directory);
            $listener->onAnything(function (JLEvent $event, $resource, $path) {
                switch ($event->getCode()) {
                    case JLEvent::RESOURCE_DELETED:
                        echo "$path was deleted.".PHP_EOL;
                        break;
                    case JLEvent::RESOURCE_MODIFIED:
                        echo "$path was modified.".PHP_EOL;
                        break;
                    case JLEvent::RESOURCE_CREATED:
                        echo "$path was created.".PHP_EOL;
                        break;
                }
            });

            return $listener;
        }, $directories);

        $this->watcher->start();

        return $this->watcher->isWatching();
    }
}
