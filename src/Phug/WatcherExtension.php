<?php

namespace Phug;

use Phug\Parser\Event\NodeEvent;
use Phug\Parser\Node\DocumentNode;
use Phug\Parser\Node\ElementNode;
use Phug\Parser\Node\TextNode;

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

    public function getTemplatesDirectories()
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

    public function browserReload($port = 8066, $maxPort = null)
    {
        $reloadServer = new BrowserReloadServer($port, $this->getTemplatesDirectories());

        return $reloadServer->listen($maxPort);
    }

    public function listen($file, $server = 8000, $browserReloadPort = 8066)
    {
        $reloadServer = new PhugDevServer($server, $file, $browserReloadPort);

        return $reloadServer->listen();
    }

    public function watch($interval = 1000000, $timeout = null, callable $callback = null)
    {
        $watcher = new Watcher();
        $templatesDirectories = $this->getTemplatesDirectories();

        $watcher->setChangeEventCallback(function ($event, $resource, $path) {
            if (file_exists($path)) {
                $sandbox = Phug::getRenderer()->getNewSandBox(function () use ($path) {
                    return Phug::cacheFile($path);
                });

                echo "Changes detected in $path\n";
                echo ' - '.($sandbox->getResult()
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

        return $watcher->watch($templatesDirectories, $interval, $timeout, $callback);
    }

    public function getDocumentEvents($browserReloadPort)
    {
        if (!$browserReloadPort) {
            return [];
        }

        return [function (NodeEvent $event) use ($browserReloadPort) {
            /** @var DocumentNode $document */
            $document = $event->getNode();
            foreach ($document->getChildren() as $child) {
                if ($child instanceof ElementNode && strtolower($child->getName()) === 'html') {
                    $document = $child;
                    break;
                }
            }
            foreach ($document->getChildren() as $child) {
                if ($child instanceof ElementNode && strtolower($child->getName()) === 'body') {
                    $document = $child;
                    break;
                }
            }
            $reloadScript = new ElementNode($document->getToken());
            $reloadScript->setName('script');
            $url = "//localhost:$browserReloadPort?directories=.";
            $code = new TextNode();
            $addScript = "var s = document.createElement('script');\n".
                "s.async = true;\n".
                "s.src = '$url';\n".
                "document.body.appendChild(s);\n";
            $code->setValue("window.onload = function () { $addScript };");
            $reloadScript->appendChild($code);
            $document->appendChild($reloadScript);
        }];
    }

    public function getOptions()
    {
        return [
            'macros'      => [
                'watch'         => [$this, 'watch'],
                'browserReload' => [$this, 'browserReload'],
                'listen'        => [$this, 'listen'],
            ],
            'commands'    => [
                'watch',
                'browserReload',
                'listen',
            ],
            'on_document' => $this->getDocumentEvents(intval(trim(getenv('BROWSER_RELOAD_PORT')))),
        ];
    }
}
