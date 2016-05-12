<?php
/**
 * Created by PhpStorm.
 * User: tiemanntan
 * Date: 07/10/15
 * Time: 14:04
 */
    class Nextorder_Guesttoreg_IndexController extends Mage_Core_Controller_Front_Action{

        public function indexAction(){

            $collection = Mage::getResourceModel('sales/order_collection')
//            ->addAttributeToFilter('increment_id', array('in' => '1380-16-105'));
                ->addFieldToSelect('*')
////            ->addAttributeToFilter('increment_id', array('nin' => $this->getSusOrder()))
                ->addAttributeToFilter('increment_id', array('like' => '12%-16-105'))
                ->addFieldToFilter('customer_group_id', 0);

                foreach($collection as $item){

                    echo $item->getData('increment_id')."<br/>";
                }
        }

        public function index_1Action(){

            $orderCollection = Mage::getModel("sales/order")->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('customer_is_guest',1)
                ->addFieldToFilter('increment_id', 100000181);

            foreach( $orderCollection  as $eachorder){

                Zend_Debug::dump($eachorder->getData());
            }
        }

        public function index_2Action()
        {
//           print_r(count(Mage::getModel('customer/customer')->load(10)->getData()));

           Zend_Debug::dump(Mage::getModel('customer/customer')->load(1)->getData());
//
//            Mage::getModel('customer/customer')->load(15)->setWebsiteId(1)->save();
//
//            Zend_Debug::dump(Mage::getModel('customer/customer')->load(15)->getData());

        }


        public function index_3Action(){

            $o = Mage::getModel("sales/order")->loadByIncrementId(100000189);

            echo Mage::getModel('core/store')->load($o->getStoreId())->getWebsiteId();
        }

        public function index_4Action(){
//            $toCustomer = Mage::getModel('customer/customer')->load(8);
            $order = Mage::getModel('sales/order')->loadByIncrementId('100000061');
            Zend_Debug::dump($order->getCustomer());
        }

        public function index_5Action(){

            $string_1 = "+4917684605358";
            $string_2 = "004917684605358";
            $string_3 = "017684605358";
            $string_4 = "03413558520";
            $string_5 = "+493413558520";
            $string_6 = "00493413558520";

            $strings = array($string_1, $string_2, $string_3, $string_4, $string_5, $string_6);

            foreach($strings as $string){

                if(substr($string,0,1) == "+"){
                    echo str_replace(substr($string,0,3),0,$string)."<br/>";
                }else{
                    if(substr($string,0,2) == 00){
                        echo str_replace(substr($string,0,4),0,$string)."<br/>";
                    }else{
                        echo $string."<br/>";
                    }
                }
            }
        }

        public function index_6Action(){

            $_custom_address = array (
                'prefix'     => 'Herr',
                'firstname'  => 'testname',
                'lastname'   => 'testasdsa',
                'street'     => array (
                    '0' => 'strstrssss',
                    '1' => 123,
                    '3' => 'left'
                ),
                'city'       => 'Berlin',
                'region'     => 'Berlin',
                'region_id'  => '82',
                'company'    => 'customerscomapnf',
                'postcode'   => 123123,
                'country_id' => 'DE',
                'telephone'  => 12312312,
                'fax'        => 2133423423423,

            );
            $customAddress   = Mage::getModel('customer/address');
            $customAddress->setData($_custom_address)
                ->setCustomerId(26) // this is the most important part
                ->setIsDefaultBilling('1')  // set as default for billing
                ->setIsDefaultShipping('1') // set as default for shipping
                ->setSaveInAddressBook('1');
            $customAddress->save();


        }

        public function index_7Action(){

            $basicCustomerData = Mage::getModel('customer/customer')->load(23);
            $addressForOrder = $basicCustomerData->getPrimaryBillingAddress();
            if($addressForOrder == false){
                Zend_Debug::dump("Nothing");
            }


        }
    }