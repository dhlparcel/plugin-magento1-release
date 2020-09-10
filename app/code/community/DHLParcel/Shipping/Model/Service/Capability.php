<?php

class DHLParcel_Shipping_Model_Service_Capability
{
    /** @var DHLParcel_Shipping_Model_Api_Connector */
    protected $connector;
    /** @var DHLParcel_Shipping_Model_Service_Cache */
    protected $cacheService;

    /**
     * DHLParcel_Shipping_Model_Service_Capability constructor.
     */
    public function __construct()
    {
        $this->connector = Mage::getSingleton('dhlparcel_shipping/api_connector');
        $this->cacheService = Mage::getSingleton('dhlparcel_shipping/service_cache');
    }

    /**
     * @param $toCountry
     * @param $toPostalCode
     * @param $toBusiness
     * @param $requestOptions
     * @return DHLParcel_Shipping_Model_Data_Api_Request_CapabilityCheck
     */
    protected function createCapabilityCheck($toCountry, $toPostalCode, $toBusiness, $requestOptions)
    {
        $fromCountry = Mage::getStoreConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_COUNTRY_ID);
        $fromPostalCode = Mage::getStoreConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_ZIP);
        $accountNumber = Mage::getStoreConfig('carriers/dhlparcel/api_account_id');

        /** @var DHLParcel_Shipping_Model_Data_Api_Request_CapabilityCheck $capabilityCheck */
        $capabilityCheck = Mage::getModel('dhlparcel_shipping/data_api_request_capabilityCheck');
        $capabilityCheck->fromCountry = $fromCountry;
        $capabilityCheck->fromPostalCode = strtoupper($fromPostalCode);
        $capabilityCheck->toCountry = $toCountry ?: $fromCountry;
        $capabilityCheck->toBusiness = $toBusiness ? 'true' : 'false';
        $capabilityCheck->accountNumber = $accountNumber;

        if ($toPostalCode !== '') {
            $capabilityCheck->toPostalCode = strtoupper($toPostalCode);
        }

        if (is_array($requestOptions) && count($requestOptions)) {
            $capabilityCheck->option = implode(',', $requestOptions);
        }

        return $capabilityCheck;
    }

    /**
     * @param DHLParcel_Shipping_Model_Data_Api_Request_CapabilityCheck $capabilityCheck
     * @return array
     * @throws DHLParcel_Shipping_Model_Api_Exception
     * @throws Zend_Cache_Exception
     */
    protected function sendRequest($capabilityCheck)
    {
        $cacheKey = $this->cacheService->createKey('capabilities', $capabilityCheck->toArray(true));
        $json = $this->cacheService->load($cacheKey);

        if ($json === false) {
            $response = $this->connector->get('capabilities/business', $capabilityCheck->toArray(true));
            if (!empty($response)) {
                $this->cacheService->save(json_encode($response), $cacheKey, 3600);
            }
        } else {
            $response = json_decode($json, true);
        }

        return $response;
    }

    /**
     * @param $toCountry
     * @param string $toPostalCode
     * @param bool $toBusiness
     * @param array $requestOptions
     * @return bool|Varien_Data_Collection
     * @throws Exception
     */
    public function get($toCountry, $toPostalCode = '', $toBusiness = false, $requestOptions = [])
    {

        $capabilityCheck = $this->createCapabilityCheck($toCountry, $toPostalCode, $toBusiness, $requestOptions);

        try {
            $response = $this->sendRequest($capabilityCheck);
        } catch (Exception $e) {
            return false;
        }

        return $this->createCollectionForResult($response, ['options']);
    }


    // @codingStandardsIgnoreEnd

    /**
     * @param Traversable $result
     *
     * @param array $subCollectionFields
     *
     * @param string $className
     *
     * @return \Varien_Data_Collection
     * @throws \Exception
     * @deprecated
     * TODO change this to the data classes extending DHLParcel_Shipping_Model_Data_AbstractData
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
}
