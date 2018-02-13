<?php

namespace core\interfaces;

/**
 * Базовый интерфейс для фильтров
 *
 * @package admin\search\base
 */
interface IBaseSearch
{
    /**
     * Общий метод получения отфильтрованных данных
     *
     * @param $params - параметры фильтрации
     *
     * @return ActiveDataProvider
     */
    public function search($params);
    
   /**
    * Добавления условий к запросу
    *
    * @param ActiveQuery $query
    */
    public function addFilters(&$query);
}