<?php
class HusseyCoding_EvolvedCaching_Model_Observer_Render_RedirectArguments extends HusseyCoding_EvolvedCaching_Model_Observer_Render_Abstract
{
    public function frontendHttpResponseSendBefore($observer)
    {
        $request = Mage::app()->getRequest();
        if ($request->getPost('evolvedupdate')):
            $response = $observer->getResponse();
            if ($response->isRedirect()):
                $headers = $response->getHeaders();
                if (!empty($headers) && is_array($headers)):
                    foreach ($headers as $id => $header):
                        if (!empty($header['name']) && strtolower($header['name']) == 'location'):
                            if (!empty($header['value'])):
                                $url = $header['value'];
                                $data = $request->getPost();
                                if (!empty($data) && is_array($data)):
                                    $args = array();
                                    foreach ($data as $key => $value):
                                        $args[] = $key . '=' . $value;
                                    endforeach;
                                    $args = implode('&', $args);
                                    if (strpos($url, '?')):
                                        $url = $url . '&' . $args;
                                    else:
                                        $url = $url . '?' . $args;
                                    endif;
                                    $response->setHeader('Location', $url, true);
                                endif;
                            endif;
                        endif;
                    endforeach;
                endif;
            endif;
        endif;
    }
}