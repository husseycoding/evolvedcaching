<?php
class HusseyCoding_EvolvedCaching_Block_Cookie extends Mage_Page_Block_Html
{
    public function useCookie()
    {
        $params = Mage::app()->getRequest()->getParams();
        if (Mage::getStoreConfig('evolvedcaching/cookie/use') && Mage::app()->useCache('evolved') && @class_exists('evolved', false) && !isset($params['shownames']) && !isset($params['disabled'])):
            return true;
        endif;
        
        return false;
    }
    
    public function getPageModifiers()
    {
        return Mage::helper('evolvedcaching')->getPageModifiers();
    }
    
    public function getLayeredModifiers()
    {
        return Mage::helper('evolvedcaching')->getLayeredModifiers();
    }
}