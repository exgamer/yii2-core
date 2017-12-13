<?php
namespace core\helpers;

use Yii;

/**
 * Вспомогательный класс для работы с почтой
 * 
 * @author Kamaelkz
 */
abstract class MailHelper
{  
    /**
     * Формирует и вовращает письмо для регистрации
     * 
     * @param string $login
     * @param string $password
     * 
     * @return string
     */
    public static function getRegistrationBody($login, $password)
    {
        $result = self::getMailTemplate(
                'registration',
                [
                    'login' => $login,
                    'password' => $password
                ]
        );
        
        return $result;
    }
    
    /**
     * Формирует и вовращает письмо при создании новой учетной записи
     * 
     * @param string $login
     * @param string $password
     * 
     * @return string
     */
    public static function getCredentialCreateBody($login, $password)
    {
        return self::getMailTemplate(
                'credential_create',
                [
                    'login' => $login,
                    'password' => $password
                ]
        );
    }
    
    /**
     * Формирует и вовращает письмо для востановления пароля
     * 
     * @return string
     */
    public static function getResetPasswordBody($hash)
    {
        return self::getMailTemplate(
                'resetpassword',
                [
                    'hash' => $hash
                ]
        );
    }
    
	/**
     * Формирует и вовращает письмо для администратора школы
     *
     * @return string
     */
    public static function getInstitutionSuperAdminMailBody($login, $password)
    {
        return self::getMailTemplate(
                'institution_super_admin_mail',
                [
                    'login' => $login,
                    'password' => $password
                ]
        );
    }

	/**
     * Формирует и вовращает письмо заявка на регистрацию организации
     *
     * @return string
     */
    public static function getInstitutionRequestMailBody($data)
    {
        return self::getMailTemplate('institution_request', $data);
    }

	/**
     * Формирует и вовращает письмо для администратора школы
     *
     * @return string
     */
    public static function getDeclineInstitutionMailBody($comment)
    {
        return self::getMailTemplate(
                'institution_decline_registration',
                [
                    'comment' => $comment
                ]
        );
    }

    /**
     * Возвращает сформированный шаблон письма
     * 
     * @param string $tpl
     * @param array $params
     * @return type
     */
    protected static function getMailTemplate($tpl , $params = [])
    {
        $trmplatePath = '@common/views/mail_template/' . $tpl;
        
        return Yii::$app->view->render($trmplatePath, $params);
    }
}