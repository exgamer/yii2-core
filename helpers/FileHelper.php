<?php
namespace core\helpers;

use yii\helpers\FileHelper as YiiHelper;

/**
 * Вспомогательный класс для работы с файлами
 * 
 * @author kamaelkz 
 */
class FileHelper extends YiiHelper
{  
    /**
     * Создает новый файл и записывает в него содержимое 
     * если $content не нулевой
     * 
     * @param string $path путь до файла
     * @param string $content содержимое которое нужно записать в файл
     */
    public static function createFile($path , $content = null)
    {
        $file = fopen ($path, 'w+');
        if(! empty($content)){
            fwrite($file, $content);
        }
        fclose($file);
    }

    /**
     * Удаление файла
     * 
     * @param string $path путь до файла
     */
    public static function deleteFile($path)
    {
        if (file_exists($path)) {
            unlink($path);
        } 
    }
    
    /**
     * Возвращает содержимое файла
     * 
     * @param string $path путь до файла
     * @return string
     */
    public static function readFile($path)
    {
        if(!file_exists($path)){
            return null;
        }
        
        return file_get_contents($path);
    }

    /**
     * Возвращает массив файлов из директории
     * 
     * @param string $path путь к директории
     * @return array
     */
    public static function scanFiles($path)
    {
        $result = [];

        $cdir = scandir($path);
        foreach ($cdir as $key => $value)
        { 
            if (in_array($value,[".",".."])) {
                continue;
            }
            if(is_dir( $path . DIRECTORY_SEPARATOR . $value ) ) {
                continue;
            }

            $result[] = $value; 
        }

        return $result;
    }
    /**
     * Возвращает массив папок в директории
     * 
     * @param string $path путь к директории
     * @return array
     */
    public static function scanDirs($path)
    {
        $result = [];

        $cdir = scandir($path);
        foreach ($cdir as $key => $value)
        { 
            if (in_array($value,[".",".."])) {
                continue;
            }
            if(is_dir( $path . DIRECTORY_SEPARATOR . $value ) ) {
                $result[] = $value; 
            }
        }

        return $result;
    }
    
    /**
     * Очистка папки и вложенностей
     * 
     * @param string $path
     * @return boolean
     */
    public static function clearDirRecursive($path)
    { 
        if (substr($path, strlen($path)-1, 1) != '/') $path .= '/'; 
        if ($handle = @opendir($path)){ 
            while ($obj = readdir($handle)){ 
                if ($obj != '.' && $obj != '..'){ 
                    if (is_dir($path.$obj)){ 
                        if (!recRMDir($path.$obj)) return false; 
                    }elseif (is_file($path.$obj)){ 
                        if (!unlink($path.$obj))    return false; 
                        } 
                } 
            } 
              closedir($handle); 
                if (!@rmdir($path)) return false; 
              return true; 
        } 

       return false; 
    }
}