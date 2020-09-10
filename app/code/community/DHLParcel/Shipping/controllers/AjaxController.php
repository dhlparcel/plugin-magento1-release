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

class DHLParcel_Shipping_AjaxController extends Mage_Core_Controller_Front_Action
{
    public function refreshOptionsAction()
    {
        $parameters = $this->getRequest()->getPost();

        if (empty($parameters['optionKey']) || !is_string($parameters['optionKey'])) {
            return;
        }

        $html = '';

        // Load rate for block
        $cart = Mage::getSingleton('checkout/cart');
        $address = $cart->getQuote()->getShippingAddress();
        $address->setCollectShippingrates(true);
        $cart->save();
        $code = 'dhlparcel_' . $parameters['optionKey'];

        // Find if our shipping has been included.
        $rates = $address->collectShippingRates()
            ->getGroupedAllShippingRates();

        $carriers = Mage::getsingleton("shipping/config")->getAllCarriers();
        foreach ($rates as $carrierKey => $rates) {
            $carrier = $carriers[$carrierKey];
            foreach ($rates as $rate) {
                if ($rate->getCode() == $code) {
                    if ($carrier->getFormBlock()) {
                        $block = Mage::app()->getLayout()->createBlock($carrier->getFormBlock());
                        $block->setMethodCode($code);
                        $block->setRate($rate);
                        $block->setMethodInstance($carrier);
                        $html = $block->toHtml();
                    }
                }
            }
        }
        // End of rate block

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode([
            'html' => $html
        ]));
    }

    public function saveOptionsAction()
    {
        $cart = Mage::getSingleton('checkout/cart');
        $data = $this->getRequest()->getPost();
        $shippingOptionsModel = Mage::getModel('dhlparcel_shipping/shipmentoptions');
        $allowedShippingMethods = $shippingOptionsModel->getAllowedShippingMethods();
        $shippingOptionsQuote = array();

        // Get Timewindow if settled
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
            $cart->getQuote()->setData('dhlparcel_shipping_date', $timeWindowDataParts[0]);

            // Fill Shipping Options
            if (!empty($timeWindowDataParts[1])) {
                $options = $timeWindowDataParts[1];
                $shippingOptionsQuote = explode(',', $options);
            }
        }

        if (!is_array($shippingOptionsQuote)) {
            $shippingOptionsQuote = array();
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
                $shippingOptionsQuote[] = $selectedOption;
            }

            $cart->getQuote()->setData('dhlparcel_shipping_options', implode(',', $shippingOptionsQuote));
            $cart->getQuote()->save();
        } else {
            $cart->getQuote()->setData('dhlparcel_shipping_options', '');
            $cart->getQuote()->save();
        }

    }
}
