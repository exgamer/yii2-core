<?php
namespace core\formatters;

use yii\web\JsonResponseFormatter;

class JsonGzipFormatter extends JsonResponseFormatter
{

    public function formatJson($response)
    {
        parent::formatJson($response);

        if (\Yii::$app->request->headers->get("X-COMPRESS") == true) {
            $start = microtime(true);
            \Yii::$app->response->headers->set("Content-Encoding", "gzip");
            $response->content = gzencode($response->content);
            $time = microtime(true) - $start;
            \Yii::trace("GZIPPED in $time second.");
        }

    }

}