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

class DHLParcel_Shipping_Model_Resource_Matrixrateps_Collection
    extends DHLParcel_Shipping_Model_Resource_CollectionAbstract
{
    /**
     * Define resource model and item.
     */
    protected function _construct()
    {
        $this->_init('dhlparcel_shipping/matrixrateps');
        /** @noinspection PhpDeprecationInspection */
        $this->_shipTable       = $this->getMainTable();
        $this->_countryTable    = $this->getTable('directory/country');
    }
}
