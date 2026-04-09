<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateGovernoratesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('governorates', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('name_fr', 'string', ['limit' => 100])
            ->addColumn('name_ar', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('latitude', 'decimal', ['precision' => 10, 'scale' => 7, 'null' => true])
            ->addColumn('longitude', 'decimal', ['precision' => 10, 'scale' => 7, 'null' => true])
            ->create();
    }
}
