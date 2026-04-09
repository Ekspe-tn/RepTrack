<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPostalCodeToCities extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('cities');
        $table
            ->addColumn('postal_code', 'string', ['limit' => 10, 'null' => true, 'after' => 'name_ar'])
            ->addIndex(['postal_code'])
            ->update();
    }
}
