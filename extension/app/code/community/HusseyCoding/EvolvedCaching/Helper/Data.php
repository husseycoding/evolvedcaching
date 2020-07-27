<?php
class HusseyCoding_EvolvedCaching_Helper_Data extends Mage_Core_Helper_Abstract
{
    private $_storage;
    
    public function setRedirectCookie()
    {
        if (Mage::getStoreConfig('evolvedcaching/cookie/use')):
            $response = Mage::app()->getResponse();
            if ($response->isRedirect()):
                foreach ($response->getHeaders() as $header):
                    if (strtolower($header['name']) == 'location'):
                        $url = $header['value'];
                        break;
                    endif;
                endforeach;
                
                if (isset($url)):
                    $this->_createCookieByUrl($url);
                endif;
            endif;
        endif;
    }
    
    private function _createCookieByUrl($url)
    {
        if (@class_exists('evolved', false)):
            if ($data = evolved::getRedirectKey($url)):
                if (Mage::getStoreConfig('evolvedcaching/cookie/area')):
                    $key = $data['area'] . '_' . $data['key'];
                else:
                    $key = $data['key'];
                endif;
                Mage::getSingleton('core/cookie')->set('evolved_key', $key, 3600, '/', $_SERVER['HTTP_HOST'], false, false);
            endif;
        endif;
    }
    
    public function getBlocks()
    {
        $excluded = $this->getExcluded();
        
        if ($blocks = Mage::getStoreConfig('evolvedcaching/exclude/blocks')):
            $return = array();
            $blocks = explode(',', $blocks);
            foreach ($blocks as $block):
                $block = trim($block);
                if ($block && !in_array($block, $excluded)):
                    $return[] = $block;
                endif;
            endforeach;
            
            return $return ? $return : false;
        endif;
        
        return array();
    }
    
    public function getExcluded($links = false)
    {
        $return = array(
                'root',
                'head',
                'left',
                'right',
                'content',
                'product_list.swatches',
                'welcome',
                'evolvedcookie',
                'evolvedupdate',
                'evolvedrefresh',
                'evolvedcrawler'
        );
        if ($links):
            return $return;
        else:
            $return[] = 'header';
            $return[] = 'footer';
            
            return $return;
        endif;
    }
    
    public function getPageModifiers()
    {
        return evolved::getPageModifiers();
    }
    
    public function getLayeredModifiers()
    {
        $modifiers = array();
        foreach (Mage::app()->getStores() as $store):
            $config = Mage::getStoreConfig('evolvedcaching/agent/agents', $store->getCode());
            $modifier = evolved::getAgentModifier($config, true);
            $modifiers[$store->getCode()] = $modifier;
        endforeach;
        
        return $modifiers;
    }
    
    public function getFadeSpeed()
    {
        $speed = Mage::getStoreConfig('evolvedcaching/load/fadespeed');
        return is_numeric($speed) ? (float) $speed : 0.4;
    }
    
    public function getSlideSpeed()
    {
        $speed = Mage::getStoreConfig('evolvedcaching/load/slidespeed');
        return is_numeric($speed) ? (float) $speed : 0;
    }
    
    public function setCustomerTaxCookie($logout = false)
    {
        $tax_helper = Mage::getSingleton('tax/calculation');
        
        if (Mage::getSingleton('customer/session')->isLoggedIn() && $logout == false):
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $id = $customer->getDefaultShipping();
        endif;
        
        if (isset($id) && $id):
            $address = Mage::getModel('customer/address')->load($id);
            $countryid = $address->getCountryId();
            $regionid = $address->getRegionId();
            $postcode = $address->getPostcode();
            $customerclass = $customer->getTaxClassId();
        else:
            $countryid = Mage::getStoreConfig('shipping/origin/country_id');
            $regionid = Mage::getStoreConfig('shipping/origin/region_id');
            $postcode = Mage::getStoreConfig('shipping/origin/postcode');
            $customerclass = $tax_helper->getDefaultCustomerTaxClass();
        endif;
        
        $tax_request = new Varien_Object();
        $tax_request->setCountryId($countryid)
            ->setRegionId($regionid)
            ->setPostcode($postcode)
            ->setCustomerClassId($customerclass);
        
        $percent = 0;
        foreach ($tax_helper->getRatesForAllProductTaxClasses($tax_request) as $rate):
            $percent = $rate > $percent ? $rate : $percent;
        endforeach;
        
        $lifetime = (int) Mage::getStoreConfig('web/cookie/cookie_lifetime');
        $lifetime = $lifetime ? $lifetime : 3600;
        
        Mage::getSingleton('core/cookie')->set('evolved_tax', $percent, $lifetime, '/', $_SERVER['HTTP_HOST'], false, false);
    }
    
    public function setCustomerLoginCookie()
    {
        if (Mage::getStoreConfig('evolvedcaching/general/mustlogin')):
            $lifetime = (int) Mage::getStoreConfig('web/cookie/cookie_lifetime');
            $lifetime = $lifetime ? $lifetime : 3600;
            
            Mage::getSingleton('core/cookie')->set('evolved_loggedin', true, $lifetime, '/', $_SERVER['HTTP_HOST'], false, false);
        endif;
    }
    
    public function deleteCustomerLoginCookie()
    {
        if (Mage::getStoreConfig('evolvedcaching/general/mustlogin')):
            Mage::getSingleton('core/cookie')->set('evolved_loggedin', true, -1, '/', $_SERVER['HTTP_HOST'], false, false);
        endif;
    }
    
    public function updateFormkeyCookie()
    {
        if (@class_exists('evolved', false)):
            evolved::updateFormkeyCookie();
        endif;
    }
    
    public function getExclusionUrls()
    {
        $exclusions = array();
        foreach (Mage::app()->getStores() as $store):
            if ($urls = Mage::getStoreConfig('evolvedcaching/exclude/pages', $store->getCode())):
                $urls = explode(',', $urls);
                foreach ($urls as $url):
                    $url = trim($url);
                    if ($url):
                        $exclusions[$store->getCode()][] = $url;
                    endif;
                endforeach;
            endif;
        endforeach;
        
        return $exclusions;
    }
    
    public function getUrlStore($url, $host)
    {
        if (strpos($url, $host) === false && strpos($url, 'http') === false):
            return Mage::app()->getStore()->getCode();
        elseif (strpos($url, '___store') !== false):
            preg_match('/___store=([a-zA-Z0-9_]*)/', $url, $store);
            return isset($store[1]) ? $store[1] : Mage::app()->getStore()->getCode();
        else:
            $secure = strpos($url, 'https' !== false) ? true : false;
            $protocol = $secure ? 'https://' : 'http://';
            $return = false;
            foreach (Mage::app()->getStores() as $store):
                if ($secure):
                    $baseurl = Mage::getStoreConfig('web/secure/base_url', $store->getCode());
                else:
                    $baseurl = Mage::getStoreConfig('web/unsecure/base_url', $store->getCode());
                endif;
                if (strpos($url, $baseurl) === 0 && strpos($url, $protocol . $host) !== 0):
                    $return = $store->getCode();
                    break;
                endif;
            endforeach;
            
            if ($return):
                return $return;
            endif;
        endif;
        
        return Mage::app()->getStore()->getCode();
    }
    
    public function getCategoryMode()
    {
        if ($mode = Mage::getSingleton('catalog/session')->getDisplayMode()):
            return $mode;
        elseif ($config = Mage::getStoreConfig('catalog/frontend/list_mode')):
            $config = explode('-', $config);
            return $config[0];
        else:
            return 'grid';
        endif;
    }
    
    public function getStorageOptions()
    {
        return array(
            'files' => 'Files',
            'redis' => 'Redis',
            'apc' => 'APC',
            'memcached' => 'Memcached'
        );
    }
    
    public function getProtocolOptions()
    {
        return array(
            'http' => 'Insecure',
            'https' => 'Secure'
        );
    }
    
    public function getStoreOptions()
    {
        $return = array();
        foreach (Mage::app()->getStores() as $store):
            $return[$store->getCode()] = $store->getName();
        endforeach;
        
        return $return;
    }
    
    public function getCurrencyOptions()
    {
        $return = array();
        foreach (Mage::getModel('directory/currency')->getConfigAllowCurrencies() as $currency):
            $return[$currency] = $currency;
        endforeach;
        
        return $return;
    }
    
    public function getStorage()
    {
        if (!$this->_storage):
            $storage = array();
            
            switch (Mage::getStoreConfig('evolvedcaching/storage/use')):
                case '1':
                    $storage['type'] = 'memcached';
                    $storage['expires'] = Mage::getStoreConfig('evolvedcaching/storage/memcachedexpires');
                    break;
                case '2':
                    $storage['type'] = 'apc';
                    $storage['expires'] = Mage::getStoreConfig('evolvedcaching/storage/apcexpires');
                    break;
                case '3':
                    $storage['type'] = 'redis';
                    $storage['expires'] = Mage::getStoreConfig('evolvedcaching/storage/rtimeout');
                    break;
                default:
                    $storage['type'] = 'files';
                    $storage['expires'] = false;
            endswitch;
            
            $this->_storage = $storage;
        endif;
        
        return $this->_storage;
    }
    
    public function getExpiredOptions()
    {
        return array(0 => 'No', 1 => 'Yes');
    }
    
    public function getCurrentVersion()
    {
        return (string) Mage::getConfig()->getNode('modules/HusseyCoding_EvolvedCaching/version');
    }
    
    public function getLatestVersion()
    {
        return (string) Mage::getStoreConfig('evolvedcaching/version/latest');
    }
}