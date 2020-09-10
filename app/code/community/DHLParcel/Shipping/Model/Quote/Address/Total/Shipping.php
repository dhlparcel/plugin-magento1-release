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
 *  @copyright ${YEAR} DHLParcel
 *  @link      https://www.dhlparcel.nl/
 */

class DHLParcel_Shipping_Model_Quote_Address_Total_Shipping
    extends Mage_Sales_Model_Quote_Address_Total_Shipping
{
    /**
     * Collect totals information about shipping
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return $this|Mage_Sales_Model_Quote_Address_Total_Shipping
     * @throws Varien_Exception
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        // Collect Totals
        Mage_Sales_Model_Quote_Address_Total_Abstract::collect($address);

        // Check if this address has items
        $items = $this->_getAddressItems($address);
        if (empty($items)) {
            return $this;
        }

        // Get Shipping Method
        $shippingMethod = $address->getShippingMethod();
        $shippingMethodsParts = explode('_', $shippingMethod);

        // Get Shipping Options Class
        /** @var  $dhlShippingOptions DHLParcel_Shipping_Model_ShipmentOptions */
        $dhlShippingOptions = Mage::getModel('dhlparcel_shipping/shipmentoptions');

        // Check if this is an DHL shipping options - or return default
        if (!$dhlShippingOptions->isAllowedShippingMethod($shippingMethod)) {
            /** @var Mage_Sales_Model_Quote_Address_Total_Shipping $totalModel */
            $totalModel = Mage::getModel('sales/quote_address_total_shipping');
            return $totalModel->collect($address);
        }

        // Collect Shipping Prices
        parent::collect($address);

        // Check if this is a DHL option
        if ($shippingMethodsParts[0] != DHLParcel_Shipping_Model_Carrier::CODE) {
            return $this;
        }

        /**
         * @var Mage_Sales_Model_Quote_Address_Rate $rate
         */
        foreach ($address->getAllShippingRates() as $rate) {
            if ($rate->getCode() != $shippingMethod) {
                continue;
            }

            $price = $rate->getPrice();

            // Calculate Fee
            $deliveryOptions = $dhlShippingOptions->getDeliveryOptions([
                'toCountry'     => Mage::getSingleton('checkout/cart')->getQuote()
                    ->getShippingAddress()
                    ->getCountryId(),
                'fromCountry'   => $this->getCarrier()->getConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_COUNTRY_ID)
            ]);
            $deliveryOptionKey = $rate->getMethod();
            $chosenServiceOptions = Mage::getSingleton('checkout/cart')->getQuote()->getData('dhlparcel_shipping_options');
            $chosenServiceOptions = explode(',', $chosenServiceOptions);

            $fee = 0;
            $serviceTitle = '';

            // Get Cart Subtotal
            $subtotal = $this->getCartSubtotal();

            foreach ($deliveryOptions as $deliveryOption) {
                if ($deliveryOption['key'] != $deliveryOptionKey) {
                    continue;
                }

                if (is_array($deliveryOption)) {
                    $serviceOptions = $dhlShippingOptions->getServiceOptions([
                        'toCountry'     => Mage::getSingleton('checkout/cart')->getQuote()
                            ->getShippingAddress()
                            ->getCountryId(),
                        'fromCountry'   => $this->getCarrier()->getConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_COUNTRY_ID),
                        'option'        => [$deliveryOptionKey]
                    ], $deliveryOption, $subtotal);

                    foreach ($serviceOptions as $serviceOption) {
                        if (in_array($serviceOption['key'], $chosenServiceOptions)) {
                            if ($serviceOption['is_available']) {
                                $fee += $serviceOption['price'];
                                $serviceTitle .= ' + ' . $serviceOption['customer_title'];
                            }
                        }
                    }
                }

                break;
            }

            $price += $fee;

            $amountPrice = $address->getQuote()->getStore()->convertPrice($price, false);
            $this->_setAmount($amountPrice);
            $this->_setBaseAmount($price);

            $shippingDescription = $rate->getCarrierTitle() . ' - ' . $rate->getMethodTitle() . $serviceTitle;

            $address->setShippingDescription(trim($shippingDescription, ' -'));
            break;
        }

        return $this;
    }

    /**
     * @return DHLParcel_Shipping_Model_Carrier
     */
    protected function getCarrier()
    {
        return Mage::getModel('shipping/config')->getCarrierInstance('dhlparcel');
    }

    private function getCartSubtotal()
    {
        $totals = Mage::getSingleton('checkout/session')->getQuote()->getTotals();
        $subtotal = $totals["subtotal"]->getValue();

        return $subtotal;
    }
}
