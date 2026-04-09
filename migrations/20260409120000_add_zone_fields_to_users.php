<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddZoneFieldsToUsers extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users');
        $table
            ->addColumn('governorate_id', 'biginteger', ['null' => true, 'signed' => false, 'after' => 'role'])
            ->addColumn('excluded_city_ids', 'text', ['null' => true, 'after' => 'governorate_id'])
            ->addForeignKey('governorate_id', 'governorates', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->update();
    }
}
