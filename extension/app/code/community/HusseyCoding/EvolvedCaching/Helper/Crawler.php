<?php
class HusseyCoding_EvolvedCaching_Helper_Crawler extends Mage_Core_Helper_Abstract
{
    private $_details;
    private $_currencies = array();
    
    public function buildUrlList()
    {
        $this->_cleanExisting();
        $this->_addHomePages();
        foreach ($this->getStores() as $store):
            $this->_processCollection(Mage::getResourceModel('sitemap/cms_page')->getCollection($store), $store, 'cms');
            $this->_processCollection(Mage::getResourceModel('sitemap/catalog_category')->getCollection($store), $store, 'category');
            $this->_processCollection(Mage::getResourceModel('sitemap/catalog_product')->getCollection($store), $store, 'product');
        endforeach;
    }
    
    public function buildStoreUrlList($store)
    {
        $this->_addHomePage($store);
        $this->_processCollection(Mage::getResourceModel('sitemap/cms_page')->getCollection($store), $store, 'cms');
        $this->_processCollection(Mage::getResourceModel('sitemap/catalog_category')->getCollection($store), $store, 'category');
        $this->_processCollection(Mage::getResourceModel('sitemap/catalog_product')->getCollection($store), $store, 'product');
    }
    
    public function cleanExisting()
    {
        $this->_cleanExisting();
    }
    
    private function _cleanExisting()
    {
        $collection = Mage::getResourceModel('evolvedcaching/crawler_collection');
        if ($collection->count()):
            foreach ($collection as $item):
                if ($item->getId()):
                    $item->delete();
                endif;
            endforeach;
        endif;
    }
    
    private function _addHomePages()
    {
        foreach ($this->getStores() as $store):
            foreach ($this->_getCurrencies($store) as $currency):
                if ($isstore = $this->_isStoreInUrl($store)):
                    $crawler = Mage::getModel('evolvedcaching/crawler');
                    $crawler
                        ->setUrl($isstore)
                        ->setStore($store)
                        ->setArea('home')
                        ->setCurrency($currency)
                        ->save();
                endif;
            endforeach;
        endforeach;
        
        if ($default = $this->_getDefaultStore()):
            foreach ($this->_getCurrencies($default) as $currency):
                $crawler = Mage::getModel('evolvedcaching/crawler');
                $crawler
                    ->setUrl()
                    ->setStore($default)
                    ->setArea('home')
                    ->setCurrency($currency)
                    ->save();
            endforeach;
        endif;
    }
    
    private function _addHomePage($store)
    {
        if ($store == $this->_getDefaultStore()):
            foreach ($this->_getCurrencies($default) as $currency):
                $crawler = Mage::getModel('evolvedcaching/crawler');
                $crawler
                    ->setUrl()
                    ->setStore($store)
                    ->setArea('home')
                    ->setCurrency($currency)
                    ->save();
            endforeach;
        endif;
        
        if ($isstore = $this->_isStoreInUrl($store)):
            foreach ($this->_getCurrencies($default) as $currency):
                $crawler = Mage::getModel('evolvedcaching/crawler');
                $crawler
                    ->setUrl($isstore)
                    ->setStore($store)
                    ->setArea('home')
                    ->setCurrency($currency)
                    ->save();
            endforeach;
        endif;
    }
    
    private function _getDefaultStore()
    {
        foreach (Mage::getResourceModel('core/website_collection') as $website):
            if ($website->getId() && $website->getIsDefault()):
                $group = $website->getDefaultGroupId();
                $group = Mage::getModel('core/store_group')->load((int) $group);
                if ($group->getId() && $group->getDefaultStoreId()):
                    $store = Mage::getModel('core/store')->load((int) $group->getDefaultStoreId());
                    if ($store->getId() && $store->getCode()):
                        return $store->getCode();
                    endif;
                endif;
            endif;
        endforeach;
        
        return false;
    }
    
    private function _processCollection($collection, $store, $area)
    {
        $isstore = $this->_isStoreInUrl($store);
        foreach ($this->_getCurrencies($store) as $currency):
            foreach ($collection as $item):
                if ($url = $item->getUrl()):
                    $crawler = Mage::getModel('evolvedcaching/crawler');
                    $crawler
                        ->setUrl($isstore . $url)
                        ->setStore($store)
                        ->setArea($area)
                        ->setCurrency($currency)
                        ->save();
                endif;
            endforeach;
        endforeach;
    }
    
    private function _isStoreInUrl($store)
    {
        $baselink = Mage::app()->getStore($store)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, false);
        $baseweb = Mage::app()->getStore($store)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, false);
        $isstore = str_replace($baseweb, '', $baselink);
        $isstore = trim($isstore, '/');
        if (!empty($isstore)):
            $isstore = $isstore . '/';
            return $isstore;
        endif;
        
        return '';
    }
    
    public function getStores()
    {
        if (!isset($this->_details)):
            $return = array();
            
            foreach (Mage::app()->getStores() as $store):
                $base = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, false);
                preg_match('/http(s)?:\/\/([^\/]*)/', $base, $match);
                if (!empty($match[2])):
                    $base = $match[2];
                    $return[] = $store->getCode();
                endif;
            endforeach;

            $this->_details = $return;
        endif;
        
        return $this->_details;
    }
    
    public function crawl($item)
    {
        $cachekey = $this->_getCacheKey($item);
        $store = $item->getStore();
        $baseurl = Mage::app()->getStore($store)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, false);
        
        preg_match('/.+:\/\//U', $baseurl, $match);
        $protocol = $match[0];
        $host = str_replace($protocol, '', $baseurl);
        preg_match('/\/(.*)\//', $host, $match);
        $remainder = !empty($match[1]) ? $match[1] : '';
        if (!empty($remainder)):
            $remainder = $remainder . '/';
        endif;
        $host = preg_replace('/\/.*/', '', $host);
        $ip = gethostbyname($host . '.');
        if ($ip != $host . '.'):
            $url = $protocol . $ip . '/' . $remainder . ltrim($item->getUrl(), '/');
        else:
            $url = $protocol . '127.0.0.1/' . $remainder . ltrim($item->getUrl(), '/');
        endif;
        
        $options = array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => array('warmrequest' => 'true', 'currency' => $item->getCurrency()),
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => array('Host: ' . $host),
            CURLOPT_USERAGENT => 'evolvedcaching_crawler',
            CURLOPT_COOKIE => 'currency=' . $item->getCurrency() . ';evolved_key=' . $cachekey,
        );
        
        if ($proxy = Mage::getStoreConfig('evolvedcaching/general/proxy')):
            $proxy = trim($proxy, '.');
            $proxy = trim($proxy);
            if (!empty($proxy)):
                $options[CURLOPT_PROXY] = $proxy;
            endif;
        endif;
        
        $adapter = new Varien_Http_Adapter_Curl();
        $adapter->multiRequest(array($url), $options);
        
        $item->delete();
    }
    
    private function _getCacheKey($item)
    {
        $storecode = $item->getStore();
        $protocol = $this->_getProtocol($storecode);
        $currency = $item->getCurrency();
        $agentmodifier = $this->_getAgentModifier();
        $categorymodifier = $this->_getCategoryModifier($item);
        $layeredmodifier = $this->_getLayeredModifier();
        $tax = $this->_getTax();
        $url = $this->_getRequestUrl($item);
        
        return md5($protocol . '_' . $storecode . '_' . $currency . '_' . $agentmodifier . '_' . $categorymodifier . '_' . $layeredmodifier . '_' . $tax . '_' . $url);
    }
    
    private function _getProtocol($storecode)
    {
        $baseurl = Mage::app()->getStore($storecode)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, false);
        preg_match('/[^:]+/', $baseurl, $match);
        
        return $match[0];
    }
    
    private function _getAgentModifier()
    {
        return md5('');
    }
    
    private function _getCategoryModifier($item)
    {
        $storecode = $item->getStore();
        if ($item->getArea() == 'category'):
            if ($config = Mage::getStoreConfig('catalog/frontend/list_mode', $storecode)):
                $config = explode('-', $config);
                $mode = $config[0];
            else:
                $mode = 'grid';
            endif;

            if ($mode == 'grid'):
                if ($config = Mage::getStoreConfig('catalog/frontend/grid_per_page', $storecode)):
                    $limit = (string) $config;
                else:
                    $limit = '9';
                endif;
            elseif ($mode == 'list'):
                if ($config = Mage::getStoreConfig('catalog/frontend/list_per_page', $storecode)):
                    $limit = (string) $config;
                else:
                    $limit = '10';
                endif;
            else:
                $limit = '';
            endif;

            if ($config = Mage::getStoreConfig('catalog/frontend/default_sort_by', $storecode)):
                $order = $config;
            else:
                $order = 'position';
            endif;

            return md5('asc' . $limit . $mode . $order . '1');
        else:
            return md5('');
        endif;
    }
    
    private function _getLayeredModifier()
    {
        return md5('');
    }
    
    private function _getTax()
    {
        $collection = Mage::getResourceModel('tax/calculation_rate_collection');
        $collection->getSelect()
            ->order('rate DESC')
            ->limit(1);
        
        if ($collection->count()):
            foreach ($collection as $item):
                return $item->getRate();
            endforeach;
        endif;
        
        return '0';
    }
    
    private function _getRequestUrl($item)
    {
        $url = $item->getUrl();
        
        return '/' . trim($url, '/');
    }
    
    private function _getCurrencies($store)
    {
        if (!isset($this->_currencies[$store])):
            if (Mage::getStoreConfig('evolvedcaching/general/crawlcurrency')):
                $this->_currencies[$store] = Mage::app()->getStore($store)->getAvailableCurrencyCodes();
            else:
                $currencies = Mage::app()->getStore($store)->getBaseCurrencyCode();
                $this->_currencies[$store] = array($currencies);
            endif;
        endif;
        
        return $this->_currencies[$store];
    }
}