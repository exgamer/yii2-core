<?php
namespace core\forms\v2;

use Yii;
use yii\base\Exception;
use yii\base\Model;
use yii\web\Application;
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
     * Параметр для перезагрузки модели без валидации
     * Используется в ActiveForm
     * 
     * @var string
     */
    public static $refreshParam = 'refresh-form';
    
    public function validate($attributeNames = null, $clearErrors = true)
    {
        if(
                (
                    Yii::$app->request->post(static::$refreshParam) 
                    || Yii::$app->request->get(static::$refreshParam)
                )
                && Yii::$app instanceof Application
        ) {

            return false;
        }
        
        return parent::validate($attributeNames, $clearErrors);
    }
    
    /**
     * По умолчанию возвращаем правила модели
     */
    public function rules()
    {
        $service = static::getBaseService();
        if(! $service){
            throw new ServerErrorHttpException(
                    Yii::t('api', 'Не выставлен основной сервис для работы с моделью.')
            );
        }
        $modelClass = $service->getRelatedModelClass();
        $model = new $modelClass();
        return $model->rules();
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
        } catch (yii\db\Exception $ex){
            if (YII_DEBUG){
                $this->addServerError($ex->getMessage());
            }else{
                $this->addServerError("Internal Db Exception");
            }
            return false;
        }catch (Exception $ex){
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
