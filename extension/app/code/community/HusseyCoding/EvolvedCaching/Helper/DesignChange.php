<?php
class HusseyCoding_EvolvedCaching_Helper_DesignChange extends HusseyCoding_EvolvedCaching_Helper_Entries
{
    public function cacheChanges()
    {
        $this->_cacheStoreDesignChanges();
        $this->_cacheProductDesignChanges();
        $this->_cacheCategoryDesignChanges();
        $this->_cacheCatalogRuleChanges();
        $this->_cacheCmsDesignChanges();
    }
    
    private function _cacheStoreDesignChanges()
    {
        $crawler = Mage::helper('evolvedcaching/crawler');
        $collection = Mage::getResourceModel('core/design_collection');
        $ids = array();
        foreach ($crawler->getStores() as $store):
            $store = Mage::getModel('core/store')->load($store);
            if ($store->getId()):
                $ids[] = $store->getId();
            endif;
        endforeach;
        
        if ($ids):
            $collection->getSelect()
                ->where('store_id IN (?)', $ids)
                ->where('date_from = "' . $this->_getToday() . '" OR date_to = "' . $this->_getYesterday() . '"');
            
            if ($collection->count()):
                $crawler->cleanExisting();
                $this->clearBlockHtmlCache();
            endif;
            
            $processed = array();
            foreach ($collection as $design):
                $id = $design->getStoreId();
                $store = Mage::getModel('core/store')->load($id);
                if ($store->getId()):
                    $code = $store->getCode();
                    if (!in_array($code, $processed)):
                        $processed[] = $code;
                        $this->_clearStoreCache($code);
                        $crawler->buildStoreUrlList($code);
                        
                        $collection = Mage::getResourceModel('evolvedcaching/crawler_collection');
                        foreach ($collection as $item):
                            $crawler->crawl($item);
                        endforeach;
                    endif;
                endif;
            endforeach;
        endif;
    }
    
    private function _cacheProductDesignChanges()
    {
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->joinAttribute(
            'custom_design_from',
            'catalog_product/custom_design_from',
            'entity_id'
        );
        $collection->joinAttribute(
            'custom_design_to',
            'catalog_product/custom_design_to',
            'entity_id'
        );
        $collection->joinAttribute(
            'special_from_date',
            'catalog_product/special_from_date',
            'entity_id'
        );
        $collection->joinAttribute(
            'special_to_date',
            'catalog_product/special_to_date',
            'entity_id'
        );
        $collection->getSelect()->where('at_custom_design_from.value = "' . $this->_getToday() . '" OR at_custom_design_to.value = "' . $this->_getYesterday() . '" OR at_special_from_date.value = "' . $this->_getToday() . '" OR at_special_to_date.value = "' . $this->_getYesterday() . '"');

        if ($collection->count()):
            $this->clearBlockHtmlCache();
            
            $warm = false;
            foreach ($collection as $item):
                if ($id = $item->getId()):
                    $product = Mage::getModel('catalog/product')->load((int) $id);
                    if ($product->getId()):
                        $this->clearProductCache($product);
                        $warm = true;
                    endif;
                endif;
            endforeach;
            
            if ($warm) $this->processWarmQueue();
        endif;
    }
    
    private function _cacheCategoryDesignChanges()
    {
        $collection = Mage::getResourceModel('catalog/category_collection');
        $collection->joinAttribute(
            'custom_design_from',
            'catalog_category/custom_design_from',
            'entity_id'
        );
        $collection->joinAttribute(
            'custom_design_to',
            'catalog_category/custom_design_to',
            'entity_id'
        );
        $collection->getSelect()->where('at_custom_design_from.value = "' . $this->_getToday() . '" OR at_custom_design_to.value = "' . $this->_getYesterday() . '"');

        if ($collection->count()):
            $this->clearBlockHtmlCache();
        
            foreach ($collection as $item):
                if ($id = $item->getId()):
                    $category = Mage::getModel('catalog/category')->load((int) $id);
                    if ($category->getId()):
                        if ($category->getCustomApplyToProducts()):
                            $products = $category->getProductCollection();
                            if ($products->count()):
                                foreach ($products as $product):
                                    $product = Mage::getModel('catalog/product')->load($product->getId());
                                    if ($product->getId()):
                                        $this->clearProductCache($product);
                                    endif;
                                endforeach;
                            endif;
                        endif;

                        $this->clearCategoryCache($category);
                        $this->_processChildCategories($category, $category->getCustomApplyToProducts());
                        $this->processWarmQueue();
                    endif;
                endif;
            endforeach;
        endif;
    }
    
    private function _processChildCategories($category, $applyprods)
    {
        if ($category->getId()):
            $children = Mage::getModel('catalog/category')->getCategories($category->getId());
            if ($children->count()):
                foreach ($children as $child):
                    $category = Mage::getModel('catalog/category')->load($child->getId());
                    if ($category->getId()):
                        $this->_processCategory($category, $applyprods);
                    endif;
                endforeach;
            endif;
        endif;
    }
    
    private function _processCategory($category, $applyprods)
    {
        if ($category->getCustomUseParentSettings()):
            if ($applyprods):
                $products = $category->getProductCollection();
                if ($products->count()):
                    foreach ($products as $product):
                        $product = Mage::getModel('catalog/product')->load($product->getId());
                        if ($product->getId()):
                            $this->clearProductCache($product);
                        endif;
                    endforeach;
                endif;
            endif;
            
            $this->clearCategoryCache($category);
            $this->_processChildCategories($category, $applyprods);
        endif;
    }
    
    private function _cacheCatalogRuleChanges()
    {
        $collection = Mage::getResourceModel('catalogrule/rule_product_price_collection');
                $collection->getSelect()->where('latest_start_date = "' . $this->_getToday() . '" OR latest_start_date = "' . $this->_getYesterday() . '" OR earliest_end_date = "' . $this->_getYesterday() . '" OR earliest_end_date = "' . $this->_getTwoDaysAgo() . '"');
        
        if ($collection->count()):
            $this->clearBlockHtmlCache();
            
            $warm = false;
            foreach ($collection as $item):
                if ($id = $item->getProductId()):
                    $product = Mage::getModel('catalog/product')->load((int) $id);
                    if ($product->getId()):
                        $this->clearProductCache($product);
                        $warm = true;
                    endif;
                endif;
            endforeach;
            
            if ($warm) $this->processWarmQueue();
        endif;
    }
    
    private function _cacheCmsDesignChanges()
    {
        $collection = Mage::getResourceModel('cms/page_collection');
        $collection->getSelect()->where('custom_theme_from = "' . $this->_getToday() . '" OR custom_theme_to = "' . $this->_getYesterday() . '"');

        if ($collection->count()):
            $this->clearBlockHtmlCache();
            
            $warm = false;
            foreach ($collection as $item):
                $cms = Mage::getModel('cms/page')->load($item->getId());
                $this->clearCmsCache($cms);
                $warm = true;
            endforeach;
            
            if ($warm) $this->processWarmQueue();
        endif;
    }
    
    private function _getToday()
    {
        return Mage::getModel('core/date')->date('Y-m-d');
    }
    
    private function _getYesterday()
    {
        return Mage::getModel('core/date')->date('Y-m-d', 'now - 1 day');
    }
    
    private function _getTwoDaysAgo()
    {
        return Mage::getModel('core/date')->date('Y-m-d', 'now - 2 days');
    }
}