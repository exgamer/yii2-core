<?php
namespace core\helpers;

use Yii;
use core\helpers\ConstHelper;

/**
 * Вспомогательный класс для работы с паролями
 * 
 * @author Kamaelkz
 */
abstract class CryptoHelper
{
    /**
     * Генирация пароля
     * 
     * @return string
     */
    public static function generatePassword($length=null)
    {
        return  YII_DEBUG 
                ? ConstHelper::DEFAULT_PASSWORD 
                : Yii::$app->security->generateRandomString($length?$length:ConstHelper::PASSWORD_LENGTH);
    }
    
    /**
     * Генирация логина
     * 
     * @return string
     */
    public static function generateLogin($length=null)
    {
        // генерируем id и зашифровываем его
        $string = crypt(uniqid()); 
        // убираем слэши
        $string = strip_tags(stripslashes($string)); 
        #убираем точки и переворачиваем строку задом наперед
        $string = str_replace(".","",$string); 
        $string = strrev(str_replace("/","",$string)); 
        // берем первые ConstHelper::LOGIN_LENGTH значений
        $string = substr($string,0,  $length?$length:ConstHelper::LOGIN_LENGTH); 
        
        return $string;
    }

    /**
     * Возвращает хэш строку пароля
     * 
     * @param string $password
     */
    public static function encodePassword($password)
    {
        return md5($password);
    }
    
    /**
     * Возвращает хэш строку идентификатора
     * 
     * @param integer $id
     * @return string
     */
    public static function encodeId($id)
    {
        return md5($id . PORTAL_SECRET_WORD);
    }
    
    /**
     * Возвращает хэш строку идентификатора
     * хэш действует пока активна дата
     * (день/месяц/год ..)
     * 
     * @param integer $id
     * @param string $format
     * @return string
     */
    public static function encodeIdByDate($id , $format = 'd.m.Y')
    {
        return md5($id . PORTAL_SECRET_WORD . date($format));
    }
}
