<?php
declare(strict_types=1);

namespace Nish\Container;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionException;

class Container implements ContainerInterface
{
    /** @var array */
    protected $components = [];
    /** @var array */
    protected $factories = [];

    protected function __construct(){}

    /**
     * @param string $id component name
     * @return mixed
     * @throws ContainerExceptionInterface component initialize error
     * @throws NotFoundExceptionInterface component not found
     */
    public function get($id)
    {
        if (isset($this->components[$id]))
            return $this->components[$id];

        if (isset($this->factories[$id])){
            try {
                $f = $this->factories[$id];
                return $f($this);
            }catch (\Throwable $e){
                throw new class($id . ' factory error.', 0, $e)
                    extends \Exception
                    implements ContainerExceptionInterface{};
            }
        }

        if (class_exists($id))
            return $this->buildByReflection($id);

        throw new class($id . ' not found')
            extends Exception
            implements NotFoundExceptionInterface{};
    }

    /**
     * @param string $id
     */
    public function has($id)
    {
        return isset($this->components[$id]) || isset($this->factories[$id]);
    }

    public function set(string $id, $component): self
    {
        $this->components[$id] = $component;
        return $this;
    }

    public function setFactory(string $id, callable $factory): self
    {
        $this->factories[$id] = $factory;
        return $this;
    }

    public function clear(): self
    {
        $this->components = [];
        $this->factories = [];
        return $this;
    }


    public static function getInstance(): self
    {
        static $instance;
        if ($instance === null)
            $instance = new static();
        return $instance;
    }

    /**
     * alias of get
     * @return mixed
     */
    public static function gets(string $id)
    {
        return static::getInstance()->get($id);
    }

    // alias
    /**
     * @return mixed|null
     */
    public static function mayget($id)
    {
        $container = static::getInstance();
        if ($container->has($id))
            return $container->get($id);
        else
            return null;
    }

    //
    //
    // Auto Wiring
    //
    //
    /**
     * @param string|object $class class name or object
     * @param string $name method name
     * @param array $callParams additional parameters
     */
    public function call($class, string $name, array $callParams = [])
    {
        try {
            $refMethod = new ReflectionMethod($class, $name);
        }catch (ReflectionException $e){
            throw new Exception('auto parameter setting error', 0, $e);
        }

        $params = $this->getMethodParams($refMethod, $class, $callParams);
        /** @var callable */
        $method = [$class, $name];
        return $method(...$params);
    }

    protected function buildByReflection($name)
    {
        if (!class_exists($name))
            throw new Exception("class name [$name] not found");

        try {
            $refClass = new ReflectionClass($name);
            $refMethod = $refClass->getConstructor();
            if ($refMethod !== null)
                $params = $this->getMethodParams($refMethod, $name);
            else
                $params = array();
        }catch (ReflectionException $e){
            throw new Exception("build error", 0, $e);
        }

        $obj = $refClass->newInstanceArgs($params);
        $this->set($name, $obj);
        return $obj;
    }

    protected function getMethodParams($refMethod, $class, $callParams = [])
    {
        if (is_string($class))
            $className = $class;
        else
            $className = get_class($class);
        $params = array();
        $refParams = $refMethod->getParameters();
        foreach ($refParams as $refParam){
            $paramName = $refParam->getName();
            if (isset($callParams[$paramName]))
                $params[] = $callParams[$paramName];
            else
                $params[] = $this->getParam($refParam, $className);
        }
        return $params;
    }

    protected function getParam($refParam, $className)
    {

        $methodName = $refParam->getDeclaringFunction()->getName();
        $paramName = $refParam->getName();
        $key = "$className#$methodName.$paramName";
        if ($this->has($key))
            return $this->get($key);

        $refClass = null;
        $refType = $refParam->getType();
        if ($refType instanceof \ReflectionNamedType && !$refType->isBuiltin()){
            $class = $refType->getName();
            if ($class){
                // not use class_exists
                // getClass maybe return interface
                $refClass = new \ReflectionClass($refType->getName());
            }
        }

        if (!$refClass){
            if (!$refParam->isOptional())
                throw new Exception('can not parse ' .
                                    $className . '#' . $methodName);

            return $refParam->getDefaultValue();
        }

        $name = $refClass->getName();

        try {
            return $this->get($name);
        }catch (Exception $e){
            if (!$refParam->isOptional())
                throw $e;

            return $refParam->getDefaultValue();
        }
    }
}
