<?php
namespace Lib\Models\Db;
/**
 * This is the model class for table "content".
 *
 * The followings are the available columns in table 'content':
 * @property integer $id
 * @property integer $author
 * @property string $title
 * @property string $body
 * @property string $created
 * @property string $updated
 * @property string $state
 * @property string $visible
 */
class Content extends \CActiveRecord
{
    const STATE_PROTECTED = 'protected';
    const STATE_PRIVATE = 'private';
    const STATE_PUBLIC = 'public';
    const STATE_HIDE = 'hide';

    public function behaviors()
    {
        return [
            'fields' => [
                'class' => '\Lib\Db\ExtendFields',
                'allowedFields' => ['icon', 'rait'],
                'rules' => [
                    ['rate', 'numerical', 'integerOnly' => true]
                ]
            ]
        ];
    }


    /**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Content the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'content';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('title, body', 'required'),
			array('author', 'numerical', 'integerOnly'=>true),
			array('title', 'length', 'max'=>512),
			array('state', 'length', 'max'=>9),
			array('state', 'default', 'value'=>self::STATE_PRIVATE,  'setOnEmpty' => true),
			array('visible', 'length', 'max'=>3),
            array('visible', 'default', 'value'=>'no',  'setOnEmpty' => true),
            array('created, updated', 'createUpdateTime'),
            array('author', 'author'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, author, title, body, created, updated, state, visible', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'author' => 'Author',
			'title' => 'Title',
			'body' => 'Body',
			'created' => 'Created',
			'updated' => 'Updated',
			'state' => 'State',
			'visible' => 'Visible',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('author',$this->author);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('body',$this->body,true);
		$criteria->compare('created',$this->created,true);
		$criteria->compare('updated',$this->updated,true);
		$criteria->compare('state',$this->state,true);
		$criteria->compare('visible',$this->visible,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

    public function createUpdateTime($attribute, $params)
    {
        $value = "";
        switch ($attribute) {
            case 'created':
                    if ($this->isNewRecord)
                        $value = date('Y-m-d H:i:s');
                break;
            case 'updated':
                    $value = date('Y-m-d H:i:s');
                break;
        }
        $this->{$attribute} = $value;
    }

    public function author($attribute, $params)
    {
        $this->{$attribute} = 1;
    }
}