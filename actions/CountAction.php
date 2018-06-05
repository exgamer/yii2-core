<?php
namespace core\actions;

use yii\base\Action;

/**
 * экшен для полученя количества
 * 
 * @author CitizenZet
 */
class CountAction extends Action 
{
    public function run()
    {
        $query = $this->controller->prepareQuery();

        return $query->count();
    }
}