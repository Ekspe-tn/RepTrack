<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateStockTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('stock', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('user_id', 'biginteger', ['signed' => false])
            ->addColumn('product_id', 'biginteger', ['signed' => false])
            ->addColumn('quantity', 'integer', ['default' => 0])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['user_id'])
            ->addIndex(['product_id'])
            ->addIndex(['user_id', 'product_id'], ['unique' => true, 'name' => 'uniq_stock_user_product'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('product_id', 'products', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }
}
