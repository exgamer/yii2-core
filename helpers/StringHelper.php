<?php
namespace core\helpers;

use core\helpers\ConstHelper;
use core\helpers\lib\TXFile;

/**
 * Вспомогательный класс для работы со строками
 * 
 * @author Kamaelkz
 */
abstract class StringHelper
{
    const SQL_COMMAND_DELIMETER = ';';
    
    static $lang2tr = array(
                    // russian
                    'й'=>'y','ц'=>'ch',
                    'у'=>'u','к'=>'k',
                    'е'=>'e','н'=>'n',
                    'г'=>'g','ш'=>'sh',
                    'щ'=>'shsh','з'=>'z',
                    'х'=>'h','ъ'=>'',
                    'ф'=>'f','ы'=>'y',
                    'в'=>'v','а'=>'a',
                    'п'=>'p','р'=>'r',
                    'о'=>'o','л'=>'l',
                    'д'=>'d','ж'=>'zh',
                    'э'=>'e','я'=>'ya',
                    'ч'=>'ch','с'=>'s',
                    'м'=>'m','и'=>'i',
                    'т'=>'t','ь'=>'',
                    'б'=>'b','ю'=>'yu',
                    'ё'=>'e','и'=>'i',

                    'Й'=>'Y','Ц'=>'CH',
                    'У'=>'U','К'=>'K',
                    'Е'=>'E','Н'=>'N',
                    'Г'=>'G','Ш'=>'SH',
                    'Щ'=>'SHSH','З'=>'Z',
                    'Х'=>'H','Ъ'=>'',
                    'Ф'=>'F','Ы'=>'Y',
                    'В'=>'V','А'=>'A',
                    'П'=>'P','Р'=>'R',
                    'О'=>'O','Л'=>'L',
                    'Д'=>'D','Ж'=>'ZH',
                    'Э'=>'E','Я'=>'YA',
                    'Ч'=>'CH','С'=>'S',
                    'М'=>'M','И'=>'I',
                    'Т'=>'T','Ь'=>'',
                    'Б'=>'B','Ю'=>'YU',
                    'Ё'=>'E','И'=>'I',
                    // czech
                    'á'=>'a', 'ä'=>'a', 'ć'=>'c', 'č'=>'c', 'ď'=>'d', 'é'=>'e', 'ě'=>'e',
                    'ë'=>'e', 'í'=>'i', 'ň'=>'n', 'ń'=>'n', 'ó'=>'o', 'ö'=>'o', 'ŕ'=>'r',
                    'ř'=>'r', 'š'=>'s', 'Š'=>'S', 'ť'=>'t', 'ú'=>'u', 'ů'=>'u', 'ü'=>'u',
                    'ý'=>'y', 'ź'=>'z', 'ž'=>'z',
                    'і'=>'i', 'ї' => 'i', 'b' => 'b', 'І' => 'i',
                    // special
                    ' '=>'-', '_' => '-' ,
                    '\''=>'', '"'=>'',
                    '\t'=>'', '«'=>'',
                    '»'=>'', '?'=>'',
                    '!'=>'', '*'=>'',
                    '+'=>'plus' , '№' => 'number',
                    '`'=> '' , '?' => ''
    );

    /**
     * Транслит строки
     * 
     * @param string $string
     * @return string
     */
    public static function translit($string)
    {
        $result = preg_replace( '/[\-]+/', '-', preg_replace( '/[^\w\-\*]/', '', strtolower( strtr( trim($string), self::$lang2tr ) ) ) );
        $result = self::utf8Normilize($result);

        return $result;
    }

    /*
     * Телефон в цифровой вид
     * 
     * @param string $phone
     * @return string
     */
    public static function getPhoneAsNumber($phone) 
    {
        $phone =preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone)>10){
                $phone = substr($phone,1);
        }
        return $phone;
    }

    /*
     *  Обратное преобразование цифр в телефон    
     */
    public static function getFormatPhone($phone = '', $convert = false, $trim = true)
    {
        // If we have not entered a phone number just return empty
        if (empty($phone)) {
            return '';
        }
        // Strip out any extra characters that we do not need only keep letters and numbers
        $phone = preg_replace("/[^0-9A-Za-z]/", "", $phone);

        // Do we want to convert phone numbers with letters to their number equivalent?
        // Samples are: 1-800-TERMINIX, 1-800-FLOWERS, 1-800-Petmeds
        if ($convert == true) {
            $replace = array('2'=>array('a','b','c'),
                     '3'=>array('d','e','f'),
                         '4'=>array('g','h','i'),
                     '5'=>array('j','k','l'),
                                     '6'=>array('m','n','o'),
                     '7'=>array('p','q','r','s'),
                     '8'=>array('t','u','v'), '9'=>array('w','x','y','z'));

            // Replace each letter with a number
            // Notice this is case insensitive with the str_ireplace instead of str_replace 
            foreach($replace as $digit=>$letters) {
                $phone = str_ireplace($letters, $digit, $phone);
            }
        }
        // If we have a number longer than 11 digits cut the string down to only 11
        // This is also only ran if we want to limit only to 11 characters
        if ($trim == true && strlen($phone)>11) {
            $phone = substr($phone,  0, 11);
        }
        // Perform phone number formatting here
        if (strlen($phone) == 7) {
            return preg_replace("/([0-9a-zA-Z]{3})([0-9a-zA-Z]{4})/", "$1-$2", $phone);
        } elseif (strlen($phone) == 10) {
            return preg_replace("/([0-9a-zA-Z]{3})([0-9a-zA-Z]{3})([0-9a-zA-Z]{2})([0-9a-zA-Z]{2})/", "($1) $2-$3-$4", $phone);
        } elseif (strlen($phone) == 11) {
            return preg_replace("/([0-9a-zA-Z]{1})([0-9a-zA-Z]{3})([0-9a-zA-Z]{3})([0-9a-zA-Z]{4})/", "$1($2) $3-$4", $phone);
        }

        // Return original phone if not 7, 10 or 11 digits long
        
        return $phone;
    }

    /**
     * Закрывает телефон маской
     * 
     * @param string $phone
     * @return string
     */
    public static function getFormatPhoneMask($phone)
    {
        $phone = preg_replace("/[^0-9A-Za-z]/", "", $phone);
        if (strlen($phone)==11) {
            $phone = substr($phone,  1, 11);
        }

        return preg_replace("/([0-9a-zA-Z]{3})([0-9a-zA-Z]{3})([0-9a-zA-Z]{2})([0-9a-zA-Z]{2})/", "($1) XXX-XX-XX", $phone);
    }    
    
    /**
     * Нормализация кодировки UTF-8
     * 
     * @param string $string
     * @return string
     */
    public static function utf8Normilize($string)
    {
        $result =  mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        #частный случай убирает знак вопроса после конвертации
        $result = str_replace('?', '', $result);
        
        return $result;
    }
       
    /**
     * Возвращает название класса без пространства имен
     * 
     * @param string $name
     * @return string
     */
    public static function getClassNameWithoutNamespace($name)
    {
        return substr(strrchr($name, "\\"), 1); 
    }
    
        
    /**
     * Идентифицирует тип телефона
     * 
     * @param string $phone
     * @return string
     */
    public static function identifyPhoneType($phone)
    {
            $tmp_lenght = preg_replace("/[^0-9]+/", "", $phone);
            if (strlen($tmp_lenght) <= 6) {
                return ConstHelper::PERSON_CONTACT_PHONE_HOME;
            }else{
                return ConstHelper::PERSON_CONTACT_PHONE_MOBILE;
            }
    }
    
    /**
     * разбиваем строку по заглавным буквам
     * @param type $string
     */
    public static function splitStringByBigSymbol($string)
    {
        return preg_split('/(?<=[a-z])(?=[A-Z])/u',$string);
    }
    
    public static function executeFile($filePath , $log = true) 
    {
        if (! isset($filePath)) {
                return false;
        }
        if($log) {
            $this->_infoLine ( $filePath );
        }
        $time = microtime ( true );
        $pdo = new \PDO;
        $file = new TXFile (['path' => $filePath]);
        if (! $file->exists){
                throw new \Exception ( "'$filePath' is not a file" );
        }
        try {
                if ($file->open ( TXFile::READ ) === false){
                        throw new Exception ( "Can't open '$filePath'" );
                }
                $total = floor ( $file->size / 1024 );
                $sql = '';
                while ( ! $file->endOfFile () ) {
                        $line = $file->readLine ();
                        $line = trim ( $line );
                        // Ignore line if empty line or comment
                        if (empty ( $line ) || substr ( $line, 0, 2 ) == '--'){
                                continue;
                        }
                        $current = floor ( $file->tell () / 1024 );
                        if($log) {
                            $this->_infoLine($filePath, " $current of $total KB" );
                        }
                        $sql .= $line . ' ';
                        if (strpos ( $line, self::SQL_COMMAND_DELIMETER )) {
                                $pdo->exec($sql);
                                $command = '';
                        }
                }
                $file->close ();
        } catch ( \Exception $e ) {
                $file->close ();
                var_dump ( $line );
                throw $e;
        }
    }
     
    /**
     * Вывод информации
     * 
     * @param string $filePath
     * @param string $next
     */
    protected static function _infoLine($filePath, $next = null) 
    {
            echo "\r    > execute file $filePath ..." . $next;
    }
}
