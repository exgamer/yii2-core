<?php
namespace core\actions;

use Yii;
use yii\base\Action;
use yii\web\ServerErrorHttpException;

/**
 * Стандартный экшен для удаления
 * 
 * @author CitizenZet
 */
class DeleteAction extends Action 
{
    public function run($id)
    {
        $model = $this->controller->findModel($id);
        $this->controller->checkAccess($this->id, $model);
        if ($model->delete() === false) {
            print_r($model->getErrors());
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }
        Yii::$app->getResponse()->setStatusCode(204);
    }
}