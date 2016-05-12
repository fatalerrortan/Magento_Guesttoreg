
<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 14.04.16
 * Time: 12:00
 */

 class Nextorder_Guesttoreg_Block_Adminhtml_Sales_Orderassign extends Mage_Adminhtml_Block_Template{

     public function loadOrderCollection(){

         $inkreId = $this->getRequest()->getParam('orderid');
         $order = Mage::getModel("sales/order")->loadByIncrementId($inkreId);
         $billingID = $order->getBillingAddress()->getId();
         $addressForOrder = Mage::getModel('sales/order_address')->load($billingID);

         return array(
             'inkrementId' => $inkreId,
             'vorname' => $addressForOrder->getData("firstname"),
             'nachname' => $addressForOrder->getData("lastname"),
             'email' => $addressForOrder->getData("email"),
             'telefon' => $addressForOrder->getData("telephone"),
             'street' => $addressForOrder->getStreet(1).$addressForOrder->getStreet(2).$addressForOrder->getStreet(3),
             'postcode' => $addressForOrder->getData("postcode"),
             'city' => $addressForOrder->getData("city"),
             'country' => $addressForOrder->getCountry(),
          );
     }

     public function getSusCustomers(){

         $inkreId = $this->getRequest()->getParam('orderid');
         $base_path = Mage::getBaseDir('base');
         $orgin_string = file_get_contents($base_path."/var/new_customer/customer_verdacht.txt");
         $string_to_array = explode('&',$orgin_string);
         foreach($string_to_array as $item){

             if(strstr($item,"@",true) == $inkreId){
                 $customerIdArray = explode(',',substr(strstr($item,"@"), 1));
                 return array($customerIdArray, $item);
             }
         }
     }

     public function getCustomerDetails($customerId){
         ;
         $customer = Mage::getModel('customer/customer')->load($customerId);
         $billingAddress = $customer->getPrimaryBillingAddress();
        if($billingAddress == false){
            return array(
                'vorname' => $customer->getData("firstname"),
                'nachname' => $customer->getData("lastname"),
                'email' => $customer->getData("email"),
                'telefon' => $customer->getData("telephone"),
                'street' => "No Default Address",
                'postcode' => "No Default Address",
                'city' => "No Default Address",
                'country' => "No Default Address",
            );
        }else{
            return array(
                'vorname' => $billingAddress->getData("firstname"),
                'nachname' => $billingAddress->getData("lastname"),
                'email' => $customer->getData("email"),
                'telefon' => $billingAddress->getData("telephone"),
                'street' => $billingAddress->getStreet(1).$billingAddress->getStreet(2).$billingAddress->getStreet(3),
                'postcode' => $billingAddress->getData("postcode"),
                'city' => $billingAddress->getData("city"),
                'country' => $billingAddress->getCountry(),
            );
        }
     }
 }
