<?php

//TODO implement caching
class DHLParcel_Shipping_Model_Service_ServicePoint
{
    /** @var DHLParcel_Shipping_Model_Api_Connector */
    protected $connector;
    /** @var DHLParcel_Shipping_Model_Service_Cache */
    protected $cacheService;

    /**
     * DHLParcel_Shipping_Model_Service_ServicePoint constructor.
     */
    public function __construct()
    {
        $this->connector = Mage::getSingleton('dhlparcel_shipping/api_connector');
        $this->cacheService = Mage::getSingleton('dhlparcel_shipping/service_cache');
    }

    /**
     * @param $postalcode
     * @param $country
     * @param int $limit
     * @return DHLParcel_Shipping_Model_Data_Api_Response_ServicePoint[]
     * @throws DHLParcel_Shipping_Model_Api_Exception
     */
    public function search($country, $postalcode, $limit = 13)
    {
        $servicePointsResponse = $this->connector->get('parcel-shop-locations/' . $country, [
            'limit'       => $limit,
            'zipCode'     => strtoupper($postalcode),
            'serviceType' => 'parcel-last-mile',
        ]);

        if (!$servicePointsResponse || !is_array($servicePointsResponse)) {
            return [];
        }

        $servicePoints = [];
        foreach ($servicePointsResponse as $servicePointResponse) {
            /** @var DHLParcel_Shipping_Model_Data_Api_Response_ServicePoint $servicePoint */
            $servicePoint = Mage::getModel('dhlparcel_shipping/data_api_response_servicePoint', $servicePointResponse);
            $servicePoint->country = $country;
            if ($servicePoint->shopType === 'packStation' && empty($servicePoint->name)) {
                $servicePoint->name = $servicePoint->keyword;
            }
            $servicePoints[] = $servicePoint;
        }

        return $servicePoints;
    }

    /**
     * @param $id
     * @param $country
     * @return bool|DHLParcel_Shipping_Model_Data_Api_Response_ServicePoint
     */
    public function get($country, $id)
    {
        if (($position = strpos($id, "|")) !== false) {
            $post_number = substr($id, $position + 1);
        } else {
            $post_number = null;
        }
        // Remove any additional fields
        $id = strstr($id, '|', true) ?: $id;
        try {
            $servicePointResponse = $this->connector->get(sprintf('parcel-shop-locations/%s/%s', $country, $id));
        } catch (\Exception $e) {
            Mage::logException($e);
            return false;
        }

        if (!$servicePointResponse) {
            return false;
        }

        /** @var DHLParcel_Shipping_Model_Data_Api_Response_ServicePoint $servicePoint */
        $servicePoint = Mage::getModel('dhlparcel_shipping/data_api_response_servicePoint', $servicePointResponse);
        $servicePoint->country = $country;
        if ($servicePoint->shopType === 'packStation') {
            if (empty($servicePoint->name)) {
                $servicePoint->name = $servicePoint->keyword;
            }
            if (!empty($post_number)) {
                $servicePoint->name = $servicePoint->name . ' ' . $post_number;
                $servicePoint->id = $servicePoint->id . '|' . $post_number;
            }
        }
        return $servicePoint;
    }
}