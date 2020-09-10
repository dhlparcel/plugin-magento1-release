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
 *  @copyright 2018 DHLParcel
 *  @link      https://www.dhlparcel.nl/
 */

$this->startSetup();

$this->getConnection()->addColumn(
    $this->getTable('sales/order_grid'),
    'dhlparcel_shipping_date',
    'DATE DEFAULT NULL'
);

$this->getConnection()->addKey(
    $this->getTable('sales/order_grid'),
    'dhlparcel_shipping_date',
    'dhlparcel_shipping_date'
);

$this->endSetup();