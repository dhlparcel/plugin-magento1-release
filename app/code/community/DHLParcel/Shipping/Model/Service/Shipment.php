<?php

class DHLParcel_Shipping_Model_Service_Shipment
{
    /** @var DHLParcel_Shipping_Model_Api_Connector */
    protected $connector;
    /** @var DHLParcel_Shipping_Model_Service_Label */
    protected $labelService;

    /**
     * DHLParcel_Shipping_Model_Service_Label constructor.
     */
    public function __construct()
    {
        $this->connector = Mage::getSingleton('dhlparcel_shipping/api_connector');

    }

    /**
     * @param DHLParcel_Shipping_Model_Data_Api_Response_Shipment_Piece[] $pieceResponses
     * @param bool $isReturn
     * @return array
     * @throws Exception
     */
    public function createTracks($pieceResponses, $isReturn = false)
    {
        if (count($pieceResponses) === 0) {
            throw new Exception(__('No pieces where given'));
        }

        $tracks = [];
        foreach ($pieceResponses as $pieceResponse) {
            /** @var DHLParcel_Shipping_Model_Piece $piece */
            $piece = Mage::getModel('dhlparcel_shipping/piece');
            $piece->addData([
                'label_id'     => $pieceResponse->labelId,
                'tracker_code' => $pieceResponse->trackerCode,
                'postal_code'  => $pieceResponse->postalCode,
                'parcel_type'  => $pieceResponse->parcelType,
                'piece_number' => $pieceResponse->pieceNumber,
                'label_type'   => $pieceResponse->labelType,
                'is_return'    => $isReturn,
            ]);
            $piece->save();

            $track = Mage::getModel('sales/order_shipment_track');
            $track->addData([
                'carrier_code' => DHLParcel_Shipping_Model_Carrier::CODE,
                'title'        => !$isReturn ? 'DHL Parcel' : 'DHLParcel - Return Label',
                'number'       => $pieceResponse->trackerCode,
            ]);

            $tracks[$pieceResponse->labelId] = $track;
        }

        return $tracks;
    }

    /**
     * @param $shipmentRequest
     * @return DHLParcel_Shipping_Model_Data_Api_Response_Shipment
     * @throws Exception
     */
    public function createShipment($shipmentRequest)
    {
        /*
         * code from app/code/community/DHLParcel/Shipping/Helper/Labels.php createDhlLabel(); should be placed here
         * but it gets called in a number of places so I left it as is for now.
         */
        try {
            $rawResponse = $this->connector->post('shipments', $shipmentRequest['body']);
        } catch (DHLParcel_Shipping_Model_Api_Exception $e) {
            if ($e->getCode() === 400) {
                $errorMessage = __("Combination of service options for the current address didn't resolve in any valid package sizes. Please try manually shipping this order");

                $response = $e->getFormatedResponse();
                if (array_key_exists('key', $response) && $response['key'] === 'PARCEL_SHOP_LOCATION_NOT_FOUND') {
                    $errorMessage = __('ServicePoint invalid, please manualy select ship this order to select a new ServicePoint');
                }
            } else {
                $errorMessage = $e->getMessage();
            }
            throw new Exception($errorMessage);
        }

        /** @var DHLParcel_Shipping_Model_Data_Api_Response_Shipment $shipmentResponse */
        $shipmentResponse = Mage::getModel('dhlparcel_shipping/data_api_response_shipment', $rawResponse);

        // Enrich pieces with postalCode
        if (!empty($shipmentResponse) && !empty($shipmentResponse->pieces)) {
            $postalCode = $shipmentRequest['body']['receiver']['address']['postalCode'];
            foreach ($shipmentResponse->pieces as $piece) {
                $piece->postalCode = strtoupper(trim($postalCode));
            }
        }

        return $shipmentResponse;
    }
}
