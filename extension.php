<?php

/*
 * (c) Aleksey Orlov <i.trancer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MarkdownFinetune
{
    use Bolt\BaseExtension;
    use MarkdownFinetune\Listener\ControllerFilterListener;

    class Extension extends BaseExtension
    {
        function info()
        {
            return array(
                'name' => "Markdown Finetune",
                'description' => "An extension provides controls for Markdown field type",
                'keywords' => "bolt, extension, markdown",
                'author' => "Aleksey Orlov",
                'link' => "https://github.com/axsy/bolt-extension-markdown-finetune",
                'version' => "0.1",
                'required_bolt_version' => "1.0.2",
                'highest_bolt_version' => "1.1",
                'type' => "General",
                'first_releasedate' => "2013-05-27",
                'latest_releasedate' => "2013-05-27",
                'dependencies' => "",
                'priority' => 10
            );
        }

        function initialize()
        {
            $listener = new ControllerFilterListener($this->app['paths']['apppath'] . '/cache', $this->app['debug']);

            $this->app['dispatcher']->addListener('kernel.controller', array($listener, 'onKernelController'));
        }
    }
}

namespace MarkdownFinetune\Listener
{
    use MarkdownFinetune\Config\Configuration;
    use MarkdownFinetune\Config\ConfigurationReader;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

    class ControllerFilterListener
    {
        protected $appPath;
        protected $isDebug;

        public function __construct($appPath, $isDebug)
        {
            $this->appPath = $appPath;
            $this->isDebug = $isDebug;
        }

        public function onKernelController(FilterControllerEvent $event)
        {
            $request = $event->getRequest();
            if ('markdownify' == $request->get('_route')) {
                $event->setController(function() use ($request) {
                    $html = $request->get('html');
                    if (isHtml($html)) {
                        $configReader = new ConfigurationReader($this->appPath, $this->isDebug);
                        $config = $configReader->read(new Configuration(), dirname(__FILE__) . '/config.yml');

                        if ($config['markdownifier']['enabled']) {
                            require_once __DIR__ . '/../../classes/markdownify/markdownify_extra.php';
                            $md = new \Markdownify(false, $config['markdownifier']['body_width'], false);
                            $output = $md->parseString($html);
                        } else {
                            $output = $html;
                        }
                    } else {
                        $output = $html;
                    }

                    return $output;
                });
            }
        }
    }
}

namespace MarkdownFinetune\Config
{
    use Symfony\Component\Config\ConfigCache;
    use Symfony\Component\Config\Definition\Builder\TreeBuilder;
    use Symfony\Component\Config\Definition\ConfigurationInterface;
    use Symfony\Component\Config\Definition\Processor;
    use Symfony\Component\Config\Resource\FileResource;
    use Symfony\Component\Yaml\Yaml;

    class Configuration implements ConfigurationInterface
    {
        public function getConfigTreeBuilder()
        {
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root('markdown');

            $rootNode
                ->children()
                    ->arrayNode('markdownifier')
                        ->children()
                            ->booleanNode('enabled')
                                ->defaultValue(true)
                            ->end()
                            ->scalarNode('body_width')
                                ->defaultValue(80)
                                ->beforeNormalization()
                                ->always()
                                    ->then(function($v) { return 0 !== $v ? (int)$v : false; })
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ;

            return $treeBuilder;
        }
    }

    class ConfigurationReader
    {
        protected $cachePath;
        protected $debug;

        public function __construct($cachePath, $debug)
        {
            $this->cachePath = $cachePath;
            $this->debug = (bool)$debug;
        }

        public function read(ConfigurationInterface $configuration, $configPath)
        {
            $cacheFile = $this->cachePath . '/extensions/' . pathinfo(dirname(__FILE__), PATHINFO_FILENAME) . '_config.php';
            $cache = new ConfigCache($cacheFile, $this->debug);

            if (!$cache->isFresh()) {
                $processor = new Processor();
                $config = $processor->processConfiguration($configuration, Yaml::parse($configPath));

                $code = sprintf('<?php return unserialize(\'%s\');', serialize($config));
                $cache->write($code, array(new FileResource($configPath)));
            }

            return require_once $cacheFile;
        }
    }
}