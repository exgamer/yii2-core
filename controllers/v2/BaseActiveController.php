<?php

namespace core\controllers\v2;

use Yii;
use yii\rest\ActiveController;

/**
 * Базовый контроллер апишки
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
abstract class BaseActiveController extends ActiveController
{    
    /**
     * Форма поиска
     * 
     * @var string
     */
    public $searchClass;

    public function actions() 
    {
        $a = parent::actions();
        $a['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];
        unset($a['delete'], $actions['create'], $a['update']);
        
        return $a;
    }
    
    /**
     * @see \yii\rest\IndexAction
     */
    public function prepareDataProvider()
    {
        $requestParams = Yii::$app->getRequest()->getBodyParams();
        if (empty($requestParams)) {
            $requestParams = Yii::$app->getRequest()->getQueryParams();
        }
        $search = new $this->searchClass();
        
        return $search->search($requestParams);
    }
}
