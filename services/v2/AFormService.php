<?php
namespace core\services\v2;

use Yii;
use core\services\v2\AService;
use yii\db\ActiveRecord;
use core\forms\v2\AModel;
use yii\base\Exception;
use yii\helpers\Json;

/**
 * Базовый Service для моделей связанных с формами
 * 
 * @property string $modelStatusFieldName наименование атрибута статуса
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class AFormService extends AService
{          
    public $modelStatusFieldName = 'status';
    
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
     * смена статуса
     * @param AR $model
     */
    public function changeStatus($model, $status)
    {
        if ($model->{$this->modelStatusFieldName} == $status){
            throw new Exception(
                    Yii::t('service','Объект уже в состоянии ').$status
            );
        }
        $model->{$this->modelStatusFieldName} = $status;
        $this->saveModel($model);
    }
    
    /**
     * удаление
     * @param AR $model
     */
    public function delete($model)
    {
        if (! $model->delete()){
            throw new Exception(
                    Yii::t('service','Не удалось удалить модель - {errors}', [
                        'errors' => Json::encode($model->getErrors())
                    ])
            );
        }
        
        return true;
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
