<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMultipleGovernoratesToUsers extends AbstractMigration
{
    public function change(): void
    {
        // Add governorate_ids column (JSON array for multiple governorates)
        $this->table('users')
            ->addColumn('governorate_ids', 'text', [
                'null' => true,
                'after' => 'governorate_id',
                'comment' => 'JSON array of governorate IDs for multi-governorate support'
            ])
            ->update();

        // Add excluded_city_ids column (JSON array for excluded delegations)
        $this->table('users')
            ->addColumn('excluded_city_ids', 'text', [
                'null' => true,
                'after' => 'governorate_ids',
                'comment' => 'JSON array of excluded city IDs'
            ])
            ->update();
    }
}