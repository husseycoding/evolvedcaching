<?php
abstract class HusseyCoding_EvolvedCaching_Model_Observer_Render_Generate_After_RenderDynamicAbstract extends HusseyCoding_EvolvedCaching_Model_Observer_Render_Abstract
{
    private $_listblock;
    
    protected function _updateSession()
    {
        $this->_updateCategorySession();
    }
    
    protected function _triggerMergeRebuild($layout)
    {
        if (Mage::getStoreConfig('evolvedcaching/general/merged')):
            if ($head = $layout->getBlock('head')):
                $head->getCssJsHtml();
            endif;
        endif;
    }
    
    private function _updateCategorySession()
    {
        if ($this->_isEnabled()):
            $url = Mage::helper('core/url')->getCurrentUrl();
            $area = evolved::getBlockArea($url);
            if (!empty($area) && $area == 'category'):
                $this->_updateSortingParams();
            endif;
        endif;
    }
    
    private function _updateSortingParams()
    {
        $session = Mage::getSingleton('catalog/session');
        if (!$session->getParamsMemorizeDisabled()):
            $params = Mage::app()->getRequest()->getParams();
            if (!empty($params) && is_array($params)):
                foreach ($params as $key => $value):
                    switch ($key):
                        case 'mode':
                            if ($key):
                                if ($mode = $session->getDisplayMode()):
                                    if ($mode != $value):
                                        $session->setData('display_mode', $value);
                                    endif;
                                else:
                                    $session->setData('display_mode', $value);
                                endif;
                            endif;
                            break;
                        case 'dir':
                            if ($key):
                                if ($dir = $session->getSortDirection()):
                                    if ($dir != $value):
                                        $session->setData('sort_direction', $value);
                                    endif;
                                else:
                                    $session->setData('sort_direction', $value);
                                endif;
                            endif;
                            break;
                        case 'limit':
                            if ($key):
                                if ($limit = $session->getLimitPage()):
                                    if ($limit != $value):
                                        $session->setData('limit_page', $value);
                                    endif;
                                else:
                                    $session->setData('limit_page', $value);
                                endif;
                            endif;
                            break;
                        case 'order':
                            if ($key):
                                if ($order = $session->getSortOrder()):
                                    if ($order != $value):
                                        $session->setData('sort_order', $value);
                                    endif;
                                else:
                                    $session->setData('sort_order', $value);
                                endif;
                            endif;
                            break;
                    endswitch;
                endforeach;
            endif;
        endif;
    }
    
    protected function _getBlocksContent($layout, $blocks, $content, $isreview, $controller)
    {
        $useajax = evolved::getUseAjax();
        $params = Mage::app()->getRequest()->getParams();
        $showelements = isset($params['evolved']) ? 'true' : 'false';
        
        foreach ($blocks as $block):
            if (!is_string($block)):
                continue;
            endif;
            if ($block == 'welcome'):
                $header = $layout->createBlock('page/html_header');
                if (Mage::helper('core')->isModuleEnabled('Mage_Persistent')):
                    $layout->addBlock('persistent/header_additional', 'header.additional');
                    $name = trim(Mage::helper('persistent/session')->getCustomer()->getName());
                    if (!empty($name) && !Mage::helper('customer')->isLoggedIn()):
                        $header->setAdditionalHtml($layout->getBlock('header.additional')->toHtml());
                    endif;
                else:
                    $header->setAdditionalHtml('');
                endif;
                if (isset($header) && is_object($header)):
                    $before = '<p class="welcome-msg">';
                    $middle = $header->getWelcome() . ' ' . $header->getAdditionalHtml();
                    $after = '</p>';
                    $renderdata = new Varien_Object(array(
                        'before' => $before,
                        'middle' => $middle,
                        'after' => $after,
                        'complete' => $before . $middle . $after
                    ));
                    Mage::dispatchEvent('evolved_caching_block_welcome_render', array('render_data' => $renderdata, 'header' => $header));
                    $html = $renderdata->getBefore() . $renderdata->getMiddle() . $renderdata->getAfter();
                    if ($useajax):
                        $content[$block] = $html;
                    else:
                        $this->_flushBlockContent($block, $html, $showelements);
                    endif;
                endif;
            elseif ($block == 'tier'):
                $html = $layout->createBlock('catalog/product_view')->getTierPriceHtml();
                if ($useajax):
                    $content[$block] = $html;
                else:
                    $this->_flushBlockContent($block, $html, $showelements);
                endif;
            elseif (strpos($block, 'eprice_') !== false):
                $product = explode('_', $block);
                $product = end($product);
                $product = Mage::getModel('catalog/product')->load((int) $product);
                if ($product):
                    $html = $this->_getListBlock($layout)->getPriceHtml($product, true);
                    if ($useajax):
                        $content[$block] = $html;
                    else:
                        $this->_flushBlockContent($block, $html, $showelements);
                    endif;
                endif;
            elseif (strpos($block, 'creview_') !== false):
                $product = explode('_', $block);
                $product = end($product);
                $product = Mage::getModel('catalog/product')->load((int) $product);
                $html = $this->_getListBlock($layout)->getReviewsSummaryHtml($product, 'short');
                if ($useajax):
                    $content[$block] = $html;
                else:
                    $this->_flushBlockContent($block, $html, $showelements);
                endif;
            elseif (strpos($block, 'ecart_') !== false):
                $product = explode('_', $block);
                $product = end($product);
                $product = Mage::getModel('catalog/product')->load((int) $product);
                $categorymode = Mage::helper('evolvedcaching')->getCategoryMode();
                if ($product->isSaleable()):
                    switch ($categorymode):
                        case 'list':
                            $before = '<p><button type="button" title="' . $this->_getListBlock($layout)->__('Add to Cart') . '" class="button btn-cart" onclick="setLocation(\'';
                            $middle = $this->_getListBlock($layout)->getAddToCartUrl($product);
                            $after = '\')"><span><span>' . $this->_getListBlock($layout)->__('Add to Cart') . '</span></span></button></p>';
                            $event = 'evolved_caching_block_product_list_list_cart_render';
                            break;
                        default:
                            $before = '<button type="button" title="' . $this->_getListBlock($layout)->__('Add to Cart') . '" class="button btn-cart" onclick="setLocation(\'';
                            $middle = $this->_getListBlock($layout)->getAddToCartUrl($product);
                            $after = '\')"><span><span>' . $this->_getListBlock($layout)->__('Add to Cart') . '</span></span></button>';
                            $event = 'evolved_caching_block_product_list_grid_cart_render';
                            break;
                    endswitch;
                    $renderdata = new Varien_Object(array(
                        'before' => $before,
                        'middle' => $middle,
                        'after' => $after,
                        'complete' => $before . $middle . $after
                    ));
                    Mage::dispatchEvent($event, array('render_data' => $renderdata, 'product' => $product));
                    $html = $renderdata->getBefore() . $renderdata->getMiddle() . $renderdata->getAfter();
                    if ($useajax):
                        $content[$block] = $html;
                    else:
                        $this->_flushBlockContent($block, $html, $showelements);
                    endif;
                else:
                    $before = '<p class="availability out-of-stock"><span>';
                    $middle = $this->_getListBlock($layout)->__('Out of stock');
                    $after = '</span></p>';
                    $renderdata = new Varien_Object(array(
                        'before' => $before,
                        'middle' => $middle,
                        'after' => $after,
                        'complete' => $before . $middle . $after
                    ));
                    Mage::dispatchEvent('evolved_caching_block_product_list_outofstock_cart_render', array('render_data' => $renderdata, 'product' => $product));
                    $html = $renderdata->getBefore() . $renderdata->getMiddle() . $renderdata->getAfter();
                    if ($useajax):
                        $content[$block] = $html;
                    else:
                        $this->_flushBlockContent($block, $html, $showelements);
                    endif;
                endif;
            elseif (strpos($block, 'preview_') !== false):
                $product = explode('_', $block);
                $product = end($product);
                $product = Mage::getModel('catalog/product')->load((int) $product);
                if ($isreview):
                    if (!Mage::registry('product')):
                        Mage::register('product', $product);
                    endif;
                    $review = $layout->createBlock('review/product_view');
                else:
                    $review = $layout->createBlock('catalog/product_list');
                endif;
                $html = $review->getReviewsSummaryHtml($product, false, true);
                if ($useajax):
                    $content[$block] = $html;
                else:
                    $this->_flushBlockContent($block, $html, $showelements);
                endif;
            else:
                if ($block == 'messages' || $block == 'global_messages'):
                    if ($block == 'messages'):
                        $controller->initLayoutMessages(
                            array(
                                'catalog/session',
                                'checkout/session',
                                'catalogsearch/session',
                                'customer/session',
                                'paypal/session',
                                'review/session',
                                'tag/session',
                                'wishlist/session'
                            )
                        );
                        $html = $layout->getMessagesBlock()->toHtml();
                    else:
                        $html = $layout->getBlock('global_messages')->toHtml();
                    endif;
                    if ($useajax):
                        $content[$block] = $html;
                    else:
                        $this->_flushBlockContent($block, $html, $showelements);
                    endif;
                elseif ($thisblock = $layout->getBlock($block)):
                    if ($block == 'breadcrumbs'):
                        $url = Mage::helper('core/url')->getCurrentUrl();
                        $area = evolved::getBlockArea($url);
                        if (!empty($area)):
                            if ($area == 'category' || $area == 'product'):
                                $layout->createBlock('catalog/breadcrumbs');
                            elseif ($area == 'cms' || $area == 'home'):
                                $layout->createBlock('cms/page');
                            endif;
                        endif;
                    endif;
                    $html = $thisblock->toHtml();
                    if ($useajax):
                        $content[$block] = $html;
                    else:
                        $this->_flushBlockContent($block, $html, $showelements);
                    endif;
                endif;
            endif;
        endforeach;
        
        if (!$useajax):
            exit();
        endif;
        
        return $content;
    }
    
    private function _flushBlockContent($id, $html, $showelements)
    {
        Mage::dispatchEvent('evolved_caching_bigpipe_flush', array('id' => $id, 'html' => $html, 'showelements' => $showelements));
    }
    
    private function _getListBlock($layout)
    {
        if (!isset($this->_listblock)):
            $this->_listblock = $layout->createBlock('catalog/product_list');
        endif;
        
        return $this->_listblock;
    }
    
    protected function _getFormKey()
    {
        return Mage::getSingleton('core/session', array('name' => 'frontend'))->getFormKey();
    }
}