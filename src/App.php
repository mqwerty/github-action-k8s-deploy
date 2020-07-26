<?php

namespace App;

use Mqwerty\DI\Container;

final class App
{
    private Container $container;

    /**
     * @suppress PhanMissingRequireFile
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $configLocal = file_exists('./config.php') ? require './config.php' : [];
        $config = array_merge($configLocal, $config);
        $this->container = new Container($config);
    }

    public function run(): void
    {
        if (getenv('RR_HTTP') === 'true') {
            (new Router($this->container))->handle();
        }
    }

    /**
     * @noinspection PhpFullyQualifiedNameUsageInspection
     * @SuppressWarnings(PHPMD.MissingImport)
     * @param mixed $val
     */
    public static function dump($val): void
    {
        if (class_exists(\Spiral\Debug\Dumper::class)) {
            $dumper = new \Spiral\Debug\Dumper();
            $dumper->setRenderer(\Spiral\Debug\Dumper::ERROR_LOG, new \Spiral\Debug\Renderer\ConsoleRenderer());
            $dumper->dump($val, \Spiral\Debug\Dumper::ERROR_LOG);
        }
    }
}
