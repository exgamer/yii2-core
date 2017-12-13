<?php
namespace core\interfaces;

/**
 * Интерфейс для моделей в которых используется мульти вставка 
 * 
 * @author Kamaelkz
 */
interface IBatch
{
    /**
     * Исключение атрибутов из входящего массива
     * 
     * @param array $columns
     */
    public function excludeAttr(&$columns);
    
    /**
     * Атрибуты модели исключенные из мульти вставки
     * 
     * @return array
     */
    public function excludeAttrMap();
    
    /**
     * Зачистка пустых элементов массива
     * 
     * @param array $attributes
     * @return array
     */
    public function clearEmptyAttr($attributes);
    
    /**
     * Действия после батча
     */
    public function afterBatch($data);
}

