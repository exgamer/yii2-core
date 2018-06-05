<?php
namespace core\models\v2\properties\table;

/**
 * Интерфейс для моделей с дополнительными свойствами,
 * которые хранятся в дополнительной таблице
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
interface IARWithTableProps
{  
    /**
     * Наименование связи с таблицей атрибутов
     * 
     * @return string
     */
    public static function getPropertiesRelation();
    
    /**
     * Массив дополнительных атрибутов
     * 
     * @return array
     */
    public static function properties();
    
    /**
     * Полный путь до модели дополнительных атрибутов
     * AR::class
     * 
     * @return string
     */
    public static function getPropertiesClass();
    
    /**
     * Атрибут в модели дополнительных атрибутов, через который
     * основной объект перевязывается с дополнительными атрибутами
     * 
     * @return string
     */
    public static function getLinkAttribute();
            
}

