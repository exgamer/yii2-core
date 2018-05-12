<?php
namespace yii\helpers;

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
    public static function toPostgresArray($array) 
    {
        settype($array, 'array'); // can be called with a scalar or array
        $result = [];
        foreach ($array as $item) {
                $r = str_replace('"', '\\"', $item); // escape double quote
                if (! is_numeric($r)) { // quote only non-numeric values
                    $r = sprintf('"%s"', $r);
                }
                $result[] = $r;
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
        return ArrayParser::parse($string);
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
    
    /**
     * проверка на ассоциативный массив
     * @param array $arr
     * @return type
     */
    public static function isAssoc($arr)
    {
            return array_keys($arr) !== range(0, count($arr) - 1);
    }
        
    /**
     * Возвращает значения массива через разделитель
     * 
     * @param array $array
     * @param string $delimiter
     */
    public static function getValuesAsString($array, $delimiter = ', ')
    {
        if(! $array) {
            return;
        }
        
        return implode($delimiter, $array);
    }
    
    /**
     * Преобразование строки массива из простгресса в массив php 
     * 
     * @param string $data
     * @param integer $start
     * @return array
     */
    protected static function _postgresqlArrayDecode($data, $start = 0)
    {
        if (empty($data) || $data[0] != '{') {
            return null;
        }
        $result = [];
        $string = false;
        $quote = '';
        $len = strlen($data);
        $v = '';
        for ($i = $start + 1; $i < $len; $i++) {
            $ch = $data[$i];
            if (!$string && $ch == '}') {
                if ($v !== '' || !empty($result)) {
                    $result[] = $v;
                }
                break;
            } else if (!$string && $ch == '{') {
                $v = self::_postgresqlArrayDecode($data, $i);
            } else if (!$string && $ch == ',') {
                $result[] = $v;
                $v = '';
            } else if (!$string && ($ch == '"' || $ch == "'")) {
                $string = true;
                $quote = $ch;
            } else if ($string && $ch == $quote && $data[$i - 1] == "\\") {
                $v = substr($v, 0, -1) . $ch;
            } else if ($string && $ch == $quote && $data[$i - 1] != "\\") {
                $string = false;
            } else {
                $v .= $ch;
            }
        }
        return $result;
    }
}

/**
* The class converts PostgreSQL array representation to PHP array
*
* @author Sergei Tigrov <rrr-r@ya.ru>
* @author Dmytro Naumenko <d.naumenko.a@gmail.com>
* @since 2.0.14
*/
class ArrayParser
{
   /**
    * @var string Character used in array
    */
   private static $delimiter = ',';
   
   /**
    * Convert array from PostgreSQL to PHP
    *
    * @param string $value string to be converted
    * @return array|null
    */
   public static function parse($value)
   {
       if ($value === null) {
           return null;
       }
       if ($value === '{}') {
           return [];
       }
       
       return self::parseArray($value);
   }
   
   /**
    * Pares PgSQL array encoded in string
    *
    * @param string $value
    * @param int $i parse starting position
    * @return array
    */
   private static function parseArray($value, &$i = 0)
   {
       $result = [];
       $len = strlen($value);
       for (++$i; $i < $len; ++$i) {
           switch ($value[$i]) {
               case '{':
                   $result[] = self::parseArray($value, $i);
                   break;
               case '}':
                   break 2;
               case self::$delimiter:
                   if (empty($result)) { // `{}` case
                       $result[] = null;
                   }
                   if (in_array($value[$i + 1], [self::$delimiter, '}'], true)) { // `{,}` case
                       $result[] = null;
                   }
                   break;
               default:
                   $result[] = self::parseString($value, $i);
           }
       }
       
       return $result;
   }
   
   /**
    * Parses PgSQL encoded string
    *
    * @param string $value
    * @param int $i parse starting position
    * @return null|string
    */
   private static function parseString($value, &$i)
   {
       $isQuoted = $value[$i] === '"';
       $stringEndChars = $isQuoted ? ['"'] : [self::$delimiter, '}'];
       $result = '';
       $len = strlen($value);
       for ($i += $isQuoted ? 1 : 0; $i < $len; ++$i) {
           if (in_array($value[$i], ['\\', '"'], true) && in_array($value[$i + 1], [$value[$i], '"'], true)) {
               ++$i;
           } elseif (in_array($value[$i], $stringEndChars, true)) {
               break;
           }
           $result .= $value[$i];
       }
       $i -= $isQuoted ? 0 : 1;
       if (!$isQuoted && $result === 'NULL') {
           $result = null;
       }
       
       return $result;
   }
}