<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateVisitsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('visits', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('user_id', 'biginteger', ['signed' => false])
            ->addColumn('contact_id', 'biginteger', ['signed' => false])
            ->addColumn('visit_type', 'enum', ['values' => ['rappel', 'presentation', 'formation'], 'default' => 'rappel'])
            ->addColumn('products_discussed', 'text', ['null' => true])
            ->addColumn('samples_given', 'text', ['null' => true])
            ->addColumn('training_content', 'text', ['null' => true])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['user_id'])
            ->addIndex(['contact_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('contact_id', 'contacts', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }
}
