<?php

namespace core\controllers;

use Yii;
use yii\base\Exception;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecordInterface;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use core\models\ActiveRecord;
use common\components\excel\ExcelGrid;
use core\rest\Serializer;

/**
 * Базовый контроллер всех контроллеров
 * Class ActiveController
 * @package app\components
 */
class ActiveController extends \yii\rest\ActiveController
{
    protected $query;

    public $serializer = [
        'class' => 'core\rest\Serializer',
    ];
    public $reservedParams = ['sort','q','page','per-page'];
    public $per_page = 100;

    public $deep_cache = false;

    public $setInstitution = true;
    
    public function actionInfo()
    {
        return (new $this->modelClass())->info();
    }

    /**
     * Стандартные действия контроллера
     * @return array
     */
    public function actions() {

        $actions = parent::actions();

        unset($actions['create']);
        unset($actions['update']);
        unset($actions['delete']);
        
        //$actions['set-status'];
        $actions['index']['prepareDataProvider'] = [$this, 'fetchRecords'];
        $actions['view']['findModel'] = [$this, 'findModel'];

        $actions['options'] = [
            'class' => 'core\actions\OptionsAction',
        ];

        return $actions;
    }

    /**
     * Returns the data model based on the primary key given.
     * If the data model is not found, a 404 HTTP exception will be raised.
     * @param string $id the ID of the model to be loaded. If the model has a composite primary key,
     * the ID must be a string of the primary key values separated by commas.
     * The order of the primary key values should follow that returned by the `primaryKey()` method
     * of the model.
     * @return ActiveRecordInterface the model found
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function findModel($id)
    {
        /* @var $modelClass ActiveRecordInterface */
        $modelClass = $this->modelClass;
        $keys = $modelClass::primaryKey();
        if (count($keys) > 1) {
            $values = explode(',', $id);
            if (count($keys) != count($values)) {
                throw new NotFoundHttpException("Object not found: $id");
            } else {
                $query = $modelClass::find()->byPk(array_combine($keys, $values));
            }
        } elseif ($id !== null) {
            $query = $modelClass::find()->byPk($id);
        }

        /* @var $query ActiveQuery */

        $search = [];
        if (!empty($params)) {
            unset($params['id']);
            foreach ($params as $key => $value) {
                if (!in_array(strtolower($key), $this->reservedParams)) {
                    $search[$key] = $value;
                }
            }
        }

        $filter = new $modelClass;
        $filter->scenario = ActiveRecord::FILTER_ONE_SCENARIO;
        $filter->attributes =  $search;
        if (!$filter->validate()) {
            return $filter;
        }
        // Применяем фильтр к запросу
        $filter->applyFilterOne($query);

        \Yii::$app->cache->pause();

        $result = $query->one();
        
        \Yii::$app->cache->resume();

        if (isset($result)) {
            return $result;
        } else {
            throw new NotFoundHttpException("Object not found: $id");
        }
    }

    /**
     * Подготовка запроса, перед отдачей клиенту
     * @return ActiveDataProvider
     */
    public function prepareQuery() {

        $params = \Yii::$app->request->queryParams;
        if ($this->deep_cache) {
            $cache_string = "deep_".$this->action->id.md5(json_encode($params));
            $cached = \Yii::$app->cache->get($cache_string);
            if ($cached) {
                \Yii::trace("SERVED FROM DEEP CACHE");
                return $cached;
            }
        }

        $modelClass = $this->modelClass;
        $search = [];
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                if (!in_array(strtolower($key), $this->reservedParams)) {
                    $search[$key] = $value;
                }
            }
        }
        $query = $modelClass::find($this->setInstitution);

        $filter = new $modelClass;
        $filter->scenario = ActiveRecord::FILTER_SCENARIO;
        $filter->attributes =  $search;
        if (!$filter->validate()) {
            return $filter;
        }
        // Применяем фильтр к запросу
        $filter->applyFilter($query);

        return $query;
    }

    /**
     * Отдача провайдера клиенту
     * @param $query
     * @return ActiveDataProvider
     */
    public function fetchRecords()
    {
        $this->query = $this->prepareQuery();
        if ($this->query  instanceof ActiveQuery) {
            return new ActiveDataProvider([
                'query' => $this->query,
                'pagination' => [
                    'defaultPageSize' => $this->per_page,
                    'pageSizeLimit' => [1, 1000]
                ]
            ]);
        } else {
            return $this->query;
        }
    }

    public function actionCreate()
    {
        \Yii::$app->cache->pause();
        $model = new $this->modelClass();
        $this->checkAccess($this->id, $model);
        //$model->scenario = ActiveRecord::SCENARIO_INSERT;
        $model->load(\Yii::$app->getRequest()->getBodyParams(), '');
        if ($model->save()) {
            $response = \Yii::$app->getResponse();
            $response->setStatusCode(201);
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }
        
        return $model;
    }

    public function actionUpdate($id)
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

    public function actionDelete()
    {

        $id = \Yii::$app->request->get("id");
        if ($id) {
            $model = $this->findModel($id);
            $this->checkAccess($this->id, $model);
            if ($model->delete() === false) {
                print_r($model->getErrors());
                throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
            }
            Yii::$app->getResponse()->setStatusCode(204);
        } else {
            \Yii::$app->getResponse()->setStatusCode(404);
            throw new ServerErrorHttpException('Model NOT FOUND');

        }
    }
    
    public function actionSetStatus($id)
    {
        $model = $this->findModel($id);
        $this->checkAccess($this->id, $model);
        $model->scenario = ActiveRecord::SCENARIO_UPDATE;
        $model->status = Yii::$app->request->post('status');
        if ($model->save() === false && !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to change the objects status for unknown reason.');
        }
        return $model;
    }

    public function actionCount()
    {
        $query = $this->prepareQuery();
        /**
         * @var $query ActiveQuery
         */
        return $query->count();
    }
    
    /**
     * Экспорт данных в эксель
     * 
     * @return type
     */
    public function actionExcelExport()
    {
        $model = new $this->modelClass;
        $name =  (new \ReflectionClass($model))->getShortName();
        $dataProvider = $this->fetchRecords();
        $filename = strtolower($name) . "-" . time();
        if($model instanceof common\components\excel\IExcel){
            throw new Exception('model must be instance of the IExcel');
        }
        $fields = $model->getExportFields();
        $extend = $this->query->getExtendModelFieldsMap();
        
        $columns[] = ['class' => 'yii\grid\SerialColumn'];
        $columns = array_merge($fields , array_values($extend));
        
        ExcelGrid::widget([ 
            'dataProvider' => $dataProvider,
            'extension'=>'xlsx',
            'filename'=> $filename,
            'formatter' => ['class' => 'yii\i18n\Formatter','nullDisplay' => ''],
            'properties' =>[
            ],
            'columns' => $columns
        ]);
    }

    public function behaviors()
    {
        $result = ArrayHelper::merge(parent::behaviors(), [
            'corsFilter' => [
                'class' => Cors::className(),
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Max-Age' => 3600,
                    'Access-Control-Allow-Credentials' => null,
                    'Access-Control-Expose-Headers' => [],
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS']
                ],
            ],
            'actionTime' => [
                'class' => 'core\filters\ActionTimeFilter',
            ],
            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'except' => ['options'],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true
                    ],
                ],
            ],
        ]);

        // remove authentication filter
        $auth = $result['authenticator'];
        unset($result['authenticator']);

        // add CORS filter
//        $result['corsFilter'] = [
//            'class' => Cors::className(),
//            'cors' => [
//                'Origin' => ['*'],
//                'Access-Control-Request-Headers' => ['*'],
//                'Access-Control-Max-Age' => 3600,
//                'Access-Control-Allow-Headers' => [$this->getPaginationHeaders()],
//            ],
//
//        ];
        // re-add authentication filter
        $result['authenticator'] = $auth;
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $result['authenticator']['except'] = ['options'];

        return $result;
    }

    public function afterAction($action, $result)
    {
        $r = parent::afterAction($action, $result);
        // TODO: Сделан костыль изза ошибки в самом фреймворке
        \Yii::$app->response->headers->set("Access-Control-Expose-Headers", $this->getPaginationHeaders());
        
        return $r;
    }
    
    public function getPaginationHeaders()
    {
        $serializer = new Serializer();
        return implode(",",[strtolower($serializer->currentPageHeader), strtolower($serializer->totalCountHeader), strtolower($serializer->totalCountHeader), strtolower($serializer->pageCountHeader), strtolower($serializer->perPageHeader)]);
    }
}