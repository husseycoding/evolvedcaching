<?php
class HusseyCoding_EvolvedCaching_Model_TaxCalculation extends Mage_Tax_Model_Calculation
{
    public function getRate($request)
    {
        $params = Mage::app()->getRequest()->getPost();
        if (isset($params['warmrequest'])):
            if (isset($params['tax'])):
                return $params['tax'];
            endif;
        endif;

        return parent::getRate($request);
    }
}