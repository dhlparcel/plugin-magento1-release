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
 * @author    Ron Oerlemans <plugins@dhl.com>
 * @author    Elmar van Wijnen <plugins@dhl.com>
 * @copyright ${YEAR} DHLParcel
 * @link      https://www.dhlparcel.nl/
 */

class DHLParcel_Shipping_Model_Shipmentoptions
{
    protected $allowedDeliveryOptions = [
        'DOOR',
        'PS',
        'BP'
    ];
    protected $allowedShippingMethods = [
        'dhlparcel_DOOR',
        'dhlparcel_PS'
    ];
    protected $allowedServiceOptions = [
        'ADD_RETURN_LABEL',
        'SDD',
        'EA',
        'HANDT',
        'EVE',
        'NBB',
        'REFERENCE',
        'REFERENCE2',
        //'H',
        'INS',
        'S',
        'EXP',
        'SSN',
        // 'COD_CASH', // Not implemented yet
        'BOUW',
        'EXW',
        'AGE_CHECK'
    ];
    protected $allowedForConsumers = [
        'SDD',
        'BOUW',
        'NBB',
        'EVE',
        'EXP',
        'S'
    ];
    protected $skipWithTimeWindows = [
        'SDD',
        'EVE',
        'S'
    ];
    protected $timeWindowsCountries = [
        'NL'
    ];
    protected $forceInputType = [
        'SSN' => 'checkbox'
    ];
    protected $inputPlaceholders = [
        'EA'  => 'Insurance Value',
        'INS' => 'Insurance Value in Euros'
    ];
    protected $allowedAsDefault = [
        'default_ea'           => 'EA',
        'default_sod'          => 'HANDT',
        'default_age_check'    => 'AGE_CHECK',
        'default_no_neighbour' => 'NBB'
    ];
    protected $forceServiceOptionDescription = [
        'INS' => 'Shipment insurance'
    ];
    protected $serviceOptionDescriptionText = [
        'INS' => 'Additional transport insurance. If the value of the goods exceeds € 50.000, please contact our Customer Service prior to shipping.',
    ];
    protected $serviceOptionInputPost = [
        'INS' => '[EUR]',
    ];

    /** @var DHLParcel_Shipping_Model_Service_DeliveryTimes */
    protected $deliveryTimesService;
    /** @var DHLParcel_Shipping_Model_Service_Capability */
    protected $capabilityService;
    /**
     * DHLParcel_Shipping_Model_Shipmentoptions constructor.
     */
    public function __construct()
    {
        $this->capabilityService = Mage::getSingleton('dhlparcel_shipping/service_capability');
        $this->deliveryTimesService = Mage::getSingleton('dhlparcel_shipping/service_deliveryTimes');
    }

    /**
     * @return DHLParcel_Shipping_Model_Carrier
     */
    public function getCarrier()
    {
        return Mage::getModel('shipping/config')->getCarrierInstance('dhlparcel');
    }

    /**
     * @param $shippingMethod
     * @return bool
     */
    public function isAllowedShippingMethod($shippingMethod)
    {
        $allowedShippingMethods = $this->getAllowedShippingMethods();

        return in_array($shippingMethod, $allowedShippingMethods);
    }

    /**
     * @param $shippingMethod
     * @return bool
     */
    public function isAllowedDeliveryMethod($deliveryMethod)
    {
        $allowedDeliveryMethods = $this->getAllowedDeliveryMethods();

        return in_array(strtoupper($deliveryMethod), $allowedDeliveryMethods);
    }

    /**
     * @return array
     */
    public function getAllowedShippingMethods()
    {
        return $this->allowedShippingMethods;
    }

    public function getAllowedServiceOptionsForCustomers()
    {
        return $this->allowedForConsumers;
    }

    /**
     * @param $serviceOption
     * @return bool
     */
    protected function isAllowedServiceOption($serviceOption, $blackListedOptions)
    {
        if (!in_array($serviceOption, $this->allowedServiceOptions)) {
            return false;
        }

        if (in_array($serviceOption, $blackListedOptions)) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getAllowedDeliveryMethods()
    {
        return $this->allowedDeliveryOptions;
    }

    /**
     * Get delivery options that are possible by giver args
     *
     * @param array $args
     * @return array delivery options
     * @throws Exception
     */
    public function getDeliveryOptions($args = [])
    {
        $deliveryOptions = [];
        // TODO currently unused here
        $args['returnProduct'] = (!isset($args['returnProduct']) ? false : $args['returnProduct']);
        $args['toBusiness'] = (!isset($args['toBusiness']) ? (bool)Mage::getStoreConfig('carriers/dhlparcel/b2b') : (bool)$args['toBusiness']);
        $args['toPostalCode'] = isset($args['toPostalCode']) ? $args['toPostalCode'] : '';
        $args['option'] = isset($args['option']) ? $args['option'] : array();

        $capabilities = $this->capabilityService->get($args['toCountry'], $args['toPostalCode'], $args['toBusiness'], $args['option']);

        foreach ($capabilities as $product) {
            foreach ($product->getData('options') as $sOption) {
                $option = $sOption->toArray();
                if ($option['optionType'] == 'DELIVERY_OPTION' && in_array($option['key'], $this->allowedDeliveryOptions)) {
                    $exclusions = [];

                    if (isset($option['exclusions'])) {
                        foreach ($option['exclusions'] as $exclusion) {
                            $exclusions[] = $exclusion['key'];
                        }
                    }

                    $deliveryOptions[$option['key']] = $option;
                    $deliveryOptions[$option['key']]['exclusions'] = $exclusions;
                }
            }
        }

        return $deliveryOptions;
    }

    /**
     * Get service options that are possible by giver args
     *
     * @param array $args
     * @param bool $deliveryOption
     * @param null $cartSubTotal
     * @return array
     * @throws Exception
     */
    public function getServiceOptions($args = [], $deliveryOption = false, $cartSubTotal = null)
    {
        $serviceOptions = [];
        // TODO currently unused here
        $args['returnProduct'] = (!isset($args['returnProduct']) ? false : $args['returnProduct']);
        $args['toBusiness'] = (!isset($args['toBusiness']) ? (bool)Mage::getStoreConfig('carriers/dhlparcel/b2b') : $args['toBusiness']);
        $args['toPostalCode'] = isset($args['toPostalCode']) ? $args['toPostalCode'] : '';
        $args['option'] = isset($args['option']) ? $args['option'] : array();

        $capabilities = $this->capabilityService->get($args['toCountry'], $args['toPostalCode'], $args['toBusiness'], $args['option']);

        // Get Blacklisted options by cart
        $cart = Mage::getModel('checkout/cart')->getQuote();
        $blackListedOptions = $this->getCarrier()->getBlacklistedByProducts($cart->getAllItems());

        // Build Service Options
        foreach ($capabilities as $product) {
            foreach ($product->getData('options') as $sOption) {
                $option = $sOption->toArray();
                if (
                    $option['optionType'] == 'SERVICE_OPTION'
                    && ($deliveryOption === false || !in_array($option['key'], $deliveryOption['exclusions']))
                    && $this->isAllowedServiceOption($option['key'], $blackListedOptions)
                ) {
                    $exclusions = [];

                    if (isset($option['exclusions'])) {
                        foreach ($option['exclusions'] as $exclusion) {
                            $exclusions[] = $exclusion['key'];
                        }
                    }

                    // Enrich array-data
                    $option['exclusions'] = $exclusions;
                    $option['is_for_consumer'] = in_array($option['key'], $this->allowedForConsumers);
                    $option['skip_with_time_widows'] = in_array($option['key'], $this->skipWithTimeWindows);
                    if ($option['is_for_consumer'] === true) {
                        $option['is_available'] = Mage::getStoreConfig('carriers/dhlparcel_shipping_option_' . $option['key'] . '/active');
                        if (
                            $cartSubTotal === null
                            || Mage::getStoreConfig('carriers/dhlparcel_shipping_option_' . $option['key'] . '/free_shipping_subtotal') == 0
                            || $cartSubTotal < Mage::getStoreConfig('carriers/dhlparcel_shipping_option_' . $option['key'] . '/free_shipping_subtotal')
                        ) {
                            $option['price'] = Mage::getStoreConfig('carriers/dhlparcel_shipping_option_' . $option['key'] . '/price');
                        } else {
                            $option['price'] = 0;
                        }

                        $option['customer_title'] = Mage::getStoreConfig('carriers/dhlparcel_shipping_option_' . $option['key'] . '/title');
                    }

                    $option['placeholder'] = '';
                    if (array_key_exists($option['key'], $this->inputPlaceholders)) {
                        $option['placeholder'] = $this->inputPlaceholders[$option['key']];
                    }
                    $option['inputType'] = (!isset($option['inputType']) ? 'checkbox' : $option['inputType']);
                    if (array_key_exists($option['key'], $this->forceInputType)) {
                        $option['inputType'] = $this->forceInputType[$option['key']];
                    }
                    if (array_key_exists($option['key'], $this->forceServiceOptionDescription)) {
                        $option['description'] = $this->forceServiceOptionDescription[$option['key']];
                    }
                    if (array_key_exists($option['key'], $this->serviceOptionDescriptionText)) {
                        $option['descriptionText'] = $this->serviceOptionDescriptionText[$option['key']];
                    }
                    if (array_key_exists($option['key'], $this->serviceOptionInputPost)) {
                        $option['postinput'] = $this->serviceOptionInputPost[$option['key']];
                    }

                    $serviceOptions[$option['key']] = $option;
                }
            }
        }

        ksort($serviceOptions);

        return $serviceOptions;

    }

    /**
     * Get Parcel Types
     *
     * @param $args
     * @return array
     * @throws Exception
     */
    public function getParcelTypes($args)
    {
        $parcelTypes = [];
        // TODO currently unused here
        $args['returnProduct'] = (!isset($args['returnProduct']) ? false : $args['returnProduct']);
        $args['toBusiness'] = (!isset($args['toBusiness']) ? (bool)Mage::getStoreConfig('carriers/dhlparcel/b2b') : $args['toBusiness']);
        $args['toPostalCode'] = isset($args['toPostalCode']) ? $args['toPostalCode'] : '';
        $args['option'] = isset($args['option']) ? $args['option'] : array();

        $capabilities = $this->capabilityService->get($args['toCountry'], $args['toPostalCode'], $args['toBusiness'], $args['option']);

        // Build Service Options
        foreach ($capabilities as $oProduct) {
            $product = $oProduct->toArray();
            if (isset($product['parcelType']) && isset($product['parcelType']['key'])) {
                $parcelTypes[$product['parcelType']['key']] = $product['parcelType'];
            }
        }

        // Sort Parcel Sizes
        usort($parcelTypes, [$this, 'parceltypeWeightSort']);

        return $parcelTypes;
    }

    /**
     * @param $one
     * @param $two
     * @return bool
     */
    protected function parceltypeWeightSort($one, $two)
    {
        return $one['maxWeightKg'] > $two['maxWeightKg'];
    }

    /**
     * @param $shipmentOption
     * @param $serviceOptions
     * @return bool
     */
    public function isAllowed($shipmentOption, $serviceOptions)
    {
        switch ($shipmentOption) {
            case 'EVE' :
                if (array_key_exists('EVE', $serviceOptions)) {
                    return (bool)$serviceOptions['EVE']['is_available'];
                }
                break;
            case 'SDD' :
                if (array_key_exists('SDD', $serviceOptions)) {
                    return (bool)$serviceOptions['SDD']['is_available'];
                }
                break;
            default :
                return true;
        }
    }

    /**
     * @param $args
     * @param null $serviceOptions
     * @return array
     * @throws Exception
     * @deprecated
     * TODO this should probably be moved towards the deliverytime service
     */
    public function getTimeWindows($countryCode, $postalCode, $serviceOptions = null)
    {
        if ($serviceOptions === null) {
            $serviceOptions = $this->getServiceOptions([
                'toCountry' => $countryCode
            ], 'DOOR', Mage::helper('checkout/cart')->getQuote()->getSubtotal());
        }

        $timeWindowsReturn = [];

        $timeWindows = $this->deliveryTimesService->get($countryCode, $postalCode);

        // Build Service Options
        $selected = false;
        $sddCutoffTime = strtotime(Mage::getStoreConfig('carriers/dhlparcel_time_windows/sdd_cutoff_time'));
        $defaultCutoffTime = strtotime(Mage::getStoreConfig('carriers/dhlparcel_time_windows/cutoff_time'));

        $todayTime = strtotime(date('d-m-Y'));
        $timeNow = Mage::getModel('core/date')->timestamp();

        $transitDays = Mage::getStoreConfig('carriers/dhlparcel_time_windows/transitdays');
        $firstDeliveryDay = ($todayTime + ($transitDays * 60 * 60 * 24));
        $shipmentDaysConfig = Mage::getStoreConfig('carriers/dhlparcel_time_windows/shipment_days');
        $shipmentDays = array();
        if (!empty($shipmentDaysConfig)) {
            $shipmentDays = explode(',', $shipmentDaysConfig);
        }

        $showDaysForward = Mage::getStoreConfig('carriers/dhlparcel_time_windows/show_days_forward');
        $showTillDate = strtotime(date('d-m-Y') . ' + ' . $showDaysForward . ' day');

        // Deliverydays possible
        $daysOfTheWeekPossible = [];
        foreach ($timeWindows as $timeWindow) {
            $daysOfTheWeekPossible[] = date('w', strtotime($timeWindow['deliveryDate']));
        }
        $daysOfTheWeekPossible = array_unique($daysOfTheWeekPossible);

        // Build delivery days of the week
        $deliveryDaysOfTheWeek = [];

        foreach ($shipmentDays as $dayOfTheWeek) {
            for ($i = 1; $i <= 7; $i++) {
                $dayToCheck = date('w', strtotime('Sunday +' . ($dayOfTheWeek + $i) . ' days'));
                if (in_array($dayToCheck, $daysOfTheWeekPossible)) {
                    $deliveryDaysOfTheWeek[] = $dayToCheck;

                    break;
                }
            }
        }

        $exclusions = [];
        foreach ($timeWindows as $timeWindow) {
            $startTime = $timeWindow['startTime'];
            $endTime = $timeWindow['endTime'];
            $dateTime = strtotime($timeWindow['deliveryDate']);

            if ($dateTime != $todayTime && $firstDeliveryDay > $dateTime || $dateTime >= $showTillDate) {
                continue;
            }

            $shipmentOptions = [];
            if ($dateTime == $todayTime) {
                // Same Day Delivery
                if ($this->isAllowed('SDD', $serviceOptions) !== true || $transitDays > 1 || !in_array(date('w', $dateTime), $shipmentDays)) {
                    continue;
                }

                // If Cutofftime is passed
                if ($sddCutoffTime < $timeNow) {
                    continue;
                }

                // Same Day Delivery is never with end-time before 1800
                if (($endTime < 1800 && $endTime !== '0000') || $startTime <= 1400) {
                    continue;
                }

                $price = $serviceOptions['SDD']['price'];
                $shipmentOptions[] = 'SDD';
                $exclusions = array_merge($exclusions, $serviceOptions['SDD']['exclusions']);
            } else {
                $deliveryDayOfTheWeek = date('w', $dateTime);
                if (!in_array($deliveryDayOfTheWeek, $deliveryDaysOfTheWeek)) {
                    continue;
                }

                // Check cutoff time
                if ($defaultCutoffTime < $timeNow && $firstDeliveryDay == $dateTime) {
                    continue;
                }

                if (intval($startTime) > 1400
                    && (intval($endTime) > 1800 || $endTime === '0000')) {
                    // Evening Delivery
                    if ($this->isAllowed('EVE', $serviceOptions) !== true) {
                        continue;
                    }
                    $price = $serviceOptions['EVE']['price'];
                    $shipmentOptions[] = 'EVE';
                    $exclusions = array_merge($exclusions, $serviceOptions['EVE']['exclusions']);
                } else {
                    // Normal Delivery
                    $price = 0;
                }
            }

            if ($price == 0 && $selected === false) {
                $selected = true;
            }

            if ($price > 0) {
                $priceText = ' + ' . Mage::helper('core')->currency($price, true, false);
            } else {
                $priceText = '';
            }

            $values = [];

            $values[] = date('Y-m-d', $dateTime);
            $values[] = implode(',', $shipmentOptions);

            $startTimeDate = strtotime($timeWindow['startTime']);
            $endTimeDate = strtotime($timeWindow['endTime']);
            $label = $this->formattedDate($dateTime) . ' (' . date('H:i', $startTimeDate) . '-' . date('H:i', $endTimeDate) . ')' . $priceText;
            $timeWindowsReturn[] = [
                'value'      => implode('_', $values),
                'label'      => $label,
                'selected'   => $selected,
                'price'      => 0,
                'options'    => $shipmentOptions,
                'exclusions' => $exclusions
            ];

            if ($selected === true) {
                $selected = null;
            }
        }

        return $timeWindowsReturn;

    }

    protected function formattedDate($timestamp)
    {
        /** @var DHLParcel_Shipping_Helper_Data $helper */
        $helper = Mage::helper('dhlparcel_shipping');
        $days = [
            1 => $helper->__('Monday'),
            2 => $helper->__('Tuesday'),
            3 => $helper->__('Wednesday'),
            4 => $helper->__('Thursday'),
            5 => $helper->__('Friday'),
            6 => $helper->__('Saturday'),
            7 => $helper->__('Sunday')
        ];
        $months = [
            1  => $helper->__('January'),
            2  => $helper->__('February'),
            3  => $helper->__('March'),
            4  => $helper->__('April'),
            5  => $helper->__('May'),
            6  => $helper->__('June'),
            7  => $helper->__('July'),
            8  => $helper->__('August'),
            9  => $helper->__('September'),
            10 => $helper->__('October'),
            11 => $helper->__('November'),
            12 => $helper->__('December')
        ];
        return $days[date('N', $timestamp)] . ' ' . date('j', $timestamp) . ' ' . $months[date('n', $timestamp)];
    }

    /**
     * @param $deliveryOptionKey
     * @return array
     * @throws Varien_Exception
     */
    public function getDefaultServiceOptions($deliveryOptionKey)
    {
        $defaultServiceOptions = [];

        foreach ($this->allowedAsDefault as $configKey => $serviceOption) {
            if (boolval(Mage::getStoreConfig('carriers/' . strtoupper($deliveryOptionKey) . '_dhlparcel/' . $configKey))) {
                $defaultServiceOptions[] = $serviceOption;
            }
        }

        // Handle default shipmentoptions without delivery-option-key
        if (Mage::getStoreConfig('carriers/dhlparcel_returnlabels/default')) {
            $defaultServiceOptions[] = 'ADD_RETURN_LABEL';
        }

        if (Mage::getStoreConfig('carriers/dhlparcel_ssn/default')) {
            $defaultServiceOptions[] = 'SSN';
        }

        return $defaultServiceOptions;
    }

    public function getLabelByServiceOptions($serviceOptionKey)
    {
        $label = Mage::getStoreConfig('carriers/dhlparcel_shipping_option_' . $serviceOptionKey . '/title');
        if (empty($label)) {
            $label = $serviceOptionKey;
        }

        return $label;
    }

    /**
     * @param bool $countryId
     * @return bool
     * @throws Varien_Exception
     */
    public function timeWindowsEnabled($countryId = false)
    {
        // If there is a country selected check if it's enabled
        if ($countryId !== false) {
            if (!in_array(strtoupper($countryId), $this->timeWindowsCountries)) {
                return false;
            }
        }

        return (
            Mage::getStoreConfig('carriers/dhlparcel_time_windows/enable') == true &&
            Mage::getStoreConfig('carriers/dhlparcel/b2b') == false
        );
    }
}
