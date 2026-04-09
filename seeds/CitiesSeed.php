<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class CitiesSeed extends AbstractSeed
{
    public function run(): void
    {
        $dataFile = __DIR__ . '/../docs/state-municipality-tunisia-main/state-municipality.json';
        if (!file_exists($dataFile)) {
            throw new RuntimeException('Dataset not found at ' . $dataFile);
        }

        $json = file_get_contents($dataFile);
        $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $rows = $this->fetchAll('SELECT id, name_fr FROM governorates');
        $map = [];
        foreach ($rows as $row) {
            $key = self::normalize($row['name_fr'] ?? '');
            if ($key !== '') {
                $map[$key] = (int) $row['id'];
            }
        }

        foreach ($payload as $gov) {
            $govKey = self::normalize((string) ($gov['Name'] ?? ''));
            $govId = $map[$govKey] ?? null;
            if (!$govId) {
                continue;
            }

            foreach (($gov['Delegations'] ?? []) as $delegation) {
                $nameFr = addslashes((string) ($delegation['Name'] ?? ''));
                $nameAr = isset($delegation['NameAr']) ? addslashes((string) $delegation['NameAr']) : null;
                $postalCode = isset($delegation['PostalCode']) ? addslashes((string) $delegation['PostalCode']) : null;
                $latitude = $delegation['Latitude'] ?? null;
                $longitude = $delegation['Longitude'] ?? null;

                $sql = "INSERT INTO cities (governorate_id, name_fr, name_ar, postal_code, latitude, longitude) VALUES (" .
                    (int) $govId . ", '{$nameFr}', " .
                    ($nameAr !== null ? "'{$nameAr}'" : 'NULL') . ", " .
                    ($postalCode !== null ? "'{$postalCode}'" : 'NULL') . ", " .
                    ($latitude !== null ? (float) $latitude : 'NULL') . ", " .
                    ($longitude !== null ? (float) $longitude : 'NULL') . ")" .
                    " ON DUPLICATE KEY UPDATE name_ar = VALUES(name_ar), postal_code = VALUES(postal_code), latitude = VALUES(latitude), longitude = VALUES(longitude)";

                $this->execute($sql);
            }
        }
    }

    private static function normalize(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8');
        }
        return strtolower($value);
    }
}
