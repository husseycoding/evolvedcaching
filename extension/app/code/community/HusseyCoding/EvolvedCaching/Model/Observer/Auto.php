<?php
class HusseyCoding_EvolvedCaching_Model_Observer_Auto
{
    private $_cms;
    
    public function adminhtmlCatalogProductSaveAfter($observer)
    {
        if (!$this->_isDataflow()):
            if (Mage::getStoreConfig('evolvedcaching/autoclear/product')):
                $product = $observer->getProduct();
                Mage::helper('evolvedcaching/entries')->clearProductCache($product);
            endif;
        endif;
    }
    
    public function adminhtmlCatalogProductSaveCommitAfter($observer)
    {
        if (!$this->_isDataflow()):
            $this->_clearBlockHtmlCache();
            if (Mage::getStoreConfig('evolvedcaching/autoclear/product')):
                Mage::helper('evolvedcaching/entries')->processWarmQueue();
            else:
                Mage::getSingleton('adminhtml/session')->addNotice('Autowarm is enabled but product autoclear is disabled, full page cache has not been refreshed.');
            endif;
        endif;
    }
    
    public function adminhtmlCatalogCategorySaveAfter($observer)
    {
        if (Mage::getStoreConfig('evolvedcaching/autoclear/category')):
            $category = $observer->getCategory();
            Mage::helper('evolvedcaching/entries')->clearCategoryCache($category);
        endif;
    }
    
    public function adminhtmlCatalogCategorySaveCommitAfter($observer)
    {
        $this->_clearBlockHtmlCache();
        if (Mage::getStoreConfig('evolvedcaching/autoclear/category')):
            Mage::helper('evolvedcaching/entries')->processWarmQueue();
        else:
            Mage::getSingleton('adminhtml/session')->addNotice('Autowarm is enabled but category autoclear is disabled, full page cache has not been refreshed.');
        endif;
    }
    
    public function adminhtmlCmsPageSaveAfter($observer)
    {
        if (Mage::getStoreConfig('evolvedcaching/autoclear/cms')):
            $this->_cms = $observer->getObject();
            Mage::helper('evolvedcaching/entries')->clearCmsCache($this->_cms);
        endif;
    }
    
    public function adminhtmlCmsPageSaveCommitAfter($observer)
    {
        $this->_clearBlockHtmlCache();
        if (Mage::getStoreConfig('evolvedcaching/autoclear/cms')):
            Mage::helper('evolvedcaching/entries')->processWarmQueue($this->_cms);
        else:
            Mage::getSingleton('adminhtml/session')->addNotice('Autowarm is enabled but cms autoclear is disabled, full page cache has not been refreshed.');
        endif;
    }
    
    private function _clearBlockHtmlCache()
    {
        if (Mage::getStoreConfig('evolvedcaching/autoclear/blocks')):
            Mage::helper('evolvedcaching/entries')->clearBlockHtmlCache();
        endif;
    }
    
    private function _isDataflow()
    {
        if (Mage::app()->getRequest()->getControllerName() == 'system_convert_gui') return true;
        
        return false;
    }
    
    public function adminhtmlCatalogProductDeleteBefore($observer)
    {
        Mage::helper('evolvedcaching/entries')->clearProductCache($observer->getProduct());
    }
    
    public function adminhtmlCatalogProductDeleteCommitAfter($observer)
    {
        $this->adminhtmlCatalogProductSaveCommitAfter($observer);
    }
    
    public function adminhtmlCmsPageDeleteBefore($observer)
    {
        $this->_cms = $observer->getObject();
        Mage::helper('evolvedcaching/entries')->clearCmsCache($this->_cms);
    }
    
    public function adminhtmlSalesOrderCreditmemoRefund($observer)
    {
        $this->_refreshItems($observer->getCreditmemo());
    }
    
    public function adminhtmlSalesOrderPlaceAfter($observer)
    {
        $this->_refreshItems($observer->getOrder());
    }
    
    public function adminhtmlOrderCancelAfter($observer)
    {
        $this->_refreshItems($observer->getOrder());
    }
    
    private function _refreshItems($object)
    {
        $items = $object->getAllItems();
        if (!empty($items) && is_array($items)):
            foreach ($items as $item):
                $product = Mage::getModel('catalog/product')->load($item->getProductId());
                if ($product->getId()):
                    Mage::helper('evolvedcaching/entries')->clearProductCache($product);
                endif;
            endforeach;
            
            if (Mage::getStoreConfig('evolvedcaching/autoclear/blocks')):
                Mage::helper('evolvedcaching/entries')->clearBlockHtmlCache();
            endif;
            Mage::helper('evolvedcaching/entries')->processWarmQueue();
        endif;
    }
    
    public function adminhtmlControllerActionPostdispatchAdminhtmlCatalogProductMassStatus($observer)
    {
        if (Mage::getStoreConfig('evolvedcaching/autoclear/product')):
            $products = Mage::app()->getRequest()->getPost('product');
            if (!empty($products) && is_array($products)):
                foreach ($products as $product):
                    $product = Mage::getModel('catalog/product')->load((int) $product);
                    if ($product->getId()):
                        Mage::helper('evolvedcaching/entries')->clearProductCache($product);
                    endif;
                endforeach;
            endif;
        endif;
        
        $this->_clearBlockHtmlCache();
        Mage::helper('evolvedcaching/entries')->processWarmQueue();
    }
    
    public function adminhtmlReviewSaveAfter($observer)
    {
        if (Mage::getStoreConfig('evolvedcaching/autoclear/product')):
            $id = $observer->getObject()->getEntityPkValue();
            $product = Mage::getModel('catalog/product')->load($id);
            if ($product->getId()):
                Mage::helper('evolvedcaching/entries')->clearProductCache($product);
            endif;
        endif;
    }
    
    public function adminhtmlReviewSaveCommitAfter($observer)
    {
        $this->_clearBlockHtmlCache();
        if (Mage::getStoreConfig('evolvedcaching/autoclear/product')):
            Mage::helper('evolvedcaching/entries')->processWarmQueue();
        else:
            Mage::getSingleton('adminhtml/session')->addNotice('Autowarm is enabled but product autoclear is disabled, full page cache has not been refreshed.');
        endif;
    }
}