<?php
namespace core\analytics;

use core\analytics\Query;
use MongoDB\BSON\UTCDateTime;

/**
 * FOR mongo DB
 * 
 * @author CitizenZet <exgamer@live.ru>
 */
abstract class MongoQuery extends Query
{
    /**
     * @see core\analytics\Query
     */
    public function getByDateParams(&$params)
    {
        $params['date'] = [
            '$gte' => new UTCDateTime (( new \DateTime (date('Y-m-d H:i:s' , strtotime($this->dateTo." 00:00:00"))))),
            '$lt' => new UTCDateTime (( new \DateTime (date('Y-m-d H:i:s' , strtotime($this->dateTo." 00:00:00" . " +1 days")))))
        ]; 
    }

}
