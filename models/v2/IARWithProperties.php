<?php
namespace core\models\v2;

interface IARWithProperties
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
    public function properties();
    
    /**
     * Полный путь до модели дополнительных атрибутов
     * AR::class
     * 
     * @return string
     */
    public function getPropertiesClass();
    
    /**
     * Атрибут в модели дополнительных атрибутов, через который
     * основной объект перевязывается с дополнительными атрибутами
     * 
     * @return string
     */
    public function getLinkAttribute();
            
}

