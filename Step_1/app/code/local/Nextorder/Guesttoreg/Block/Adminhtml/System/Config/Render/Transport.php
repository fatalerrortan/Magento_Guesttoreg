<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 12.01.16
 * Time: 16:39
 */
class Nextorder_Guesttoreg_Block_Adminhtml_System_Config_Render_Transport
    extends Mage_Core_Block_Html_Select{

    public function _toHtml(){

//        $helper = Mage::helper('guesttoreg/data');
//        $options = $helper->_getOrdersInVerdacht();

        $options = array(
            array('value' => 1, 'label' => 'Ja'),
            array('value' => 0, 'label' => 'Nein'),
        );

        foreach ($options as $option) {
            $this->addOption($option['value'], $option['label']);
        }
        //Superclass

        if (!$this->_beforeToHtml()) {
            return '';
        }

        $html = '<select style="width:50px" name="' . $this->getName() . '" id="' . $this->getId() . '" class="'
            . $this->getClass() . '" title="' . $this->getTitle() . '" ' . $this->getExtraParams() . '>';
        $values = $this->getValue();

        if (!is_array($values)){
            if (!is_null($values)) {
                $values = array($values);
            } else {
                $values = array();
            }
        }

        $isArrayOption = true;
        foreach ($this->getOptions() as $key => $option) {
            if ($isArrayOption && is_array($option)) {
                $value  = $option['value'];
                $label  = (string)$option['label'];
                $params = (!empty($option['params'])) ? $option['params'] : array();
            } else {
                $value = (string)$key;
                $label = (string)$option;
                $isArrayOption = false;
                $params = array();
            }

            if (is_array($value)) {
                $html .= '<optgroup label="' . $label . '">';
                foreach ($value as $keyGroup => $optionGroup) {
                    if (!is_array($optionGroup)) {
                        $optionGroup = array(
                            'value' => $keyGroup,
                            'label' => $optionGroup
                        );
                    }
                    $html .= $this->_optionToHtml(
                        $optionGroup,
                        in_array($optionGroup['value'], $values)
                    );
                }
                $html .= '</optgroup>';
            } else {
                $html .= $this->_optionToHtml(
                    array(
                        'value' => $value,
                        'label' => $label,
                        'params' => $params
                    ),
                    in_array($value, $values)
                );
            }
        }
        $html .= '</select>';
        return $html;

        //Superclass

        // return parent::_toHtml();
    }

    public function setInputName($value){

        return $this->setName($value);
    }
}