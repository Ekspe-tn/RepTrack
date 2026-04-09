<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsersTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('email', 'string', ['limit' => 150])
            ->addColumn('password', 'string', ['limit' => 255])
            ->addColumn('role', 'enum', ['values' => ['admin', 'rep'], 'default' => 'rep'])
            ->addColumn('phone', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('zone', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('active', 'boolean', ['default' => 1])
            ->addColumn('last_login', 'datetime', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['email'], ['unique' => true])
            ->create();
    }
}
