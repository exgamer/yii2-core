<?php

namespace core\models;

use core\interfaces\Filterable;

class Model extends \yii\base\Model implements Filterable
{

    const FILTER_SCENARIO = "filter";
    const FILTER_ONE_SCENARIO = "filter_one";

    const SCENARIO_INSERT = 'insert';
    const SCENARIO_UPDATE = 'update';

    public function scenarios()
    {
        $scenarios = array_merge(parent::scenarios(),
            [
                self::SCENARIO_INSERT => $this->attributesForSave(self::SCENARIO_INSERT),
                self::SCENARIO_UPDATE => $this->attributesForSave(self::SCENARIO_UPDATE),
                self::FILTER_SCENARIO => $this->filterAttributes(),
                self::FILTER_ONE_SCENARIO => $this->filterOneAttributes()
            ]);
        return $scenarios;

    }

    public function attributesForSave($scenario)
    {
        return $this->attributes();
    }

    public function filterAttributes()
    {
        return [];
    }

    public function filterOneAttributes()
    {
        return [];
    }

    public function applyFilter(&$query)
    {
        foreach ($this->attributes as $key => $value) {
            if ($value != null) {
                $query->andWhere([
                    $key => $value
                ]);
            }
        }
    }

    public function applyFilterOne(&$query)
    {

    }

    public function isExpandAttribute($attr)
    {
        $expand = explode(",", \Yii::$app->request->get("expand"));
        if (in_array($attr, $expand)) {
            return true;
        }
        return false;
    }


}
?>