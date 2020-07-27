<?php
class HusseyCoding_EvolvedCaching_Block_Adminhtml_Config_Version_Latest extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $helper = Mage::helper('evolvedcaching');
        $current = $helper->getCurrentVersion();
        if ($latest = $helper->getLatestVersion()):
            if (version_compare($latest, $current) == 1):
                $latest .= '<br /><a href="https://store.husseycoding.co.uk/customer/account/login/" target="_blank">Get latest version</a>';
            endif;
        else:
            return 'Save settings to show latest version';
        endif;
        
        return $latest;
    }
}