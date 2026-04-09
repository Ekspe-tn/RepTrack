<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateStockGlobalTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('stock_global', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('product_id', 'biginteger', ['signed' => false])
            ->addColumn('quantity', 'integer', ['default' => 0])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['product_id'], ['unique' => true, 'name' => 'uniq_stock_global_product'])
            ->addForeignKey('product_id', 'products', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }
}
