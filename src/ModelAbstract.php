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

abstract class ModelAbstract
{
    /**
     * @param array|null $params
     */
    public function __construct(array $params = null)
    {
        if (!empty($params))
            $this->fromMapToModel($params);
    }

    /**
     * @param array $params
     * @return void
     */
    public function fromMapToModel(array $params): void
    {
        foreach ($params as $key => $item)
        {
            $this->{$key} = $item;
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
     * @return array|null
     */
    public function toMap($objArray = null): ?array
    {
        $data = $objArray??$this;
        if (is_array($data) || is_object($data))
        {
            $result = [];
            foreach ($data as $key => $value)
            {
                $result[$key] = (is_array($value) || is_object($value)) ? $this->toMap($value) : $value;
            }
            return $result;
        }
        return null;
    }

    /**
     * @return string
     */
    public function toJson():string
    {
        return json_encode($this->toMap(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return string
     */
    public function toString():string
    {
        $data = (object) $this->toMap();
        $re_2 = new ReflectionObject($data);
        $classname = get_class($this);
        if ($pos = strrpos($classname, '\\')) $classname = substr($classname, $pos + 1);
        return $classname .' {' . implode(', ', array_map(
                function($p_0) use ($data)
                {
                    $p_0->setAccessible(true);
                    return $p_0->getName() .': '. $p_0->getValue($data);
                }, $re_2->getProperties())) .'}';

    }

    /**
     * @param 
     * @return string
     */
    public function __get($attribute) {
        return $this->{$attribute}??"";
    }

    /**
     * @param $attribute
     * @param $value
     * @return void
     */
    public function __set($attribute, $value): void {
        $this->{$attribute} = $value;
    }
}