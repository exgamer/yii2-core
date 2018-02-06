<?php
namespace core\forms;

use Yii;
use yii\base\Exception;
use yii\base\Model;
use Codeception\Lib\Interfaces\ActiveRecord;
use yii\web\ServerErrorHttpException;
use core\models\ActiveRecord as AR;

/**
 * Базовая модель 
 * @property string $relatedModel Основная модель связанная с данной формой
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class AModel extends Model
{
    protected $relatedModel;
    protected $errors=[];  // Суда записываем ошибки
    protected $warnings=[];  // Суда записываем предупреждения
    protected $logs=[];  // Суда записываем логи
    protected $saveMethodName = 'save';
      
    public function init()
    {
        parent::init();
        $this->relatedModel = $this->getRelatedModel();
    }
    
    /**
     * @see yii\base\Model
     */
    public function scenarios()
    {
        $scenarios = array_merge(parent::scenarios(),
            [
                AR::SCENARIO_INSERT => $this->attributes(),
                AR::SCENARIO_UPDATE => $this->attributes()
            ]);
        
        return $scenarios;
    }
    
    /**
     * @return \yii\db\Connection
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     */
    public static function getDb()
    {
        $self = new static();
        $relatedModelClass = $self->getRelatedModel();
        if ($relatedModelClass){
            return $relatedModelClass::getDb();
        }
        throw new ServerErrorHttpException(
                Yii::t('api', 'Не определена БД для работы формы.')
        );
    }
    
    /**
     * Модель связанная с даной формой
     * 
     * @example return ABC::class;
     */
    public function getRelatedModel()
    {
        return null;
    }
    
    /**
     * @see app\modules\v2\forms\base\BaseForm
     * @param ActiveRecord $model если передается происходит редактирование
     *
     * @throws \Exception
     * @return mixed boolean|ActiveRecord
     */
    public function save($model = null, $validate = true)
    {
        if ($validate && ! $this->validate()){
            return false ;
        }
        try {
            return static::getDb()->transaction(function($db) use($model) {
                $this->beforeFormSave();
                if(! $this->getBaseService()){
                    throw new ServerErrorHttpException(
                            Yii::t('api', 'Не выставлен основной сервис для работы с моделью.')
                    );
                }
                $result =  $this->getBaseService()->{$this->saveMethodName}($this , $model);
                $this->afterFormSave();
                
                return $result;
            });
        } catch (Exception $ex){
            $this->addServerError($ex->getMessage());
            return false;
        }
    }
    
    protected function beforeFormSave()
    {
        
    }
    
    protected function afterFormSave()
    {
        
    }
    
    /**
     * Возвращает ошиибку сервера
     * 
     * @param string|Exception $m
     */
    public function addServerError($m)
    {
        if($m instanceof \Exception){
            $m = $m->getMessage();
        }
        $this->addError('server-error' , $m);
    }

    /**
     * Возвращает ошибку сохранения модели
     * 
     * @throws Exception
     */
    protected static function saveModelFail()
    {
        throw new Exception(Yii::t('api','Ошибка сохранения модели.'));
    }
    
    /**
     * Записываем ошибку
     * @param type $message
     */
    protected function writeError($message)
    {
        $this->errors[]=$message;
    }
    
    /**
     * Записываем предупреждение
     * @param type $message
     */
    protected function writeWarninng($message)
    {
        $this->warnings[]=$message;
    }
    
    /**
     * Записываем логи
     * @param type $message
     */
    protected function writeLog($message)
    {
        $this->logs[]=$message;
    }
    
    /**
     * основной сервис дял работы с моделью
     * 
     * @example return service;
     */
    abstract function getBaseService();
}
