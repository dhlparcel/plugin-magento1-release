<?php

class DHLParcel_Shipping_Model_Service_Label
{
    /** @var DHLParcel_Shipping_Model_Api_Connector */
    protected $connector;

    /**
     * DHLParcel_Shipping_Model_Service_Label constructor.
     */
    public function __construct()
    {
        $this->connector = Mage::getSingleton('dhlparcel_shipping/api_connector');
    }

    /**
     * @param $labelId
     * @return bool|string
     * @throws Exception
     */
    public function getLabelPdf($labelId)
    {
        $rawResponse = $this->connector->get('labels/' . $labelId);

        /** @var DHLParcel_Shipping_Model_Data_Api_Response_Label $labelResponse */
        $labelResponse = Mage::getModel('dhlparcel_shipping/data_api_response_label', $rawResponse);

        if (!$label = base64_decode($labelResponse->pdf)) {
            throw new Exception(__('Failed to decode pdf'));
        }

        return $label;
    }

    /**
     * @param $labelIds
     * @return bool|Zend_Pdf
     * @throws Exception
     */
    public function getLabelPdfs($labelIds)
    {
        if (count($labelIds) === 0) {
            throw new Exception(__('No label ids where given'));
        }
        $pdf = false;
        foreach ($labelIds as $labelId) {
            try {
                $labelContent = $this->getLabelPdf($labelId);
            } catch (Exception $e) {
                Mage::logException($e);
                throw new Exception(__('Failed to get label id: %s', $labelId));
            }
            $labelPdf = Zend_Pdf::parse($labelContent);

            if (!$pdf instanceof Zend_Pdf) {
                $pdf = $labelPdf;
            } else {
                foreach ($labelPdf->pages as $page) {
                    $pdf->pages[] = clone $page;
                }
            }
        }

        return $pdf;
    }
}
