<?php

/**
 * This is the model class for table "domains".
 *
 * The followings are the available columns in table 'domains':
 * @property integer $id
 * @property string $name
 * @property string $master
 * @property integer $last_check
 * @property string $type
 * @property integer $notified_serial
 * @property string $account
 */
class Domain extends CActiveRecord {

    const TYPE_MASTER = 'MASTER';
    const TYPE_NATIVE = 'NATIVE';

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return Domain the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'domains';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('name, type', 'required'),
            array('last_check, notified_serial', 'numerical', 'integerOnly' => true),
            array('name', 'length', 'max' => 255),
            array('name','unique'),
            array('master', 'length', 'max' => 128),
            array('type', 'length', 'max' => 6),
            array('account', 'length', 'max' => 40),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, name, master, last_check, type, notified_serial, account', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'name' => 'Name',
            'master' => 'Master',
            'last_check' => 'Last Check',
            'type' => 'Type',
            'notified_serial' => 'Notified Serial',
            'account' => 'Account',
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search() {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $criteria = new CDbCriteria;

        $criteria->compare('id', $this->id);
        $criteria->compare('name', $this->name, true);
        $criteria->compare('master', $this->master, true);
        $criteria->compare('last_check', $this->last_check);
        $criteria->compare('type', $this->type, true);
        $criteria->compare('notified_serial', $this->notified_serial);
        $criteria->compare('account', $this->account, true);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    /**
     * Transforms a SOA content record in an array with the fields
     * @param string $content
     * @return array
     */
    public static function soaContentToArray($content) {
        $array = explode(' ', $content);
        return array(
            'name_server' => $array[0],
            'email_address' => $array[1],
            'serial_number' => $array[2],
            'refresh_time' => $array[3],
            'retry_time' => $array[4],
            'expiry_time' => $array[5],
            'nx_time' => $array[6],
        );
    }

    /**
     * Transforms an array to a SOA content record
     * @param string $content
     * @return array
     */
    public static function soaArrayToContent($array) {
        return $array['name_server'] . ' ' .
                $array['email_address'] . ' ' .
                $array['serial_number'] . ' ' .
                $array['refresh_time'] . ' ' .
                $array['retry_time'] . ' ' .
                $array['expiry_time'] . ' ' .
                $array['nx_time'];
    }

    public function updateSOA() {
        // We need to create/update the SOA record
        // First, we check if the SOA record already exists
        $recordSoa = Record::model()->findByAttributes(array('domain_id' => $this->id, 'type' => Record::TYPE_SOA));
        if (isset($recordSoa)) {
            // The record exists, we need to update the serial                        
            $array = self::soaContentToArray($recordSoa->content);
            $array['serial_number'] = intval($array['serial_number']) + 1;
            $recordSoa->content = self::soaArrayToContent($array);
            if (!$recordSoa->update(array('content'))) {
                // We couldn't update the SOA record
                $result = false;
                $error['code'] = self::ERROR_SOA_UPDATE_CODE;
                $error['message'] = $recordSoa->getErrors();
            }
        } else {
            $this->addSOA();
        }
    }

    private function addSOA() {
        $domain = $this;

        // check if SOA already exists
        if (Record::model()->countByAttributes(array('domain_id' => $domain->id, 'type' => Record::TYPE_SOA)) == 0) {
            $recordSoa = new Record();
            $recordSoa->name = $domain->name;
            $recordSoa->type = Record::TYPE_SOA;
            $recordSoa->domain_id = $domain->id;
            $recordSoa->content = Domain::soaArrayToContent(
                            array(
                                'name_server' => Yii::app()->params['soa_defaults']['name_server'],
                                'email_address' => Yii::app()->params['soa_defaults']['email_address'],
                                'serial_number' => '1',
                                'refresh_time' => Yii::app()->params['soa_defaults']['refresh_time'],
                                'retry_time' => Yii::app()->params['soa_defaults']['retry_time'],
                                'expiry_time' => Yii::app()->params['soa_defaults']['expiry_time'],
                                'nx_time' => Yii::app()->params['soa_defaults']['nx_time'],
                            )
            );
            $recordSoa->ttl = Yii::app()->params['soa_defaults']['expiry_time'];
            $recordSoa->prio = 0;
            if (!$recordSoa->save()) {
                Yii::log('Cannot add SOA: ' . print_r($recordSoa->getErrors(),true), CLogger::LEVEL_ERROR);
                return false;
            }
        } else {
            Yii::log('SOA record already existing', CLogger::LEVEL_ERROR);
            return false;
        }        

        return true;
    }

}
