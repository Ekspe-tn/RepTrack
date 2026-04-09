<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMultipleGovernoratesToUsers extends AbstractMigration
{
    public function change(): void
    {
        $adapter = $this->getAdapter();
        $tableName = 'users';
        
        // Check if columns exist by querying information_schema
        $columns = $adapter->fetchAll("SHOW COLUMNS FROM `$tableName`");
        $columnNames = array_column($columns, 'Field');
        
        // Add governorate_ids column if it doesn't exist
        if (!in_array('governorate_ids', $columnNames, true)) {
            $this->table($tableName)
                ->addColumn('governorate_ids', 'text', [
                    'null' => true,
                    'after' => 'governorate_id',
                    'comment' => 'JSON array of governorate IDs for multi-governorate support'
                ])
                ->update();
        }
        
        // Add excluded_city_ids column if it doesn't exist
        if (!in_array('excluded_city_ids', $columnNames, true)) {
            $this->table($tableName)
                ->addColumn('excluded_city_ids', 'text', [
                    'null' => true,
                    'after' => 'governorate_ids',
                    'comment' => 'JSON array of excluded city IDs'
                ])
                ->update();
        }
    }
}
