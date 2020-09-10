<?php

class DHLParcel_Shipping_Adminhtml_Dhlparcel_SystemController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/system/config/carriers');
    }

    /**
     * Check authentication and return
     * all nessary info
     */
    public function testauthenticationAction()
    {
        /** @var DHLParcel_Shipping_Model_Api_Connector $connector */
        $connector = Mage::getSingleton('dhlparcel_shipping/api_connector');
        $return = array ();

        $authenticationData = $connector->testAuthenticate(
            $this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_key')
        );

        if ($authenticationData === false) {
            $return['valid'] = false;
            $return['button_text'] = __('API Credentials are invalid');
        } else {
            $return['valid'] = true;
            $return['account_ids'] = $authenticationData['accounts'];
            $return['button_text'] =  __('API Credentials are VALID');
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($return));
    }
}
