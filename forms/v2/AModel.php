<?php
namespace core\forms\v2;

use Yii;
use yii\base\Exception;
use yii\base\Model;
use Codeception\Lib\Interfaces\ActiveRecord;
use yii\web\ServerErrorHttpException;
use core\models\ActiveRecord as AR;
use core\forms\v2\IHaveService;

/**
 * Базовая модель 
 * @property string $saveMethodName метод сервиса для сохранения
 * @property string $transactionalSave использовать ли транзакцию при вызове сохранения
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class AModel extends Model implements IHaveService
{
    use \core\traits\ModelTrait;

    protected $saveMethodName = 'save';
    protected $transactionalSave = true;
    
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
                $service = static::getBaseService();
                if(! $service){
                    throw new ServerErrorHttpException(
                            Yii::t('api', 'Не выставлен основной сервис для работы с моделью.')
                    );
                }
                $method = $this->getSaveMethodName();
                if ($this->transactionalSave){
                    return $service->getDb()->transaction(function($db) use($service, $model, $method) {
                        return $service->{$method}($this , $model);
                    });
                }

                return $service->{$method}($this , $model);
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
}
