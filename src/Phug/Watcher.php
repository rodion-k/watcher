<?php

namespace Phug;

use Illuminate\Filesystem\Filesystem;
use JasonLewis\ResourceWatcher\Event as JLEvent;
use JasonLewis\ResourceWatcher\Resource\FileResource;
use JasonLewis\ResourceWatcher\Tracker;
use JasonLewis\ResourceWatcher\Watcher as JLWatcher;

class Watcher
{
    /**
     * @var JLWatcher watcher resource.
     */
    private $watcher;

    /**
     * @var array list of last listeners created.
     */
    private $listeners;

    /**
     * @var callable change event callback.
     */
    private $changeEventCallback;

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
     * Set the change event callback.
     *
     * @param callable $changeEventCallback
     */
    public function setChangeEventCallback(callable $changeEventCallback)
    {
        $this->changeEventCallback = $changeEventCallback;
    }

    /**
     * Log event change to standard output.
     *
     * @param JLEvent $event
     * @param string  $resource
     * @param string  $path
     */
    public function logEventChange(JLEvent $event, FileResource $resource, $path)
    {
        $resourcePath = $path ?: $resource->getPath();
        switch ($event->getCode()) {
            case JLEvent::RESOURCE_DELETED:
                echo "$resourcePath was deleted.".PHP_EOL;
                break;
            case JLEvent::RESOURCE_MODIFIED:
                echo "$resourcePath was modified.".PHP_EOL;
                break;
            case JLEvent::RESOURCE_CREATED:
                echo "$resourcePath was created.".PHP_EOL;
                break;
        }
    }

    /**
     * Get the current listeners.
     *
     * @return array
     */
    public function getListeners()
    {
        return $this->listeners;
    }

    /**
     * Get the current watcher.
     *
     * @return JLWatcher
     */
    public function getWatcher()
    {
        return $this->watcher;
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

        $changeEventCallback = $this->changeEventCallback;
        $this->listeners = array_map(function ($directory) use ($changeEventCallback) {
            $listener = $this->watcher->watch($directory);
            $listener->onAnything(function (JLEvent $event, $resource, $path) use ($changeEventCallback) {
                $changeEventCallback
                    ? call_user_func($changeEventCallback, $event, $resource, $path)
                    : $this->logEventChange($event, $resource, $path);
            });

            return $listener;
        }, $directories);

        $this->watcher->start();

        return $this->watcher->isWatching();
    }
}
