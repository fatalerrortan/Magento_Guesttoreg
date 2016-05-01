<?php

class Nextorder_Guesttoreg_Helper_Data extends Mage_Core_Helper_Abstract{


    public function _getOrdersInVerdacht(){

        $options = array();
        $base_path = Mage::getBaseDir('base');
        $orgin_string = file_get_contents($base_path."/var/new_customer/customer_verdacht.txt");
        $objects = explode('&',$orgin_string);
        foreach($objects as $object){
            $options[] = array("value"=>(int)strstr($object, "@", true), "label"=>strstr($object, "@", true));
        }

        return $options;
    }
}
?>