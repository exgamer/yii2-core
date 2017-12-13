<?php

namespace core\filters;

use yii\base\ActionFilter;
use yii\web\MethodNotAllowedHttpException;

class TokenAccessFilter extends ActionFilter
{
    public function beforeAction($action)
    {

        $token = \Yii::$app->request->getHeaders()->get("X-TOKEN");

        $data = \Yii::$app->request->getBodyParams();

        $_token = "";
        foreach ($data as $k) {
            $_token .= !is_array($k) ? $k : json_encode($k);
        }
        $_token .= POLL_SECRET_WORD;
        $_token = md5($_token);

        if ($_token !== $token) {
            throw new MethodNotAllowedHttpException("WRONG TOKEN");
        }

        return parent::beforeAction($action);
    }

}