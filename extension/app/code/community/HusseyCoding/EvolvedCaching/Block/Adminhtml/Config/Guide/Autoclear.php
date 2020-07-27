<?php
class HusseyCoding_EvolvedCaching_Block_Adminhtml_Config_Guide_Autoclear extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return '<a href="http://store.husseycoding.co.uk/evolved-caching/documentation/automatic-cache-clearing/" target="_blank">Learn about the automatic cache clearing settings</a>';
    }
}