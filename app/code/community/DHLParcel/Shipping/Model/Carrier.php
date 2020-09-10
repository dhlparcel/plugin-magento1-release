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
 *  @author    Ron Oerlemans <plugins@dhl.com>
 *  @author    Elmar van Wijnen <plugins@dhl.com>
 *  @copyright ${YEAR} DHLParcel
 *  @link      https://www.dhlparcel.nl/
 */

class DHLParcel_Shipping_Model_Carrier
    extends DHLParcel_Shipping_Model_Carrier_Abstract
    implements DHLParcel_Shipping_Model_Carrier_Interface
{

    /**
     * @const string
     */
    const CODE = 'dhlparcel';

    /**
     * @const string
     */
    const CACHE_TAG = 'dhlparcel_shipping';

    // Five Minutes
    const CACHE_LIFETIME_CAPABILITIES = 900;
    const CACHE_LIFETIME_TIME_WINDOWS = 1800;

    /**
     * can be removed before the release
     *
     * @deprecated
     */
    const TEST_PARCELSHOP_ID = '8004-NL-354251';

    /**
     * @const shipment option codes
     */
    const SHIPOPTION_DOOR = 'DOOR';
    const SHIPOPTION_EVE = 'EVE';
    const SHIPOPTION_NBB = 'NBB';
    const SHIPOPTION_PS = 'PS';
    const SHIPOPTION_BP = 'BP';
    const SHIPOPTION_EA = 'EA';
    const SHIPOPTION_HANDT = 'HANDT';

    /**
     * @const Product blacklist
     */
    const PRODUCT_ATTRIBUTE_GROUP = 'DHL Parcel';
    const PRODUCT_ATTRIBUTE_BLACKLIST_SERVICEPOINT = 'dhlparcel_blacklis_ps';
    const PRODUCT_ATTRIBUTE_BLACKLIST_GENERAL = 'dhlparcel_blacklist';

    /**
     * Code of the carrier
     *
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * Rate request data
     *
     * @var Mage_Shipping_Model_Rate_Request|null
     */
    protected $_request = null;

    /**
     * Raw rate request data
     *
     * @var Varien_Object|null
     */
    protected $_rawRequest = null;

    /**
     * Rate result data
     *
     * @var Mage_Shipping_Model_Rate_Result|null
     */
    protected $_result = null;

    /**
     * Errors placeholder
     *
     * @var array
     */
    protected $_errors = array();

    /**
     * Rates result
     *
     * @var array|null
     */
    protected $_rates;

    /**
     * Number of boxes in package
     *
     * @var int
     */
    protected $_numBoxes = 1;

    /**
     * Free Method config path
     *
     * @var string
     */
    protected $_freeMethod = 'free_method';

    /**
     * Whether this carrier has fixed rates calculation
     *
     * @var bool
     */
    protected $_isFixed = false;

    /**
     * Container types that could be customized
     *
     * @var array
     */
    protected $_customizableContainerTypes = array('CP');

    /**
     * Fields that should be replaced in debug with '***'
     *
     * @var array
     */
    protected $_debugReplacePrivateDataKeys = array();
    /**
     * @var DHLParcel_Shipping_Model_Service_Capability
     */
    protected $_capabilityService;

    /**
     * @return DHLParcel_Shipping_Model_Service_Capability
     */
    protected function getCapabilityService()
    {
        if (!$this->_capabilityService instanceof DHLParcel_Shipping_Model_Service_Capability) {
            $this->_capabilityService = Mage::getSingleton('dhlparcel_shipping/service_capability');
        }
        return $this->_capabilityService;
    }

    /**
     * @return \DHLParcel_Shipping_Helper_Data|\Mage_Core_Helper_Abstract
     */
    protected function getHelper()
    {
        return Mage::helper('dhlparcel_shipping');
    }

    /**
     * Get configuration data of carrier
     *
     * @param string $type
     * @param string $code
     *
     * @return array|bool
     */
    public function getCode($type, $code = '')
    {
        $codes = array(
            'shipment_type' => array(
                'CP' => $this->getHelper()->__('Customer packaging'),
                'BULKY' => $this->getHelper()->__('BULKY'),
                'PALLET' => $this->getHelper()->__('PALLET'),
                'LARGE' => $this->getHelper()->__('LARGE'),
                'MEDIUM' => $this->getHelper()->__('MEDIUM'),
                'SMALL' => $this->getHelper()->__('SMALL'),
            ),
        );


        if (!isset($codes[$type])) {
            return false;
        } elseif ('' === $code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            return false;
        } else {
            return $codes[$type][$code];
        }
    }


    /**
     * Return container types of carrier
     *

     * @param Varien_Object|null $params
     *
     * @return array
     * @throws \Exception
     */
    public function getContainerTypes(Varien_Object $params = null)
    {
        $validContainerTypes = [];

        $capabilities = $this->getCapabilityService()->get(
            $params->getData('country_shipper'),
            '',
            boolval($this->getConfigData('b2b')),
            ['option'=>$params->getData('method')]
        );
        if (!$capabilities) {
            return [];
        }

        foreach ($capabilities as $capability) {
            $validContainerTypes[$capability->getData('parcelType')['key']] = sprintf(
                '%s (max %d KG)',
                $capability->getData('parcelType')['key'],
                $capability->getData('parcelType')['maxWeightKg']
            );
        }

        return array_unique($validContainerTypes);
    }


    /**
     * Returns available shipping rates for DHLParcel Shipping carrier
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     *
     * @return Mage_Shipping_Model_Rate_Result
     * @throws \Exception
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        /** @var Mage_Shipping_Model_Rate_Result $result */
        $result = Mage::getModel('shipping/rate_result');

        // Get all valid shipment options for the receiver address
        $validShipmentOptions = $this->getValidShipmentOptions($request);

        // Default Shipping Rate
        if ($this->isValidShipmentOption(self::SHIPOPTION_DOOR, $validShipmentOptions)) {
            /** @var Mage_Shipping_Model_Rate_Result_Method $rate */
            $rate = Mage::getModel('shipping/rate_result_method');
            $rate->setCarrier($this->_code);
            $rate->setCarrierTitle($this->getConfigData('title'));
            $rate->setMethod(self::SHIPOPTION_DOOR);
            $rate->setMethodTitle(__($this->getOptionConfig(self::SHIPOPTION_DOOR, 'title')));
            $rate->setPrice($this->getOptionPrice(self::SHIPOPTION_DOOR, $request));
            $result->append($rate);
        }

        // Servicepoint Shipping Rate
        if ($this->isValidShipmentOption(self::SHIPOPTION_PS, $validShipmentOptions)) {
            /** @var Mage_Shipping_Model_Rate_Result_Method $rate */
            $rate = Mage::getModel('shipping/rate_result_method');
            $rate->setCarrier($this->_code);
            $rate->setCarrierTitle($this->getConfigData('title'));
            $rate->setMethod(self::SHIPOPTION_PS);
            $rate->setMethodTitle(__($this->getOptionConfig(self::SHIPOPTION_PS, 'title')));
            $rate->setPrice($this->getOptionPrice(self::SHIPOPTION_PS, $request));
            $result->append($rate);
        }

        return $result;
    }


    /**
     * @param $shipmentOptionCode
     * @param $field
     * @return mixed
     * @throws Varien_Exception
     */
    public function getOptionConfig($shipmentOptionCode, $field)
    {
        $path = 'carriers/' . $shipmentOptionCode . '_' . $this->_code . '/' . $field;
        return $this->getConfig($path);
    }

    /**
     * @param \Mage_Shipping_Model_Shipment_Request $request
     *
     * @return array
     */
    public function getShipmentOptionsForShipmentRequest(Mage_Shipping_Model_Shipment_Request $request)
    {
        $shipmentOptions = [];
        // the method is the minimal option added
        $shipmentOption = [
            'key' => $request->getShippingMethod(),
        ];

        if ($request->getShippingMethod() === self::SHIPOPTION_PS) {
            /** @var Mage_Sales_Model_Order_Shipment $shipment */
            $shipment = $request->getOrderShipment();
            /** @var Mage_Sales_Model_Order $order */
            $order = $shipment->getOrder();

            $servicepoint = $order->getData('dhlparcel_servicepoint');

            if ($servicepoint) {
                $shipmentOption['input'] = $servicepoint;
            }
        }

        $shipmentOptions[] = $shipmentOption;

        return $shipmentOptions;
    }

    /**
     * @param \Mage_Shipping_Model_Rate_Request $request
     *
     * @return array
     * @throws \Exception
     */
    public function getValidShipmentOptions(Mage_Shipping_Model_Rate_Request $request)
    {
        $validShipmentOptions = [];


        $capabilities = $this->getCapabilityService()->get(
            $request->getDestCountryId(),
            str_replace(" ", "", $request->getDestPostcode()),
            boolval($this->getConfigData('b2b'))
        );
        if (!$capabilities) {
            return [];
        }

        foreach ($capabilities as $capability) {
            $options = $capability->getOptions();
            foreach ($options as $option) {
                $validShipmentOptions[] = $option->getData('key');
            }
        }

        $possibleOptions = array_unique($validShipmentOptions);
        $blacklistedOptions = $this->getBlacklistedByProducts($request->getAllItems());

        return array_diff($possibleOptions, $blacklistedOptions);
    }

    /**
     * @param $products
     * @return array
     */
    public function getBlacklistedByProducts($products)
    {
        $productBlacklist = array ();
        foreach ($products as $cartItem) {
            if (!is_object($cartItem->getProduct())) {
                continue;
            }

            $product = Mage::getModel('catalog/product')->load($cartItem->getProduct()->getId());

            // Get Blacklist
            if (boolval($product->getData(self::PRODUCT_ATTRIBUTE_BLACKLIST_SERVICEPOINT))) {
                $productBlacklist[] = 'PS';
            }

            $generalOptionsBlacklist = explode(',', $product->getData('dhlparcel_blacklist'));
            if (!empty($generalOptionsBlacklist)) {
                $productBlacklist = array_merge($productBlacklist, $generalOptionsBlacklist);
            }
        }

        return array_unique($productBlacklist);
    }

    /**
     * @param Traversable $result
     *
     * @param array $subCollectionFields
     *
     * @param string $className
     *
     * @return \Varien_Data_Collection
     * @throws \Exception
     */
    public function createCollectionForResult($result, array $subCollectionFields = null, $className = 'Varien_Object')
    {
        if (!$result instanceof Traversable && !is_array($result)) {
            throw new Exception(sprintf('result is not traversable'));
        }

        $collection = new Varien_Data_Collection();
        foreach ($result as $index => $resultItem) {
            if ($subCollectionFields !== null) {
                foreach ($subCollectionFields as $subCollectionField) {
                    if (isset($resultItem[$subCollectionField])) {
                        $subCollection = $this->createCollectionForResult($resultItem[$subCollectionField]);
                        unset($resultItem[$subCollectionField]);
                        $resultItem[$subCollectionField] = $subCollection;
                    }
                }
            }
            /** @var \Varien_Object $varienObject */
            $varienObject = new $className();
            if (!$varienObject instanceof Varien_Object) {
                throw new Exception(sprintf('$className should be instance of Varien_Object'));
            }
            $varienObject->setData($resultItem);
            if ($varienObject->getIdFieldName() === null) {
                $varienObject->setIdFieldName('id');
            }
            if ($varienObject->getId() === null) {
                $varienObject->setId($index);
            }
            $collection->addItem($varienObject);
        }
        return $collection;
    }

    /**
     * @param $shipmentOptionCode
     * @param $validShipmentOptions
     * @return bool
     * @throws Varien_Exception
     */
    public function isValidShipmentOption($shipmentOptionCode, $validShipmentOptions)
    {
        // Check if shipment option is enabled in system config
        if ($this->getOptionConfig($shipmentOptionCode, 'active') != 1) {
            return false;
        }

        // Reflect shipment option to Capabilities API
        return in_array($shipmentOptionCode, $validShipmentOptions);
    }

    /**
     * Returns Allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return array(
            $this->getCarrierCode() => __($this->getConfigData('name')),
        );
    }

    /**
     * @param $shipmentOptionCode
     * @param $request
     * @return mixed|string
     * @throws Varien_Exception
     */
    public function getOptionPrice($shipmentOptionCode, $request)
    {
        $totals = Mage::getSingleton('checkout/cart')->getQuote()->getTotals();
        $subTotal = $totals["subtotal"]->getValue();

        if ($shipmentOptionCode == 'DOOR' || $shipmentOptionCode == 'PS') {
            if ($this->getConfig('carriers/' . $shipmentOptionCode . '_dhlparcel/rate_type') == 'matrix') {
                // Get Matrix Price
                if ($shipmentOptionCode == 'DOOR') {
                    /** @var DHLParcel_Shipping_Model_Resource_Matrixrate $model */
                    $model = Mage::getResourceModel('dhlparcel_shipping/matrixrate');
                    $rate = $model->getRate($request);

                    return $rate['price'];
                }
                // Get Matrix Price
                if ($shipmentOptionCode == 'PS') {
                    /** @var DHLParcel_Shipping_Model_Resource_Matrixrateps $model */
                    $model = Mage::getResourceModel('dhlparcel_shipping/matrixrateps');
                    $rate = $model->getRate($request);

                    return $rate['price'];
                }
            }
        }

        // Return default price stucture
        return $this->getOptionConfig($shipmentOptionCode, 'free_shipping_subtotal') <= $subTotal
        && $this->getOptionConfig($shipmentOptionCode, 'free_shipping_subtotal') > 0
            ? '0.00'
            : $this->getOptionConfig($shipmentOptionCode, 'price');
    }

    /**
     * @param $tracking
     * @return false|Mage_Core_Model_Abstract
     * @throws Varien_Exception
     */
    public function getTrackingInfo($tracking)
    {
        /** @var Mage_Shipping_Model_Tracking_Result_Status $statusModel */
        $track = Mage::getModel('shipping/tracking_result_status');
        $currentTrack = $this->getTrackByNumber($tracking);

        if ($currentTrack === false) {
            return false;
        }

        $shipment = $currentTrack->getShipment();

        $track->setUrl($this->getTrackingUrl($tracking, $shipment->getShippingAddress()->getPostcode()))
            ->setTracking($tracking)
            ->setCarrierTitle($this->getConfigData('name'));

        return $track;
    }

    /**
     * Get tracking url by trackCode
     *
     * @param $trackCode
     * @param string $postalcode
     * @return mixed
     */
    public function getTrackingUrl($trackCode, $postalcode = '')
    {
        return str_replace(
            array(
                '{{tracknumber}}',
                '{{postalcode}}'
            ),
            array(
                $trackCode,
                $postalcode
            ),
            $this->getConfigData('track_trace_url')
        );
    }

    public function getFormBlock()
    {
        return 'dhlparcel_shipping/checkout_shippingoptions';
    }
}
