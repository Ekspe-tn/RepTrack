<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateVisitSamplesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('visit_samples', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('visit_id', 'biginteger', ['signed' => false])
            ->addColumn('product_id', 'biginteger', ['signed' => false])
            ->addColumn('quantity', 'integer')
            ->addIndex(['visit_id'])
            ->addIndex(['product_id'])
            ->addForeignKey('visit_id', 'visits', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('product_id', 'products', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->create();
    }
}
