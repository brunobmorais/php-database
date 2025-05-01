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
     * @return void
     */
    public function fromMapToModel(array $params)
    {
        $reflection = new \ReflectionClass($this);

        foreach ($params as $key => $item) {
            if ($reflection->hasProperty($key)) {
                $property = $reflection->getProperty($key);
                $property->setAccessible(true);
                $property->setValue($this, $item);
            }
        }

        return $this;
    }

    /**
     * @param string $json
     * @return void
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
        $reflection = new \ReflectionClass($this);
        $objeto = new \stdClass;

        foreach ($reflection->getProperties() as $prop) {
            $prop->setAccessible(true);

            $method = 'get' . $prop->getName();

            if (method_exists($this, $method)) {
                $objeto->{$prop->getName()} = call_user_func([$this, $method]);
            } else {
                $objeto->{$prop->getName()} = $prop->getValue($this);
            }
        }

        return $objeto;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = [];
        foreach ((new \ReflectionClass($this))->getProperties() as $prop) {
            $prop->setAccessible(true);
            $method = 'get' . ucfirst($prop->getName());

            $array[$prop->getName()] = method_exists($this, $method)
                ? $this->{$method}()
                : $prop->getValue($this);
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
        $classname = (new \ReflectionClass($this))->getShortName();
        $properties = array_map(
            fn($prop) => "{$prop->getName()}: '" . ($prop->getValue($this) ?? '') . "'",
            (new \ReflectionClass($this))->getProperties()
        );
        return "$classname {" . implode(', ', $properties) . '}';
    }

}