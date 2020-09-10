<?php
/**
 * Dhl Shipping
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to
 * newer versions in the future.
 *
 *  PHP version 5.6+
 *
 *  @category  Dhlparcel
 *  @author    Shin Ho <plugins@dhl.com>
 *  @author    Rudger Gravenstein <plugins@dhl.com>
 *  @author    Ron Oerlemans <plugins@dhl.com>
 *  @copyright 2019 DHLParcel
 *  @link      https://www.dhlparcel.nl/
 */

class DHLParcel_Shipping_Block_Adminhtml_Sales_Order_View_Servicepoint extends Mage_Adminhtml_Block_Abstract
{
    public function _toHtml()
    {
        // Ignore when order isn't a ServicePoint-order
        if (!$this->isServicePointOrder()) {
            return '';
        }

        return parent::_toHtml();
    }

    public function getOrder()
    {
        return Mage::registry('current_order');
    }


    protected function isServicePointOrder()
    {
        $order = $this->getOrder();
        $shippingMethod = $order->getData('shipping_method');

        if ($shippingMethod != 'dhlparcel_PS') {
            return false;
        }

        if ($order->getData('dhlparcel_servicepoint') == '' || $order->getData('dhlparcel_servicepoint') == 0) {
            return false;
        }

        return true;
    }

    /**
     * @return bool|DHLParcel_Shipping_Model_Data_Api_Response_ServicePoint
     */
    public function getServicePoint()
    {
        $order = $this->getOrder();
        $servicePointId = $order->getData('dhlparcel_servicepoint');
        $servicePointCountry = $order->getShippingAddress()->getCountryId();

        /** @var DHLParcel_Shipping_Model_Service_ServicePoint $servicePointService */
        $servicePointService = Mage::getSingleton('dhlparcel_shipping/service_servicePoint');

        return $servicePointService->get($servicePointCountry, $servicePointId);
    }
}
