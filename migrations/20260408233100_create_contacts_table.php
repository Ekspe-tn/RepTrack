<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateContactsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('contacts', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('type', 'enum', ['values' => ['doctor', 'pharmacy', 'parapharmacie', 'clinic', 'hospital']])
            ->addColumn('name', 'string', ['limit' => 150])
            ->addColumn('specialty', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('establishment', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('governorate_id', 'biginteger', ['signed' => false])
            ->addColumn('city_id', 'biginteger', ['signed' => false])
            ->addColumn('address', 'text', ['null' => true])
            ->addColumn('latitude', 'decimal', ['precision' => 10, 'scale' => 7, 'null' => true])
            ->addColumn('longitude', 'decimal', ['precision' => 10, 'scale' => 7, 'null' => true])
            ->addColumn('phone', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('email', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('contact_person', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('status', 'enum', ['values' => ['chain', 'independent', 'group', 'hospital_public', 'clinic_private'], 'default' => 'independent'])
            ->addColumn('potential', 'enum', ['values' => ['A', 'B', 'C'], 'default' => 'B'])
            ->addColumn('client_type', 'enum', ['values' => ['local', 'tourist', 'specialized', 'mixed'], 'default' => 'local'])
            ->addColumn('collaboration_history', 'enum', ['values' => ['new', 'occasional', 'regular', 'key_account'], 'default' => 'new'])
            ->addColumn('plv_present', 'boolean', ['default' => 0])
            ->addColumn('team_engagement', 'enum', ['values' => ['low', 'medium', 'high'], 'default' => 'medium'])
            ->addColumn('specific_needs', 'text', ['null' => true])
            ->addColumn('visit_frequency_days', 'integer', ['default' => 30])
            ->addColumn('assigned_rep_id', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('added_by', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('active', 'boolean', ['default' => 1])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['governorate_id'])
            ->addIndex(['city_id'])
            ->addIndex(['assigned_rep_id'])
            ->addForeignKey('governorate_id', 'governorates', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('city_id', 'cities', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('assigned_rep_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('added_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();
    }
}
