<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUniqueIndexesGovernoratesCities extends AbstractMigration
{
    public function change(): void
    {
        $governorates = $this->table('governorates');
        if (!$governorates->hasIndex(['name_fr'])) {
            $governorates->addIndex(['name_fr'], ['unique' => true, 'name' => 'uniq_governorates_name_fr'])->update();
        }

        $cities = $this->table('cities');
        if (!$cities->hasIndex(['governorate_id', 'name_fr'])) {
            $cities->addIndex(['governorate_id', 'name_fr'], ['unique' => true, 'name' => 'uniq_cities_gov_name'])->update();
        }
    }
}
