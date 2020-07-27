<?php
class HusseyCoding_EvolvedCaching_Block_Adminhtml_Config_Guide_License extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return '<a href="http://store.husseycoding.co.uk/evolved-caching/documentation/licensing/" target="_blank">Learn about the license settings</a>';
    }
}