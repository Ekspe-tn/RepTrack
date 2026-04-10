<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCarInfoToUsers extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users');

        // Add car information columns
        $table
            ->addColumn('car_make', 'string', [
                'limit' => 100,
                'null' => true,
                'after' => 'phone',
                'comment' => 'Car manufacturer (e.g., Renault, Peugeot)'
            ])
            ->addColumn('car_model', 'string', [
                'limit' => 100,
                'null' => true,
                'after' => 'car_make',
                'comment' => 'Car model (e.g., Clio, 308)'
            ])
            ->addColumn('car_leasing_start', 'date', [
                'null' => true,
                'after' => 'car_model',
                'comment' => 'Leasing start date'
            ])
            ->addColumn('car_leasing_end', 'date', [
                'null' => true,
                'after' => 'car_leasing_start',
                'comment' => 'Leasing end date'
            ])
            ->addColumn('car_monthly_cost', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => true,
                'after' => 'car_leasing_end',
                'comment' => 'Monthly leasing cost'
            ])
            ->addColumn('car_fuel_cost', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => true,
                'after' => 'car_monthly_cost',
                'comment' => 'Monthly fuel cost'
            ])
            ->addColumn('car_weekly_km', 'int', [
                'null' => true,
                'after' => 'car_fuel_cost',
                'comment' => 'Weekly kilometers'
            ])
            ->update();
    }
}