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

class DHLParcel_Shipping_Model_Resource_MatrixrateAbstract extends Mage_Shipping_Model_Resource_Carrier_Tablerate
{
    /**  File Type DOOR/PS */
    protected $_fileType;

    /**
     * Define main table and id field name.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('dhlparcel_shipping/matrixrate', 'id');
    }

    public function getRate(Mage_Shipping_Model_Rate_Request $request)
    {
        $adapter = $this->_getReadAdapter();

        if ($request->getBaseSubtotalInclTax() == 0) {
            $totals = Mage::getSingleton('checkout/cart')->getQuote()->getTotals();
            $subtotal = $totals["subtotal"]->getValue();
        } else {
            $subtotal = $request->getBaseSubtotalInclTax();
        }

        $select = $adapter->select()
            ->from($this->getMainTable())
            ->where('(website_id = :website_id) OR (website_id = ?)', Mage_Core_Model_App::ADMIN_STORE_ID)
            ->order(
                array(
                    'website_id DESC',
                    'country_id DESC',
                    'postalcode DESC',
                    'weight DESC',
                    'subtotal DESC',
                    'qty DESC',
                )
            )
            ->limit(1);

        $select->where(
            '(' .
            // Same country and same postalcode
            "country_id LIKE :country_id AND postalcode = :postcode" . ') OR (' .

            // Just same country
            "country_id LIKE :country_id AND postalcode = ''" . ') OR (' .
            "country_id LIKE :country_id AND postalcode = '*'" . ') OR (' .

            // Fallback for all countries and postalcodes
            "country_id = '0' AND postalcode = '*'" .
            ')'
        );

        $select->where('weight <= :weight');
        $select->where('subtotal <= :subtotal');
        $select->where('qty <= :qty');

        $result = $adapter->fetchRow($select, array (
            ':website_id'  => (int) $request->getWebsiteId(),
            ':country_id'  => "%{$request->getDestCountryId()}%",
            ':postcode'    => str_replace(' ', '', $request->getDestPostcode()),
            ':weight'      => $request->getPackageWeight(),
            ':subtotal'    => $subtotal,
            ':qty'         => $request->getPackageQty(),
        ));

        if (!$result) {
            return false;
        }

        // Set costs to 0
        $result['cost'] = 0;

        return $result;
    }

    public function uploadAndImport(Varien_Object $object)
    {
        if ($this->_fileType == 'DOOR') {
            $filePath = 'DOOR_dhlparcel';
        } elseif ($this->_fileType == 'PS') {
            $filePath = 'PS_dhlparcel';
        } else {
            return false;
        }

        if (empty($_FILES['groups']['tmp_name'][$filePath]['fields']['matrix_import']['value'])) {
            return $this;
        }

        $website = Mage::app()->getWebsite($object->getScopeId());

        $this->_importWebsiteId     = (int)$website->getId();
        $this->_importUniqueHash    = array();
        $this->_importErrors        = array();
        $this->_importedRows        = 0;


        $info = pathinfo($_FILES['groups']['tmp_name'][$filePath]['fields']['matrix_import']['value']);

        $io   = new Varien_Io_File();
        $io->open(array('path' => $info['dirname']));
        $io->streamOpen($info['basename'], 'r');

        // check and skip headers
        $delimiter = ",";
        $headers = $io->streamReadCsv($delimiter);
        if ($headers === false || count($headers) < 6) {
            $io->streamClose();

            // Rebuild new stream
            $io   = new Varien_Io_File();
            $io->open(array('path' => $info['dirname']));
            $io->streamOpen($info['basename'], 'r');

            $delimiter = ";";
            $headers = $io->streamReadCsv($delimiter);
            if ($headers === false || count($headers) < 6) {
                $io->streamClose();
                throw new Exception(
                    __('Invalid Matrix Rates file format')
                );
            }
        }

        $adapter = $this->_getWriteAdapter();
        $adapter->beginTransaction();

        try {
            $rowNumber  = 1;
            $importData = array();

            $this->_loadDirectoryCountries();
            $this->_loadDirectoryRegions();

            // delete old data by website ID
            $condition = array(
                'website_id = ?'     => $this->_importWebsiteId,
            );

            $adapter->delete($this->getMainTable(), $condition);

            while (false !== ($csvLine = $io->streamReadCsv($delimiter))) {
                $rowNumber ++;

                if (empty($csvLine)) {
                    continue;
                }

                $row = $this->convertRow($csvLine, $rowNumber);
                if ($row !== false) {
                    $importData[] = $row;
                }

                if (count($importData) == 5000) {
                    $this->_saveImportData($importData);
                    $importData = array();
                }
            }
            $this->_saveImportData($importData);
            $io->streamClose();
        } catch (Exception $e) {
            $adapter->rollback();
            $io->streamClose();

            throw new Exception(
                __('An error occurred while importing the rates')
            );
        }

        $adapter->commit();

        if ($this->_importErrors) {
            throw new Exception(__(
                'File has not been imported. See the following list of errors: %s',
                implode(" \n", $this->_importErrors)
            ));
        }

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     * @throws Exception
     */
    public function import(array $data)
    {
        $adapter = $this->_getWriteAdapter();
        $adapter->beginTransaction();

        try {
            $rowNumber  = 1;
            $importData = array();

            $this->_loadDirectoryCountries();
            $this->_loadDirectoryRegions();

            // delete old data by website ID
            $condition = array(
                'website_id = ?'     => $this->_importWebsiteId,
            );
            $adapter->delete($this->getMainTable(), $condition);

            foreach ($data as $key => $line) {
                $rowNumber ++;

                if (empty($line)) {
                    continue;
                }

                $row = $this->convertRow($line, $rowNumber);
                if ($row !== false) {
                    $this->_saveImportData($importData);
                }
            }
        } catch (Exception $e) {
            $adapter->rollback();

            throw new Exception(
                __('An error occurred while importing the matrix rates')
            );
        }

        $adapter->commit();

        if ($this->_importErrors) {
            $error = __(
                'Data has not been imported. See the following list of errors: %s',
                implode(" \n", $this->_importErrors)
            );
            throw new Exception($error);
        }

        return $this;
    }

    /**
     * @param $row
     * @param int $rowNumber
     * @return array|bool
     */
    protected function convertRow($row, $rowNumber = 0)
    {
        // validate row
        if (count($row) < 6) {
            $this->_importErrors[] = __(
                'Invalid row #%s',
                $rowNumber
            );
            return false;
        }

        // strip whitespace from the beginning and end of each row
        foreach ($row as $k => $v) {
            $row[$k] = trim($v);
        }

        // Countries
        $countries = explode(',', $row[0]);
        $countryIds = array();
        foreach ($countries as $country) {
            $country = trim($country);
            if (isset($this->_importIso2Countries[$country])) {
                $countryIds[] = $this->_importIso2Countries[$country];
            } elseif ($country == '*' || $country == '') {
                $countryIds[] = '0';
            }
        }
        $countryId = implode(',', $countryIds);
        $zipCode = ($row[1] == '' ? '*' : $row[1]);
        $weight = $row[2];
        $subtotal = $row[3];
        $qty = $row[4];
        $price = $row[5];

        // protect from duplicate
        $hash = sprintf("%s-%s-%s-%s-%s", $countryId, $zipCode, $weight, $subtotal, $qty);
        if (isset($this->_importUniqueHash[$hash])) {
            $this->_importErrors[] = __(
                'Duplicate row #%s (country "%s", zip "%s", weight "%s", subtotal "%s" and quantity ' .
                '"%s").',
                $rowNumber,
                $row[0],
                $zipCode,
                $row[2],
                $row[3],
                $row[4]
            );
            return false;
        }
        $this->_importUniqueHash[$hash] = true;

        return array(
            $this->_importWebsiteId, // website_id
            $countryId,              // country_id
            $zipCode,                // postalcode
            $weight,                 // weight,
            $subtotal,               // subtotal
            $qty,                    // quantity
            $price                   // price
        );
    }

    /**
     * @param array $data
     * @return $this|Mage_Shipping_Model_Resource_Carrier_Tablerate
     */
    protected function _saveImportData(array $data)
    {
        if (!empty($data)) {
            $columns = array(
                'website_id',
                'country_id',
                'postalcode',
                'weight',
                'subtotal',
                'qty',
                'price',
            );

            $this->_getWriteAdapter()->insertArray($this->getMainTable(), $columns, $data);

            $this->_importedRows += count($data);
        }

        return $this;
    }
}
