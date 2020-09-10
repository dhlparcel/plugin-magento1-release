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
 *  @category  Dhlparcel
 *  @author    Shin Ho <plugins@dhl.com>
 *  @author    Rudger Gravenstein <plugins@dhl.com>
 *  @author    Ron Oerlemans <plugins@dhl.com>
 *  @copyright ${YEAR} DHLParcel
 *  @link      https://www.dhlparcel.nl/
 */

class DHLParcel_Shipping_Adminhtml_Dhlparcel_RatesController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/system/config/carriers');
    }

    public function exportAction()
    {
        $fileName   = 'dhlparcel_matrixrates.csv';

        $f = fopen('php://memory', 'w');

        // Print Headers
        fputcsv($f, array (
            __('Country'),
            __('Postal code'),
            __('Weight (and higher)'),
            __('Amount (and higher)'),
            __('Quantity (and higher)'),
            __('price')
        ), ',');

        // Print Rows
        $dhlTableRates = Mage::getModel('dhlparcel_shipping/matrixrate')->getCollection();
        $dhlTableRates->getSelect()->order('id', 'DESC');

        foreach ($dhlTableRates as $tableRate) {
            $countryId = $tableRate->getData('country_id');
            if ($countryId == '0') {
                $countryId = '*';
            }

            fputcsv($f, array (
                $countryId,
                $tableRate->getData('postalcode'),
                $tableRate->getData('weight'),
                $tableRate->getData('subtotal'),
                $tableRate->getData('qty'),
                $tableRate->getData('price')
            ), ',');
        }
        fseek($f, 0);

        $content = '';
        while (!feof($f)) {
            $content .= fread($f, 8192);
        }
        fclose($f);

        return $this->_prepareDownloadResponse($fileName, $content, 'application/csv');
    }

    public function exportpsAction()
    {
        $fileName   = 'dhlparcel_matrixrates_ps.csv';

        $f = fopen('php://memory', 'w');

        // Print Headers
        fputcsv($f, array (
            __('Country'),
            __('Postal code'),
            __('Weight (and higher)'),
            __('Amount (and higher)'),
            __('Quantity (and higher)'),
            __('price')
        ), ',');

        // Print Rows
        $dhlTableRates = Mage::getModel('dhlparcel_shipping/matrixrateps')->getCollection();
        $dhlTableRates->getSelect()->order('id', 'DESC');

        foreach ($dhlTableRates as $tableRate) {
            $countryId = $tableRate->getData('country_id');
            if ($countryId == '0') {
                $countryId = '*';
            }

            fputcsv($f, array (
                $countryId,
                $tableRate->getData('postalcode'),
                $tableRate->getData('weight'),
                $tableRate->getData('subtotal'),
                $tableRate->getData('qty'),
                $tableRate->getData('price')
            ), ',');
        }
        fseek($f, 0);

        $content = '';
        while (!feof($f)) {
            $content .= fread($f, 8192);
        }
        fclose($f);

        return $this->_prepareDownloadResponse($fileName, $content, 'application/csv');
    }
}
