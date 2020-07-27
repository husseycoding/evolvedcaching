<?php
class HusseyCoding_EvolvedCaching_Helper_Varnish extends Mage_Core_Helper_Abstract
{
    private $_enabled;
    
    public function clearAllHtml()
    {
        if ($this->_shouldClear()):
            if ($this->_sendRequest('BAN_HTML', '200 ban_html')):
                Mage::getSingleton('adminhtml/session')->addSuccess('The Varnish HTML cache has been cleaned.');
            else:
                Mage::getSingleton('adminhtml/session')->addError('Failed to clear Varnish HTML cache.');
            endif;
        endif;
    }
    
    public function clearAllImages()
    {
        if ($this->_shouldClear()):
            if ($this->_sendRequest('BAN_IMAGES', '200 ban_images')):
                Mage::getSingleton('adminhtml/session')->addSuccess('The Varnish images cache has been cleaned.');
            else:
                Mage::getSingleton('adminhtml/session')->addError('Failed to clear Varnish images cache.');
            endif;
        endif;
    }
    
    public function clearAllCssJs()
    {
        if ($this->_shouldClear()):
            if ($this->_sendRequest('BAN_CSSJS', '200 ban_cssjs')):
                Mage::getSingleton('adminhtml/session')->addSuccess('The Varnish JavaScript/CSS cache has been cleaned.');
            else:
                Mage::getSingleton('adminhtml/session')->addError('Failed to clear Varnish CSS/JS cache.');
            endif;
        endif;
    }
    
    public function clearByEntry($entry)
    {
        if ($this->_shouldClear()):
            $key = $entry->getCachekey();
            if (Mage::getStoreConfig('evolvedcaching/cookie/area', $entry->getStorecode())):
                if (@class_exists('evolved', false)):
                    if ($area = evolved::getBlockArea($entry->getRequest())):
                        $key = $area . '_' . $key;
                    endif;
                endif;
            endif;
            
            $this->_sendRequest('PURGE_SINGLE', '200 purge_single', $key);
        endif;
    }
    
    private function _sendRequest($type, $success, $key = false)
    {
        $baseurl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, false);
        if ($urldata = $this->_getUrlData($baseurl)):
            $adapter = new Varien_Http_Adapter_Curl();

            $options = array(
                CURLOPT_CUSTOMREQUEST => $type,
                CURLOPT_NOBODY => true,
                CURLOPT_HEADER => true,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => array('Host: ' . $urldata['host'])
            );
            
            if ($proxy = Mage::getStoreConfig('evolvedcaching/general/proxy')):
                $proxy = trim($proxy, '.');
                $proxy = trim($proxy);
                if (!empty($proxy)):
                    $options[CURLOPT_PROXY] = $proxy;
                endif;
            endif;
            
            if ($key):
                $options[CURLOPT_COOKIE] = 'evolved_key=' . $key;
            endif;

            $response = $adapter->multiRequest(array($urldata['url']), $options);
            
            if (!empty($response[0]) && strpos($response[0], $success)):
                return true;
            endif;
        endif;
        
        return false;
    }
    
    private function _getUrlData($url)
    {
        $protocol = preg_replace('/(http(s)?):\/\/.*/', '$1', $url);
        $host = preg_replace('/http(s)?:\/\//', '', $url);
        $host = trim($host, '/');
        $ip = gethostbyname($host . '.');
        if ($ip != $host . '.'):
            $url = $protocol . '://' . $ip;
        else:
            $url = $protocol . '://127.0.0.1';
        endif;
        
        if ($host && $url):
            return array('host' => $host, 'url' => $url);
        endif;
        
        return false;
    }
    
    private function _shouldClear()
    {
        if (!isset($this->_enabled)):
            $this->_enabled = (bool) Mage::getStoreConfig('evolvedcaching/general/flushvarnish');
        endif;
        
        return $this->_enabled;
    }
}