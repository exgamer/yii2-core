<?php
namespace core\actions;

use Yii;
use yii\base\Action;
use yii\web\ServerErrorHttpException;

/**
 * Стандартный экшен для создания
 * 
 * @author CitizenZet
 */
class CreateAction extends Action 
{
    public function run()
    {
        Yii::$app->cache->pause();
        $model = new $this->modelClass();
        $this->checkAccess($this->id, $model);
        $model->scenario = ActiveRecord::SCENARIO_INSERT;
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if ($model->save()) {
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }
        
        return $model;
    }
}