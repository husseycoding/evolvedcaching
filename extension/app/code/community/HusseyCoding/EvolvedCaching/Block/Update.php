<?php
class HusseyCoding_EvolvedCaching_Block_Update extends Mage_Page_Block_Html
{
    public function isEnabled()
    {
        $params = Mage::app()->getRequest()->getParams();
        if (Mage::app()->useCache('evolved') && !isset($params['shownames']) && !isset($params['disabled']) && !isset($params['evolvedforward']) && @class_exists('evolved', false)):
            return true;
        endif;
        
        return false;
    }
    
    public function getBlocks()
    {
        return Mage::helper('evolvedcaching')->getBlocks();
    }
    
    public function getFadeSpeed()
    {
        return Mage::helper('evolvedcaching')->getFadeSpeed();
    }
    
    public function getSlideSpeed()
    {
        return Mage::helper('evolvedcaching')->getSlideSpeed();
    }
    
    public function getExcludeConfig($config)
    {
        if (Mage::getStoreConfig('evolvedcaching/exclude/' . $config)):
            $module = strtolower(Mage::app()->getRequest()->getModuleName());
            if ($config == 'welcome' || $config == 'tier'):
                return 'true';
            elseif ((Mage::registry('current_category') || $module == 'catalogsearch') && !Mage::registry('current_product')):
                return 'true';
            elseif ($config == 'preview' && Mage::registry('current_product')):
                return 'true';
            endif;
        endif;
        
        return 'false';
    }
    
    public function getReviewModule()
    {
        $return = strtolower(Mage::app()->getRequest()->getModuleName());
        $id = Mage::app()->getRequest()->getParam('id');
        $return = $return == 'review' && !empty($id) ? Mage::app()->getRequest()->getParam('id') : 'false';

        return $return;
    }
    
    public function getUseAjax()
    {
        return Mage::getStoreConfig('evolvedcaching/general/useajax') ? 'true' : 'false';
    }
    
    public function getExcludedPage()
    {
        $action = Mage::app()->getRequest()->getActionName();
        if ((@class_exists('evolved', false) && evolved::$excludedpage) || strtolower($action) == 'noroute'):
            return 'true';
        endif;
        
        return 'false';
    }
}