<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class GovernoratesSeed extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            ['name_fr' => 'Ariana', 'name_ar' => 'أريانة'],
            ['name_fr' => 'Beja', 'name_ar' => 'باجة'],
            ['name_fr' => 'Ben Arous', 'name_ar' => 'بن عروس'],
            ['name_fr' => 'Bizerte', 'name_ar' => 'بنزرت'],
            ['name_fr' => 'Gabes', 'name_ar' => 'قابس'],
            ['name_fr' => 'Gafsa', 'name_ar' => 'قفصة'],
            ['name_fr' => 'Jendouba', 'name_ar' => 'جندوبة'],
            ['name_fr' => 'Kairouan', 'name_ar' => 'القيروان'],
            ['name_fr' => 'Kasserine', 'name_ar' => 'القصرين'],
            ['name_fr' => 'Kebili', 'name_ar' => 'قبلي'],
            ['name_fr' => 'Kef', 'name_ar' => 'الكاف'],
            ['name_fr' => 'Mahdia', 'name_ar' => 'المهدية'],
            ['name_fr' => 'Manouba', 'name_ar' => 'منوبة'],
            ['name_fr' => 'Medenine', 'name_ar' => 'مدنين'],
            ['name_fr' => 'Monastir', 'name_ar' => 'المنستير'],
            ['name_fr' => 'Nabeul', 'name_ar' => 'نابل'],
            ['name_fr' => 'Sfax', 'name_ar' => 'صفاقس'],
            ['name_fr' => 'Sidi Bouzid', 'name_ar' => 'سيدي بوزيد'],
            ['name_fr' => 'Siliana', 'name_ar' => 'سليانة'],
            ['name_fr' => 'Sousse', 'name_ar' => 'سوسة'],
            ['name_fr' => 'Tataouine', 'name_ar' => 'تطاوين'],
            ['name_fr' => 'Tozeur', 'name_ar' => 'توزر'],
            ['name_fr' => 'Tunis', 'name_ar' => 'تونس'],
            ['name_fr' => 'Zaghouan', 'name_ar' => 'زغوان'],
        ];

        $table = $this->table('governorates');
        foreach ($data as $row) {
            $nameFr = addslashes($row['name_fr']);
            $nameAr = $row['name_ar'] !== null ? addslashes($row['name_ar']) : null;

            $sql = "INSERT INTO governorates (name_fr, name_ar) VALUES ('{$nameFr}', " .
                ($nameAr !== null ? "'{$nameAr}'" : 'NULL') . ")" .
                " ON DUPLICATE KEY UPDATE name_ar = VALUES(name_ar)";
            $this->execute($sql);
        }
    }
}
