<?php

namespace BMorais\Database;

/**
 * CLASS CRUD
 * Basic class to make connection between the database and the application
 *
 * @author Bruno Morais <brunomoraisti@gmail.com>
 * @copyright MIT, bmorais.com
 * @package bmorais\database
 * @subpackage class
 * @access private
 */

use ReflectionObject;

#[\AllowDynamicProperties]
abstract class ModelAbstract
{
    /** @var array<string, \ReflectionClass> */
    private static array $reflectionCache = [];

    /** @var array<string, \ReflectionProperty[]> */
    private static array $propertiesCache = [];

    private function getReflection(): \ReflectionClass
    {
        $class = static::class;
        if (!isset(self::$reflectionCache[$class])) {
            self::$reflectionCache[$class] = new \ReflectionClass($this);
        }
        return self::$reflectionCache[$class];
    }

    /**
     * Retorna apenas propriedades de instância (não-estáticas), filtrando propriedades internas.
     * @return \ReflectionProperty[]
     */
    private function getInstanceProperties(): array
    {
        return self::$propertiesCache[static::class] ??= array_filter(
            $this->getReflection()->getProperties(),
            fn(\ReflectionProperty $prop) => !$prop->isStatic()
        );
    }

    /**
     * @param array|null $params
     */
    public function __construct(?array $params = null)
    {
        if (!empty($params)) {
            $this->fromMapToModel($params);
        }
    }

    /**
     * @param $name
     */
    public function __get($name)
    {
        return property_exists($this, $name) ? $this->{$name} : null;
    }

    /**
     * @param $key
     * @param $value
     * @return void
     */
    public function __set($key, $value): void
    {
        if (property_exists($this, $key)) {
            $this->{$key} = $value;
        }
    }

    /**
     * @param $key
     * @return bool
     */
    function __isset($key) {

        return property_exists($this, $key) && isset($this->{$key});

    }

    /**
     * @param array $params
     * @return $this
     */
    public function fromMapToModel(array $params)
    {
        foreach ($this->getInstanceProperties() as $property) {
            $name = $property->getName();
            if (array_key_exists($name, $params)) {
                $property->setAccessible(true);
                $property->setValue($this, $params[$name]);
            }
        }

        return $this;
    }

    /**
     * @param string $json
     * @return $this
     */
    public function fromJsonToModel(string $json)
    {
        $params = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        return $this->fromMapToModel($params);

    }

    /**
     * @param $objArray
     * @return array|null
     */
    public function toMap($objArray = null): ?array
    {
        $data = $objArray ?? $this;
        if (is_array($data) || is_object($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = (is_array($value) || is_object($value)) ? $this->toMap($value) : $value;
            }

            return $result;
        }

        return null;
    }

    /**
     * @return \stdClass
     */
    public function toObject(): \stdClass{
        $objeto = new \stdClass;

        foreach ($this->getInstanceProperties() as $prop) {
            $objeto->{$prop->getName()} = $this->readProperty($prop);
        }

        return $objeto;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = [];
        foreach ($this->getInstanceProperties() as $prop) {
            $array[$prop->getName()] = $this->readProperty($prop);
        }
        return $array;
    }

    /**
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $classname = $this->getReflection()->getShortName();
        $properties = array_map(
            fn($prop) => "{$prop->getName()}: '" . $this->stringifyValue($this->readProperty($prop, false)) . "'",
            $this->getInstanceProperties()
        );
        return "$classname {" . implode(', ', $properties) . '}';
    }

    /**
     * Lê a propriedade (via getter, se existir). Propriedade tipada ainda não inicializada
     * vira null em vez de lançar "must not be accessed before initialization".
     * @param \ReflectionProperty $prop
     * @param bool $useGetter
     * @return mixed
     */
    private function readProperty(\ReflectionProperty $prop, bool $useGetter = true): mixed
    {
        $prop->setAccessible(true);
        $method = 'get' . ucfirst($prop->getName());

        if ($useGetter && method_exists($this, $method)) {
            try {
                return $this->{$method}();
            } catch (\Error $e) {
                if ($prop->isInitialized($this) || !str_contains($e->getMessage(), 'before initialization')) {
                    throw $e;
                }
                return null;
            }
        }

        return $prop->isInitialized($this) ? $prop->getValue($this) : null;
    }

    /**
     * Converte para texto como antes ((string) do valor, "Array" para arrays);
     * objetos sem __toString, que antes lançavam Error, viram JSON.
     * @param mixed $value
     * @return string
     */
    private function stringifyValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_array($value)) {
            return 'Array';
        }
        if (is_object($value) && !$value instanceof \Stringable) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: get_class($value);
        }
        return (string) $value;
    }

}