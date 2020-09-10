<?php

class DHLParcel_Shipping_Model_Service_Printing
{
    /** @var DHLParcel_Shipping_Model_Service_Cache */
    protected $cacheService;
    /** @var DHLParcel_Shipping_Model_Api_Connector */
    protected $connector;

    /**
     * DHLParcel_Shipping_Model_Service_Printing constructor.
     */
    public function __construct()
    {
        $this->cacheService = Mage::getSingleton('dhlparcel_shipping/service_cache');
        $this->connector = Mage::getSingleton('dhlparcel_shipping/api_connector');
    }

    public function getPrinters()
    {
        $cacheKey = $this->cacheService->createKey('printers');
        $json = $this->cacheService->load($cacheKey);

        if ($json === false) {
            try {
                $response = $this->connector->get('printers');
            } catch (DHLParcel_Shipping_Model_Api_Exception $e) {
                return [];
            }
            if (!empty($response)) {
                $this->cacheService->save(json_encode($response), $cacheKey, 15);
            }
        } else {
            $response = json_decode($json, true);
        }

        $printers = [];
        if ($response && is_array($response)) {
            foreach ($response as $entry) {
                $printers[] = Mage::getModel('dhlparcel_shipping/data_api_response_printer', $entry);
            }
        }

        return $printers;
    }

    /**
     * @param array $labelIds
     * @param bool $retry
     * @return bool
     * @throws Exception
     */
    public function sendPrintJob($labelIds = [], $retry = true)
    {
        $printerId = Mage::getStoreConfig('carriers/dhlparcel_direct_print/printer');

        if (empty($printerId)) {
            throw new Exception(__('No printer selected'));
        }

        try {
            $this->connector->post('printers/' . $printerId . '/jobs', [
                'id'       => $this->connector->getUuidV4(),
                'labelIds' => $labelIds
            ], true);
        } catch (DHLParcel_Shipping_Model_Api_Exception $e) {
            switch ($e->getCode()) {
                case 400:
                    throw new Exception(__('One of the labels you are trying to print is invalid'));
                    break;
                case 404:
                    throw new Exception(__('This printer no longer exists'));
                    break;
                case 409:
                    if ($retry) {
                        // Retries with newly formed UUID
                        $this->sendPrintJob($labelIds, false);
                    }
                    break;
                default:
                    throw new Exception(__('Unexpected Response with code %s', $e->getCode()));
                    break;
            }
        }

        return true;
    }
}
