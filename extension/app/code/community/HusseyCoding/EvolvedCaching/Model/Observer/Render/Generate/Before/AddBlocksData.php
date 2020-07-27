<?php
class HusseyCoding_EvolvedCaching_Model_Observer_Render_Generate_Before_AddBlocksData extends HusseyCoding_EvolvedCaching_Model_Observer_Render_Generate_Before_AddBlocksDataAbstract
{
    private $_usecookie;
    private $_processed = array();
    private $_params;
    
    public function frontendCoreBlockAbstractToHtmlAfter($observer)
    {
        if (!isset($this->_params)):
            $this->_params = Mage::app()->getRequest()->getParams();
        endif;
        
        if (!isset($this->_params['evolvedupdate']) && !isset($this->_params['evolvedforward'])):
            $excluded = $this->_getExcluded();
            $name = $observer->getBlock()->getNameInLayout();
            if ($this->_isEnabled()):
                $this->_collectParams();

                if ($this->_dynamic):
                    $module = strtolower(Mage::app()->getRequest()->getModuleName());
                    if ($this->_welcome && $name == 'header'):
                        $this->_insertWelcomeElement($observer);
                    endif;
                    if ((Mage::registry('current_category') || $module == 'catalogsearch') && !Mage::registry('current_product')):
                        $this->_insertCategoryElements($observer, $name);
                    elseif (Mage::registry('current_product')):
                        $this->_insertProductElements($observer, $name);
                    endif;
                endif;
            endif;

            $restrict = $name == 'header' || $name == 'footer' ? true : false;
            if (!in_array($name, $this->_processed) && $this->_isEnabled()):
                $this->_processed[] = $name;
                if ($this->_isEnabled()):
                    if (!isset($this->_usecookie)):
                        $this->_usecookie = Mage::getStoreConfig('evolvedcaching/cookie/use');
                    endif;

                    $transport = $observer->getTransport();

                    if ($this->_usecookie):
                        $this->_insertCookieData($transport);
                    endif;
                    
                    if (!$restrict && !in_array($name, $excluded)):
                        $blocks = $this->_getBlocks();
                        $block = $observer->getBlock();
                        $name = $block->getNameInLayout();

                        if (in_array($name, $blocks)):
                            $id = preg_replace('/[^A-Za-z0-9]{1}/', '_', $name);
                            if ($holdinghtml = $this->_hasHoldingHtml($name)):
                                $style = '';
                                $class = ' evolved_holding';
                            else:
                                $style = ' style="display:none"';
                                $class = '';
                            endif;
                            $open = '<div' . $style . ' class="evolved_class' . $class . ' evolved_id-' . $id . '">';
                            $close = '</div>';
                            $action = Mage::app()->getRequest()->getActionName();
                            if ((@class_exists('evolved', false) && evolved::$excludedpage) || strtolower($action) == 'noroute'):
                                $html = $transport->getHtml();
                            else:
                                $html = !empty($holdinghtml) ? $holdinghtml : '';
                            endif;
                            $transport->setHtml($open . $html . $close);
                        endif;
                    endif;
                endif;
            endif;
        else:
            if (isset($this->_params['shownames'])):
                $excluded = $this->_getExcluded();
                $name = $observer->getBlock()->getNameInLayout();
                $restrict = $name == 'header' || $name == 'footer' ? true : false;
                if (!in_array($name, $this->_processed)):
                    $this->_processed[] = $name;
                    $this->_insertBlockLayoutNames($observer, $name, $excluded, $restrict);
                endif;
            elseif ($this->_isEnabled() && Mage::getStoreConfig('evolvedcaching/cookie/use')):
                $transport = $observer->getTransport();
                $this->_insertCookieData($transport);
            endif;
        endif;
    }
}