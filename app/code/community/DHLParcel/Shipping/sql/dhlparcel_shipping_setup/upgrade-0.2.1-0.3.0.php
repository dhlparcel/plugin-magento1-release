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

$installer = $this;
$installer->startSetup();

$installer->addAttribute("order", "dhlparcel_shipping_date", array(
    'type'          => Varien_Db_Ddl_Table::TYPE_DATE,
    'frontend_input' => 'text',
    'is_user_defined' => false,
    'label'         => 'DHL Shipping Date',
    'visible'       => false,
    'required'      => false,
    'user_defined'  => false,
    'searchable'    => false,
    'filterable'    => false,
    'comparable'    => false,
    'nullable'      => true
));


$installer->addAttribute("quote", "dhlparcel_shipping_date", array(
    'type'          => Varien_Db_Ddl_Table::TYPE_DATE,
    'frontend_input' => 'text',
    'is_user_defined' => false,
    'label'         => 'DHL Shipping Date',
    'visible'       => false,
    'required'      => false,
    'user_defined'  => false,
    'searchable'    => false,
    'filterable'    => false,
    'comparable'    => false,
    'nullable'      => true
));



$installer->endSetup();
