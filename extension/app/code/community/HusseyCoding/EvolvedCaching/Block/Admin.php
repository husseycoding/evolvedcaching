<?php
class HusseyCoding_EvolvedCaching_Block_Admin extends Mage_Core_Block_Template
{
    public function isEnabled()
    {
        $params = Mage::app()->getRequest()->getParams();
        if (Mage::app()->useCache('evolved') && @class_exists('evolved', false) && !isset($params['disabled']) && !isset($params['evolvedforward'])):
            return true;
        endif;
        
        return false;
    }
}