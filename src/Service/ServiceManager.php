<?php

/** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace App\Service;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;

class ServiceManager implements ContainerInterface
{
    protected array $config = [];
    protected array $container = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge(
            [
                'env' => 'prod', // prod | dev
                'shared' => [
                    LoggerInterface::class,
                ],
                LoggerInterface::class => static function () {
                    return (new \Monolog\Logger('app'))
                        ->pushHandler(new \Monolog\Handler\StreamHandler(STDERR));
                },
            ],
            require __DIR__ . '/../../config.php',
            $config
        );
        $this->container = array_flip($this->config['shared']);
        foreach ($this->container as $key => $value) {
            $this->container[$key] = null;
        }
    }

    public function get($id)
    {
        if (array_key_exists($id, $this->config)) {
            if (!is_callable($this->config[$id])) {
                return $this->config[$id];
            }
            if (isset($this->container[$id])) {
                return $this->container[$id];
            }
            if (array_key_exists($id, $this->container)) {
                $this->container[$id] = call_user_func($this->config[$id]);
                return $this->container[$id];
            }
            return call_user_func($this->config[$id]);
        }

        if (class_exists($id)) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $class = new ReflectionClass($id);
            $args = [];
            if ($constructor = $class->getConstructor()) {
                $params = $constructor->getParameters();
                foreach ($params as $param) {
                    if ($param->isOptional()) {
                        /** @noinspection PhpUnhandledExceptionInspection */
                        $args[] = $param->getDefaultValue();
                    } else {
                        $paramClass = $param->getClass();
                        if (!$paramClass) {
                            throw new ServiceNotFoundException("Can't resolve param '{$param->getName()}' for $id");
                        }
                        $args[] = $this->get($paramClass->getName());
                    }
                }
            }
            return $class->newInstanceArgs($args);
        }

        throw new ServiceNotFoundException("$id not found");
    }

    public function has($id): bool
    {
        return array_key_exists($id, $this->config) || class_exists($id);
    }
}
