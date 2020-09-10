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
 * @category  Dhlparcel
 * @author    Shin Ho <plugins@dhl.com>
 * @author    Rudger Gravenstein <plugins@dhl.com>
 * @author    Ron Oerlemans <plugins@dhl.com>
 * @copyright 2018 DHLParcel
 * @link      https://www.dhlparcel.nl/
 */

class DHLParcel_Shipping_Block_Jsdata_Checkout extends Mage_Core_Block_Template
{

    public function getRefreshOptionsUrl()
    {
        return Mage::getUrl('dhlparcel_shipping/ajax/refreshOptions', array('_secure' => $this->isSecure()));
    }

    public function getSaveOptionsUrl()
    {
        return Mage::getUrl('dhlparcel_shipping/ajax/saveOptions', array('_secure' => $this->isSecure()));
    }

    /**
     * Since _isSecure() does not exists before
     * 1.9.2 we create this method
     * to check if method exists or return
     * true
     *
     * @return boolean
     */
    protected function isSecure()
    {
        $isSecure = true;
        if (method_exists($this, '_isSecure')) {
            $isSecure = $this->_isSecure();
        }

        return $isSecure;
    }
}
