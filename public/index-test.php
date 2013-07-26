<?php
/**
 * This is the bootstrap file for test application.
 * This file should be removed when the application is deployed for production.
 */

// change the following paths if necessary
$yii=dirname(__FILE__).'/../../yii_fw/yii.php';
$config=dirname(__FILE__).'/../protected/config/test.php';

// remove the following line when in production mode
defined('YII_DEBUG') or define('YII_DEBUG',true);

require_once($yii);
Yii::createConsoleApplication($config);
$migrate = new SchemaMigrate(Yii::app()->db, 'schema.mii');
$migrate->compare();
$migrate->save();


class SchemaMigrate {
    protected $connection;
    protected $dbSchema;
    protected $fileSchema;
    protected $fileName;
    protected $log = [];

    public function __construct(CDbConnection $connection, $fileName)
    {
        $this->connection = $connection;
        $this->fileName = Yii::getPathOfAlias('application.data') . '/' . $fileName;
        $this->preapreDbSchema();
        $this->prepareFileSchema();
    }

    protected function preapreDbSchema()
    {
        $this->dbSchema = [];
        foreach ($this->connection->getSchema()->tables as $table) {
            /**
             * @var $table CDbColumnSchema
             */
            $createTable = $this->connection->createCommand("SHOW CREATE TABLE {$table->name}")->query()->readColumn(1);
            $this->dbSchema[$table->name] = [
                'hash' => md5($createTable),
                'sql'  => $createTable
            ];
        }
    }

    protected function prepareFileSchema()
    {
        echo $this->fileName.PHP_EOL;
        if (file_exists($this->fileName.'.php')) {
            $this->fileSchema = require_once($this->fileName.'.php');
        }
    }

    public function compare()
    {
        foreach($this->dbSchema as $name => $table) {
            if (!isset($this->fileSchema[$name])) {
                $this->fileSchema[$name] = $table['sql'];
                $this->log($name,' createTable', $table['sql']);
            } elseif($table['hash'] != md5($this->fileSchema[$name])) {
                $fileTableParts = $this->getTableParts($this->fileSchema[$name]);
                $this->fileSchema[$name] = $table['sql'];
                $dbTableParts = $this->getTableParts($table['sql']);
                var_dump($dbTableParts);
                var_dump($fileTableParts);

                $this->_compare($dbTableParts, $fileTableParts, $name);


            }
        }
    }

    protected function _compare($dbParts, $fileParts, $table, $type = 'COLUMN')
    {
        foreach ($dbParts as $columnName => $part) {
            if (is_array($part)) {
                $this->_compare($part, $fileParts[$columnName], $table, '');
            } else {
                if (isset($fileParts[$columnName]) && $part != $fileParts[$columnName]) {
                    $this->log($table, 'update'.$type, "ALTER TABLE {$table } ADD {$type} " . $part);
                } elseif (!isset($fileParts[$columnName])) {
                    $this->log($table, 'add'.$type, "ALTER TABLE {$table } ADD {$type} " . $part);
                }
            }
        }
        foreach(array_keys($fileParts) as $columnName) {
            if (!isset($dbParts[$columnName])) {
                if (is_array($fileParts[$columnName])) {
                    $this->_compare(isset($dbParts[$columnName]) ? $dbParts[$columnName] : array(), $fileParts[$columnName], $table, $columnName);
                } else {
                    $this->log($table, 'drop'.$type, "ALTER TABLE {$table} DROP {$type} " . $columnName . PHP_EOL);
                }
            }
        }
    }

    protected function getColumnNameFromPart($part)
    {
        $name = "column";
        if (preg_match("/^(PRIMARY KEY|KEY)?[ ]?[`(]+([\w-]+)?`/i", trim($part), $match)) {
            if (!empty($match[1])) {
                $name = [$match[1], $match[2]];
            } else $name = $match[2];
        }
        return $name;
    }

    protected function getTableParts($sql)
    {
        $tableParts = explode("\n", $sql);
        array_pop($tableParts);
        array_shift($tableParts);
        $newParts = [];
        foreach ($tableParts as $value) {
            $value = trim($value);
            $columnName = $this->getColumnNameFromPart($value);
            if (is_array($columnName)) {
                $newParts[$columnName[0]][$columnName[1]] = substr($value,-1,1) == ',' ? substr($value, 0, strlen($value)-1) : $value;
            } else {
                $newParts[$columnName] = substr($value, 0, strlen($value)-1);
            }
        }
        return $newParts;
    }

    protected function log($table, $type, $sql)
    {
        $this->log[] = $table . "\t" . $type . "\t" . $sql;
    }

    public function save()
    {
//        var_dump($this->log);
        file_put_contents($this->fileName.'.php', "<?php \n return " . var_export($this->fileSchema, true) . ';');
        if (!empty($this->log))
            file_put_contents($this->fileName.'.log', "[" . date('d.m.Y H:i:s') . "]\n" . implode(PHP_EOL, (array)$this->log) . PHP_EOL, FILE_APPEND);
        file_put_contents($this->fileName.'.sql', '');
        foreach ($this->fileSchema as $table) {
            file_put_contents($this->fileName.'.sql', $table.';'.PHP_EOL, FILE_APPEND);
        }
    }
}