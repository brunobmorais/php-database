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
    private array $dataModelArray = [];

    /**
     * @param array|null $params
     */
    public function __construct(array $params = null)
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
        return $this->{$name} ?? ($this->dataModelArray[$name]?? "");
    }

    /**
     * @param $key
     * @param $value
     * @return void
     */
    public function __set($key, $value): void
    {
        if (property_exists($this, $key)){
            $this->{$key} = $value;
        }
        $this->dataModelArray[$key] = $value;
    }

    /**
     * @param $key
     * @return bool
     */
    function __isset($key) {

        $result = isset($this->dataModelArray[$key]);
        if (property_exists($this, $key)){
            $result = isset($this->{$key});
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getDataModelArray():array {
        return $this->dataModelArray;
    }

    /**
     * @param array $params
     * @return void
     */
    public function fromMapToModel(array $params): void
    {
        $reflection = new \ReflectionClass($this);

        foreach ($params as $key => $item) {
            if ($reflection->hasProperty($key)) {
                $property = $reflection->getProperty($key);
                $property->setAccessible(true);
                $property->setValue($this, $item);
            }

            $this->dataModelArray[$key] = $item;

        }
    }

    /**
     * @param string $json
     * @return void
     */
    public function fromJsonToModel(string $json): void
    {
        $params = json_decode($json, true);
        $this->fromMapToModel($params);
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
                if(strlen($value) > 0)
                    $result[$key] = (is_array($value) || is_object($value)) ? $this->toMap($value) : $value;
            }

            return $result;
        }

        return null;
    }

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
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toMap(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $data = (object) $this->toMap();
        $re_2 = new ReflectionObject($data);
        $classname = get_class($this);
        if ($pos = strrpos($classname, '\\')) {
            $classname = substr($classname, $pos + 1);
        }

        return $classname . ' {' . implode(', ', array_map(
                function ($p_0) use ($data) {
                    $p_0->setAccessible(true);

                    return $p_0->getName() . ': ' . $p_0->getValue($data);
                },
                $re_2->getProperties()
            )) . '}';
    }

}

