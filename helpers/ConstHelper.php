<?php
namespace core\helpers;

/**
 * Класс для хранения констант
 *
 * @author CitizenZet <exgamer@live.ru>
 */
class ConstHelper
{

    
    #дни недели
    const MONDAY = 'monday';
    const TUESDAY = 'tuesday';
    const WEDNESDAY = 'wednesday';
    const THURSDAY = 'thursday';
    const FRIDAY = 'friday';
    const SATURDAY = 'saturday';
    const SUNDAY = 'sunday';

    #дефолтный пароль
    const DEFAULT_PASSWORD = '12345678';

   
    #язык по умолчанию
    const DEFAULT_LANGUAGE_ISO = "ru";
    #новый
    const STATUS_CREATED = 0;
    #активный
    const STATUS_ACTIVE = 1;
    #заблокирован
    const STATUS_LOCKED = 2;
    #удален
    const STATUS_REMOVED = 3;
    #перемещен
    const STATUS_MOVED = 4;
    #оставлен (на второй год)
    const STATUS_ABANDONED = 5;
    #выпущен
    const STATUS_RELEASED = 6;
    # пусто / например из класса
    const STATUS_EMPTY = 7;
    # запрос
    const STATUS_REQUEST=8;
    #подтвержден
    const STATUS_APPROVED=9;
    #отклонен
    const STATUS_REJECTED=10;
    #замена
    const STATUS_REPLACED=11;
    #переназначение
    const STATUS_REMAPPED=12;
    

    #номер мобильного телефона
    const TYPE_MOBILE_PHONE = 'mobile_phone';
    #номер домашнего телефона
    const TYPE_LANDLINE_PHONE = 'landline_phone';
    #адрес элеткронной почты
    const TYPE_EMAIL = 'email';
    #токен
    const TYPE_TOKEN = 'token';
    #сброс пароля
    const TYPE_RESET = 'reset';
    #логин
    const TYPE_LOGIN = 'login';
    #временный пароль
    const TYPE_TEMPORARY = 'temporary';
    #запись администратора (админка)
    const TYPE_ADMIN = 'admin';

    

    const TYPE_DEFAULT = 0;
    #секретный ключ для формирования JWT токена
    const JWT_SECRET_KEY = 'o^ieONLzxEJ69gB&aoce20eiwU6ebj';
    #время жизни JWT токена (30 дней)
    const JWT_EXPIRE = 30 * 24 * 60 * 60;
    #разрешенное кол-во запросов для пользователя
    const USER_ALLOW_REQUEST_COUNT = 100;
    #период времени для разрешенного кол-во запросов 
    const USER_ALLOW_REQUEST_PER_SECOND = 600;
    #длина пароля
    const PASSWORD_LENGTH = 8;
    #длина логина
    const LOGIN_LENGTH = 10;

    
    # Языки
    const LANGUAGE_RU = 'ru'; // ru
    const LANGUAGE_KK = 'kk'; // kk
    const LANGUAGE_EN = 'en'; // en
    const LANGUAGE_DE = 'de'; // germany
    const LANGUAGE_CN = 'cn'; // china
    const LANGUAGE_UA = 'ua'; // ukrain
    const LANGUAGE_BY = 'by'; // Белоруссия
    const LANGUAGE_MD = 'md'; // moldovy
    const LANGUAGE_GE = 'ge'; // gruzy
    const LANGUAGE_AR = 'ar'; // armeny
    const LANGUAGE_UZ = 'uz'; // uzbek
    const LANGUAGE_AZ = 'az'; // azerbaydjan
    const LANGUAGE_TM = 'tm'; // turkmen
    const LANGUAGE_KG = 'kg'; // kirgiz
    const LANGUAGE_TG = 'tg'; // tadgikistan
    const LANGUAGE_EE = 'ee'; // estoy
    const LANGUAGE_LT = 'lt'; // litva
    const LANGUAGE_LV = 'lv'; // latvia
    const LANGUAGE_FR = 'fr'; // francia
    const LANGUAGE_UG = 'ug'; // yigury
    const LANGUAGE_KP = 'kp'; // koreeya 
   
}
