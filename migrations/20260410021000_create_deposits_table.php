<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDepositsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('deposits');

        $table
            ->addColumn('user_id', 'integer', [
                'signed' => true,
                'null' => false,
                'comment' => 'Delegate/rep user ID'
            ])
            ->addColumn('amount', 'decimal', [
                'precision' => 15,
                'scale' => 2,
                'null' => false,
                'comment' => 'Deposit amount'
            ])
            ->addColumn('description', 'text', [
                'null' => true,
                'comment' => 'Deposit description'
            ])
            ->addColumn('deposit_date', 'date', [
                'null' => false,
                'comment' => 'Date of deposit'
            ])
            ->addColumn('created_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false
            ])
            ->addColumn('created_by', 'integer', [
                'signed' => true,
                'null' => true,
                'comment' => 'User who created the deposit'
            ])
            ->addIndex(['user_id'])
            ->addIndex(['deposit_date'])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION'
            ])
            ->create();
    }
}