<?php

class DHLParcel_Shipping_Model_Data_Api_Response_Shipment extends DHLParcel_Shipping_Model_Data_AbstractData
{
    public $shipmentId;
    /** @var DHLParcel_Shipping_Model_Data_Api_Response_Shipment_Piece[] */
    public $pieces;

    protected function getClassArrayMap()
    {
        return [
            'pieces' => 'dhlparcel_shipping/data_api_response_shipment_piece',
        ];
    }
}
