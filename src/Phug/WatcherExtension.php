<?php

namespace Phug;

class WatcherExtension extends AbstractExtension
{
    protected function getOption($name, $default = null)
    {
        $options = Phug::getOptions();

        return (isset($options[$name])
            ? $options[$name]
            : (Phug::hasOption($name) ? Phug::getOption($name) : null)
        ) ?: $default;
    }

    protected function getTemplatesDirectories()
    {
        $paths = $this->getOption('paths', []);
        foreach (['base_dir', 'basedir'] as $option) {
            $path = $this->getOption($option);
            if ($path && !in_array($path, $paths)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    public function watch()
    {
        $watcher = new Watcher();
        $templatesDirectories = $this->getTemplatesDirectories();

        $watcher->setChangeEventCallback(function ($event, $resource, $path) {
            if (file_exists($path)) {
                echo "Changes detected in $path\n";
                echo ' - '.(Phug::cacheFile($path)
                        ? 'template cached successfully'
                        : 'cache failure'
                    )."\n";
            }
        });

        $templatesDirectories = array_filter($templatesDirectories, 'file_exists');

        if (empty($templatesDirectories)) {
            echo "Directories to watch must exist before to be watched, none given.\n";

            return false;
        }

        echo "Start watching\n - ".implode("\n - ", $templatesDirectories)."\n";

        return $watcher->watch($templatesDirectories);
    }

    public function getOptions()
    {
        return [
            'macros' => [
                'watch' => [$this, 'watch'],
            ],
            'commands' => [
                'watch',
            ],
        ];
    }
}
