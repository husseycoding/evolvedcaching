<?php
class HusseyCoding_EvolvedCaching_Model_Observer_Entries
{
    public function adminhtmlCoreCollectionAbstractLoadAfter($observer)
    {
        $collection = $observer->getCollection();
        
        if (method_exists($collection, 'getShouldValidate')):
            if ($collection->getShouldValidate()):
                Mage::helper('evolvedcaching/entries')->validateEntries($collection);
            endif;
        endif;
    }
}