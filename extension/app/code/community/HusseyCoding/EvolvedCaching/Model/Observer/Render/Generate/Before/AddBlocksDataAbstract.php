<?php
abstract class HusseyCoding_EvolvedCaching_Model_Observer_Render_Generate_Before_AddBlocksDataAbstract extends HusseyCoding_EvolvedCaching_Model_Observer_Render_Abstract
{
    private $_blocks;
    private $_excluded;
    private $_processed = array();
    private $_exclusionurls;
    private $_thisstore;
    private $_price;
    private $_creview;
    private $_cart;
    private $_preview;
    private $_tier;
    protected $_welcome;
    private $_products = array();
    protected $_dynamic;
    
    public function __construct()
    {
        $this->_processed['anonymous'] = array();
        $this->_processed['list'] = array();
        $this->_processed['review'] = array();
    }
    
    protected function _getExcluded()
    {
        if (!isset($this->_excluded)):
            $this->_excluded = Mage::helper('evolvedcaching')->getExcluded(true);
        endif;
        
        return $this->_excluded;
    }
    
    protected function _collectParams()
    {
        if (!isset($this->_dynamic)):
            $this->_price = (bool) Mage::getStoreConfig('evolvedcaching/exclude/price');
            $this->_creview = (bool) Mage::getStoreConfig('evolvedcaching/exclude/creview');
            $this->_cart = (bool) Mage::getStoreConfig('evolvedcaching/exclude/cart');
            $this->_preview = (bool) Mage::getStoreConfig('evolvedcaching/exclude/preview');
            $this->_tier = (bool) Mage::getStoreConfig('evolvedcaching/exclude/tier');
            $this->_welcome = (bool) Mage::getStoreConfig('evolvedcaching/exclude/welcome');

            $this->_dynamic = $this->_price || $this->_creview || $this->_cart || $this->_preview || $this->_tier || $this->_welcome;
        endif;
    }
    
    protected function _insertWelcomeElement($observer)
    {
        $transport = $observer->getTransport();
        $html = $transport->getHtml();
        $regex = new Varien_Object(array('pattern' => '/<p class="welcome-msg">.*<\/p>/Us'));
        Mage::dispatchEvent('evolved_caching_block_welcome_regex', array('regex' => $regex));
        if ($pattern = $regex->getPattern()):
            preg_match($pattern, $html, $match);
            if (!empty($match[0])):
                $match = $match[0];
                if ($holdinghtml = $this->_hasHoldingHtml('welcome')):
                    $style = '';
                    $class = ' evolved_holding';
                else:
                    $style = ' style="display:none"';
                    $class = '';
                endif;
                $open = '<div' . $style . ' class="evolved_class' . $class . ' evolved_id-welcome">';
                $close = '</div>';
                $action = Mage::app()->getRequest()->getActionName();
                if ((@class_exists('evolved', false) && evolved::$excludedpage) || strtolower($action) == 'noroute'):
                    $replace = $open . $match . $close;
                else:
                    $replace = !empty($holdinghtml) ? $open . $holdinghtml . $close : $open . $close;
                endif;
                $html = str_replace($match, $replace, $html);
                $transport->setHtml($html);
            endif;
        endif;
    }
    
    protected function _insertCategoryElements($observer, $name)
    {
        if (strpos($name, 'ANONYMOUS') !== false && ($this->_price || $this->_creview)):
            $this->_insertCategoryAnonymousElements($observer);
        elseif (($name == 'product_list' || $name == 'search_result_list') && $this->_cart):
            $this->_collectListProducts($name);
            $this->_insertProductListElements($observer);
        endif;
    }
    
    private function _insertCategoryAnonymousElements($observer)
    {
        $product = $observer->getBlock()->getProduct();
        if ($product):
            foreach (debug_backtrace() as $class):
                if ($class['function'] == 'getPriceHtml' && $this->_price):
                    $id = 'eprice_' . $product->getId();
                    $class = ' evolved_price';
                    break;
                elseif ($class['function'] == 'getReviewsSummaryHtml' && $this->_creview):
                    $id = 'creview_' . $product->getId();
                    $class = ' evolved_creview';
                    break;
                endif;
            endforeach;
            $transport = $observer->getTransport();
            if (isset($id) && !in_array($id, $this->_processed['anonymous'])):
                $this->_processed['anonymous'][] = $id;
                $block = str_replace(' evolved_', '', $class);
                if ($holdinghtml = $this->_hasHoldingHtml($block)):
                    $style = '';
                    $class .= ' evolved_holding';
                else:
                    $style = ' style="display:none"';
                endif;
                $open = '<div' . $style . ' class="evolved_class' . $class . ' evolved_id-' . $id . '">';
                $close = '</div>';
                $action = Mage::app()->getRequest()->getActionName();
                if ((@class_exists('evolved', false) && evolved::$excludedpage) || strtolower($action) == 'noroute'):
                    $html = $open . $transport->getHtml() . $close;
                else:
                    $html = !empty($holdinghtml) ? $open . $holdinghtml . $close : $open . $close;
                endif;
                $transport->setHtml($html);
            endif;
        endif;
    }
    
    private function _collectListProducts($name)
    {
        if (empty($this->_products)):
            if ($collection = Mage::app()->getLayout()->getBlock($name)->getLoadedProductCollection()):
                foreach ($collection as $product):
                    if (!in_array($product->getId(), $this->_products)):
                        $this->_products[] = (int) $product->getId();
                    endif;
                endforeach;
            endif;
        endif;
    }
    
    private function _insertProductListElements($observer)
    {
        $transport = $observer->getTransport();
        $html = $transport->getHtml();
        $mode = $observer->getBlock()->getMode();
        
        switch ($mode):
            case 'list':
                $regex = new Varien_Object(array('pattern' => '/(<p>\s*<button[^>]*class[^>]*btn-cart[^>]*>.*<\/button>\s*<\/p>|<p[^>]*class[^>]*availability[^>]*>\s*<span>.*<\/span>\s*<\/p>)/Us'));
                Mage::dispatchEvent('evolved_caching_block_product_list_list_cart_regex', array('regex' => $regex));
                if ($pattern = $regex->getPattern()):
                    preg_match_all($pattern, $html, $matches);
                    $matches = !empty($matches[0]) ? $matches[0] : array();
                endif;
                break;
            default:
                $regex = new Varien_Object(array('pattern' => '/<div[^>]*class[^>]*actions.*(<(button|p).+(p|button)>)/Us'));
                Mage::dispatchEvent('evolved_caching_block_product_list_grid_cart_regex', array('regex' => $regex));
                if ($pattern = $regex->getPattern()):
                    preg_match_all($pattern, $html, $matches);
                    $matches = !empty($matches[1]) ? $matches[1] : array();
                endif;
                break;
        endswitch;

        $replacements = array();
        foreach ($matches as $key => $match):
            if (isset($this->_products[$key])):
                $id = 'ecart_' . $this->_products[$key];
            elseif (isset($id)):
                unset($id);
            endif;
            $class = ' evolved_cart';

            if (isset($id) && !in_array($id, $this->_processed['list'])):
                $this->_processed['list'][] = $id;
                $html = preg_replace('/' . preg_quote($match, '/') . '/', '<!--' . $id . '-->', $html, 1);
                if ($holdinghtml = $this->_hasHoldingHtml('cart')):
                    $style = '';
                    $class .= ' evolved_holding';
                else:
                    $style = ' style="display:none"';
                endif;
                $open = '<div' . $style . ' class="evolved_class' . $class . ' evolved_id-' . $id . '">';
                $close = '</div>';
                $action = Mage::app()->getRequest()->getActionName();
                if ((@class_exists('evolved', false) && evolved::$excludedpage) || strtolower($action) == 'noroute'):
                    $match = $match;
                else:
                    $match = !empty($holdinghtml) ? $holdinghtml : '';
                endif;
                $replacements[$id] = $open . $match . $close;
            endif;
        endforeach;

        foreach ($replacements as $key => $content):
            $html = str_replace('<!--' . $key . '-->', $content, $html);
        endforeach;

        $transport->setHtml($html);
    }
    
    protected function _insertProductElements($observer, $name)
    {
        if ((strpos($name, 'ANONYMOUS') !== false || $name == 'product_review_list.count') && $this->_preview):
            $this->_insertProductReviewElements($observer, $name);
        elseif ($name == "product.info" && $this->_tier):
            $this->_insertProductTierPricingElements($observer);
        endif;
    }
    
    private function _insertProductReviewElements($observer, $name)
    {
        $product = Mage::registry('current_product');
        if ($product):
            foreach (debug_backtrace() as $class):
                if ($class['function'] == 'getReviewsSummaryHtml'):
                    $id = 'preview_' . $product->getId();
                    $class = ' evolved_preview';
                    break;
                endif;
            endforeach;
            $transport = $observer->getTransport();
            if (isset($id) && !in_array($id, $this->_processed['review'])):
                $this->_processed['review'][] = $id;
                if ($holdinghtml = $this->_hasHoldingHtml('preview')):
                    $style = '';
                    $class .= ' evolved_holding';
                else:
                    $style = ' style="display:none"';
                endif;
                $open = '<div' . $style . ' class="evolved_class' . $class . ' evolved_id-' . $id . '">';
                $close = '</div>';
                $action = Mage::app()->getRequest()->getActionName();
                if ((@class_exists('evolved', false) && evolved::$excludedpage) || strtolower($action) == 'noroute'):
                    $html = $open . $transport->getHtml() . $close;
                else:
                    $html = !empty($holdinghtml) ? $open . $holdinghtml . $close : $open . $close;
                endif;
                $transport->setHtml($html);
            elseif ($name == 'product_review_list.count'):
                $transport->setHtml('');
            endif;
        endif;
    }
    
    private function _insertProductTierPricingElements($observer)
    {
        $transport = $observer->getTransport();
        $html = $transport->getHtml();
        $regex = new Varien_Object(array('pattern' => '/<ul class="tier-prices.*<\/ul>/Us'));
        Mage::dispatchEvent('evolved_caching_block_product_info_tier_regex', array('regex' => $regex));
        if ($pattern = $regex->getPattern()):
            preg_match($pattern, $html, $match);
            if (!empty($match[0])):
                $match = $match[0];
                if ($holdinghtml = $this->_hasHoldingHtml('tier')):
                    $style = '';
                    $class = ' evolved_holding';
                else:
                    $style = ' style="display:none"';
                    $class = '';
                endif;
                $open = '<div' . $style . ' class="evolved_class' . $class . ' evolved_id-tier">';
                $close = '</div>';
                $action = Mage::app()->getRequest()->getActionName();
                if ((@class_exists('evolved', false) && evolved::$excludedpage) || strtolower($action) == 'noroute'):
                    $replace = $open . $match . $close;
                else:
                    $replace = !empty($holdinghtml) ? $open . $holdinghtml . $close : $open . $close;
                endif;
                $html = str_replace($match, $replace, $html);
                $transport->setHtml($html);
            endif;
        endif;
    }
    
    protected function _insertCookieData($transport)
    {
        if (!isset($this->_exclusionurls)):
            $this->_exclusionurls = Mage::helper('evolvedcaching')->getExclusionUrls();
        endif;
        if (!isset($this->_thisstore)):
            $this->_thisstore = Mage::app()->getStore()->getCode();
        endif;
        $html = $transport->getHtml();

        preg_match_all('/(<a.*)>.*<\/a>/Us', $html, $links);
        if (isset($links[0])):
            $this->_insertCookieAnchorData($links, $transport, $html);
        endif;
        
        $html = $transport->getHtml();
        
        preg_match_all('/(<select.*)>.*<\/select>/Us', $html, $selects);
        if (isset($selects[0])):
            $this->_insertCookieSelectsData($selects, $transport, $html);
        endif;
    }
    
    private function _insertCookieAnchorData($links, $transport, $html)
    {
        foreach ($links[0] as $index => $link):
            if (!preg_match('/evolved_area/s', $link)):
                preg_match('/(href="|\')(.*)("|\')/U', $link, $url);
                $host = Mage::app()->getRequest()->getHttpHost();
                if (!empty($url[2]) && (strpos($url[2], '#') === false || strpos($url[2], '#') > 0) && strpos($url[2], 'javascript') === false):
                    if ($urlstore = Mage::helper('evolvedcaching')->getUrlStore($url[2], $host)):
                        $request = $url[2];
                        $request = explode('?', $request);
                        $request = reset($request);
                        $request = rtrim($request, '/');
                        $request = preg_replace('/http(s)?:\/\/[^\/]*/', '', $request);
                        if (!empty($this->_exclusionurls[$urlstore]) && is_array($this->_exclusionurls[$urlstore])):
                            foreach ($this->_exclusionurls[$urlstore] as $exclusionurl):
                                if (strpos($request, trim($exclusionurl, '/')) !== false):
                                    $attribute = ' evolved_excluded="1"';
                                    $html = str_replace($links[1][$index], $links[1][$index] . $attribute, $html);
                                    break;
                                endif;
                            endforeach;
                        endif;

                        if ($urlstore != $this->_thisstore):
                            $attribute = ' evolved_store="' . $urlstore . '"';
                            $html = str_replace($links[1][$index], $links[1][$index] . $attribute, $html);
                        endif;

                        $area = evolved::getBlockArea($url[2]);
                        if ($area):
                            $attribute = ' evolved_area="' . $area . '"';
                            $html = str_replace($links[1][$index], $links[1][$index] . $attribute, $html);
                        endif;
                    endif;
                endif;
            endif;
        endforeach;
        $transport->setHtml($html);
    }
    
    private function _insertCookieSelectsData($selects, $transport, $html)
    {
        foreach ($selects[0] as $index => $select):
            preg_match('/<select.*>/Us', $select, $match);
            preg_match('/onchange=("|\').*("|\')/Us', $match[0], $onchange);
            $onchange = isset($onchange[0]) ? strtolower($onchange[0]) : '';
            if (strpos($onchange, 'location') !== false):
                preg_match_all('/(<option.*)>.*<\/option>/Us', $select, $options);
                if (isset($options[0])):
                    foreach ($options[0] as $oindex => $option):
                        if (!preg_match('/evolved_area/s', $option)):
                            preg_match('/(value="|\')(.*)("|\')/Us', $option, $url);
                            $host = Mage::app()->getRequest()->getHttpHost();
                            if ($urlstore = Mage::helper('evolvedcaching')->getUrlStore($url[2], $host)):
                                $request = $url[2];
                                $request = explode('?', $request);
                                $request = reset($request);
                                $request = rtrim($request, '/');
                                $request = preg_replace('/http(s)?:\/\/[^\/]*/', '', $request);
                                foreach ($this->_exclusionurls[$urlstore] as $exclusionurl):
                                    if (strpos($request, trim($exclusionurl, '/')) !== false):
                                        $attribute = ' evolved_excluded="1"';
                                        $html = str_replace($options[1][$oindex], $options[1][$oindex] . $attribute, $html);
                                        break;
                                    endif;
                                endforeach;

                                if ($urlstore != $this->_thisstore):
                                    $attribute = ' evolved_store="' . $urlstore . '"';
                                    $html = str_replace($options[1][$oindex], $options[1][$oindex] . $attribute, $html);
                                endif;

                                $area = evolved::getBlockArea($url[2]);
                                if ($area):
                                    $attribute = ' evolved_area="' . $area . '"';
                                    $html = str_replace($options[1][$oindex], $options[1][$oindex] . $attribute, $html);
                                endif;
                            endif;
                        endif;
                    endforeach;
                endif;
            endif;
        endforeach;
        $transport->setHtml($html);
    }
    
    protected function _getBlocks()
    {
        if (!isset($this->_blocks)):
            $this->_blocks = Mage::helper('evolvedcaching')->getBlocks();
        endif;
        
        return $this->_blocks;
    }
    
    protected function _hasHoldingHtml($block)
    {
        switch ($block):
            case 'price':
                if (Mage::getStoreConfig('evolvedcaching/exclude/price')):
                    if ($html = Mage::getStoreConfig('evolvedcaching/exclude/price_holding')):
                        return $html;
                    endif;
                endif;
                break;
            case 'creview':
               if (Mage::getStoreConfig('evolvedcaching/exclude/creview')):
                   if ($html = Mage::getStoreConfig('evolvedcaching/exclude/creview_holding')):
                       return $html;
                   endif;
               endif;
                break;
            case 'cart':
                if (Mage::getStoreConfig('evolvedcaching/exclude/cart')):
                    if ($html = Mage::getStoreConfig('evolvedcaching/exclude/cart_holding')):
                        return $html;
                    endif;
                endif;
                break;
            case 'preview':
                if (Mage::getStoreConfig('evolvedcaching/exclude/preview')):
                    if ($html = Mage::getStoreConfig('evolvedcaching/exclude/preview_holding')):
                        return $html;
                    endif;
                endif;
                break;
            case 'tier':
                if (Mage::getStoreConfig('evolvedcaching/exclude/tier')):
                    if ($html = Mage::getStoreConfig('evolvedcaching/exclude/tier_holding')):
                        return $html;
                    endif;
                endif;
                break;
            case 'welcome':
                if (Mage::getStoreConfig('evolvedcaching/exclude/welcome')):
                    if ($html = Mage::getStoreConfig('evolvedcaching/exclude/welcome_holding')):
                        return $html;
                    endif;
                endif;
                break;
            default:
                $holdinghtml = Mage::getStoreConfig('evolvedcaching/exclude/blocks_holding');
                if (!empty($holdinghtml)):
                    if ($holdinghtml = Zend_Json::decode($holdinghtml)):
                        if (!empty($holdinghtml[$block])):
                            return $holdinghtml[$block];
                        endif;
                    endif;
                endif;
                break;
        endswitch;

        return '';
    }
    
    protected function _insertBlockLayoutNames($observer, $name, $excluded, $restrict)
    {
        if (!in_array($name, $excluded)):
            if (strpos($name, 'ANONYMOUS') === false && !$restrict):
                $transport = $observer->getTransport();
                $transport->setHtml(
                    '<div style="border:1px solid #f00; margin:10px">
                        <div style="float:left; color:#f00; font-weight:bold; font-size:12px; margin:5px; padding:3px 5px; border:1px solid #000; background-color:#fff; text-transform:initial">' . $name . '</div>
                        '. $transport->getHtml() . '
                        <div style="clear:both"></div>
                    </div>'
                );
            endif;
        endif;
    }
}
