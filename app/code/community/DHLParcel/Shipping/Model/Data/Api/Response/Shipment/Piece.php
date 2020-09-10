<?php

class DHLParcel_Shipping_Model_Data_Api_Response_Shipment_Piece extends DHLParcel_Shipping_Model_Data_AbstractData
{
    public $labelId;
    public $trackerCode;
    public $parcelType;
    public $pieceNumber;
    public $labelType;
    // Custom internal field
    public $postalCode;
}
