<?php
namespace core\services;

use core\services\AService;
use yii\db\ActiveRecord;
use core\forms\AModel;

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
        $modelClass = $form->getRelatedModel();
        if (property_exists($form, 'id') && $form->id && ! $model){
            $model = $modelClass::find()->byPk($form->id)->one();
        }
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
}
