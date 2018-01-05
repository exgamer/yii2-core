<?php
namespace core\actions;

use Yii;
use yii\base\Action;
use yii\web\ServerErrorHttpException;

/**
 * Стандартный экшен для редактирования
 * 
 * @author CitizenZet
 */
class UpdateAction extends Action 
{
    public function run($id)
    {
        $model = $this->findModel($id);
        $this->checkAccess($this->id, $model);
        $model->scenario = ActiveRecord::SCENARIO_UPDATE;
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if ($model->save() === false && !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }
        
        return $model;
    }
}