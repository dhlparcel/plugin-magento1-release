<?php

class DHLParcel_Shipping_Model_System_Config_Source_Printer
{
    /**
     * Returns an option array for rate type options
     *
     * @return array
     */
    public function toOptionArray()
    {
        /** @var DHLParcel_Shipping_Model_Service_Printing $printService */
        $printService = Mage::getSingleton('dhlparcel_shipping/service_printing');
        $printers = [];
        foreach ($printService->getPrinters() as $printer) {
            $printers[$printer->id] = $printer->name . ' - ' . $printer->timeRegistered;
        }

        if (count($printers) === 0) {
            $helper = Mage::helper('dhlparcel_shipping');
            $printers[] = $helper->__('No printers found');
        }
        return $printers;
    }
}
