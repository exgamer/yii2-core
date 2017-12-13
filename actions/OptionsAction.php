<?php
namespace core\actions;

use Yii;

class OptionsAction extends \yii\rest\OptionsAction
{

    public $collectionOptions = ['GET', 'POST', 'HEAD', 'OPTIONS', "DELETE"];

    public function run($id = null)
    {
        if (Yii::$app->getRequest()->getMethod() !== 'OPTIONS') {
            Yii::$app->getResponse()->setStatusCode(405);
        }
        $options = $id === null ? $this->collectionOptions : $this->resourceOptions;
        Yii::$app->getResponse()->getHeaders()->set('Access-Control-Allow-Methods', implode(', ', $options));
    }

}