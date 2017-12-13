<?php
namespace yii\helpers;

use Yii;
use yii\base\Arrayable;
use yii\helpers\BaseArrayHelper;

class ArrayHelper extends BaseArrayHelper
{
    /**
     * конвертим массив в postgres jsonb строку
     *
     * @param array $value
     * @return string
     */
    public static function postgresStyleJsonEncode($value)
    {
            settype($value, 'array');
            array_walk($value, function(&$item, $key) {
                    $item = sprintf('"%s": "%s"', $key, $item);
            });
            return '{' . implode(', ', $value) . '}';
    }
    
    /**
     * конвертим массив в строку дял бд
     * для конвертации массива в postgres
     * 
     * @param array $set
     * @return string
     */
    public static function toPostgresArray($set) 
    {
        settype($set, 'array'); // can be called with a scalar or array
        $result = array();
        foreach ($set as $t) {
                $t = str_replace('"', '\\"', $t); // escape double quote
                if (! is_numeric($t)) // quote only non-numeric values
                    $t = '"' . $t . '"';
                $result[] = $t;
        }

        return '{' . implode(",", $result) . '}'; // format
    }
    
    /**
     * конвертим стркоу в массив php
     * для конвертации данных из postgres
     * 
     * @param string $string - строка из постгреса {"a","b"}
     * @return array
     */
    public static function toPhpArray($string) 
    {
        $result = [];
        $items = (str_getcsv(trim($string, '[2:2]={}')));
        if(! $items){
            return $result;
        }
        foreach ($items as $key => $string){
            $result[] = trim($string, '{\"}');
        }
 
        return $result;
    }

    /**
     * @inheritdoc
     */
    public static function toArray($object, $properties = [], $recursive = true, $expands = [])
    {
        if (is_array($object)) {
            if ($recursive) {
                foreach ($object as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        if(is_int($key)){
                            $expand = $expands;
                        }elseif (isset ($expands[$key])) {
                            $expand = $expands[$key];
                        }  else {
                            $expand = [];
                        }
                        $object[$key] = static::toArray($value, $properties, true, $expand);
                    }
                }
            }

            return $object;
        } elseif (is_object($object)) {
            if (!empty($properties)) {
                $className = get_class($object);
                if (!empty($properties[$className])) {
                    $result = [];
                    foreach ($properties[$className] as $key => $name) {
                        if (is_int($key)) {
                            $result[$name] = $object->$name;
                        } else {
                            $result[$key] = static::getValue($object, $name);
                        }
                    }

                    return $recursive ? static::toArray($result, $properties) : $result;
                }
            }
            if ($object instanceof Arrayable) {
                $result = $object->toArray([], $expands, $recursive);
            } else {
                $result = [];
                foreach ($object as $key => $value) {
                    $result[$key] = $value;
                }
            }

            return $recursive ? static::toArray($result, [], true, $expands) : $result;
        } else {
            return [$object];
        }
    }
    
    /**
     * Возвращает массив элементов имеющих пустые значения
     * 
     * @param array $array
     * @return array
     */
    public static function getEmptyValues($array)
    {
        if(! is_array($array)){
            return [];
        }
        return array_filter($array , function($v){
            if(is_array($v)){
                return self::getEmptyValues($v);
            }
            if(! $v){
                return true;
            }
            return false;
        });
    }
}