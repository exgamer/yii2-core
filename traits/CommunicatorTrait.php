<?php
namespace core\traits;

use Yii;
use yii\base\Exception;
use core\helpers\StringHelper;

/**
 * Треит для получения комуникатора
 * @property string $communicatorNameSpace  - папка где лежит фаил для неимспеиса
 * @property string $communicatorClassName  - название класса комуникатора
 * @property string $classPostfix  - постфикс класса который использует треит
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
trait CommunicatorTrait
{
    protected $communicatorNameSpace = 'common\communicators';
    protected $communicatorClassName;
    protected $classPostfix=null;
    
    /**
     * Получить экземпляр комуникатора
     * постфикс названия класса для автогенерации названия комуникатора (например если вызываем из UserSerializer будет Serializer сгенерится UserCommunicator)
     * @param string $classPostfix
     * @return ACommunicator
     * @throws Exception
     */
    public function getCommunicator()
    {
        if (! $this->communicatorClassName || ! $this->communicatorNameSpace){
            $this->communicatorClassName = $this->getClassNameWithoutPostfix()."Communicator";
        }
        if (! $this->communicatorNameSpace && ! $this->communicatorClassName){
            throw new Exception(Yii::t('api', 'Не удалось инициализировать комуникатор'));
        }
        $communicatorClass = $this->communicatorNameSpace.'\\'.$this->communicatorClassName;
        
        return new $communicatorClass();
    }
    
    /**
     * Получить постфикс класса
     * @return string
     */
    public function getClassNameWithoutPostfix()
    {
        if (is_string($this->classPostfix))
        {
            return $this->classPostfix;
        }
        $className =  (new \ReflectionClass($this))->getShortName();
        $WordArray = StringHelper::splitStringByBigSymbol($className);
        $count = count($WordArray);
        $classPostfix = array_pop($WordArray);
        if ($count>1){
            return  str_replace($classPostfix, "", $className);
        }
        
        return $classPostfix;
    }
    
    /**
     * Установка название класса комуникатора
     */
    public function setCommunicatorClassName($value)
    {
        $this->communicatorClassName = $value;
    }
}

