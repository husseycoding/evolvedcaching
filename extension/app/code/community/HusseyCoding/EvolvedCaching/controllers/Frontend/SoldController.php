<?php
class HusseyCoding_EvolvedCaching_Frontend_SoldController extends Mage_Core_Controller_Front_Action
{   
    public function refreshAction()
    {
        if ($ids = $this->getRequest()->getPost('ids')):
            $ids = explode(',', $ids);
            foreach ($ids as $id):
                $order = Mage::getModel('sales/order')->load((int) $id);
                if ($order->getId()):
                    $items = $order->getAllItems();
                    if (!empty($items) && is_array($items)):
                        foreach ($items as $item):
                            $product = Mage::getModel('catalog/product')->load($item->getProductId());
                            if ($product->getId()):
                                Mage::helper('evolvedcaching/entries')->clearProductCache($product);
                            endif;
                        endforeach;
                    endif;
                endif;
            endforeach;
            
            if (Mage::getStoreConfig('evolvedcaching/autoclear/blocks')):
                Mage::helper('evolvedcaching/entries')->clearBlockHtmlCache();
            endif;
            Mage::helper('evolvedcaching/entries')->processWarmQueue();
        endif;
    }
}