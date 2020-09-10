<?php

class DHLParcel_Shipping_Model_Service_DeliveryTimes
{
    /** @var DHLParcel_Shipping_Model_Api_Connector */
    protected $connector;
    /** @var DHLParcel_Shipping_Model_Service_Cache */
    protected $cacheService;

    /**
     * DHLParcel_Shipping_Model_Service_DeliveryTimes constructor.
     */
    public function __construct()
    {
        $this->connector = Mage::getSingleton('dhlparcel_shipping/api_connector');
        $this->cacheService = Mage::getSingleton('dhlparcel_shipping/service_cache');
    }

    public function get($countryCode, $postalCode)
    {
        $cacheKey = $this->cacheService->createKey('time-windows', ['countryCode' => $countryCode, 'postalCode' => $postalCode]);
        $json = $this->cacheService->load($cacheKey);

        if ($json === false) {
            $response = $this->getTimeFrames($countryCode, $postalCode);
            if (!empty($response)) {
                $this->cacheService->save(json_encode($response), $cacheKey, 900);
            }
        } else {
            $response = json_decode($json, true);
        }

        $collection = $this->createCollectionForResult($response);

        return $collection;
    }

    protected function getTimeFrames($countryCode, $postalCode)
    {
        if (!$postalCode || !$countryCode) {
            return [];
        }

        $trimmedPostalCode = preg_replace('/\s+/', '', $postalCode);

        try {
            $timeWindowsResponse = $this->connector->get('time-windows', [
                'countryCode' => $countryCode,
                'postalCode'  => strtoupper($trimmedPostalCode),
            ]);
        } catch (DHLParcel_Shipping_Model_Api_Exception $e) {
            //TODO write meaningful response to failed timeframe request
            return [];
        }

        return $timeWindowsResponse;

        /*
         * temporarily removed but when refactoring this should be changed to model conversion either in this function or the get function, but wil probabbly replace createCollectionForResult
        if (!$timeWindowsResponse || !is_array($timeWindowsResponse) || empty($timeWindowsResponse)) {
            return [];
        }

        $deliveryTimes = [];
        foreach ($timeWindowsResponse as $timeWindowData) {
            $timeWindow = Mage::getModel('dhlparcel_shipping/data_api_response_timewindow', $timeWindowData);
            $deliveryTime = $this->parseTimeWindow($timeWindow->deliveryDate, $timeWindow->startTime, $timeWindow->endTime, $timeWindow);
            $deliveryTimes[] = $deliveryTime;
        }

        return $deliveryTimes;
        */
    }

    /**
     * @param $result
     * @param array|null $subCollectionFields
     * @param string $className
     * @return Varien_Data_Collection
     * @throws Exception
     * @deprecated
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