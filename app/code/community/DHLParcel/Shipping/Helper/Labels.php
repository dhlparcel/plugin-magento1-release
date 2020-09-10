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
 * @author    Rudger Gravenstein <plugins@dhl.com>
 * @author    Ron Oerlemans <plugins@dhl.com>
 * @copyright ${YEAR} DHLParcel
 * @link      https://www.dhlparcel.nl/
 */

class DHLParcel_Shipping_Helper_Labels extends DHLParcel_Shipping_Helper_Data
{
    protected $lastError = '';

    /**
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @param bool $shipmentOptions
     * @param array $packageSizes
     * @param null $toBusiness
     * @param bool $addReturnLabel
     * @param bool $isBulk
     * @return bool|string
     * @throws Zend_Pdf_Exception
     */
    public function createDhlLabel($shipment, $shipmentOptions = false, $packageSizes = [], $toBusiness = null, $addReturnLabel = false, $isBulk = false)
    {
        /** @var DHLParcel_Shipping_Model_Service_Label $labelService */
        $labelService = Mage::getSingleton('dhlparcel_shipping/service_label');
        /** @var DHLParcel_Shipping_Model_Service_Shipment $shipmentService */
        $shipmentService = Mage::getSingleton('dhlparcel_shipping/service_shipment');
        try {
            $shipmentRequest = $this->shipmentToShipmentRequest($shipment, $packageSizes, $shipmentOptions, $toBusiness, $isBulk);

            if (boolval(Mage::getStoreConfig('carriers/dhlparcel/save_label_requests'))) {
                $shipment->setDhlparcelShippingRequest(json_encode($shipmentRequest['body']));
            }

            $shipmentResponse = $shipmentService->createShipment($shipmentRequest);

            $tracks = $shipmentService->createTracks($shipmentResponse->pieces, false);

            foreach ($tracks as $track) {
                $shipment->addTrack($track)->save();
            }

            $labelIds = array_keys($tracks);
            $shipment->save();
        } catch (Exception $e) {
            $this->lastError = $this->__('There was an error creating your label: %s', $e->getMessage());

            $this->rollBackShipment($shipment);

            return false;
        }

        // Check if we need to create a return label
        if ($addReturnLabel === true) {
            try {
                $shipmentRequest = $this->shipmentToReturnShipmentRequest($shipment, $packageSizes, $toBusiness);
                $shipmentResponse = $shipmentService->createShipment($shipmentRequest);

                $tracks = $shipmentService->createTracks($shipmentResponse->pieces, false);

                foreach ($tracks as $track) {
                    $shipment->addTrack($track)->save();
                }

                $labelIds = array_merge($labelIds, array_keys($tracks));
            } catch (Exception $e) {
                if ($isBulk === false) {
                    $this->lastError = __('There was an error creating a returnlabel for: %s', $e->getMessage());

                    $this->rollBackShipment($shipment);

                    return false;
                }
            }
        }
        try {
            $labelPdf = $labelService->getLabelPdfs($labelIds);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->lastError = $this->__('Failed to retrieve all labels: %s', $e->getMessage());
            $this->rollBackShipment($shipment);
            return false;
        }

        $labelContent = $labelPdf->render();

        return $labelContent;
    }

    /**
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @param array $packageSizes
     * @param bool $shipmentOptions
     * @param null $toBusiness
     * @param bool $isBulk
     * @return array
     * @throws Exception
     */
    public function shipmentToShipmentRequest($shipment, $packageSizes = array(), $shipmentOptions = false, $toBusiness = null, $isBulk = false)
    {
        $dhlHelper = Mage::helper('dhlparcel_shipping');
        $order = $shipment->getOrder();
        $shippingAddress = $shipment->getShippingAddress();

        if ($shipmentOptions == false) {
            // Use default requested shipment options of shipment
            $deliveryMethod = $shipment->getOrder()->getData('shipping_method');
            $deliveryCodeParts = explode('_', $deliveryMethod);
            if (reset($deliveryCodeParts) === 'dhlparcel') {
                $deliveryCode = end($deliveryCodeParts);
            } else {
                $deliveryCode = 'DOOR';
            }

            $savedOrderOptions = $order->getData('dhlparcel_shipping_options');
            if (!empty($savedOrderOptions)) {
                $shipmentOptions = explode(',', $savedOrderOptions);
            } else {
                $shipmentOptions = [];
            }
            if ($deliveryCode !== 'PS') {
                $shipmentOptions[] = $deliveryCode;
            }

            // Add default shipment options
            $defaultOptions = $this->getDefaultShipmentOptions($deliveryCode);
            if (is_array($defaultOptions)) {
                $shipmentOptions = array_merge($shipmentOptions, $defaultOptions);
            }
        }

        // Get store ID
        $storeId = $shipment->getOrder()->getStore()->getId();

        // Check if package size is requested or choose smallest
        if (empty($packageSizes)) {
            // Build array for capabilitiesOptions call
            // In case of 'PS' an ID don't need to be set (and when creating a label it does)
            $capabilitiesOptions = $shipmentOptions;
            if (isset($deliveryCode) && $deliveryCode === 'PS') {
                $capabilitiesOptions[] = 'PS';
            }

            $parcelTypes = Mage::getModel('dhlparcel_shipping/shipmentoptions')->getParcelTypes([
                'toCountry'     => $shipment
                    ->getShippingAddress()
                    ->getCountryId(),
                'fromCountry'   => $this->getConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_COUNTRY_ID, $storeId),
                'option'        => implode(',', $capabilitiesOptions)
            ]);

            // Helper Data
            $parcelTypes = $dhlHelper->parcelTypesToArray($parcelTypes);

            // Get first value (by default sorted by weight)
            $packageSizes = array (key($parcelTypes) => 1);
        }

        // Set parcelshop ID
        if (isset($deliveryCode) && $deliveryCode === 'PS') {
            $shipmentOptions['PS'] = $order->getData('dhlparcel_servicepoint');
        }


        // Fill Packages Sizes
        $colliCount = 0;
        $pieces = array();
        foreach ($packageSizes as $packageSize => $packageQty) {
            $pieces[] = [
                'parcelType'  => $packageSize,
                'quantity'    => $packageQty
            ];

            $colliCount += $packageQty;
        }

        // Print labels on order by default (mass label creations)
        if ((boolval(Mage::getStoreConfig('carriers/dhlparcel_labels/label_print_ordernumer')) && $isBulk === true) || $colliCount > 1) {
            if ($isBulk === true) {
                // Force Reference on bulk labels
                $shipmentOptions['REFERENCE'] = $this->getReferenceValue($shipment);
                $shipmentOptions['REFERENCE2'] = $this->getReference2Value($shipment);
            } elseif ($colliCount > 1) {
                // When there is more as 1 colli the REFERENCE fields needs to be set.
                if (!array_key_exists('REFERENCE', $shipmentOptions)) {
                    $shipmentOptions['REFERENCE'] = $this->getReferenceValue($shipment);
                    Mage::getSingleton('adminhtml/session')->addNotice('Using a reference causes the volume discount for multi colli shipments to be applied correctly.');
                }
            }
        }

        $shipmentOptionsForRequest = array();

        // Build request for options
        foreach ($shipmentOptions as $key => $shipmentOption) {
            // Skip if empty
            if (empty($key) && empty($shipmentOption) || $shipmentOption == 'ADD_RETURN_LABEL') {
                continue;
            }

            $option = [];

            if (!is_numeric($key)) {
                $option['key'] = $key;
                $option['input'] = $shipmentOption;
            } else {
                $option['key'] = $shipmentOption;
            }
            $shipmentOptionsForRequest[] = $option;
        }

        // Check if SSN is requested
        $ssnSelected = in_array('SSN', $shipmentOptions);

        try {
            $recipientAddress = $this->parseStreetData(implode(' ', $shippingAddress->getStreet()));
        } catch (Exception $exception) {
            throw new Exception(__('Could not retrieve street and housenumber from the recipient address.'));
        }

        try {
            $shipperAddress = $this->parseStreetData(implode(' ', [
                $this->getConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_ADDRESS1, $storeId),
                $this->getConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_ADDRESS2, $storeId)
            ]));
        } catch (Exception $exception) {
            throw new Exception(__('Could not retrieve street and housenumber from the shipper address. Please check your settings in System -> Configuration -> Sales -> Shipping Settings -> Origin: Street Address not set (correctly)'));
        }

        $parameters = [
            'body' => [
                'shipmentId' => $this->getUuidV4(),
                'orderReference' => $order->getIncrementId(),
                'receiver' => [
                    'name' => [
                        'firstName' => (string)$shippingAddress->getFirstname(),
                        'lastName' => trim((string)$shippingAddress->getMiddlename() . ' ' . $shippingAddress->getLastname()),
                        'companyName' => (string)$shippingAddress->getCompany(),
                    ],
                    'address' => [
                        'countryCode' => (string)$shippingAddress->getCountryId(),
                        'postalCode' => str_replace(" ", "", strtoupper($shippingAddress->getPostcode())),
                        'city' => $shippingAddress->getCity(),
                        'street' => $recipientAddress['street'],
                        'number' => $recipientAddress['number'],
                        'isBusiness' => $toBusiness !== null ? $toBusiness : boolval(Mage::getStoreConfig('carriers/dhlparcel/b2b')),
                        'addition' => $recipientAddress['addition'],
                    ],
                    'email' => $shippingAddress->getEmail(),
                    'phoneNumber' => $shippingAddress->getTelephone(),
                ],
                'shipper' => [
                    'name' => [
                        'firstName' => '',
                        'lastName' => '',
                        'companyName' => $this->getConfig('general/store_information/name', $storeId),
                    ],
                    'address' => [
                        'countryCode' => $this->getConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_COUNTRY_ID, $storeId),
                        'postalCode' => str_replace(" ", "", strtoupper($this->getConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_ZIP, $storeId))),
                        'city' => $this->getConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_CITY, $storeId),
                        'street' => $shipperAddress['street'],
                        'number' => $shipperAddress['number'],
                        'addition' => $shipperAddress['addition'],
                        'isBusiness' => true,
                    ],
                    'email' => $this->getConfig('trans_email/ident_support/email', $storeId),
                    'phoneNumber' => $this->getConfig('general/store_information/phone', $storeId)
                ],
                'accountId' => Mage::getStoreConfig('carriers/dhlparcel/api_account_id'),
                'options' => $shipmentOptionsForRequest,
                'application' => $this->getApplicationName(),
                'pieces' => $pieces
            ],
        ];

        // Check if SSN is selected
        if ($ssnSelected) {
            $onBehalfOf = $this->getOnBehalfOf($storeId);
            $parameters['body']['onBehalfOf'] = $onBehalfOf;
        }

        return $parameters;
    }

    /**
     * Generate Return Label Call
     *
     * @param $shipment
     * @param $packageSizes
     * @param bool $fromBusiness
     * @return array
     * @throws Exception
     */
    public function shipmentToReturnShipmentRequest($shipment, $packageSizes = array(), $fromBusiness = false)
    {
        $dhlHelper = Mage::helper('dhlparcel_shipping');
        $order = $shipment->getOrder();
        $shippingAddress = $shipment->getShippingAddress();

        $shipmentOptionsForRequest = array(array ('key' => 'DOOR'));

        $storeId = $shipment->getOrder()->getStore()->getId();

        try {
            $shipperAddress = $this->parseStreetData(implode(' ', $shippingAddress->getStreet()));
        } catch (Exception $exception) {
            throw new Exception(__('Could not retrieve street and housenumber from the recipient address.'));
        }

        if ($this->getConfig('carriers/dhlparcel_returnlabels/return_type', $storeId) == 1) {
            if (
                (
                    (
                        empty($this->getConfig('carriers/dhlparcel_returnlabels/firstname', $storeId))
                        || empty($this->getConfig('carriers/dhlparcel_returnlabels/lastname', $storeId))
                    )
                    && empty($this->getConfig('carriers/dhlparcel_returnlabels/companyname', $storeId))
                )
                || empty($this->getConfig('carriers/dhlparcel_returnlabels/country_id', $storeId))
                || empty($this->getConfig('carriers/dhlparcel_returnlabels/postalcode', $storeId))
                || empty($this->getConfig('carriers/dhlparcel_returnlabels/city', $storeId))
                || empty($this->getConfig('carriers/dhlparcel_returnlabels/streetname', $storeId))
                || empty($this->getConfig('carriers/dhlparcel_returnlabels/housenumber', $storeId))
                || empty($this->getConfig('carriers/dhlparcel_returnlabels/email', $storeId))
                || empty($this->getConfig('carriers/dhlparcel_returnlabels/phonenumber', $storeId))
            ) {
                throw new Exception(__('Please check your settings in System -> Configuration -> Sales -> Shipping Methods -> "DHLParcel - Return Labels": Address not set (correctly)'));
            }

            $receiverAddress = [
                'name' => [
                    'firstName' => $this->getConfig('carriers/dhlparcel_returnlabels/firstname', $storeId),
                    'lastName' => $this->getConfig('carriers/dhlparcel_returnlabels/lastname', $storeId),
                    'companyName' => $this->getConfig('carriers/dhlparcel_returnlabels/companyname', $storeId),
                ],
                'address' => [
                    'countryCode' => $this->getConfig('carriers/dhlparcel_returnlabels/country_id', $storeId),
                    'postalCode' => str_replace(" ", "", $this->getConfig('carriers/dhlparcel_returnlabels/postalcode', $storeId)),
                    'city' => $this->getConfig('carriers/dhlparcel_returnlabels/city', $storeId),
                    'street' => $this->getConfig('carriers/dhlparcel_returnlabels/streetname', $storeId),
                    'number' => $this->getConfig('carriers/dhlparcel_returnlabels/housenumber', $storeId),
                    'addition' => $this->getConfig('carriers/dhlparcel_returnlabels/addition', $storeId),
                    'isBusiness' => true,
                ],
                'email' => $this->getConfig('carriers/dhlparcel_returnlabels/email', $storeId),
                'phoneNumber' => $this->getConfig('carriers/dhlparcel_returnlabels/phonenumber', $storeId),
            ];
        } else {
            try {
                $recipientAddress = $this->parseStreetData(implode(' ', [
                    $this->getConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_ADDRESS1, $storeId),
                    $this->getConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_ADDRESS2, $storeId)]
                ));
            } catch (Exception $exception) {
                throw new Exception(__('Could not retrieve street and housenumber from the shipper address. Please check your settings in System -> Configuration -> Sales -> Shipping Settings -> Origin: Street Address not set (correctly)'));
            }

            $receiverAddress = [
                'name' => [
                    'firstName' => '',
                    'lastName' => '',
                    'companyName' => $this->getConfig('general/store_information/name', $storeId),
                ],
                'address' => [
                    'countryCode' => $this->getConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_COUNTRY_ID, $storeId),
                    'postalCode' => str_replace(" ", "", strtoupper($this->getConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_ZIP, $storeId))),
                    'city' => $this->getConfig(Mage_Shipping_Model_Shipping::XML_PATH_STORE_CITY, $storeId),
                    'street' => $recipientAddress['street'],
                    'number' => $recipientAddress['number'],
                    'addition' => $recipientAddress['addition'],
                    'isBusiness' => true,
                ],
                'email' => $this->getConfig('trans_email/ident_support/email', $storeId),
                'phoneNumber' => $this->getConfig('general/store_information/phone', $storeId)
            ];
        }

        // Check if package size is requested or choose smallest
        if (empty($packageSizes)) {
            $parcelTypes = Mage::getModel('dhlparcel_shipping/shipmentoptions')->getParcelTypes([
                'fromCountry'     => $shipment
                    ->getShippingAddress()
                    ->getCountryId(),
                'toCountry'   => $receiverAddress['address']['countryCode'],
                'option'        => 'DOOR'
            ]);

            // Helper Data
            $parcelTypes = $dhlHelper->parcelTypesToArray($parcelTypes);

            // Get first value (by default sorted by weight)
            $packageSizes = array (key($parcelTypes) => 1);
        }

        // Fill Packages Sizes
        $pieces = array();
        foreach ($packageSizes as $packageSize => $packageQty) {
            $pieces[] = [
                'parcelType'  => $packageSize,
                'quantity'    => $packageQty
            ];
        }

        $parameters = [
            'body' => [
                'shipmentId' => $this->getUuidV4(),
                'orderReference' => $order->getIncrementId(),
                'receiver' => $receiverAddress,
                'shipper' => [
                    'name' => [
                        'firstName' => (string)$shippingAddress->getFirstname(),
                        'lastName' => trim((string)$shippingAddress->getMiddlename() . ' ' . $shippingAddress->getLastname()),
                        'companyName' => (string)$shippingAddress->getCompany(),
                    ],
                    'address' => [
                        'countryCode' => (string)$shippingAddress->getCountryId(),
                        'postalCode' => str_replace(" ", "", strtoupper($shippingAddress->getPostcode())),
                        'city' => $shippingAddress->getCity(),
                        'street' => $shipperAddress['street'],
                        'number' => $shipperAddress['number'],
                        'isBusiness' => $fromBusiness !== null ? $fromBusiness : boolval(Mage::getStoreConfig('carriers/dhlparcel/b2b')),
                        'addition' => $shipperAddress['addition'],
                    ],
                    'email' => $shippingAddress->getEmail(),
                    'phoneNumber' => $shippingAddress->getTelephone(),
                ],
                'returnLabel' => true,
                'accountId' => Mage::getStoreConfig('carriers/dhlparcel/api_account_id'),
                'options' => $shipmentOptionsForRequest,
                'application' => $this->getApplicationName(),
                'pieces' => $pieces
            ],
        ];

        return $parameters;
    }

    public function getDefaultShipmentOptions($deliveryOptionKey)
    {
        return Mage::getModel('dhlparcel_shipping/shipmentoptions')
                                            ->getDefaultServiceOptions($deliveryOptionKey);
    }

    /**
     * @param $raw
     * @return array [
     *      'street'   => (string) Parsed street $raw
     *      'number'   => (string) Parsed number from $raw
     *      'addition' => (string) Parsed additional street data from $raw
     * ]
     * @throws Exception
     */
    protected function parseStreetData($raw)
    {
        $skipAdditionCheck = false;

        //if first word has ONE numbers and letter(s)
        $rawParts = explode(" ", trim($raw));
        $streetPrefix = '';
        $streetFirstWord = reset($rawParts);

        preg_match('/[0-9]+[a-zA-Z]+/i', trim($streetFirstWord), $firstWordParts);
        if (!empty($firstWordParts)) {
            $streetPrefix = $streetFirstWord . " ";
            unset($rawParts[key($rawParts)]);
        }

        $raw = implode(" ", $rawParts);

        preg_match('/([^0-9]*)\s*(.*)/i', trim($raw), $streetParts);
        $data = [
            'street' => isset($streetParts[1]) ? trim($streetParts[1]) : '',
            'number' => isset($streetParts[2]) ? trim($streetParts[2]) : '',
            'addition' => '',
        ];

        // Check if $street is empty
        if (strlen($data['street']) === 0) {
            // Try a reverse parse
            preg_match('/([\d]+[\w.-]*)\s*(.*)/i', trim($raw), $streetParts);
            $data['street'] = isset($streetParts[2]) ? trim($streetParts[2]) : '';
            $data['number'] = isset($streetParts[1]) ? trim($streetParts[1]) : '';
            $skipAdditionCheck = true;
        }

        // Check if $number has numbers
        if (preg_match("/\d/", $data['number']) !== 1) {
            $data['street'] = trim($raw);
            $data['number'] = '';
        } elseif (!$skipAdditionCheck) {
            preg_match('/([\d]+)[ .-]*(.*)/i', $data['number'], $numberParts);
            $data['number'] = isset($numberParts[1]) ? trim($numberParts[1]) : '';
            $data['addition'] = isset($numberParts[2]) ? trim($numberParts[2]) : '';
        }

        // Reassemble street
        if (isset($data['street'])) {
            $data['street'] = $streetPrefix . $data['street'];
        }

        return $data;
    }

    /**
     * @param int $storeId
     * @return array
     * @throws Exception
     */
    protected function getOnBehalfOf($storeId = 0)
    {
        if (
            (
                empty($this->getConfig('carriers/dhlparcel_ssn/firstname', $storeId))
                && empty($this->getConfig('carriers/dhlparcel_ssn/lastname', $storeId))
                && empty($this->getConfig('carriers/dhlparcel_ssn/companyname', $storeId))
            )
            || empty($this->getConfig('carriers/dhlparcel_ssn/country_id', $storeId))
            || empty($this->getConfig('carriers/dhlparcel_ssn/postalcode', $storeId))
            || empty($this->getConfig('carriers/dhlparcel_ssn/city', $storeId))
            || empty($this->getConfig('carriers/dhlparcel_ssn/streetname', $storeId))
            || empty($this->getConfig('carriers/dhlparcel_ssn/housenumber', $storeId))
            || empty($this->getConfig('carriers/dhlparcel_ssn/email', $storeId))
            || empty($this->getConfig('carriers/dhlparcel_ssn/phonenumber', $storeId))
        ) {
            throw new Exception('Please check your settings in System -> Configuration -> Sales -> Shipping Methods -> "DHLParcel - Undisclosed sender (B2B)": Address not set (correctly)');
        }

        return [
            'name' => [
                'firstName' => $this->getConfig('carriers/dhlparcel_ssn/firstname', $storeId),
                'lastName' => $this->getConfig('carriers/dhlparcel_ssn/lastname', $storeId),
                'companyName' => $this->getConfig('carriers/dhlparcel_ssn/companyname', $storeId),
            ],
            'address' => [
                'countryCode' => $this->getConfig('carriers/dhlparcel_ssn/country_id', $storeId),
                'postalCode' => str_replace(" ", "", $this->getConfig('carriers/dhlparcel_ssn/postalcode', $storeId)),
                'city' => $this->getConfig('carriers/dhlparcel_ssn/city', $storeId),
                'street' => $this->getConfig('carriers/dhlparcel_ssn/streetname', $storeId),
                'number' => $this->getConfig('carriers/dhlparcel_ssn/housenumber', $storeId),
                'addition' => $this->getConfig('carriers/dhlparcel_ssn/addition', $storeId),
                'isBusiness' => true,
            ],
            'email' => $this->getConfig('carriers/dhlparcel_ssn/email', $storeId),
            'phoneNumber' => $this->getConfig('carriers/dhlparcel_ssn/phonenumber', $storeId),
        ];
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getUuidV4()
    {
        // phpcs:disable
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0C2f) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0x2Aff),
            mt_rand(0, 0xffD3),
            mt_rand(0, 0xff4B)
        );
        // phpcs:enable
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    protected function rollBackShipment($shipment)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $shipment->getOrder();

        // Unset Shipped Items
        foreach ($shipment->getAllItems() as $shipmentItem) {
            $qty = $shipmentItem->getQty();

            /**
             * @var $orderItem Mage_Sales_Model_Order_Item
             */
            $orderItem = $shipmentItem->getOrderItem();
            $orderItem->setQtyShipped($orderItem->getQtyShipped()-$qty);
            $orderItem->save();

            // Delete Shipment Item
            $shipmentItem->delete();
        }

        // Delete Shipment
        $shipment->delete();

        // Set back order to last status
        $history = $order->getStatusHistoryCollection()->getFirstItem();
        $state = $history->getData('status');

        // 'Pending' is a protected state
        if (trim($state) == 'pending' || trim($state) == 'pending_payment') {
            $state = 'pending_payment';
        }

        if ($state == Mage_Sales_Model_Order::STATE_COMPLETE) {
            $order->addStatusHistoryComment("Rollback", Mage_Sales_Model_Order::STATE_COMPLETE)->setIsCustomerNotified(false);
            $order->setData('state', Mage_Sales_Model_Order::STATE_COMPLETE);
        } else {
            $order->setState($state, true, 'Undo Shipment; Due to error: ' . $this->getLastError());
        }

        $order->save();

        /** @var Mage_Admin_Model_Session $session */
        $session = Mage::getSingleton('adminhtml/session');
        // Clear messages
        $session->getMessages(true);

        return;
    }

    public function getReferenceValue(Mage_Sales_Model_Order_Shipment $shipment)
    {
        $storeId = $shipment->getStoreId();

        if ($this->getConfig('carriers/dhlparcel_labels/label_print_ordernumer', $storeId) != 1) {
            return '';
        }

        switch ($this->getConfig('carriers/dhlparcel_labels/label_reference_field', $storeId)) :
            case 'order_id' :
                $order = $shipment->getOrder();

                return $order->getId();
                break;
            case 'shipment_increment_id' :
                return $shipment->getIncrementId();
                break;
            case 'shipment_id' :
                    return $shipment->getId();
                break;
            case 'shipment_custom' :
                $customField = $this->getConfig('carriers/dhlparcel_labels/label_reference_custom_field', $storeId);

                return $shipment->getData($customField);
            break;
            case 'order_custom' :
                $customField = $this->getConfig('carriers/dhlparcel_labels/label_reference_custom_field', $storeId);
                $order = $shipment->getOrder();

                return $order->getData($customField);
                break;
            case 'order_increment_id' :
            default :
                $order = $shipment->getOrder();

                return $order->getIncrementId();
                break;
        endswitch;
    }

    public function getReference2Value(Mage_Sales_Model_Order_Shipment $shipment)
    {
        $storeId = $shipment->getStoreId();
        if ($this->getConfig('carriers/dhlparcel_labels/label_print_ordernumer', $storeId) != 1) {
            return '';
        }

        return $this->getConfig('carriers/dhlparcel_labels/label_reference2_text', $storeId);
    }

    /**
     * @return string
     */
    protected function getApplicationName()
    {
        $edition = (Mage::getEdition() === 'Community') ? 'C' : 'E';
        $version = Mage::getVersion();
        return sprintf('Magento%s%s', $edition, $version);
    }
}
