<?php
abstract class HusseyCoding_EvolvedCaching_Model_Observer_Render_Abstract
{
    private $_enabled;
    
    protected function _isEnabled()
    {
        if (!isset($this->_enabled)):
            $params = Mage::app()->getRequest()->getParams();
            $this->_enabled = @class_exists('evolved', false) && Mage::app()->useCache('evolved') && !isset($params['disabled']);
        endif;
        
        return $this->_enabled;
    }
}