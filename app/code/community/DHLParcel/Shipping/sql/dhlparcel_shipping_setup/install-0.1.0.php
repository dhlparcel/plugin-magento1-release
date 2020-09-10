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

$installer = $this;
$installer->startSetup();

$installer->addAttribute("order", "dhlparcel_servicepoint", array(
    'type'          => 'varchar',
    'backend_type'  => 'varchar',
    'frontend_input' => 'text',
    'is_user_defined' => false,
    'label'         => 'DHL ServicePoint ID',
    'visible'       => false,
    'required'      => false,
    'user_defined'  => false,
    'searchable'    => false,
    'filterable'    => false,
    'comparable'    => false,
    'default'       => 0
));

$installer->addAttribute("quote", "dhlparcel_servicepoint", array(
    'type'          => 'varchar',
    'backend_type'  => 'varchar',
    'frontend_input' => 'text',
    'is_user_defined' => false,
    'label'         => 'DHL ServicePoint ID',
    'visible'       => false,
    'required'      => false,
    'user_defined'  => false,
    'searchable'    => false,
    'filterable'    => false,
    'comparable'    => false,
    'default'       => 0
));

$installer->endSetup();
