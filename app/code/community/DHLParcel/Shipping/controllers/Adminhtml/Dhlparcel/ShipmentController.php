<?php

class DHLParcel_Shipping_Adminhtml_Dhlparcel_ShipmentController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/sales/order/actions/ship');
    }

    public function directPrintAction()
    {
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        /** @var Mage_Sales_Model_Order_Shipment $shipment */
        $shipment = Mage::getModel('sales/order_shipment')->load($shipmentId);
        $labelIds = [];

        /** @var Mage_Sales_Model_Order_Shipment_Track $track */
        foreach ($shipment->getAllTracks() as $track) {
            /** @var DHLParcel_Shipping_Model_Piece $piece */
            $piece = Mage::getModel('dhlparcel_shipping/piece')->load($track->getNumber(), 'tracker_code');
            $labelId = $piece->getData('label_id');
            if ($labelId) {
                $labelIds[] = $labelId;
            }
        }

        /** @var DHLParcel_Shipping_Model_Service_Printing $printingService */
        $printingService = Mage::getSingleton('dhlparcel_shipping/service_printing');

        try {
            $printingService->sendPrintJob($labelIds);
            $this->_getSession()->addSuccess($this->__('Successfully sent %d label(s) to the DHL Direct Label Printing', count($labelIds)));
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($e->getMessage());
        }

        $this->_redirect('adminhtml/sales_order_shipment/view', ['shipment_id' => $shipmentId]);
    }

    /**
     * Show a json formatted request
     */
    public function apiRequestAction()
    {
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        /** @var Mage_Sales_Model_Order_Shipment $shipment */
        $shipment = Mage::getModel('sales/order_shipment')->load($shipmentId);

        if (empty($labelRequest = $shipment->getData('dhlparcel_shipping_request'))) {
            // Return 404
            return $this->_forward('noroute');
        } else {
            $this->getResponse()->setBody($labelRequest);
        }
    }
}
