<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddProductFields extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('products');
        $table
            ->addColumn('photo', 'string', ['limit' => 255, 'null' => true, 'after' => 'name'])
            ->addColumn('cost', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true, 'after' => 'photo'])
            ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true, 'after' => 'cost'])
            ->addColumn('gtin13', 'string', ['limit' => 13, 'null' => true, 'after' => 'price'])
            ->addColumn('specialities', 'text', ['null' => true, 'after' => 'gtin13'])
            ->update();
    }
}