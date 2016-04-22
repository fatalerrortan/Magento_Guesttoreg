<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 11.04.16
 * Time: 13:58
 */
class Nextorder_Guesttoreg_Block_Adminhtml_Sales_Guestorder extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'guesttoreg';
        $this->_controller = 'adminhtml_sales_guestorder';
        $this->_headerText = Mage::helper('guesttoreg')->__('Bitte Klicken Sie die BestellungNr. zur Anordnung der Gastbestellungen');

        parent::__construct();
        $this->_removeButton('add');
    }
}