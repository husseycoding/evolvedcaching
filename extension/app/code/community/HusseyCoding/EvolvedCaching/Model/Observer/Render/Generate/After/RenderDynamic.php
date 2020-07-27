<?php
class HusseyCoding_EvolvedCaching_Model_Observer_Render_Generate_After_RenderDynamic extends HusseyCoding_EvolvedCaching_Model_Observer_Render_Generate_After_RenderDynamicAbstract
{
    public function frontendControllerActionLayoutGenerateBlocksAfter($observer)
    {
        $request = Mage::app()->getRequest();
        if ($request->getPost('evolvedupdate')):
            $this->_updateSession();
            $layout = $observer->getLayout();
            $this->_triggerMergeRebuild($layout);
            $controller = $observer->getAction();
            
            $content = array();
            $blocks = Mage::app()->getRequest()->getPost();
            unset($blocks['evolvedupdate']);
            if (isset($blocks['evolved'])):
                unset($blocks['evolved']);
            endif;
            $isreview = isset($blocks['id']) && $blocks['id'] != 'false' ? true : false;
            if (isset($blocks['id'])):
                unset($blocks['id']);
            endif;
            
            if (!evolved::getUseAjax()):
                foreach (Mage::helper('evolvedcaching')->getBlocks() as $exclude):
                    $santised = preg_replace('/[^A-Za-z0-9]{1}/', '_', $exclude);
                    if (array_key_exists($santised, $blocks)):
                        unset($blocks[$santised]);
                    endif;
                    $blocks[$exclude] = $exclude;
                endforeach;
            endif;
            
            if ($blocks):
                $content = $this->_getBlocksContent($layout, $blocks, $content, $isreview, $controller);
            endif;

            $formkey = $this->_getFormKey();

            if ($content):
                $params = Mage::app()->getRequest()->getParams();
                if (isset($params['evolved'])):
                    $response = Zend_Json::encode(array('content' => $content, 'showelements' => true, 'formkey' => $formkey));
                else:
                    $response = Zend_Json::encode(array('content' => $content, 'showelements' => false, 'formkey' => $formkey));
                endif;
            else:
                $response = Zend_Json::encode(array('formkey' => $formkey));
            endif;
            
            if (evolved::getUseAjax()):
                Mage::app()->getResponse()->setBody($response)->sendResponse();
                exit();
            endif;
        endif;
    }
}