<?php
/**
 * Created by JetBrains PhpStorm.
 * User: chipx
 * Date: 02.08.13
 * Time: 18:01
 * To change this template use File | Settings | File Templates.
 */

namespace Lib\Db;


use CEvent;
use CException;
use CList;
use CModelEvent;
use Yii;

class ExtendFields extends \CActiveRecordBehavior
{
    const TABLE_SUFFIX = '_fields';
    public $fieldsTable;
    public $linkColumn;
    public $allowedFields;
    public $rules;

    protected $errors = [];
    protected $fields;
    protected $modFiled = ['insert' => [], 'update' => [], 'delete' => []];
    protected $_validators;
    public function afterFind($event)
    {
        $command = $this->owner->dbConnection->createCommand();
        $command->select()->from($this->getFieldsTable())->where('parent = :p', [':p' => $this->owner->{$this->getLinkColumn()}]);
        if (is_array($this->allowedFields) && !empty($this->allowedFields)) {
            $command->where(['in', 'name', $this->allowedFields]);
        }
        $this->prepareFields($command->query());
    }


    protected function prepareFields(\CDbDataReader $data)
    {
        while ($row = $data->read()) {
            if (!isset($this->fields[$row['name']])) {
                $this->fields[$row['name']] = [$row['id'] => $row['value']];
            } else {
                $this->fields[$row['name']][$row['id']] =  $row['value'];
            }
        }
    }

    public function __get($name)
    {
        if (isset($this->fields[$name])) {
            if (count($this->fields[$name]) == 1) {
                return current($this->fields[$name]);
            } else {
                return $this->fields[$name];
            }
        } else {
            return parent::__get($name); // TODO: Change the autogenerated stub
        }
    }

    public function __set($name, $value)
    {
        if ($this->isFieldAllow($name)) {
            if (isset($this->fields[$name])) {
                $id = key($this->fields[$name]);
                $type = 'update';
            } else {
                $id = 0;
                $type = 'insert';
            }
            $this->modFiled[$type][] = $name;
            $this->fields[$name][$id] = $value;
        } else {
            throw new ExtendFieldsException('Field "' . $name . '" is not allowed', 1);
        }
    }

    /**
     * @return mixed
     */
    public function getFieldsTable()
    {
        if (!$this->fieldsTable) {
            $this->fieldsTable = $this->owner->tableName() . self::TABLE_SUFFIX;
        }
        return $this->fieldsTable;
    }

    /**
     * @return mixed
     */
    public function getLinkColumn()
    {
        if (!$this->linkColumn) {
            $this->linkColumn = (string)$this->owner->getTableSchema()->primaryKey;
        }
        return $this->linkColumn;
    }

    protected function isFieldAllow($name)
    {
        return in_array($name, $this->allowedFields);
    }

    public function afterSave($event)
    {
        $this->insertFields();
        $this->updateFields();
    }

    protected function insertFields()
    {
        $db = $this->owner->dbConnection;
        $command = $db->createCommand();
        foreach ($this->modFiled['insert'] as $name) {
            $field = [
                'parent'    => $this->owner->{$this->getLinkColumn()},
                'value'     => current($this->fields[$name]),
                'name'      => $name
            ];
            $command->insert($this->getFieldsTable(), $field);
            $this->owner->dbConnection->getLastInsertID();
            $this->fields[$name][$db->getLastInsertID()] = $this->fields[$name][0];
            unset($this->fields[$name][0]);
        }
        $this->modFiled['insert'] = [];
    }

    protected function updateFields()
    {
        $command = $this->owner->dbConnection->createCommand();

        foreach ($this->modFiled['update'] as $name) {
            $id = key($this->fields[$name]);
            $command->update($this->getFieldsTable(), ['value' => current($this->fields[$name])], 'id = :id', [':id' => $id]);
        }

        $this->modFiled['update'] = [];
    }

    public function beforeValidate($event)
    {
        if (is_array($this->rules)) {
            $event->isValid = $this->validate('insert') && $this->validate('update');
        }

    }

    protected function validate($type)
    {
        $ret = true;
        $fields = $this->modFiled[$type];
        if (!empty($fields)) {
            foreach($this->getValidators($type) as $validator) {
                $validator->validate($this, $fields);
            }
            foreach ($fields as $field) {
                if (isset($this->errors[$field])) {
                    $ret = false;
                }
            }
        }
        return $ret;
    }

    public function createValidators()
    {
        $validators=new CList;
        foreach($this->rules as $rule)
        {
            if(isset($rule[0],$rule[1]))  // attributes, validator name
            $validators->add(\CValidator::createValidator($rule[1],$this->getOwner(),$rule[0],array_slice($rule,2)));
            else
                throw new CException(Yii::t('yii','{class} has an invalid validation rule. The rule must specify attributes to be validated and the validator name.',
                    array('{class}'=>get_class($this))));
        }
        return $validators;
    }

    public function getValidators($scenario, $attribute=null)
    {
        if($this->_validators===null)
            $this->_validators=$this->createValidators();

        $validators=array();
        foreach($this->_validators as $validator)
        {
            if($validator->applyTo($scenario))
            {
                if($attribute===null || in_array($attribute,$validator->attributes,true))
                    $validators[]=$validator;
            }
        }
        return $validators;
    }

    public function addError($field, $message)
    {
        $this->errors[$field] = $message;
    }
}

class ExtendFieldsException extends \Exception
{

}