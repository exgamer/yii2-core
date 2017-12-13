<?php
namespace core\models;

use core\models\ActiveRecord;
use core\interfaces\IBatch;

/**
 * Данный  класс описывает стандартное поведение свойств(-а) объектов системы.
 * @property integer id_object идентификатор объекта к которому принадлежит свойство
 * @property string name - имя свойства на английском
 * @property string value - значениe
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class AProperty extends ActiveRecord implements IBatch
{
    public function rules()
    {
        return [
                [
                    [
                        'id_object',
                        'name'
                    ], 
                    'required'
                ],
                ['id_object', 'integer'],
                ['name', 'string', 'max' => 255],
                ['value', 'string']
        ];
    }
    
        

    /**
     * @see common\interfaces\IBatch
     * @return array
     */
    public function excludeAttrMap()
    {
        return [
        ];
    }
    
    /**
     * @see common\interfaces\IBatch
     * @param array $columns
     * @return mixed
     */
    public function excludeAttr(&$columns)
    {
        if(! $columns || !is_array($columns)){
            return null;
        }
        $excludeMap = $this->excludeAttrMap();
        foreach ($excludeMap as $item) {
            if (isset($columns[$item])){
                unset($columns[$item]);
            }
        }
    }
    
    /**
     * @see common\interfaces\IBatch
     * @param array $attributes
     * @return array
     */
    public function clearEmptyAttr($attributes)
    {
            return  array_filter (
                            $attributes ,
                            function($key){
                                    if($key !== null && $key !== ""){
                                            return true;
                                    }
                            }
            );
    }
    
    /**
     * @see common\interfaces\IBatch
     */
    public function afterBatch($data)
    {

    }
}