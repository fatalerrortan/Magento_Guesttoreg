<?php

class Nextorder_Guesttoreg_Helper_Data extends Mage_Core_Helper_Abstract{


    public function _nameCheck($ref, $param){

        $compareArray = array();
        $customerCollection = Mage::getModel('customer/customer')->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter($ref, array('like' => $param));

        foreach($customerCollection as $customer){

            $rechnung_Lastname = $customer->getPrimaryBillingAddress()->getData("lastname");
            if((!empty($rechnung_Lastname)) && ($rechnung_Lastname != $customer->getData($ref))){
                $compareArray[] = $rechnung_Lastname;
            }else{
                $compareArray[] = $param;
            };
        }

        return $compareArray;
    }
}
?>