<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateStockMovementsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('stock_movements', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('user_id', 'biginteger', ['signed' => false])
            ->addColumn('product_id', 'biginteger', ['signed' => false])
            ->addColumn('visit_id', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('movement_type', 'enum', ['values' => ['add', 'deduct'], 'default' => 'deduct'])
            ->addColumn('quantity', 'integer')
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['user_id'])
            ->addIndex(['product_id'])
            ->addIndex(['visit_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('product_id', 'products', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('visit_id', 'visits', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();
    }
}
