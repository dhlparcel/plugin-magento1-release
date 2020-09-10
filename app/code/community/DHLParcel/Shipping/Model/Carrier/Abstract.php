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

abstract class DHLParcel_Shipping_Model_Carrier_Abstract
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    /**
     * @param string $path
     *
     * @return string|null
     * @throws Varien_Exception
     */
    public function getConfig($path)
    {
        return Mage::getStoreConfig($path, $this->getStore());
    }

    /**
     * @param array $params
     *
     * @return array
     */
    protected function filterEmptyParams(array $params = [])
    {
        $filteredParams = array_filter($params, function ($v) {
            return $v !== null && $v !== '';
        });
        return $filteredParams;
    }

    /**
     * Do return of shipment
     * Implementation must be in overridden method
     *
     * @param $request
     *
     * @return Varien_Object
     */
    public function returnOfShipment($request)
    {
        return new Varien_Object();
    }

    /**
     * Return content types of package
     *
     * @param Varien_Object $params
     *
     * @return array
     */
    public function getContentTypes(Varien_Object $params)
    {
        return array();
    }

    /**
     * Get Container Types, that could be customized
     *
     * @return array
     */
    public function getCustomizableContainerTypes()
    {
        return $this->_customizableContainerTypes;
    }

    /**
     * Return delivery confirmation types of carrier
     *
     * @param Varien_Object|null $params
     *
     * @return array
     */
    public function getDeliveryConfirmationTypes(Varien_Object $params = null)
    {
        return array();
    }

    /**
     * Processing additional validation to check is carrier applicable.
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     *
     * @return Mage_Shipping_Model_Carrier_Abstract|Mage_Shipping_Model_Rate_Result_Error|boolean
     */
    public function proccessAdditionalValidation(Mage_Shipping_Model_Rate_Request $request)
    {
        return $this;
    }

    /**
     * Return weight in pounds
     *
     *
     * @param integer Weight in someone measure
     *
     * @return float Weight in pounds
     */
    public function convertWeightToLbs($weight)
    {
        return $weight;
    }

    /**
     * Whether this carrier has fixed rates calculation
     *
     * @return bool
     */
    public function isFixed()
    {
        return $this->_isFixed;
    }

    /**
     * Check if carrier has shipping tracking option available
     *
     * @return boolean
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * Check if carrier has shipping label option available
     *
     * @return boolean
     */
    public function isShippingLabelsAvailable()
    {
        return true;
    }


    /**
     * Is state province required
     *
     * @return bool
     */
    public function isStateProvinceRequired()
    {
        return false;
    }

    /**
     * Check if city option required
     *
     * @return boolean
     */
    public function isCityRequired()
    {
        return true;
    }

    /**
     * Determine whether zip-code is required for the country of destination
     *
     * @param string|null $countryId
     *
     * @return bool
     */
    public function isZipCodeRequired($countryId = null)
    {
        return true;
    }

    /**
     * For multi package shipments. Delete requested shipments if the current shipment
     * request is failed
     *
     * @param array $data
     * @return bool
     */
    public function rollBack($data)
    {
        return true;
    }


    /**
     * @return string
     * @throws Exception
     */
    public static function uuid()
    {
        $uuid4 = Uuid::uuid4();
        return $uuid4->toString();
    }

    /**
     * Returns all shipmentoptions selected for shipmentRequest
     *
     * @param \Mage_Shipping_Model_Shipment_Request $request
     *
     * @return array
     */
    abstract public function getShipmentOptionsForShipmentRequest(Mage_Shipping_Model_Shipment_Request $request);

    /**
     * @param $number
     * @return Mage_Core_Model_Abstract
     */
    protected function getTrackByNumber($number)
    {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');

        $trackSelect = $readConnection->select();
        $trackSelect->from($resource->getTableName('sales/shipment_track'), array('entity_id'));
        $trackSelect->where('track_number = ?', $number);

        $tracking = $readConnection->fetchRow($trackSelect);

        if (!is_array($tracking)) {
            return false;
        }

        $track = Mage::getModel('sales/order_shipment_track')->load($tracking['entity_id']);

        return $track;
    }

    /**
     * Prepare shipment request.
     * Validate and correct request information
     *
     * @param Varien_Object $request
     * @throws Varien_Exception
     */
    protected function _prepareShipmentRequest(Varien_Object $request)
    {
        $phonePattern = '/[\s\_\-\(\)]+/';
        $phoneNumber = $request->getShipperContactPhoneNumber();
        $phoneNumber = preg_replace($phonePattern, '', $phoneNumber);
        $request->setShipperContactPhoneNumber($phoneNumber);
        $phoneNumber = $request->getRecipientContactPhoneNumber();
        $phoneNumber = preg_replace($phonePattern, '', $phoneNumber);
        $request->setRecipientContactPhoneNumber($phoneNumber);
    }

}
