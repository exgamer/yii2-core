<?php
namespace core\interfaces;

interface Filterable
{
    /**
     * Переопределяйте данный метод в своей модели и применяйте к $query любый условия и тд нужные для выборки
     * @param \app\components\ActiveQuery $query
     * @return mixed
     */
    public function applyFilter(&$query);
    public function filterAttributes();

    public function applyFilterOne(&$query);
    public function filterOneAttributes();
}

?>