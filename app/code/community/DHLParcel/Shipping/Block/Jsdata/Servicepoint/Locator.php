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

class DHLParcel_Shipping_Block_Jsdata_Servicepoint_Locator extends Mage_Core_Block_Template
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

    public function getLanguage()
    {
        $locale = Mage::app()->getLocale()->getLocaleCode();

        if (empty($locale)) {
            return 'en';
        }

        $localeParts = explode('_', $locale);
        return strtolower($localeParts[0]);
    }
}
