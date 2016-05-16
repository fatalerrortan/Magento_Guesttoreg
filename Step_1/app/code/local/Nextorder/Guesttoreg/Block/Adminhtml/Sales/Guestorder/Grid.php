<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 11.04.16
 * Time: 14:07
 */
class Nextorder_Guesttoreg_Block_Adminhtml_Sales_Guestorder_Grid extends Mage_Adminhtml_Block_Widget_Grid{

    public function __construct(){

        parent::__construct();
        $this->setId('guest_order');
        $this->setDefaultSort('increment_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection(){

        $collection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToSelect('*')
//            ->addAttributeToFilter('increment_id', array('nin' => $this->getSusOrder()))
//            ->addAttributeToFilter('increment_id', array('like' => '%-16-%'))
            ->addFieldToFilter('customer_group_id', 0);
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }

    protected function _prepareColumns()
    {
        $helper = Mage::helper('guesttoreg');

        $this->addColumn('increment_id', array(
            'header' => $helper->__('Order #'),
            'index'  => 'increment_id',
            'renderer'  => 'nextorder_guesttoreg_block_adminhtml_render_check',
        ));
        $this->addColumn('purchased_on', array(
            'header' => $helper->__('Purchased On'),
            'type'   => 'datetime',
            'index'  => 'created_at'
        ));
        $this->addColumn('firstname', array(
            'header'       => $helper->__('Vorname'),
            'index'        => 'customer_firstname',
        ));
        $this->addColumn('lasttname', array(
            'header'       => $helper->__('Nachname'),
            'index'        => 'customer_lastname',
        ));
        $this->addColumn('gender', array(
            'header'       => $helper->__('Geschlecht'),
            'index'        => 'customer_gender',
            'renderer'  => 'nextorder_guesttoreg_block_adminhtml_render_gender',
        ));
        $this->addColumn('email', array(
            'header'       => $helper->__('Email'),
            'index'        => 'increment_id',
            'renderer'  => 'nextorder_guesttoreg_block_adminhtml_render_email',
        ));
        $this->addColumn('phone', array(
            'header'       => $helper->__('Telefon'),
            'index'        => 'increment_id',
            'renderer'  => 'nextorder_guesttoreg_block_adminhtml_render_phone',
        ));
        $this->addColumn('street', array(
            'header'       => $helper->__('Adresse'),
            'index'        => 'increment_id',
            'renderer'  => 'nextorder_guesttoreg_block_adminhtml_render_street',
        ));
        $this->addColumn('postcode', array(
            'header'       => $helper->__('PLZ'),
            'index'        => 'increment_id',
            'renderer'  => 'nextorder_guesttoreg_block_adminhtml_render_postcode',
        ));
        $this->addColumn('city', array(
            'header'       => $helper->__('Stadt und Staat'),
            'index'        => 'increment_id',
            'renderer'  => 'nextorder_guesttoreg_block_adminhtml_render_city',
        ));
//
//        $this->addColumn('customer_group', array(
//            'header' => $helper->__('Customer Group'),
//            'index'  => 'customer_group_id'
//        ));


//        $this->addColumn('shipping_method', array(
//            'header' => $helper->__('Shipping Method'),
//            'index'  => 'shipping_description'
//        ));

        $this->addColumn('order_status', array(
            'header'  => $helper->__('Status'),
            'index'   => 'status',
            'type'    => 'options',
            'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
        ));

//        $this->addExportType('*/*/exportInchooCsv', $helper->__('CSV'));
//        $this->addExportType('*/*/exportInchooExcel', $helper->__('Excel XML'));
        return parent::_prepareColumns();
    }

    public function getGridUrl(){

        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

    public function getSusOrder(){
        $base_path = Mage::getBaseDir('base');
        $orgin_string = file_get_contents($base_path."/var/new_customer/customer_generate.txt");
        return  explode(',',$orgin_string);
    }
}