<?php
namespace core\actions;

use Yii;
use yii\base\Action;
use yii\web\ServerErrorHttpException;
use core\models\ActiveRecord;

/**
 * экшен для изменения статуса 
 * 
 * @author CitizenZet
 */
class SetStatusAction extends Action 
{
    public function run($id)
    {
        $model = $this->controller->findModel($id);
        $this->controller->checkAccess($this->id, $model);
        $model->scenario = ActiveRecord::SCENARIO_UPDATE;
        $model->status = Yii::$app->request->post('status');
        if ($model->save() === false && !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to change the objects status for unknown reason.');
        }
        return $model;
    }
}