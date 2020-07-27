<?php
class HusseyCoding_EvolvedCaching_Model_System_Config_Source_Currency
{
    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label' => Mage::helper('evolvedcaching')->__('Store Base Currency')),
            array('value' => 1, 'label' => Mage::helper('evolvedcaching')->__('All Store Currencies'))
        );
    }
}
