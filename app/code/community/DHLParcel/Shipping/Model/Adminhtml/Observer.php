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

class DHLParcel_Shipping_Model_Adminhtml_Observer extends Varien_Object
{
    /**
     * Get current shipment
     *
     * @return Mage_Sales_Model_Order_Shipment.
     */
    public function getShipment()
    {
        return Mage::registry('current_shipment');
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer|void
     * @throws Exception
     */
    public function saveShipment(Varien_Event_Observer $observer)
    {
        /** @var DHLParcel_Shipping_Helper_Data $dhlHelper */
        $dhlHelper = Mage::helper('dhlparcel_shipping');
        if (!$dhlHelper->isActive()) {
            return;
        }

        $postData = Mage::app()->getRequest()->getParams();
        $shipment = $this->getShipment();

        // Check if order is a DHL order
        if (empty($postData['shipment']['create_dhl_label'])) {
            return;
        }

        // Check if label is requested
        if (!isset($postData['dhlparcel_shipping_delivery_method'])) {
            return;
        }

        $packageSizes = [];
        foreach ($postData['dhl_shipping_parcel_type'] as $packageSize) {
            if (!array_key_exists($packageSize, $packageSizes)) {
                $packageSizes[$packageSize] = 0;
            }

            $packageSizes[$packageSize]++;
        }

        // Get label options
        $options = [];
        $toBusiness = isset($postData['dhlparcel_shipping_to_business']) && $postData['dhlparcel_shipping_to_business'] == 'on';
        $deliveryMethod = $postData['dhlparcel_shipping_delivery_method'];
        $shipmentOptions = (isset($postData['dhlparcel_shipping_options']) && is_array($postData['dhlparcel_shipping_options'][$deliveryMethod]) ? $postData['dhlparcel_shipping_options'][$deliveryMethod] : []);

        foreach ($shipmentOptions as $shipmentOptionKey => $shipmentOption) {
            if (!empty($shipmentOption['VALUE']) && isset($shipmentOption['KEY'])) {
                // Valued Shipment options
                $options[$shipmentOptionKey] = $shipmentOption['VALUE'];
            } elseif (!isset($shipmentOption['VALUE'])) {
                $options[] = $shipmentOptionKey;
            } // Else ignore this option
        }

        if ($deliveryMethod == 'PS' && !empty($postData['dhlparcel_shipping_options']['PS']['PS']['VALUE'])) {
            $options['PS'] = $postData['dhlparcel_shipping_options']['PS']['PS']['VALUE'];
        }
        if ($deliveryMethod == 'BP' || $deliveryMethod == 'DOOR') {
            $options[] = $deliveryMethod;
        }

        $addReturnLabel = false;
        if (is_array($shipmentOptions)) {
            $addReturnLabel = in_array('ADD_RETURN_LABEL', $shipmentOptions);
        }

        /** @var  $labelHelper DHLParcel_Shipping_Helper_Labels */
        $labelHelper = Mage::helper('dhlparcel_shipping/labels');
        $labelContent = $labelHelper->createDhlLabel($shipment, $options, $packageSizes, $toBusiness, $addReturnLabel);

        // If there was an error save it to session
        if ($labelContent === false) {
            Mage::getSingleton('adminhtml/session')->addError($labelHelper->getLastError());
        } else {
            $shipment->setData('shipping_label', $labelContent)->save();
        }

        return $observer;
    }

    /**
     * @return DHLParcel_Shipping_Model_Carrier
     */
    public function getCarrier()
    {
        return Mage::getModel('shipping/config')->getCarrierInstance('dhlparcel');
    }

    public function salesOrderShipmentSaveBefore(Varien_Event_Observer $observer)
    {
        if (Mage::registry('salesOrderShipmentSaveBeforeTriggered')) {
            return $this;
        }

        /** @var DHLParcel_Shipping_Helper_Data $dhlHelper */
        $dhlHelper = Mage::helper('dhlparcel_shipping');
        if (!$dhlHelper->isActive()) {
            return;
        }

        /* @var $shipment Mage_Sales_Model_Order_Shipment */
        $shipment = $observer->getEvent()->getShipment();

        // Get PostData
        $postData = Mage::app()->getRequest()->getParams();

        // Check if order is a DHL order
        if (empty($postData['shipment']['create_dhl_label'])) {
            return;
        }

        if ($shipment) {
            if ($this->_isValidForShipmentEmail($shipment)) {
                $shipment->setEmailSent(true);
                Mage::register('salesOrderShipmentSaveBeforeTriggered', true);
            }
        }
        return $this;
    }

    public function salesOrderShipmentSaveAfter(Varien_Event_Observer $observer)
    {
        if (Mage::registry('salesOrderShipmentSaveAfterTriggered')) {
            return $this;
        }

        /** @var DHLParcel_Shipping_Helper_Data $dhlHelper */
        $dhlHelper = Mage::helper('dhlparcel_shipping');
        if (!$dhlHelper->isActive()) {
            return $this;
        }

        /* @var $shipment Mage_Sales_Model_Order_Shipment */
        $shipment = $observer->getEvent()->getShipment();

        // Get PostData
        $postData = Mage::app()->getRequest()->getParams();
        $module = Mage::app()->getRequest()->getControllerModule();
        $controller = Mage::app()->getRequest()->getControllerName();
        // Check if create label has been checked for manual creation or check if dhlparcel shipping massaction triggerred the observer
        if (!empty($postData['shipment']['create_dhl_label']) || ($module === 'DHLParcel_Shipping_Adminhtml' && $controller === 'dhlparcel_massActions')) {
            // Check if shipment object is available and is allowed
            if ($shipment && $this->_isValidForShipmentEmail($shipment)) {
                $shipment->sendEmail();
                Mage::register('salesOrderShipmentSaveAfterTriggered', true);
            }
        }

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @return bool
     */
    protected function _isValidForShipmentEmail($shipment)
    {
        $trackingNumbers = [];
        /** @var Mage_Sales_Model_Order_Shipment_Track $track */
        foreach ($shipment->getAllTracks() as $track) {
            $trackingNumbers[] = $track->getNumber();
        };
        // send shipment email only when carrier tracking info is added
        if (count($trackingNumbers) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $observer
     * @return mixed
     * @throws Exception
     */
    public function addMassActions($observer)
    {
        /** @var Mage_Adminhtml_Block_Widget_Grid_Massaction $block */
        $block = $observer->getEvent()->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Widget_Grid_Massaction && $block->getRequest()->getControllerName() == 'sales_order') {
            // Create labels
            $block->addItem('dhlparcel_shipping_create', [
                'label' => 'DHLParcel - Create labels',
                'url'   => Mage::helper('adminhtml')->getUrl('adminhtml/dhlparcel_massActions/createlabels')
            ]);

            // Create labels & print
            if (Mage::getStoreConfig('carriers/dhlparcel_direct_print/enable_download') == true) {
                $block->addItem('dhlparcel_shipping_create_print', [
                    'label' => 'DHLParcel - Create labels & Print',
                    'url'   => Mage::helper('adminhtml')->getUrl('adminhtml/dhlparcel_massActions/createlabelsprint')
                ]);
            }

            // Create labels & direct print
            if (Mage::getStoreConfig('carriers/dhlparcel_direct_print/printer')) {
                $block->addItem('dhlparcel_shipping_create_direct_print', [
                    'label' => 'DHLParcel - Create labels & Direct Print',
                    'url'   => Mage::helper('adminhtml')->getUrl('adminhtml/dhlparcel_massActions/createlabelsdirectprint')
                ]);
            }

            // Create mailbox labels
            $block->addItem('dhlparcel_shipping_mailbox_create', [
                'label' => 'DHLParcel - Mailbox - Create labels',
                'url'   => Mage::helper('adminhtml')->getUrl('adminhtml/dhlparcel_massActions/createlabels', [
                    'mailbox' => 1
                ])
            ]);

            // Create mailbox labels & print
            if (Mage::getStoreConfig('carriers/dhlparcel_direct_print/enable_download') == true) {
                $block->addItem('dhlparcel_shipping_mailbox_create_print', [
                    'label' => 'DHLParcel - Mailbox - Create labels & Print',
                    'url'   => Mage::helper('adminhtml')->getUrl('adminhtml/dhlparcel_massActions/createlabelsprint', [
                        'mailbox' => 1
                    ])
                ]);
            }

            if (Mage::getStoreConfig('carriers/dhlparcel_direct_print/printer')) {
                // Create mailbox labels & direct print
                $block->addItem('dhlparcel_shipping_mailbox_create_direct_print', [
                    'label' => 'DHLParcel - Mailbox - Create labels & Direct Print',
                    'url'   => Mage::helper('adminhtml')->getUrl('adminhtml/dhlparcel_massActions/createlabelsdirectprint', [
                        'mailbox' => 1
                    ])
                ]);
            }
        }
        if ($block instanceof Mage_Adminhtml_Block_Widget_Grid_Massaction
            && (
                $block->getRequest()->getControllerName() == 'sales_order'
                || $block->getRequest()->getControllerName() == 'sales_shipment'
            )) {
            if (Mage::getStoreConfig('carriers/dhlparcel_direct_print/printer')) {
                // Direct print
                $block->addItem('dhlparcel_shipping_direct_print', [
                    'label' => 'DHLParcel - Direct Print shipping labels',
                    'url'   => Mage::helper('adminhtml')->getUrl('adminhtml/dhlparcel_massActions/directprint')
                ]);
            }
        }

        return $observer;
    }
}
