<?php
namespace core\models\v2\properties\column;

/**
 * Интерфейс для моделей с дополнительными свойствами,
 * которые хранятся в дополнительной колонке 
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
interface IARWithColumnProps
{  
    /**
     * Массив дополнительных атрибутов
     * 
     * @return array
     */
    public static function properties(); 
    
    /**
     * Колонка в которой будут храниться дополнительные свойства
     * 
     * @return string
     */
    public static function propertiesColumn();
}

