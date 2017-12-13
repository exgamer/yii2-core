<?php
namespace core\db;

use core\db\Command;

class Connection extends \yii\db\Connection
{

    /**
     * Creates a command for execution.
     * @param string $sql the SQL statement to be executed
     * @param array $params the parameters to be bound to the SQL statement
     * @return Command the DB command
     */
    public function createCommand($sql = null, $params = [])
    {
        $command = new Command([
            'db' => $this,
            'sql' => $sql,
        ]);

        $command->bindValues($params);

        \Yii::trace("CREATING COMMAND");
        if (\Yii::$app->cache->getIsCaching()) {
            \Yii::trace("CACHING IS ON");
            return $command->cache(3600);
        }
        return $command->noCache();
    }

}
