<?php
namespace core\filters;

use Yii;
use yii\web\UnauthorizedHttpException;
use yii\filters\auth\HttpBearerAuth as Base;
use common\models\person\Person;

/**
 * Класс для авторизации через токен
 * 
 * @author Kamaelkz <arxangel921@gmail.com>
 */
class HttpBearerAuth extends Base
{
    public $userClassName = null;
    public $universalUser = false;
    /**
     * Универсальный токен для авторизации
     */
    const UNIVERSAL_TOKEN = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJleHAiOjYyMjA4MDAwMCwiaXNzIjoiIiwiYXVkIjoiIiwiaWF0IjoxNTAyNTM4MTczLCJuYmYiOjE1MDI1MzgxNzMsInVpZCI6LTUyMDF9.oAaPgbbh4OKv8ccbg7WjO_ZOPjHtlF_TWyr4PB4omg0';
    
    /**
     * Переопределяем для проверки пользовательских данных
     */
    public function authenticate($user, $request, $response)
    {
        $token = self::getAuthHeader();
        if ($token) {
            #проверка универсального токена и получение фейкового пользователя
            $universal = $this->getUniversalPerson($token);
            if($universal && $universal instanceof $this->userClassName){
                return $universal;
            }
            #ищем пользователя в БД
            $identity = $user->loginByAccessToken($token, get_class($this));
            if ($identity === null) {
                $this->handleFailure($response, Yii::t('api' ,'Пользователь не найден.'));
            }
            $this->afterAuthenticate($identity);
            
            return $identity;
        }

        return null;
    }
    
    /**
     * Раздные доп де1йствия после авторизации
     */
    public function afterAuthenticate($identity)
    {
        
    }
    
    /**
     * Полуение токена из хидера
     * @return string
     */
    public static function getAuthHeader()
    {
        $authHeader = Yii::$app->request->getHeaders()->get('Authorization');
        if ($authHeader !== null && preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * @inheritdoc
     */
    public function handleFailure($response, $message = 'Your request was made with invalid credentials.')
    {
        throw new UnauthorizedHttpException($message);
    }
    
    /**
     * Получение пользователя по универсальному токену
     * 
     * @param string $token универсальный токен
     * @return Person
     */
    protected function getUniversalPerson($token)
    {
        if($this->universalUser == false || $token != self::UNIVERSAL_TOKEN){
            return null;
        }
        $person = new $this->userClassName();
        $person->id = -5201;
        
        return $person;
    }
}

