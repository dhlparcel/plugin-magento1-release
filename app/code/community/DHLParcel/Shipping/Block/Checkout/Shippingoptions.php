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
 * @copyright ${YEAR} DHLParcel
 * @link      https://www.dhlparcel.nl/
 */

class DHLParcel_Shipping_Block_Checkout_Shippingoptions extends Mage_Checkout_Block_Onepage_Shipping_Method_Available
{

    /**
     * Set template for shipping options
     */
    public function _construct()
    {
        parent::_construct();

        $this->setTemplate('dhlparcel_shipping/checkout/shippingoptions.phtml');
    }

    /**
     * @param $rate
     * @return array
     * @throws Exception
     */
    public function getCheckoutShipmentOptions($rate)
    {
        /** @var DHLParcel_Shipping_Model_ShipmentOptions $shipmentOptions */
        $shipmentOptions = $this->getShipmentOptionsModel();

        $deliveryOptions = $shipmentOptions->getDeliveryOptions([
            'toCountry' => Mage::getSingleton('checkout/cart')->getQuote()
                ->getShippingAddress()
                ->getCountryId(),
            'fromCountry' => Mage::getStoreConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_COUNTRY_ID)
        ]);

        $deliveryOptionKey = $rate->getMethod();
        $cartSubTotal = $this->getCartSubtotal();

        foreach ($deliveryOptions as $deliveryOption) {
            // DOOR | PS
            if ($deliveryOption['key'] !== $deliveryOptionKey) {
                    continue;
            }

            if (is_array($deliveryOption)) {
                return $shipmentOptions->getServiceOptions([
                    'toCountry' => Mage::getSingleton('checkout/cart')->getQuote()
                        ->getShippingAddress()
                        ->getCountryId(),
                    'fromCountry' => Mage::getStoreConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_COUNTRY_ID),
                    'option' => [$deliveryOptionKey]
                ], $deliveryOption, $cartSubTotal);
            }

            break;
        }

        return [];
    }

    /**
     * @param $rate
     * @return array
     * @throws Exception
     */
    public function getTimeWindows($rate)
    {
        $countryCode = Mage::getSingleton('checkout/cart')->getQuote()->getShippingAddress()->getCountryId();
        $postalCode = str_replace(" ", "", strtoupper(Mage::getSingleton('checkout/cart')->getQuote()->getShippingAddress()->getPostcode()));

        // Get Time Frames
        return $this->getShipmentOptionsModel()->getTimeWindows($countryCode, $postalCode, $this->getCheckoutShipmentOptions($rate));
    }

    /**
     * Check if the order is in stock
     *
     * @return bool
     */
    protected function isOrderInStock()
    {
        /** @var DHLParcel_Shipping_Helper_Data $helper */
        $helper = Mage::helper('dhlparcel_shipping');

        return $helper->isQuoteInStock(Mage::getSingleton('checkout/cart')->getQuote());
    }

    /**
     * @param $rate
     * @return bool
     * @throws Exception
     */
    public function timeWindowsEnabled($rate)
    {
        $timeFrames = $this->getTimeWindows($rate);

        return (
            $this->getShipmentOptionsModel()->timeWindowsEnabled(Mage::getSingleton('checkout/cart')->getQuote()
                ->getShippingAddress()
                ->getCountryId())
                &&
            count($timeFrames) > 0
                &&
            (Mage::getStoreConfig('carriers/dhlparcel_time_windows/stock_only') == 0 || $this->isOrderInStock())
        );
    }

    /**
     * @return false|DHLParcel_Shipping_Model_Shipmentoptions
     */
    protected function getShipmentOptionsModel()
    {
        return Mage::getModel('dhlparcel_shipping/shipmentoptions');
    }

    /**
     * Get cart subtotal to determine right option prices
     *
     * @return double
     */
    protected function getCartSubtotal()
    {
        return Mage::getModel('checkout/cart')->getQuote()->getSubtotal();
    }

}