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
 * @author    Ron Oerlemans <plugins@dhl.com>
 * @author    Elmar van Wijnen <plugins@dhl.com>
 * @copyright ${YEAR} DHLParcel
 * @link      https://www.dhlparcel.nl/
 */

class DHLParcel_Shipping_Helper_Data extends Mage_Core_Helper_Abstract
{
    const MINIMUM_MAGENTO_VERSION = '1.9.3.0';

    /**
     * Convert weight in different measure types
     *
     * @param  mixed $value
     * @param  string $sourceWeightMeasure
     * @param  string $toWeightMeasure
     *
     * @return int|null|string     *
     * @throws \Zend_Measure_Exception
     */
    public function convertMeasureWeight($value, $sourceWeightMeasure, $toWeightMeasure)
    {
        if ($value) {
            $locale = Mage::app()->getLocale()->getLocale();
            $unitWeight = new Zend_Measure_Weight($value, $sourceWeightMeasure, $locale);
            $unitWeight->setType($toWeightMeasure);
            return $unitWeight->getValue();
        }
        return null;
    }

    /**
     * Convert dimensions in different measure types
     *
     * @param  mixed $value
     * @param  string $sourceDimensionMeasure
     * @param  string $toDimensionMeasure
     *
     * @return int|null|string
     * @throws \Zend_Measure_Exception
     */
    public function convertMeasureDimension($value, $sourceDimensionMeasure, $toDimensionMeasure)
    {
        if ($value) {
            $locale = Mage::app()->getLocale()->getLocale();
            $unitDimension = new Zend_Measure_Length($value, $sourceDimensionMeasure, $locale);
            $unitDimension->setType($toDimensionMeasure);
            return $unitDimension->getValue();
        }
        return null;
    }

    /**
     * @param $parcelTypes
     * @return array
     */
    public function parcelTypesToArray($parcelTypes)
    {
        $return = array();

        usort($parcelTypes, array($this, 'parcelTypeWeightSort'));

        foreach ($parcelTypes as $parcelType) {
            $return[$parcelType['key']] = __('%s (%d-%d kg, %dx%dx%d cm) ',
                ucfirst(strtolower($parcelType['key'])),
                $parcelType['minWeightKg'],
                $parcelType['maxWeightKg'],
                $parcelType['dimensions']['maxWidthCm'],
                $parcelType['dimensions']['maxLengthCm'],
                $parcelType['dimensions']['maxHeightCm']
            );
        }

        return $return;
    }

    protected function parcelTypeWeightSort($one, $two)
    {
        return $one['maxWeightKg'] > $two['maxWeightKg'];
    }

    /**
     * @param DHLParcel_Shipping_Model_Data_Api_Response_ServicePoint $servicePoint
     * @return string
     */
    public function formatServicePointToHtml($servicePoint)
    {
        $address = (array)$servicePoint->address;

        return "<strong>" . $servicePoint->name . "</strong><br />" .
            $address['street'] . " " . $address['number'] . $address['addition'] . "<br />" .
            $address['zipCode'] . " " . $address['city'] . "(" . $address['countryCode'] . ")";
    }

    /**
     * @param $path
     * @param $storeId
     * @return mixed
     */
    public function getConfig($path, $storeId)
    {
        return Mage::getStoreConfig($path, $storeId);
    }

    /**
     * @param $quote
     * @return bool
     */
    public function isQuoteInStock($quote)
    {
        $cartItems = $quote->getAllVisibleItems();
        foreach ($cartItems as $item) {
            /** @var Mage_Sales_Model_Quote_Item $item */
            $product = $item->getProduct()->getId();

            if ($option = $item->getOptionByCode('simple_product')) {
                $product = $option->getProduct();
            }

            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
            if ($item->getQty() > $stock->getQty()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return DHLParcel_Shipping_Model_Carrier
     */
    public function getCarrier()
    {
        return Mage::getModel('shipping/config')->getCarrierInstance('dhlparcel');
    }

    /**
     * Module version number.
     *
     * @return string
     */
    public function getModuleVersion()
    {
        return Mage::getConfig()->getNode('modules')->children()->DHLParcel_Shipping->version;
    }

    /**
     * Module version number.
     *
     * @return string
     */
    public function getModuleEnabled()
    {
        return Mage::getConfig()->getNode('modules')->children()->DHLParcel_Shipping->active;
    }

    /**
     * @return string
     */
    public function getModuleName()
    {
        return parent::_getModuleName();
    }

    /**
     * @return string
     */
    public function getModuleStatus()
    {
        $statusLines = [];
        $allIsFine = true;

        // Check magento version (only community a.t.m.)
        if (Mage::getEdition() === Mage::EDITION_COMMUNITY) {
            if (version_compare(Mage::getVersion(), self::MINIMUM_MAGENTO_VERSION, '<')) {
                $allIsFine = false;

                $statusLines[] = '<b>' . $this->__('Unknown Magento Version') . '</b>';
                $statusLines[] = '<span style="color:yellow">' . $this->__('Some features might not work as expected for this Magento version');
                $statusLines[] = ' - ' . $this->__('Recommended minimum Magento version: ') . self::MINIMUM_MAGENTO_VERSION;
                $statusLines[] = ' - ' . $this->__('Current Magento version: ') . Mage::getVersion() . '</span>';
            }
        }

        if (!$this->getModuleEnabled()) {
            $allIsFine = false;
            $statusLines[] = '<b>' . $this->__('Status') . '</b><br /><span style="color:#EB5E00">' . $this->__('The module is currently disabled.') . '</span>';
        }

        if ($allIsFine) {
            $statusLines[] = '<b>'.$this->__('Status').'</b><br /><span style="color:green">'.$this->__('The module is currently active.').'</span>';
        }

        return nl2br(implode(PHP_EOL, $statusLines));
    }

    /**
     * Magento version number.
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return Mage::getVersion();
    }

    /**
     * Returns the locale
     *
     *  example: nl_NL
     *
     * @return string
     */
    public function getLocale()
    {
        $lang = Mage::getStoreConfig('general/locale/code');
        return $lang;
    }

    /**
     * Check if the module is active
     * and enabled in the
     * backend
     *
     * @return bool
     */
    public function isActive()
    {
        if ($this->isModuleEnabled() === false) {
            return false;
        }

        if ( Mage::getStoreConfigFlag('carriers/dhlparcel/active') === false) {
            return false;
        }

        return true;
    }

    /**
     * Check if this order is an
     * order that needs to be handled by
     * our module
     *
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    public function isDHLOrder(Mage_Sales_Model_Order $order)
    {
        $shippingMethod = $order->getData('shipping_method');
        $shippingMethodParts = explode('_', $shippingMethod);

        return ($shippingMethodParts[0] == DHLParcel_Shipping_Model_Carrier::CODE);
    }
}
