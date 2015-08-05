<?php

/**
 * This is the model class for table "records".
 *
 * The followings are the available columns in table 'records':
 * @property integer $id
 * @property integer $domain_id
 * @property string $name
 * @property string $type
 * @property string $content
 * @property integer $ttl
 * @property integer $prio
 * @property integer $change_date
 */
class Record extends CActiveRecord {
    
    const TYPE_A = 'A';
    const TYPE_AAAA = 'AAAA';
    const TYPE_CNAME = 'CNAME';
    const TYPE_HINFO = 'HINFO';
    const TYPE_MX = 'MX';
    const TYPE_NAPTR = 'NAPTR';
    const TYPE_NS = 'NS';
    const TYPE_PTR = 'PTR';
    const TYPE_SOA = 'SOA';
    const TYPE_SPF = 'SPF';
    const TYPE_SRV = 'SRV';
    const TYPE_SSHFP = 'SSHFP';
    const TYPE_TXT = 'TXT';
    const TYPE_RP = 'RP';

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return Record the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'records';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('domain_id, ttl, prio, change_date', 'numerical', 'integerOnly' => true),
            array('name, content', 'length', 'max' => 255),
            array('type', 'length', 'max' => 6),
            array('name', 'recordUniqueness'),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, domain_id, name, type, content, ttl, prio, change_date', 'safe', 'on' => 'search'),
        );
    }
    
    public function recordUniqueness($attribute,$params) {
        $count = 0;
        switch($this->type) {
            case self::TYPE_SOA:
                $count = self::model()->countByAttributes(array('type'=>$this->type,'domain_id'=>$this->domain_id));
                break;
            case self::TYPE_CNAME:
                $count = self::model()->countByAttributes(array('name'=>$this->name,'type'=>$this->type,'domain_id'=>$this->domain_id));
                break;
            case self::TYPE_A: 
            case self::TYPE_TXT:
            case self::TYPE_NS:
                $count = self::model()->countByAttributes(array('name'=>$this->name,'type'=>$this->type,'content'=>$this->content,'domain_id'=>$this->domain_id));
                break;
            case self::TYPE_MX:
                $count = self::model()->countByAttributes(array('name'=>$this->name,'type'=>$this->type,'content'=>$this->content,'prio'=>$this->prio,'domain_id'=>$this->domain_id));
                break;
        }        
        
        if ($count > 0) {
            $this->addError($attribute, 'cannot add duplicate record. name: '.$this->name.'. type: '.$this->type);
        }
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'domain' => array(self::BELONGS_TO, 'Domain', 'domain_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'domain_id' => 'Domain',
            'name' => 'Name',
            'type' => 'Type',
            'content' => 'Content',
            'ttl' => 'Ttl',
            'prio' => 'Prio',
            'change_date' => 'Change Date',
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
        $criteria->compare('domain_id', $this->domain_id);
        $criteria->compare('name', $this->name, true);
        $criteria->compare('type', $this->type, true);
        $criteria->compare('content', $this->content, true);
        $criteria->compare('ttl', $this->ttl);
        $criteria->compare('prio', $this->prio);
        $criteria->compare('change_date', $this->change_date);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }
    
    public function beforeSave() {
        if ($this->type == self::TYPE_CNAME) {
            // The name must contain the domain (www.domain.com NOT JUST www)
            if (stripos($this->name, $this->domain->name)===false) {
                $this->name = $this->name.'.'.$this->domain->name;
            }
        }
        return parent::beforeSave();
    }

}