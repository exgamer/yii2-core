<?php
namespace core\controllers\v2;

use Yii;
use yii\base\InvalidConfigException;
use core\models\ActiveRecord;
use core\controllers\ActiveController;
use yii\web\ServerErrorHttpException;

/**
 * Базовый контролле для работы с формой и связанной с ней моделью
 * ActiveRecord 
 * 
 * @property string $formClass полный путь до формы 
 * @property boolean $callParent вызов родительский действий (бывает необходимость)
 * 
 * @author Kamaelkz <arxangel921@gmail.com>
 */
abstract class BaseActiveControllerWithForm extends ActiveController
{
    /**
     * Полный путь до формы
     * @var string 
     */
    public $formClass;
    
    /**
     * Вызывать родительские действия
     * 
     * @var boolean
     */
    public $callParent = false;
    
    public $create_form_method = 'save';
    public $update_form_method = 'save';
    public $delete_form_method = 'delete';
    public $change_status_form_method = 'changeStatus';

    public function init()
    {
        parent::init();
        if ($this->formClass === null) {
            throw new InvalidConfigException(Yii::t('api','Свойство $formClass должен быть заполнен.'));
        }
    }
    
    public function actions() {

        $actions = parent::actions();
        unset($actions['create']);
        unset($actions['update']);

        return $actions;
    }
    
    public function actionCreate()
    {
        if($this->callParent){
            return parent::{__FUNCTION__}();
        }
        \Yii::$app->cache->pause();
        $model = new $this->modelClass();
        $form = new $this->formClass();
        $model->scenario = $form->scenario = ActiveRecord::SCENARIO_INSERT;
        $this->checkAccess($this->id, $model);
        $form->load(\Yii::$app->getRequest()->getBodyParams(), '');
        if (($result = $form->{$this->create_form_method}()) != false) {
            $response = \Yii::$app->getResponse();
            $response->setStatusCode(201);
        } elseif (! $form->hasErrors()) {
            throw new ServerErrorHttpException(\Yii::t('api','Не удалось создать объект по неизвестной причине.'));
        }
        #если есть ошидки присваиваем их модели для возврата
        if($form->hasErrors()){
            $model->addErrors($form->getErrors());
            return $model;
        }

        return $result;
    }
    
    public function actionUpdate($id) 
    {
        if($this->callParent){
            return parent::{__FUNCTION__}();
        }
        $model = $this->findModel($id);
        $form = new $this->formClass();
        #TODO тестируем как себя ведет
        $form->setAttributes($model->attributes, false);
        $this->checkAccess($this->id, $model);
        $model->scenario = $form->scenario = ActiveRecord::SCENARIO_UPDATE;
        $form->load(\Yii::$app->getRequest()->getBodyParams(), '');
        if (($result = $form->{$this->update_form_method}($model)) === false 
            && ! $form->hasErrors()
        ) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }
        #если есть ошидки присваиваем их модели для возврата
        if($form->hasErrors()){
            $model->addErrors($form->getErrors());
            return $model;
        }
        
        return $result;
    }
}