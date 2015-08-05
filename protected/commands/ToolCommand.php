<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ToolCommand
 *
 * @author simone
 */
class ToolCommand extends CConsoleCommand {
    
    public function actionIndex() {
        $ips = array('95.110.192.200','95.110.193.249','95.110.204.16','95.110.204.20','69.175.126.90','69.175.126.94','46.4.96.70','46.4.96.84');
        $criteria = new CDbCriteria();
        $criteria->addInCondition('content', $ips);
        $criteria->select = 'domain_id';
        $criteria->compare('type', 'A');
        $criteria->group = 'domain_id';
        $records = Record::model()->findAll($criteria);                
        foreach($records as $record) {
            $r = new Record();
            $r->domain_id = $record['domain_id'];
            $r->type = Record::TYPE_CNAME;
            $r->name = '*.'.$record->domain->name;
            $r->content = $record->domain->name;
            $r->ttl = '86400';
            if (!$r->save()) {
                echo "\nCannot save duplicate record to domain ".$r->domain->name;
            }
            else {
                echo "\nRecord * added to domain ".$r->domain->name;
                $record->domain->updateSOA();
            }
        }
        echo "\n";
        
    }
}
