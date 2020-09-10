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

class DHLParcel_Shipping_Block_Adminhtml_Sales_Order_Shipment_View_Form extends Mage_Adminhtml_Block_Sales_Order_Shipment_View_Form
{
    /**
     * @return bool
     */
    protected function isDhlOrder()
    {
        $deliveryMethod = $this->getShipment()->getOrder()->getData('shipping_method');
        $deliveryMethodParts = explode('_', $deliveryMethod);

        // Check if shipping method is dhlparcel
        if ($deliveryMethodParts[0] == 'dhlparcel') {
            return true;
        }

        // Or check if there is a "track" with a DHL method
        foreach ($this->getShipment()->getAllTracks() as $track) {
            if ($track->getData('carrier_code') == 'dhlparcel') {
                return true;
            }
        }

        return false;
    }

    /**
     * Get current shipment
     *
     * @return Mage_Sales_Model_Order_Shipment.
     */
    public function getShipment()
    {
        if ($this->hasShipment()) {
            return $this->_getData('shipment');
        }

        $shipment = Mage::registry('current_shipment');

        $this->setShipment($shipment);
        return $shipment;
    }

    /**
     * Get create label button html
     *
     * @return string
     */
    public function getCreateLabelButton()
    {
        // Disable 'Create Shipping Label...' button
        if ($this->isDhlOrder()) {
            return '';
        }

        return parent::getCreateLabelButton();
    }

    /**
     * Overwrite for canCreateShippingLabel
     * to enable the print label button
     *
     * @return bool
     */
    public function canCreateShippingLabel()
    {
        if ($this->isDhlOrder()) {
            return true;
        }

        return parent::canCreateShippingLabel();
    }

    public function getPrintLabelButton()
    {
        $buttons = [];

        if (!(Mage::getStoreConfig('carriers/dhlparcel_direct_print/printer') && Mage::getStoreConfig('carriers/dhlparcel_direct_print/hide_download'))) {
            $buttons[] = parent::getPrintLabelButton();
        }

        if (Mage::getStoreConfig('carriers/dhlparcel_direct_print/printer')) {
            $url = $this->getUrl('adminhtml/dhlparcel_shipment/directprint', ['shipment_id' => $this->getShipment()->getId()]);
            $buttons[] = $this->getLayout()
                ->createBlock('adminhtml/widget_button')
                ->setData([
                    'label'   => Mage::helper('sales')->__('Direct print Shipping Label'),
                    'onclick' => "setLocation('$url')"
                ])
                ->toHtml();
        }

        if (
            boolval(Mage::getStoreConfig('carriers/dhlparcel/debug'))
            && boolval(Mage::getStoreConfig('carriers/dhlparcel/save_label_requests'))
            && $this->isDhlOrder()
            && !empty($this->getShipment()->getData('dhlparcel_shipping_request'))
        ) {
            $url = $this->getUrl('adminhtml/dhlparcel_shipment/apiRequest', ['shipment_id' => $this->getShipment()->getId()]);
            $buttons[] = $this->getLayout()
                ->createBlock('adminhtml/widget_button')
                ->setData([
                    'label'   => Mage::helper('sales')->__('Debug - API Request'),
                    'onclick' => "setLocation('$url')"
                ])
                ->toHtml();
        }

        return implode(' ', $buttons);
    }
}
