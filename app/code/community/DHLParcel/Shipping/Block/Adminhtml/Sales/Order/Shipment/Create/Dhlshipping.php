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

class DHLParcel_Shipping_Block_Adminhtml_Sales_Order_Shipment_Create_Dhlshipping extends Mage_Adminhtml_Block_Abstract
{
    /**
     * @var null
     */
    protected $_toBusiness = null;

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->getTemplate()) {
            return '';
        }
        $html = $this->renderView();
        return $html;
    }

    /**
     * @return bool
     */
    protected function isDHLOrder()
    {
        $order = $this->getShipment()->getOrder();
        $helper = $this->getDhlHelper();

        return $helper->isDHLOrder($order);
    }

    /**
     * Get current shipment
     *
     * @return Mage_Sales_Model_Order_Shipment.
     */
    public function getShipment()
    {
        if ($this->hasShipment()) {
            return $this->_getData('shipment');
        }

        $shipment = Mage::registry('current_shipment');

        $this->setShipment($shipment);
        return $shipment;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getDeliveryOptions()
    {
        $deliveryOptionArgs = [
            'toCountry'   => $this->getShipment()
                ->getShippingAddress()
                ->getCountryId(),
            'fromCountry' => $this->getConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_COUNTRY_ID),
        ];

        if ($this->_toBusiness !== null) {
            $deliveryOptionArgs['toBusiness'] = $this->_toBusiness;
        }

        // Shipment
        $deliveryOptions = $this->getShipmentOptionsModel()
                                ->getDeliveryOptions($deliveryOptionArgs);

        foreach ($deliveryOptions as $k => $deliveryOption) {
            $deliveryOptions[$k]['is_selected'] = false;
            $deliveryMethod = $this->getShipment()->getOrder()->getData('shipping_method');
            $deliveryMethodParts = explode('_', $deliveryMethod);
            $deliveryCode = end($deliveryMethodParts);

            if ($deliveryCode == $deliveryOption['key']) {
                $deliveryOptions[$k]['is_selected'] = true;
            }
        }

        return $deliveryOptions;
    }

    /**
     * @param $deliveryOption
     * @return array
     * @throws Varien_Exception
     */
    public function getShipmentOptions($deliveryOption)
    {
        $helper = $this->getDhlHelper();
        $chosenShipmentOptions = explode(',', $this->getShipment()->getOrder()->getData('dhlparcel_shipping_options'));

        // Build delivery options as a string
        $deliveryOptionsString = null;
        if (is_array($deliveryOption['key'])) {
            $deliveryOptionsString = implode(',', $deliveryOption['key']);
        }

        $serviceOptionsArgs =[
            'toCountry'     => $this->getShipment()
                ->getShippingAddress()
                ->getCountryId(),
            'fromCountry'   => $this->getConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_COUNTRY_ID),
            'option'        => $deliveryOptionsString
        ];

        if ($this->_toBusiness !== null) {
            $serviceOptionsArgs['toBusiness'] = $this->_toBusiness;
        }

        $shipmentOptions = $this->getShipmentOptionsModel()->getServiceOptions($serviceOptionsArgs, $deliveryOption);

        // Add default shipment options
        if (is_string($deliveryOption['key']) && $this->getShipmentOptionsModel()->isAllowedDeliveryMethod($deliveryOption['key'])) {
            $defaultShipmentOptions = $this->getShipmentOptionsModel()->getDefaultServiceOptions($deliveryOption['key']);

            if (!empty($defaultShipmentOptions)) {
                $chosenShipmentOptions = array_merge($chosenShipmentOptions, $defaultShipmentOptions);
            }
        }

        foreach ($shipmentOptions as $k => $shipmentOption) {
            $shipmentOptions[$k]['is_selected'] = in_array($shipmentOption['key'], $chosenShipmentOptions);
        }

        //
        if ($this->getConfig('carriers/dhlparcel_labels/label_print_ordernumer')) {
            if (array_key_exists('REFERENCE', $shipmentOptions)) {
                $shipmentOptions['REFERENCE']['is_selected'] = 1;
                $shipmentOptions['REFERENCE']['value'] = $helper->getReferenceValue($this->getShipment());
            }

            if (array_key_exists('REFERENCE2', $shipmentOptions)) {
                $shipmentOptions['REFERENCE2']['is_selected'] = 1;
                $shipmentOptions['REFERENCE2']['value'] = $helper->getReference2Value($this->getShipment());
            }
        }

        return $shipmentOptions;
    }

    /**
     * @param array $options
     * @return array
     * @throws Varien_Exception
     */
    public function getParcelTypes($options = array())
    {
        if (empty($options)) {
            $options = explode(',', $this->getShipment()->getOrder()->getData('dhlparcel_shipping_options'));
            $deliveryMethod = $this->getShipment()->getOrder()->getData('shipping_method');
            $deliveryCodeParts = explode('_', $deliveryMethod);
            $deliveryCode = end($deliveryCodeParts);
            $options[] = $deliveryCode;
        }

        $parcelTypes = $this->getShipmentOptionsModel()->getParcelTypes([
            'toCountry'     => $this->getShipment()
                ->getShippingAddress()
                ->getCountryId(),
            'fromCountry'   => $this->getConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_COUNTRY_ID),
            'option'        => implode(',', $options)
        ]);

        // Helper Data
        return $this->getDhlHelper()->parcelTypesToArray($parcelTypes);
    }

    /**
     * @return bool|Varien_Object
     * @throws Exception
     */
    public function getSelectedServicePoint()
    {
        $servicePointId = $this->getShipment()->getOrder()->getData('dhlparcel_servicepoint');
        $countryCode = $this->getShipment()->getShippingAddress()->getCountryId();

        /** @var DHLParcel_Shipping_Model_Service_ServicePoint $servicePointService */
        $servicePointService = Mage::getSingleton('dhlparcel_shipping/service_servicePoint');

        // When there is no parcelshop selected, return false
        if (empty($servicePointId)) {
            return false;
        }
        return $servicePointService->get($countryCode, $servicePointId);
    }

    /**
     * @return \DHLParcel_Shipping_Helper_Labels
     */
    public function getDhlHelper()
    {
        return Mage::helper('dhlparcel_shipping/labels');
    }

    /**
     * @return DHLParcel_Shipping_Model_ShipmentOptions|false
     */
    public function getShipmentOptionsModel()
    {
        return Mage::getModel('dhlparcel_shipping/shipmentoptions');
    }

    /**
     * @param $path
     * @return mixed
     */
    public function getConfig($path)
    {
        return Mage::getStoreConfig($path, $this->getShipment()->getOrder()->getStore()->getId());
    }

    /**
     * @return string
     */
    public function getParcelTypesUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/dhlparcel_ajax/getparceltypes');
    }

    /**
     * @return string
     */
    public function getShippingFormUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/dhlparcel_ajax/getshippingform');
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->getShipment()->getOrderId();
    }

    public function setToBusiness($toBusiness)
    {
        $this->_toBusiness = (bool) $toBusiness;
    }

    /**
     * @return bool|null
     */
    public function getBusinessSelection()
    {
        if ($this->_toBusiness !== null) {
            return $this->_toBusiness;
        }

        return (bool) Mage::getModel('shipping/config')->getCarrierInstance('dhlparcel')->getConfigData('b2b');
    }

}
