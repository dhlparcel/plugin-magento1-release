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

class DHLParcel_Shipping_Adminhtml_Dhlparcel_AjaxController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/sales/order/actions/ship');
    }

    public function getParcelTypesAction()
    {
        $helper = Mage::helper('dhlparcel_shipping');
        $parameters = $this->getRequest()->getPost();

        $deliveryMethod = $parameters['dhlparcel_shipping_delivery_method'];
        $toBusiness = (isset($parameters['dhlparcel_shipping_to_business']) && $parameters['dhlparcel_shipping_to_business'] == 'on') ? true : false;

        $args = array();
        $args['toCountry'] = $parameters['dhl_to_country'];
        $args['option'] = array();
        $args['option'][] = $parameters['dhlparcel_shipping_delivery_method'];
        $args['toBusiness'] = $toBusiness;

        if (isset($parameters['dhlparcel_shipping_options'][$deliveryMethod])) {
            foreach ($parameters['dhlparcel_shipping_options'][$deliveryMethod] as $key => $option) {
                if (is_array($option) && array_key_exists('VALUE', $option) && empty($option['VALUE'])) {
                    continue;
                }

                $args['option'][] = $key;
            }
        }

        $shipmentOptions = Mage::getModel('dhlparcel_shipping/shipmentoptions');
        $shipmentOptionsArray =  $helper->parcelTypesToArray($shipmentOptions->getParcelTypes($args));

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode([
            'parceltypes' => $shipmentOptionsArray,
            'errormessage' => __('No labels can be created with the selected service options.'),
        ]));
    }

    public function getShippingFormAction()
    {
        $parameters = $this->getRequest()->getPost();

        if (empty($parameters['orderId'])) {
            return;
        }
        if (!array_key_exists('toBusiness', $parameters)) {
            return;
        }

        $orderId = $parameters['orderId'];
        $this->_initShipment($orderId);

        $toBusiness = $parameters['toBusiness'] == 'true';

        $block = Mage::getSingleton('core/layout')
            ->createBlock('dhlparcel_shipping/adminhtml_sales_order_shipment_create_dhlshipping')
            ->setTemplate('dhlparcel/sales/order/shipment/create/dhl_shipping.phtml');
        $block->setToBusiness($toBusiness);
        $html = $block->toHtml();

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode([
            'form' => $html
        ]));
    }

    /**
     * @param $orderId
     * @return bool
     * @throws Mage_Core_Exception
     */
    protected function _initShipment($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);

        // Copied validation from ShipmentController
        if (!$order->getId()) {
            return false;
        }

        if ($order->getForcedDoShipmentWithInvoice()) {
            return false;
        }

        if (!$order->canShip()) {
            return false;
        }

        $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment([]);
        Mage::register('current_shipment', $shipment);
        return $shipment;
    }

    public function getServicePointAction()
    {
        // get Helper
        /** @var DHLParcel_Shipping_Helper_Data $helper */
        $helper = Mage::helper('dhlparcel_shipping');
        $parcelShopId = $this->getRequest()->getParam('id');
        $parcelShopCountryCode = $this->getRequest()->getParam('countryCode');

        /** @var DHLParcel_Shipping_Model_Service_ServicePoint $servicePointService */
        $servicePointService = Mage::getSingleton('dhlparcel_shipping/service_servicePoint');

        $servicePoint = $servicePointService->get($parcelShopCountryCode, $parcelShopId);

        $servicePointInfo = $helper->formatServicePointToHtml($servicePoint);

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode([
            'parcelshopFormatted' => $servicePointInfo,
            'parcelshopId'        => $servicePoint->id
        ]));
    }
}
