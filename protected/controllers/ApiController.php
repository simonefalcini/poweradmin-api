<?php

class ApiController extends Controller {

    const ERROR_VALIDATION_CODE = 1;
    const ERROR_TEMPLATE_CODE = 2;
    const ERROR_DOMAIN_NOT_FOUND = 3;
    const ERROR_SOA_UPDATE_CODE = 4;
    const ERROR_SOA_CREATE_CODE = 5;
    const ERROR_SOA_ALREADY_EXISTING_CODE = 6;
    const ERROR_DOMAIN_ALREADY_EXISTING_CODE = 7;
    const ERROR_RECORD_NOT_FOUND = 8;
    const ERROR_RECORDS_MALFORMED = 9;
    
    private $access_password = 'SPECIFY-A-PASSWORD';
    
    public $layout='//layouts/json';
    
    public function beforeAction($action) {        
        if (parent::beforeAction($action)) {
            // Check if the password parameter exists and is correct
            if (isset($_REQUEST['pass']) && $_REQUEST['pass'] == $this->access_password) {
                return true;
            }
            else {
                echo "Wrong password";
            }
        }
        return false;
    }

    /**
     * Adds a domain to the database
     * 
     * @param string $name The name of the domain
     * @param string type The type of the domain. Can be:<br/>
     * MASTER
     * NATIVE
     * @param int $template_id An optional template ID to be loaded in the domain
     * @return array {result:boolean,error_code:int,error_message:string} A json encoded array containing:
     * result: true if the domain has been created, false otherwise
     * error_code: the code of the error. 0 means no error
     * error_message: the explanation of the error
     */
    public function actionAddDomain($name, $type = Domain::TYPE_MASTER, $template_id = '') {

        $result = true;
        $error = array(
            'code' => 0,
            'message' => ''
        );

        $domain = new Domain();
        $domain->name = $name;
        $domain->type = $type;

        try {
            if (!$domain->save()) {
                $error['code'] = self::ERROR_VALIDATION_CODE;
                $error['message'] = $domain->getErrors();
                $result = false;
            } else {
                // If there is a template, let's load it and insert:
                if ($template_id != '') {
                    $records = Yii::app()->db->createCommand()->select()->from('zone_templ_records')->where('zone_templ_id = :id', array(':id' => $template_id))->queryAll();
                    $record_results = array();
                    foreach ($records as $record) {
                        $record['name'] = str_replace(array('[ZONE]','[SERIAL]'), array($name,'1'), $record['name']);
                        $record['content'] = str_replace(array('[ZONE]','[SERIAL]'), array($name,'1'), $record['content']);                        
                        $record_results[] = $this->actionAddRecord($name, $record['name'], $record['type'], $record['content'], empty($record['ttl'])?3600:$record['ttl'],empty($record['prio'])?0:$record['prio'], false, false);
                    }
                    $domain->updateSOA();
                    
                    $all_ok = true;
                    foreach ($record_results as $record_result) {
                        if ($record_result['result'] !== true)
                            $all_ok = false;
                    }
                    if ($all_ok) {
                        $result = true;
                    } else {
                        $result = false;
                        $error['code'] = self::ERROR_TEMPLATE_CODE;
                        $error['message'] = $record_results;
                    }
                }
            }
        } catch (CDbException $e) {
            if ($e->getCode()==23000) {
                // Domain already existing
                $error['code'] = self::ERROR_DOMAIN_ALREADY_EXISTING_CODE;
                $error['message'] = "Domain already existing";
            }
            else {
                $error['code'] = $e->getCode();
                $error['message'] = $e->getMessage();
            }
            $result = false;
        }

        $var = array(
            'error_code' => $error['code'],
            'error_message' => $error['message'],
            'result' => $result
        );
        $this->renderText(
                CJSON::encode($var)
        );
        return $var;
    }

    public function actionIndex() {
        $this->renderText('Hello');
    }

    /**
     * Adds a record to an existing database
     * @param string $domain The domain name (without www)
     * @param string $name The name of the record
     * @param string $type The type of the record. Can be:<br>
     * A
     * AAAA
     * CNAME
     * HINFO
     * MX
     * NAPTR
     * NS
     * PTR
     * SOA
     * SPF
     * SRV
     * SSHFP
     * TXT
     * RP
     * @param string $content The content of the record
     * @param string $ttl The expiration time of the record in seconds. Defaults to <b>86400</b>
     * @param int $prio The priority of the record. Defaults to <b>0</b>
     * @param boolean $autoUpdateSOA Set to <b>true</b> if you want to update automatically the SOA record.
     * @param boolean $_output
     * If set to <b>false</b> you need to update the SOA record manually
     * @return array {result:boolean,error_code:int,error_message:string} A json encoded array containing:
     * result: true if the record has been created, false otherwise
     * error_code: the code of the error. 0 means no error
     * error_message: the explanation of the error
     */
    public function actionAddRecord($domain, $name, $type, $content, $ttl = 86400, $prio = 0, $autoUpdateSOA = true, $_output = true) {

        $result = true;
        $error = array(
            'code' => 0,
            'message' => ''
        );

        $domain = Domain::model()->findByAttributes(array('name' => $domain));

        if (isset($domain)) {
            $record = new Record();
            $record->domain_id = $domain->id;
            $record->name = $name;
            $record->type = $type;
            $record->content = $content;
            $record->ttl = $ttl;
            $record->prio = $prio;
            if (!$record->save()) {
                $error['code'] = self::ERROR_VALIDATION_CODE;
                $error['message'] = $record->getErrors();
                $result = false;
            } else {
                if ($autoUpdateSOA) {
                    $domain->updateSOA();
                }
            }
        } else {
            $result = false;
            $error['code'] = self::ERROR_DOMAIN_NOT_FOUND;
            $error['message'] = 'Domain not found';
        }

        $var = array(
            'error_code' => $error['code'],
            'error_message' => $error['message'],
            'result' => $result
        );
        if ($_output) {
            $this->renderText(
                    CJSON::encode($var)
            );
        }
        return $var;
    }
    
    /**
     * List all records for a specific domain
     * @param String $domain The domain to select     
     * @return array {result:boolean,error_code:int,error_message:string} A json encoded array containing:
     * result: an array with the record details
     *   {"name":"test.com","type":"CNAME","content":"domee.elasticbeanstalk.com","ttl":"3600","prio":"0","change_date":null}
     * false otherwise
     * error_code: the code of the error. 0 means no error
     * error_message: the explanation of the error
     */
    public function actionListRecords($domain, $type=null, $_output = true) {
        
        $result = true;
        $error = array(
            'code' => 0,
            'message' => ''
        );
        
        $criteria = new CDbCriteria();
        $criteria->compare('domain.name', $domain);
        $criteria->with = array('domain');        
        if (!empty($type)) {
            $criteria->compare('t.type',$type);
        }
        $criteria->compare('t.type', '<>SOA');
        
        $records = Record::model()->findAll($criteria);
        if (isset($records)) {
            $result = array();
            foreach($records as $record) {
                $row = $record->attributes;
                unset($row['id'],$row['domain_id']);
                $result[] = $row;
            }            
        }        
        
        $var = array(
            'error_code' => $error['code'],
            'error_message' => $error['message'],
            'result' => $result
        );
        if ($_output) {
            $this->renderText(
                    CJSON::encode($var)
            );
        }
        return $var;
    }
    
    /**
     * Edit a specific record
     * @param string $domain The domain (zone)
     * @param string $name The record to edit
     * @param type $type The type of record to edit     
     * @param type $content The new content to set
     * @param type $old_content The content of record to edit [optional]
     * @param type $ttl The new ttl to set
     * @param type $prio The new priority to set
     * @param type $_output
     * @return array {result:boolean,error_code:int,error_message:string} A json encoded array containing:
     * result: true if the domain has been created, false otherwise
     * error_code: the code of the error. 0 means no error
     * error_message: the explanation of the error
     */
    public function actionEditRecord($domain, $name, $type, $content, $old_content = null, $ttl = null, $prio = null, $autoUpdateSOA = true, $_output = true) {
        
        $result = true;
        $error = array(
            'code' => 0,
            'message' => ''
        );
        
        $criteria = new CDbCriteria();
        $criteria->compare('domain.name', $domain);
        $criteria->compare('t.name', $name);
        $criteria->compare('t.type', $type);
        if (isset($old_content)) {
            $criteria->compare('t.content', $old_content);
        }
        $record = Record::model()->with('domain')->find($criteria);
        if (isset($record)) {
            $record->content = $content;
            if (isset($ttl)) {
                $record->ttl = $ttl;
            }
            if (isset($prio)) {
                $record->prio = $prio;
            }
            if (!$record->update()) {
                $error['code'] = self::ERROR_VALIDATION_CODE;
                $error['message'] = $record->getErrors();
                $result = false;
            }
            else {
                if ($autoUpdateSOA) {
                    $record->domain->updateSOA();
                }
            }
        }
        else {
            $error['code'] = self::ERROR_RECORD_NOT_FOUND;
            $error['message'] = 'Record not found';
            $result = false;
        }
        
        $var = array(
            'error_code' => $error['code'],
            'error_message' => $error['message'],
            'result' => $result
        );
        if ($_output) {
            $this->renderText(
                    CJSON::encode($var)
            );
        }
        return $var;
    }
    
    public function actionEditBulkRecords($domain, $records, $autoUpdateSOA = true, $_output = true) {
        $result = true;
        $error = array(
            'code' => 0,
            'message' => ''
        );
        $records = @json_decode($records,true);
        
        if (isset($records) && count($records)>0) {
            
            $domain = Domain::model()->findByAttributes(array('name'=>$domain));
            if (!isset($domain)) {
                $error['code'] = self::ERROR_DOMAIN_NOT_FOUND;
                $error['message'] = 'Domain not found';
                $result = false;
            }
            else {                
                // Clear all records for that domain      
                $criteria = new CDbCriteria();
                $criteria->compare('domain_id', $domain->id);
                $criteria->compare('type', '<>SOA');
                Record::model()->deleteAll($criteria);
                foreach($records as $record) {
                    $r = new Record('insert');
                    $r->name = $record['name'];
                    $r->content = $record['content'];
                    $r->type = $record['type'];
                    $r->domain_id = $domain->id;
                    if (isset($record['ttl'])) {
                        $r->ttl = $record['ttl'];
                    }
                    if (isset($record['prio'])) {
                        $r->prio = $record['prio'];
                    }
                    if (!$r->save()) {
                        $error['code'] = self::ERROR_VALIDATION_CODE;
                        $error['message'] = $record->getErrors();
                        $result = false;
                        break;
                    }                    
                }
                
                if ($autoUpdateSOA) {
                    $domain->updateSOA();
                }
                
            }            
        }
        else {
            $error['code'] = self::ERROR_RECORDS_MALFORMED;
            $error['message'] = 'Record array malformed';
            $result = false;
        }
        
        
        $var = array(
            'error_code' => $error['code'],
            'error_message' => $error['message'],
            'result' => $result
        );
        if ($_output) {
            $this->renderText(
                    CJSON::encode($var)
            );
        }
        return $var;
        
    }
    
    /**
     * Delete a domain
     * 
     * @param string $domain Domain name
     * @return array {result:boolean,error_code:int,error_message:string} A json encoded array containing:
     * result: true if the domain has been created, false otherwise
     * error_code: the code of the error. 0 means no error
     * error_message: the explanation of the error
     */
    public function actionDeleteDomain($domain, $_output=true) {
        $result = true;
        $error = array(
            'code' => 0,
            'message' => ''
        );
        
        $domain = Domain::model()->deleteAllByAttributes(array('name'=>$domain));
        
        if ($domain==0) {
            $error['code'] = self::ERROR_DOMAIN_NOT_FOUND;
            $error['message'] = 'Domain not found';
            $result = false;
        }
        
        $var = array(
            'error_code' => $error['code'],
            'error_message' => $error['message'],
            'result' => $result
        );
        
        if ($_output) {
            $this->renderText(
                    CJSON::encode($var)
            );
        }
        return $var;
    }
    
    /**
     * Delete specific records
     * @param string $domain The domain to select
     * @param string $name The record name
     * @param string $type The record type
     * @param string $type The content to delete [optional]
     * @return array {result:boolean,error_code:int,error_message:string} A json encoded array containing:
     * result: true if the domain has been created, false otherwise
     * error_code: the code of the error. 0 means no error
     * error_message: the explanation of the error
     */
    public function actionDeleteRecord($domain,$name,$type,$content=null,$_output = true) {
        $result = true;
        $error = array(
            'code' => 0,
            'message' => ''
        );

        $domain = Domain::model()->findByAttributes(array('name' => $domain));

        if (isset($domain)) {
            $attr = array('name'=>$name,'type'=>$type,'domain_id'=>$domain->id);
            if (isset($content)) {
                $attr['content'] = $content;
            }
            $records = Record::model()->deleteAllByAttributes($attr);
            if ($records==0) {
                $error['code'] = self::ERROR_RECORD_NOT_FOUND;
                $error['message'] = 'Record not found';
                $result = false;
            }
        }
        else {
            $error['code'] = self::ERROR_DOMAIN_NOT_FOUND;
            $error['message'] = 'Domain not found';
            $result = false;
        }
        
        $var = array(
            'error_code' => $error['code'],
            'error_message' => $error['message'],
            'result' => $result
        );
        
        if ($_output) {
            $this->renderText(
                    CJSON::encode($var)
            );
        }
        return $var;
    }

    public function actionError() {
        if ($error = Yii::app()->errorHandler->error) {
            $var = array(
                'error_code' => $error['code'],
                'error_message' => $error['message'],
                'result' => false
            );
            $this->renderText(
                    CJSON::encode($var)
            );
        }
    }
    
    public function actionDisableDomain($domain, $fallback_ip, $_output = true) {
        
        $result = true;
        $error = array(
            'code' => 0,
            'message' => ''
        );
        
        $domain = Domain::model()->findByAttributes(array('name'=>$domain));
        if (!isset($domain)) {
            $result = false;
            $error['code'] = self::ERROR_DOMAIN_NOT_FOUND;
            $error['message'] = 'Domain not found';
        }
        else {
            $dd = new DisabledDomain();
            $records = $this->actionListRecords($domain->name,null,false);
            if (isset($records['result'])) {
                $dd->records = serialize($records['result']);
                $dd->domain = $domain->name;
                if (!$dd->save()) {
                    $result = false;
                    $error['code'] = 999;
                    $error['message'] = print_r($dd->getErrors(),true);
                }
                else {
                    Record::model()->deleteAllByAttributes(array('domain_id'=>$domain->id));
                    $this->actionAddRecord($domain->name, $domain->name, Record::TYPE_A, $fallback_ip, 86400, 0, false, false);
                    $this->actionAddRecord($domain->name, 'www.'.$domain->name, Record::TYPE_A, $fallback_ip, 86400, 0, true, false);                    
                    //$domain->delete();
                }
            }
            else {
                $result = false;
                $error['code'] = 999;
                $error['message'] = "Cannot get record list";
            }
        }
        
        $var = array(
            'error_code' => $error['code'],
            'error_message' => $error['message'],
            'result' => $result
        );
        if ($_output) {
            $this->renderText(
                    CJSON::encode($var)
            );
        }
        return $var;
        
    }
    
    public function actionEnableDomain($domain, $_output=true) {
        $result = true;
        $error = array(
            'code' => 0,
            'message' => ''
        );
                
        $dd = DisabledDomain::model()->findByAttributes(array('domain'=>$domain));
        if (!isset($dd)) {
            $result = false;
            $error['code'] = self::ERROR_DOMAIN_NOT_FOUND;
            $error['message'] = 'Domain not found';
        }
        else {
            $records = @unserialize($dd->records);
            if (!is_array($records)) {
                $result = false;
                $error['code'] = self::ERROR_RECORD_NOT_FOUND;
                $error['message'] = 'Cannot recover records';
            }
            else {
                // Remove fallback records                
                $domain = Domain::model()->findByAttributes(array('name'=>$dd->domain));                
                Record::model()->deleteAllByAttributes(array('domain_id'=>$domain->id));
                
                $resp = $this->actionEditBulkRecords($dd->domain, json_encode($records), true, false);
                if ($resp['result']===true) {
                    $dd->delete();                    
                }
                else {
                    $error['code'] = $resp['error_code'];
                    $error['message'] = $resp['error_message'];
                    $result = $resp['result'];
                }                                
            }
            
        }
        
        $var = array(
            'error_code' => $error['code'],
            'error_message' => $error['message'],
            'result' => $result
        );
        if ($_output) {
            $this->renderText(
                    CJSON::encode($var)
            );
        }
        return $var;
    }

}