<?php

namespace core\forms\v2;

/**
 * Базовый интерфейс для базовой модели
 *
 * @package admin\search\base
 */
interface IHaveService
{
    /**
     * основной сервис дял работы с моделью
     * 
     * @example return service;
     */
    public static function getBaseService();
}