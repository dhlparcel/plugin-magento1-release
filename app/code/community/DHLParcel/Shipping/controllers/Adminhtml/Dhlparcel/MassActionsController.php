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
 * @copyright 2019 DHLParcel
 * @link      https://www.dhlparcel.nl/
 */

class DHLParcel_Shipping_Adminhtml_Dhlparcel_MassActionsController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/sales/order/actions/ship');
    }

    public function createlabelsAction()
    {
        /** @var  $labelHelper DHLParcel_Shipping_Helper_Labels */
        $labelHelper = Mage::helper('dhlparcel_shipping/labels');
        $orderIds = $this->getRequest()->getPost('order_ids');

        $mailboxDelivery = false;
        if ($this->getRequest()->getParam('mailbox') == 1) {
            $mailboxDelivery = true;
        }

        $hasErrors = false;
        $errors = [];
        if (!empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                // Create shipment
                /** @var Mage_Sales_Model_Order $order */
                $order = Mage::getModel('sales/order')->load($orderId);

                try {
                    if (!$order->canShip()) {
                        throw new Exception($this->__("Can't create a shipment for order #%s", $order->getIncrementId()));
                    }

                    $shipmentOptions = false;
                    if ($mailboxDelivery) {
                        $shippingMethod = $order->getData('shipping_method');
                        if ($shippingMethod == 'dhlparcel_DOOR') {
                            $shipmentOptions = ['BP'];
                        }
                    }

                    // Create Shipment
                    $addReturnLabel = boolval(Mage::getStoreConfig('carriers/dhlparcel_returnlabels/default'));
                    $shipmentApi = new Mage_Sales_Model_Order_Shipment_Api();
                    $shipmentId = $shipmentApi->create($order->getIncrementId());
                    /** @var Mage_Sales_Model_Order_Shipment $shipment */
                    $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentId);

                    // Create label
                    $labelContent = $labelHelper->createDhlLabel($shipment, $shipmentOptions, [], null, $addReturnLabel, true);
                    if ($labelContent === false) {
                        throw new Exception($labelHelper->getLastError());
                    }
                    $shipment->setData('shipping_label', $labelContent);
                    $shipment->save();
                } catch (Exception $e) {
                    $errors[] = $this->__('Error for order %s: %s', $order->getIncrementId(), $e->getMessage());
                    $hasErrors = true;
                }
            }
        }

        if ($hasErrors === false) {
            $this->_getSession()->addSuccess($this->__('%d shipment(s) are created with success', count($orderIds)));
        } else {
            // onError auto clears all messages, so put this messages in session afterwards
            foreach ($errors as $error) {
                $this->_getSession()->addError($error);
            }
        }

        $this->_redirect('adminhtml/sales_order/index');
    }

    public function createlabelsprintAction()
    {
        /** @var  $labelHelper DHLParcel_Shipping_Helper_Labels */
        $labelHelper = Mage::helper('dhlparcel_shipping/labels');

        $orderIds = $this->getRequest()->getPost('order_ids');

        $mailboxDelivery = false;
        if ($this->getRequest()->getParam('mailbox') == 1) {
            $mailboxDelivery = true;
        }

        $hasErrors = false;
        $errors = [];
        $pdf = null;
        if (!empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                try {
                    // Create shipment
                    /** @var Mage_Sales_Model_Order $order */
                    $order = Mage::getModel('sales/order')->load($orderId);
                    if (!$order->canShip()) {
                        throw new Exception($this->__("Can't create a shipment for orderId #%s", $order->getId()));
                        continue;
                    }

                    $shipmentOptions = false;
                    if ($mailboxDelivery) {
                        $shippingMethod = $order->getData('shipping_method');
                        if ($shippingMethod == 'dhlparcel_DOOR') {
                            $shipmentOptions = ['BP'];
                        }
                    }

                    // Create Shipment
                    $shipmentApi = new Mage_Sales_Model_Order_Shipment_Api();
                    $shipmentId = $shipmentApi->create($order->getIncrementId());
                    /** @var Mage_Sales_Model_Order_Shipment $shipment */
                    $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentId);

                    // Create label
                    $addReturnLabel = boolval(Mage::getStoreConfig('carriers/dhlparcel_returnlabels/default'));
                    $labelContent = $labelHelper->createDhlLabel($shipment, $shipmentOptions, [], null, $addReturnLabel, true);
                    if ($labelContent === false) {
                        throw new Exception($labelHelper->getLastError());
                    }
                    $shipment->setData('shipping_label', $labelContent);
                    $shipment->save();

                    // Load labels
                    $labelContent = $shipment->getData('shipping_label');
                    if (empty($labelContent)) {
                        throw new Exception($this->__('No label available for shipment #%s', $shipment->getId()));
                    }

                    if (!$pdf instanceof Zend_Pdf) {
                        $pdf = new Zend_Pdf($labelContent);
                    } else {
                        $newPdf = new Zend_Pdf($labelContent);
                        foreach ($newPdf->pages as $page) {
                            $pdf->pages[] = clone $page;
                        }
                        unset($newPdf);
                    }
                } catch (Exception $e) {
                    $errors[] = $this->__('Error for order %s: %s', $order->getIncrementId(), $e->getMessage());
                    $hasErrors = true;
                }
            }

            if (is_object($pdf)) {
                try {
                    $renderedPdf = $pdf->render();
                    /** @var Mage_Core_Model_Date $date */
                    $date = Mage::getSingleton('core/date');

                    return $this->_prepareDownloadResponse('Labels ' . $date->date('Y-m-d_H-i-s') . '.pdf', $renderedPdf, 'application/pdf');
                } catch (Exception $e) {
                    Mage::logException($e);
                    $errors[] = $this->__('Something went wrong when rendering the PDF');
                    $hasErrors = true;
                }
            }
        }

        /** @var Mage_Admin_Model_Session $session */
        $session = Mage::getSingleton('adminhtml/session');
        // Clear messages
        $session->getMessages(true);

        if ($hasErrors === false) {
            $session->addSuccess($this->__('%d shipment(s) are created with success', count($orderIds)));
        } else {
            // onError auto clears all messages, so put this messages in session afterwards
            foreach ($errors as $error) {
                $session->addError($error);
            }
        }

        return $this->_redirect('adminhtml/sales_order/index');
    }

    public function createLabelsDirectPrintAction()
    {
        /** @var  $labelHelper DHLParcel_Shipping_Helper_Labels */
        $labelHelper = Mage::helper('dhlparcel_shipping/labels');

        $orderIds = $this->getRequest()->getPost('order_ids');

        $mailboxDelivery = false;
        if ($this->getRequest()->getParam('mailbox') == 1) {
            $mailboxDelivery = true;
        }

        $hasErrors = false;
        $errors = [];
        if (!empty($orderIds)) {
            $trackingNumbers = [];
            foreach ($orderIds as $orderId) {
                try {
                    // Create shipment
                    /** @var Mage_Sales_Model_Order $order */
                    $order = Mage::getModel('sales/order')->load($orderId);
                    if (!$order->canShip()) {
                        throw new Exception($this->__("Can't create a shipment for orderId #%s", $order->getId()));
                        continue;
                    }

                    $shipmentOptions = false;
                    if ($mailboxDelivery) {
                        $shippingMethod = $order->getData('shipping_method');
                        if ($shippingMethod == 'dhlparcel_DOOR') {
                            $shipmentOptions = ['BP'];
                        }
                    }

                    // Create Shipment
                    $shipmentApi = new Mage_Sales_Model_Order_Shipment_Api();
                    $shipmentId = $shipmentApi->create($order->getIncrementId());

                    /** @var Mage_Sales_Model_Order_Shipment $shipment */
                    $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentId);

                    // Create label
                    $addReturnLabel = boolval(Mage::getStoreConfig('carriers/dhlparcel_returnlabels/default'));
                    $labelContent = $labelHelper->createDhlLabel($shipment, $shipmentOptions, [], null, $addReturnLabel, true);
                    if ($labelContent === false) {
                        throw new Exception($labelHelper->getLastError());
                    }
                    $shipment->setData('shipping_label', $labelContent);
                    $shipment->save();

                    /** @var Mage_Sales_Model_Order_Shipment_Track $track */
                    foreach ($shipment->getTracksCollection() as $track) {
                        $trackingNumbers[] = $track->getNumber();
                    }
                } catch (Exception $e) {
                    $errors[] = $this->__('Error for order %s: %s', $order->getIncrementId(), $e->getMessage());
                    $hasErrors = true;
                }
            }

            if (empty($trackingNumbers)) {
                $errors[] = $this->__("No tracking numbers found for any of the orders selected");
                $hasErrors = true;
            } else {
                $labelIds = [];

                foreach ($trackingNumbers as $trackingNumber) {
                    /** @var DHLParcel_Shipping_Model_Piece $piece */
                    $piece = Mage::getModel('dhlparcel_shipping/piece')->load($trackingNumber, 'tracker_code');
                    $labelId = $piece->getData('label_id');
                    if ($labelId) {
                        $labelIds[] = $labelId;
                    }
                }

                /** @var DHLParcel_Shipping_Model_Service_Printing $printingService */
                $printingService = Mage::getSingleton('dhlparcel_shipping/service_printing');

                try {
                    $printingService->sendPrintJob($labelIds);
                } catch (Exception $e) {
                    Mage::logException($e);
                    $errors[] = $e->getMessage();
                }
            }
        }

        /** @var Mage_Admin_Model_Session $session */
        $session = Mage::getSingleton('adminhtml/session');
        // Clear messages
        $session->getMessages(true);

        if ($hasErrors === false) {
            $session->addSuccess($this->__('%d shipment(s) are created with success', count($orderIds)));
            $this->_getSession()->addSuccess($this->__('Successfully sent %d label(s) to the DHL Direct Label Printing', count($labelIds)));
        } else {
            // onError auto clears all messages, so put this messages in session afterwards
            foreach ($errors as $error) {
                $session->addError($error);
            }
        }

        return $this->_redirect('adminhtml/sales_order/index');
    }

    public function directPrintAction()
    {
        $trackingNumbers = [];

        if ($this->getRequest()->getParam('massaction_prepare_key') === 'order_ids') {
            // handles mass actions from sales_order grid
            $orderIds = $this->getRequest()->getPost('order_ids');
            $redirectPath = 'adminhtml/sales_order/index';
            if (empty($orderIds)) {
                $this->_getSession()->addError($this->__('No orders selected'));
                return $this->_redirect($redirectPath);
            }

            foreach ($orderIds as $orderId) {
                /** @var Mage_Sales_Model_Order $order */
                $order = Mage::getModel('sales/order')->load($orderId);
                /** @var Mage_Sales_Model_Order_Shipment_Track $track */
                foreach ($order->getTracksCollection() as $track) {
                    $trackingNumbers[] = $track->getNumber();
                }
            }
        } elseif ($this->getRequest()->getParam('massaction_prepare_key') === 'shipment_ids') {
            // handles mass actions from sales_shipment grid
            $shipmentIds = $this->getRequest()->getPost('shipment_ids');
            $redirectPath = 'adminhtml/sales_shipment/index';
            if (empty($shipmentIds)) {
                $this->_getSession()->addError($this->__('No Shipments selected'));
                return $this->_redirect($redirectPath);
            }

            foreach ($shipmentIds as $shipmentId) {
                /** @var Mage_Sales_Model_Order_Shipment $shipment */
                $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentId);

                /** @var Mage_Sales_Model_Order_Shipment_Track $track */
                foreach ($shipment->getTracksCollection() as $track) {
                    $trackingNumbers[] = $track->getNumber();
                }
            }
        } else {
            // redirects due to failing to be called from a valid sales grid
            $this->_getSession()->addError($this->__('invalid input for mass action'));
            return $this->_redirect('adminhtml/sales_order/index');
        }

        if (empty($trackingNumbers)) {
            $this->_getSession()->addError($this->__("No tracking numbers found for any of the orders/shipments selected"));
            return $this->_redirect($redirectPath);
        }
        $labelIds = [];

        foreach ($trackingNumbers as $trackingNumber) {
            /** @var DHLParcel_Shipping_Model_Piece $piece */
            $piece = Mage::getModel('dhlparcel_shipping/piece')->load($trackingNumber, 'tracker_code');
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

        return $this->_redirect($redirectPath);
    }
}
