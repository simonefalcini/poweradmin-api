<?php

/**
 * This is the model class for table "disabled_domain".
 *
 * The followings are the available columns in table 'disabled_domain':
 * @property integer $id
 * @property string $domain
 * @property string $records
 * @property string $disabled_timestamp
 */
class DisabledDomain extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return DisabledDomain the static model class
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
		return 'disabled_domain';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('domain, records', 'required'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, domain_id, records, disabled_timestamp', 'safe', 'on'=>'search'),
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
			'domain_id' => 'Domain',
			'records' => 'Records',
			'disabled_timestamp' => 'Disabled Timestamp',
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
		$criteria->compare('domain_id',$this->domain_id);
		$criteria->compare('records',$this->records,true);
		$criteria->compare('disabled_timestamp',$this->disabled_timestamp,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}