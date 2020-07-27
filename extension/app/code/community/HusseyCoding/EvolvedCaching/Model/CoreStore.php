<?php
class HusseyCoding_EvolvedCaching_Model_CoreStore extends Mage_Core_Model_Store
{
    public function getCurrentCurrencyCode()
    {
        $params = Mage::app()->getRequest()->getPost();
        if (isset($params['warmrequest'])):
            if (isset($params['currency'])):
                return $params['currency'];
            endif;
        endif;
        
        return parent::getCurrentCurrencyCode();
    }
}