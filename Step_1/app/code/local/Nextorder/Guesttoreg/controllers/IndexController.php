<?php
/**
 * Created by PhpStorm.
 * User: tiemanntan
 * Date: 07/10/15
 * Time: 14:04
 */
    class Nextorder_Guesttoreg_IndexController extends Mage_Core_Controller_Front_Action{

        public function indexAction(){
            $shop_customer_names =array();
            $shop_customers = Mage::getModel('customer/customer')->getCollection();
            $index_customers = count($shop_customers);

            for($i = 1;$i <= $index_customers; $i++){

                $billingAddress = Mage::getModel('customer/customer')->load($i)->getPrimaryBillingAddress();
                if(empty($billingAddress)){
                    $shop_customer_names[$i]['first'] = '';
                    $shop_customer_names[$i]['last'] = '';
                }else {
                    $shop_customer_names[$i]['first'] = soundex($billingAddress->getFirstname());
                    $shop_customer_names[$i]['last'] = soundex($billingAddress->getLastname());
                }
            }
            print_r( $shop_customer_names);

            echo "<br/>";
        }

        public function index_1Action(){

            $orderCollection = Mage::getModel("sales/order")->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('customer_is_guest',1)
                ->addFieldToFilter('increment_id', 100000057);

            foreach( $orderCollection  as $eachorder){

                Zend_Debug::dump($eachorder->getData());
            }
        }

        public function index_2Action(){

            $billingAddress = Mage::getModel('customer/customer')->load(1)->getPrimaryBillingAddress();
            Zend_Debug::dump($billingAddress->getData());
        }

        public function index_3Action(){

            $billingID = Mage::getModel("sales/order")->loadByIncrementId(100000049)->getBillingAddress()->getId();
            echo Mage::getModel("sales/order")->loadByIncrementId(100000049)->getId(). "<br/>";
            $addressForOrder = Mage::getModel('sales/order_address')->load($billingID);
            Zend_Debug::dump($addressForOrder->getData());
        }

        public function index_4Action(){
            $toCustomer = Mage::getModel('customer/customer')->load(1)->getData();
//            $order = Mage::getModel('sales/order')->loadByIncrementId('100000061');
//            echo $order->getCustomerId();
//            $order->setCustomerId()->save();
            print_r($toCustomer);
        }
    }