<?php
namespace core\forms\v2;

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
    use \core\traits\ModelTrait;

    protected $saveMethodName = 'save';
    
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
                $service = $this->getBaseService();
                if(! $service){
                    throw new ServerErrorHttpException(
                            Yii::t('api', 'Не выставлен основной сервис для работы с моделью.')
                    );
                }
                return $service->getDb()->transaction(function($db) use($service, $model) {
                    $method = $this->getSaveMethodName();
                    return $service->{$method}($this , $model);
                });
        } catch (Exception $ex){
            $this->addServerError($ex->getMessage());
            return false;
        }
    }
    
    /**
     * Получить метод для сохранения
     */
    public function getSaveMethodName()
    {
        return $this->saveMethodName;
    }
    
    /**
     * основной сервис дял работы с моделью
     * 
     * @example return service;
     */
    abstract function getBaseService();
}
