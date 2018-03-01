<?php
namespace core\services\v2;

use core\services\v2\AService;
use yii\db\ActiveRecord;
use core\forms\v2\AModel;

/**
 * Базовый Service для моделей связанных с формами
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class AFormService extends AService
{          
    /**
     * Сохранение формы
     * 
     * @param BaseForm $form класс для работы
     * @param ActiveRecord $model модель данных - передается при редактировании
     * @return ActiveRecord
     */
    public function save(AModel $form , ActiveRecord $model = null)
    {
        $modelClass = $this->getRelatedModelClass();
        if($model === null){
            $model = new $modelClass();
        }
        #заполнениe атрибутов
        $model->load($form->attributes, '');
        $this->beforeModelSave($form, $model);
        if (! $model->save()) {
            $form->addErrors($model->getErrors());
            
            return false;
        }
        $this->setPrimaryKeysToFrom($form, $model);
        $this->afterModelSave($form, $model);
        
        return $model;
    }
    
        
    /**
     * Дополнительные действия с моделью перед сохранением
     * @param BaseForm $form класс для работы
     * @param ActiveRecord $model
     */
    protected function beforeModelSave($form , $model)
    {
        
    }
    
    /**
     * Дополнительные действия с моделью после сохранения
     * @param BaseForm $form класс для работы
     * @param ActiveRecord $model
     */
    protected function afterModelSave($form , $model)
    {

    }
    
    /**
     * Выставляем полученные примари ключи в форму
     * @param AR $form
     * @param AR $model
     */
    public function setPrimaryKeysToFrom($form, $model)
    {
        $primaryKeys = $model::primaryKey();
        foreach ($primaryKeys as $attribute) {
            if (property_exists($form, $attribute)){
                $form->{$attribute} = $model->{$attribute};
            }
        }
    }
}
