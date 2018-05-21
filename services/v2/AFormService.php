<?php
namespace core\services\v2;

use Yii;
use yii\base\Exception;
use yii\helpers\Json;
use yii\db\ActiveRecord;
use core\services\v2\AService;
use core\forms\v2\AModel;
use core\forms\v2\ChangeStatusForm;

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
     * Смена статуса
     * 
     * @param ChangeStatusForm $form
     * @param ActiveRecord $model
     * @return ActiveRecord
     * @throws Exception
     */
    public function changeStatus(ChangeStatusForm $form, ActiveRecord $model)
    {
        if(! $model->hasAttribute($this->modelStatusFieldName)) {
            throw new Exception(
                    Yii::t('service','Свойство {property} не определено.' , [
                        'property' => $this->modelStatusFieldName
                    ])
            );
        }
        if ($model->{$this->modelStatusFieldName} == $form->status){
            throw new Exception(
                    //TODO расшифровка статуса - метка
                    Yii::t('service','Объект уже в состоянии {status}' , [
                        'status' => $form->status
                    ])
            );
        }
        $this->beforeChangeState($form , $model);
        $model->{$this->modelStatusFieldName} = $form->status;
        $this->saveModel($model,false);
        $this->afterChangeState($form , $model);
        
        return $model;
    }
    
    /**
     * Возвращет новый экзэмплярформы смены статуса, заполняет переданные
     * в функцию параметры
     * 
     * @param integer $status
     * @param integer $reason_id
     * @param string $comment
     */
    public function getChangeStatusFormInstance($status, $reason_id = null, $comment = null)
    {
        $model = new ChangeStatusForm();
        $model->status = $status;
        if($reason_id) {
            $model->reason_id = $reason_id;
        }
        if($comment) {
            $model->$comment = $comment;
        }
        
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
        $model = $this->setAttributeWithHistory($model , $attribute, $value, $history_attribute);
        
        return $this->saveModel($model);
    }
    
    /**
     * установка значения аттрибута с историей
     * @param AR $model
     * @param string $attribute имя атрибута
     * @param string $value значение
     * @param string $history_attribute название атрибута для хранения истории
     * @param array  $extAttrs доп атрибуты которые нужно записать в историю
     * 
     * @return boolean
     */
    public function setAttributeWithHistory($model , $attribute, $value, $history_attribute = null, $extAttrs = [])
    {
        if (! $history_attribute){
            $history_attribute = $attribute.'_change_history';
        }
        $history = [
                'from' => $model->{$attribute},
                'to' => $value,
                'date' => date('Y-m-d H:i:s')
        ];
        if (! empty($extAttrs) && is_array($extAttrs)){
            foreach ($extAttrs as $attr => $value) {
                $history[$attr] = $value;
            }
        }
        if ( Yii::$app->has('user') &&  Yii::$app->user->identity){
            $history['person_id'] = Yii::$app->user->identity->id;
        }
        if ($model->{$history_attribute}){
            $model->{$history_attribute} = array_merge($model->{$history_attribute}, [ $history ]);
        }else{
            $model->{$history_attribute} = [ $history ];
        }
        $model->{$attribute} = $value;
        
        return $model;
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
     * @param Model $form
     * @param ActiveRecord $model
     */
    protected function beforeChangeState($form, $model){}
    
    /**
     * Дополнительные действия после смены статуса
     * @param Model $form
     * @param ActiveRecord $model
     */
    protected function afterChangeState($form, $model){}
    
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