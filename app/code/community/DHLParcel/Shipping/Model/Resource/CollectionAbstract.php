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

class DHLParcel_Shipping_Model_Resource_CollectionAbstract
    extends Mage_Shipping_Model_Resource_Carrier_Tablerate_Collection
{
    /**
     * Initialize select, add country iso3 code and region name, and define default sorting.
     */
    public function _initSelect()
    {
        Mage_Core_Model_Resource_Db_Collection_Abstract::_initSelect();

        $this->addOrder('country_id', self::SORT_ORDER_ASC);
        $this->addOrder('postalcode', self::SORT_ORDER_ASC);
        $this->addOrder('weight', self::SORT_ORDER_ASC);
        $this->addOrder('subtotal', self::SORT_ORDER_ASC);
        $this->addOrder('qty', self::SORT_ORDER_ASC);
    }
}
