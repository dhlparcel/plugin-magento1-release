<?php


class DHLParcel_Shipping_Model_Data_Api_Response_ServicePoint extends DHLParcel_Shipping_Model_Data_AbstractData
{
    public $id;
    public $name;
    public $keyword;
    /** @var DHLParcel_Shipping_Model_Data_Api_Response_ServicePoint_Address */
    public $address;
    public $geoLocation;
    public $distance;
    public $openingTimes;
    public $shopType;
    public $country;

    protected function getClassMap()
    {
        return [
            'address' => 'dhlparcel_shipping/data_api_response_servicePoint_address',
        ];
    }
}
