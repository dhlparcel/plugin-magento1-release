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

class DHLParcel_Shipping_Model_Checkout_Observer
{
    protected function getCarrier()
    {
        return Mage::getModel('shipping/config')->getCarrierInstance('dhlparcel');
    }

    /**
     * At the start of the checkout process
     * start with none of any DHL
     * shipment options.
     *
     * Especcialiy for onestepcheckout
     *
     * @param Varien_Event_Observer $event
     */
    public function preDispatchCheckout(Varien_Event_Observer $event)
    {
        $quote = Mage::getModel('checkout/cart')->getQuote();

        // Reset selected options
        $quote->setData('dhlparcel_shipping_options', '');
        $quote->collectTotals();
        $quote->save();
    }

    public function saveShippingMethod(Varien_Event_Observer $event)
    {
        $data = $event->getRequest()->getPost();
        $shippingOptions = Mage::getModel('dhlparcel_shipping/shipmentoptions');
        $allowedShippingMethods = $shippingOptions->getAllowedShippingMethods();

        if ($data && is_array($data) && !empty($data['shipping_method']) && $data['shipping_method'] == 'dhlparcel_PS' && !empty($data['dhlparcel-servicepoint-select'])) {
            $event->getQuote()->setData('dhlparcel_servicepoint', $data['dhlparcel-servicepoint-select']);
        }

        // Get Timewindow if settled
        $shippingOptions = array();
        if (
            $data
            && is_array($data)
            && in_array($data['shipping_method'], $allowedShippingMethods)
            && array_key_exists('dhlparcel_shipping_options', $data)
            && isset($data['dhlparcel_shipping_options'][$data['shipping_method']]['time_window'])
        ) {
            $timeWindowData = $data['dhlparcel_shipping_options'][$data['shipping_method']]['time_window'];
            $timeWindowDataParts = explode('_', $timeWindowData);

            // Add selected shipping date
            $event->getQuote()->setData('dhlparcel_shipping_date', $timeWindowDataParts[0]);

            // Fill Shipping Options
            if (!empty($timeWindowDataParts[1])) {
                $options = $timeWindowDataParts[1];
                $shippingOptions = explode(',', $options);
            }
        }

        if (!is_array($shippingOptions)) {
            $shippingOptions = array();
        }

        // Save chosen shipment options
        if (
            $data
            && is_array($data)
            && in_array($data['shipping_method'], $allowedShippingMethods)
            && array_key_exists('dhlparcel_shipping_options', $data)
            && isset($data['dhlparcel_shipping_options'][$data['shipping_method']])
            && is_array($data['dhlparcel_shipping_options'][$data['shipping_method']])
        ) {
            foreach ($data['dhlparcel_shipping_options'][$data['shipping_method']] as $k => $selectedOption) {
                if (strpos($selectedOption, '_') !== false) {
                    continue;
                }
                $shippingOptions[] = $selectedOption;
            }

            $event->getQuote()->setData('dhlparcel_shipping_options', implode(',', $shippingOptions));
        }

        return $event;
    }

}