<?php
class HusseyCoding_EvolvedCaching_Model_Observer_Customer
{
    public function frontendCustomerLogin($observer)
    {
        $helper = Mage::helper('evolvedcaching');
        $helper->setCustomerTaxCookie();
        $helper->setCustomerLoginCookie();
        $helper->updateFormkeyCookie();
    }
    
    public function frontendCustomerLogout($observer)
    {
        $helper = Mage::helper('evolvedcaching');
        $helper->setCustomerTaxCookie(true);
        $helper->deleteCustomerLoginCookie();
        $helper->updateFormkeyCookie();
    }
    
    public function frontendCustomerAddressSaveAfter($observer)
    {
        Mage::helper('evolvedcaching')->setCustomerTaxCookie();
    }
    
    public function frontendCatalogControllerProductInit($observer)
    {
        if (!Mage::registry('current_category')):
            if ($product = Mage::registry('current_product')):
                if ($product->getId()):
                    $ids = $product->getCategoryIds();
                    if (!empty($ids) && is_array($ids)):
                        if ($id = $this->_filterCategoryIdsByStore($ids)):
                            if (!empty($id) && is_numeric($id)):
                                $category = Mage::getModel('catalog/category')->load((int) $id);
                                if ($category->getId()):
                                    if ($category->getLevel() > 1):
                                        Mage::register('current_category', $category);
                                    else:
                                        if (!empty($ids)):
                                            $id = array_shift($ids);
                                            if (!empty($id) && is_numeric($id)):
                                                $category = Mage::getModel('catalog/category')->load((int) $id);
                                                if ($category->getId()):
                                                    Mage::register('current_category', $category);
                                                endif;
                                            endif;
                                        endif;
                                    endif;
                                endif;
                            endif;
                        endif;
                    endif;
                endif;
            endif;
        endif;
    }
    
    private function _filterCategoryIdsByStore($ids)
    {
        $rootid = Mage::app()->getStore()->getRootCategoryId();
        $categories = Mage::getResourceModel('catalog/category_collection');
        $categories
            ->getSelect()
            ->where('path = \'1/' . $rootid . '\' OR path LIKE \'1/' . $rootid . '/%\'')
            ->where('entity_id IN (?)', $ids)
            ->limit(1);
        
        if ($categories->count()):
            return $categories->getFirstItem()->getId();
        endif;
        
        return false;
    }
}