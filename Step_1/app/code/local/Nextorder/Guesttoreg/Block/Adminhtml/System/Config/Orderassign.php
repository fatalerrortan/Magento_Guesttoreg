<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 12.01.16
 * Time: 13:39
 */
class Nextorder_Guesttoreg_Block_Adminhtml_System_Config_Orderassign
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract{

    protected $_itemRendererForOrderId;
    protected $_itemRendererForBilling;
    protected $_itemRendererForTransport;

    public function _prepareToRender()
    {
        $this->addColumn('orderid', array(
            'label' => Mage::helper('guesttoreg')->__('Bestellung Inkrement ID '),
            'renderer' => $this->_getRendererForOrderId(),
//            'style' => 'width:100px',
        ));
        $this->addColumn('customerid', array(
            'label' => Mage::helper('guesttoreg')->__('Customer ID'),
            'style' => 'width:100px',
        ));


        $this->addColumn('billing', array(
            'label' => Mage::helper('guesttoreg')->__('Als Default Rechnungsadresse'),
            'renderer' => $this->_getRendererForBilling(),
        ));

        $this->addColumn('transport', array(
            'label' => Mage::helper('guesttoreg')->__('Als Default Lieferungsadresse'),
            'renderer' => $this->_getRendererForTransport(),
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('guesttoreg')->__('hinzufügen');
    }
    protected function  _getRendererForOrderId(){

        if (!$this->_itemRendererForOrderId) {
            $this->_itemRendererForOrderId = $this->getLayout()->createBlock(
                'guesttoreg/adminhtml_system_config_render_orderid', '',
                array('is_render_to_js_template' => true)
            );
        }
        return $this->_itemRendererForOrderId;
    }
    protected function  _getRendererForBilling(){

        if (!$this->_itemRendererForBilling) {
            $this->_itemRendererForBilling = $this->getLayout()->createBlock(
                'guesttoreg/adminhtml_system_config_render_billing', '',
                array('is_render_to_js_template' => true)
            );
        }
        return $this->_itemRendererForBilling;
    }
    protected function  _getRendererForTransport(){

        if (!$this->_itemRendererForTransport) {
            $this->_itemRendererForTransport = $this->getLayout()->createBlock(
                'guesttoreg/adminhtml_system_config_render_transport', '',
                array('is_render_to_js_template' => true)
            );
        }
        return $this->_itemRendererForTransport;
    }
    protected function _prepareArrayRow(Varien_Object $row){

        $row->setData(
            'option_extra_attr_' . $this->_getRendererForOrderId()
                ->calcOptionHash($row->getData('orderid')),
            'selected="selected"'
        );

        $row->setData(
            'option_extra_attr_' . $this->_getRendererForBilling()
                ->calcOptionHash($row->getData('billing')),
            'selected="selected"'
        );

        $row->setData(
            'option_extra_attr_' . $this->_getRendererForTransport()
                ->calcOptionHash($row->getData('transport')),
            'selected="selected"'
        );
    }
}