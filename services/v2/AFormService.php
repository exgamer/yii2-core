<?php
namespace core\services\v2;

use Yii;
use core\services\v2\AService;
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
    public function save(AModel $form , $model = null)
    {
        $modelClass = $this->getRelatedModelClass();
        if($model === null){
            $model = new $modelClass();
        }
        #флаг для понимания операции создания/редактирования
        $is_new_record = $model->isNewRecord;
        #заполнениe атрибутов
        $model->load($form->attributes, '');
        $this->beforeModelSave($form, $model, $is_new_record);
        if (! $model->save()) {
            $form->addErrors($model->getErrors());
            
            return false;
        }
        $this->setPrimaryKeysToFrom($form, $model);
        $this->afterModelSave($form, $model, $is_new_record);
        
        return $model;
    }
    
    /**
     * смена статуса
     * @param AR $model
     */
    public function changeStatus($model, $status)
    {
        $this->beforeChangeState($model , $status);
        if ($model->{$this->modelStatusFieldName} == $status){
            throw new Exception(
                    Yii::t('service','Объект уже в состоянии ').$status
            );
        }
        $model->{$this->modelStatusFieldName} = $status;
        $this->saveModel($model,false);
        $this->afterChangeState($model , $status);
        
        return $model;
    }
    
    /**
     * Смена значения аттрибута с историей
     * @param AR $model
     * @param string $attribute имя атрибута
     * @param string $value значение
     * @param string $history_attribute название атрибута для хранения истории
     * 
     * @return boolean
     */
    public function changeAttributeWithHistory($model , $attribute, $value, $history_attribute = null)
    {
        if (! $history_attribute){
            $history_attribute = $attribute.'_change_history';
        }
        $history = [
                'from' => $model->{$attribute},
                'to' => $value,
                'date' => date('Y-m-d H:i:s')
        ];
        if (isset(Yii::$app->user)){
            $history['person_id'] = Yii::$app->user->identity->id;
        }
        if ($model->{$history_attribute}){
            $model->{$history_attribute} = array_merge($model->{$history_attribute}, [$history]);
        }else{
            $model->{$history_attribute} = [$history];
        }
        $model->{$attribute} = $value;
        
        return $this->saveModel($model);
    }
    
    /**
     * удаление
     * @param AR $model
     */
    public function delete($model)
    {
        $this->beforeDelete($model);
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
    
    /**
     * Дополнительные действия перед удалением
     * @param ActiveRecord $model
     */
    protected function beforeDelete($model){}
    
    /**
     * Дополнительные действия перед сменой статуса
     * @param ActiveRecord $model
     * @param integer $status
     */
    protected function beforeChangeState($model , $status){}
    
    /**
     * Дополнительные действия после смены статуса
     * @param ActiveRecord $model
     * @param integer $status
     */
    protected function afterChangeState($model, $status){}
    
    /**
     * Дополнительные действия с моделью перед сохранением
     * @param BaseForm $form класс для работы
     * @param ActiveRecord $model
     * @param boolean $is_new_record
     */
    protected function beforeModelSave($form , $model, $is_new_record){}
    
    /**
     * Дополнительные действия с моделью после сохранения
     * @param BaseForm $form класс для работы
     * @param ActiveRecord $model
     * @param boolean $is_new_record
     */
    protected function afterModelSave($form , $model, $is_new_record){}
}