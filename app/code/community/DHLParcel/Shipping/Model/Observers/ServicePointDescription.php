<?php

class DHLParcel_Shipping_Model_Observers_ServicePointDescription
{
    public function update(Varien_Event_Observer $observer)
    {
        /** @var $address Mage_Sales_Model_Quote_Address */
        $address = $observer->getQuoteAddress();
        if (empty($address) || $address->getShippingMethod() !== 'dhlparcel_PS') {
            return;
        }

        if (!boolval(Mage::getStoreConfig('carriers/PS_dhlparcel/full_address_description'))) {
            return;
        }

        $quote = $address->getQuote();
        if (empty($quote) || empty($servicePointId = $quote->getData('dhlparcel_servicepoint'))) {
            return;
        }

        /** @var DHLParcel_Shipping_Model_Service_ServicePoint $servicePointService */
        $servicePointService = Mage::getSingleton('dhlparcel_shipping/service_servicePoint');
        $servicePoint = $servicePointService->get($address->getCountryId(), $servicePointId);
        if (empty($servicePoint)) {
            return;
        }

        $address->setShippingDescription(implode(', ', [
            __('DHL ServicePoint:') . ' ' . $servicePoint->name,
            trim($servicePoint->address->street . ' ' . $servicePoint->address->number . ' ' . $servicePoint->address->addition),
            $servicePoint->address->postalCode . ' ' . $servicePoint->address->city
        ]));
    }
}
