<?php
$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();
$tableName = $installer->getTable('dhlparcel_shipping/matrixrate');

if (!$conn->isTableExists($tableName)) {
    $table = $installer->getConnection()
        ->newTable($tableName);

    $table->addColumn(
        'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
        ), 'ID'
    )
    ->addColumn(
        'website_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable' => false,
            'default'  => '0',
        ), 'Website Id'
    )
    ->addColumn(
        'country_id', Varien_Db_Ddl_Table::TYPE_TEXT, 12, array(
            'nullable' => false,
            'default'  => '0',
        ), 'Destination country ISO/2 or ISO/3 code'
    )
    ->addColumn(
        'postalcode', Varien_Db_Ddl_Table::TYPE_TEXT, 10, array(
            'nullable' => false,
            'default'  => '*',
        ), 'Destination Post Code (Zip)'
    )
    ->addColumn(
        'weight', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default'  => '0.0000',
        ), 'Minimum Order Weight'
    )
    ->addColumn(
        'subtotal', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default'  => '0.0000',
        ), 'Minimum Order Amount'
    )
    ->addColumn(
        'qty', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
            'nullable' => false,
            'default'  => '0',
        ), 'Minimum Quantity'
    )
    ->addColumn(
        'price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default'  => '0.0000',
        ), 'Price'
    )
    ->addIndex(
        $installer->getIdxName(
            'dhlparcel_shipping/matrixrate',
            array(
                'website_id',
                'country_id',
                'postalcode',
                'weight',
                'subtotal',
                'qty',
            ),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        array(
            'website_id',
            'country_id',
            'postalcode',
            'weight',
            'subtotal',
            'qty',
        ),
        array(
            'type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        )
    )
    ->setComment('DHLParcel Shipping Matrixrates');

    $installer->getConnection()->createTable($table);
}

$installer->endSetup();