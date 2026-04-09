<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCitiesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('cities', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('governorate_id', 'biginteger', ['signed' => false])
            ->addColumn('name_fr', 'string', ['limit' => 100])
            ->addColumn('name_ar', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('latitude', 'decimal', ['precision' => 10, 'scale' => 7, 'null' => true])
            ->addColumn('longitude', 'decimal', ['precision' => 10, 'scale' => 7, 'null' => true])
            ->addIndex(['governorate_id'])
            ->addForeignKey('governorate_id', 'governorates', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->create();
    }
}
