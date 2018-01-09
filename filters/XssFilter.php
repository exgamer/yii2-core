<?php
namespace core\filters;

use yii\base\ActionFilter;

/**
 * XssFilter
 *
 * @property array $data
 */
class XssFilter extends ActionFilter
{
    public $data;

    public function beforeAction($action)
    {

        //Yii::$app->request->setBodyParams($this->filter($this->data));
        //var_dump($this->filter($this->data)); die;
        return parent::beforeAction($action);
    }

    public function afterAction($action, $result)
    {
        return parent::afterAction($action, $result);
    }

    /**
     * @param array $data
     * @return array
     */
    private function filter(array $data)
    {
        foreach ($data as $key => $item) {
            if (is_array($item)) {
                $data[$key] = $this->filter($item);
                continue;
            }

            if (!is_array($data[$key])) {
                $data[$key] = is_null($item) ? null : htmlspecialchars($item);
            }
        }

        return $data;
    }

}