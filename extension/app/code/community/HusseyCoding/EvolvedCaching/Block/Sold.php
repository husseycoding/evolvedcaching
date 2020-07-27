<?php
class HusseyCoding_EvolvedCaching_Block_Sold extends Mage_Core_Block_Template
{
    private function _shouldRefresh()
    {
        if ($this->_isCachingEnabled() && $this->_isClearEnabled()):
            return true;
        endif;
        
        return false;
    }
    
    private function _isCachingEnabled()
    {
        if (Mage::app()->useCache('evolved') && @class_exists('evolved', false)):
            return true;
        endif;
        
        return false;
    }
    
    private function _isClearEnabled()
    {
        return Mage::getStoreConfig('evolvedcaching/autoclear/product');
    }
    
    public function getOrderIds()
    {
        if ($this->_shouldRefresh()):
            switch ($this->getOrderType()):
                case 'multi':
                    $ids = Mage::getSingleton('core/session')->getOrderIds(true);
                    break;
                case 'onepage':
                    $id = Mage::getSingleton('checkout/session')->getLastOrderId();
                    $ids = array($id);
                    break;
            endswitch;

            if (!empty($ids) && is_array($ids)):
                return implode(',', $ids);
            endif;
        endif;
        
        return '';
    }
}