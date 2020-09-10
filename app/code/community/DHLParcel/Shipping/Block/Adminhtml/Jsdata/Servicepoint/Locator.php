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
 *  @copyright 2018 DHLParcel
 *  @link      https://www.dhlparcel.nl/
 */

class DHLParcel_Shipping_Block_Adminhtml_Jsdata_Servicepoint_Locator extends Mage_Adminhtml_Block_Abstract
{

    public function getGatewayUrl()
    {
        $url = Mage::getStoreConfig('carriers/dhlparcel/gateway_url');
        return rtrim($url, '/');
    }

    public function getMapsKey()
    {
        return Mage::getStoreConfig('carriers/PS_dhlparcel/google_maps_api_key');
    }

    public function getSelectUrl()
    {
        /** @var Mage_Adminhtml_Helper_Data $helper */
        $helper = Mage::helper('adminhtml');
        return $helper->getUrl('adminhtml/dhlparcel_ajax/getServicePoint');
    }

    public function getPostcode()
    {
        /** @var Mage_Sales_Model_Order_Shipment $current_shipment */
        $current_shipment = Mage::registry('current_shipment');
        return $current_shipment->getShippingAddress()->getPostcode();
    }

    public function getCountryCode()
    {
        /** @var Mage_Sales_Model_Order_Shipment $current_shipment */
        $current_shipment = Mage::registry('current_shipment');
        return $current_shipment->getShippingAddress()->getCountryId();
    }

}
