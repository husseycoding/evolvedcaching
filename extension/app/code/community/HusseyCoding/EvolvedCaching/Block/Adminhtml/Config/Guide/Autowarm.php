<?php
class HusseyCoding_EvolvedCaching_Block_Adminhtml_Config_Guide_Autowarm extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return '<a href="http://store.husseycoding.co.uk/evolved-caching/documentation/automatic-cache-warming/" target="_blank">Learn about the automatic cache warming settings</a>';
    }
}