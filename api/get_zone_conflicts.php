<?php

declare(strict_types=1);

require_role('admin');

header('Content-Type: application/json');

$governorateId = (int) ($_GET['governorate_id'] ?? 0);
$repId = (int) ($_GET['rep_id'] ?? 0);
$excludedRaw = (string) ($_GET['excluded_city_ids'] ?? '');
$excludedCities = [];
if ($excludedRaw !== '') {
    $parts = array_filter(array_map('trim', explode(',', $excludedRaw)));
    $excludedCities = array_map('intval', $parts);
}

if ($governorateId <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Gouvernorat requis.', 'conflicts' => []]);
    exit;
}

$stmt = db()->prepare('SELECT id, name_fr FROM cities WHERE governorate_id = ?');
$stmt->execute([$governorateId]);
$cities = $stmt->fetchAll();
$allIds = [];
$nameMap = [];
foreach ($cities as $city) {
    $id = (int) $city['id'];
    $allIds[] = $id;
    $nameMap[$id] = $city['name_fr'];
}

$excludedCities = array_values(array_unique(array_map('intval', $excludedCities)));
$excludedCities = array_values(array_filter($excludedCities, function ($id) use ($allIds) {
    return in_array($id, $allIds, true);
}));
$included = array_values(array_diff($allIds, $excludedCities));

$params = [$governorateId];
$sql = "SELECT id, excluded_city_ids FROM users WHERE role = 'rep' AND governorate_id = ?";
if ($repId > 0) {
    $sql .= ' AND id <> ?';
    $params[] = $repId;
}
$stmt = db()->prepare($sql);
$stmt->execute($params);
$conflicts = [];
foreach ($stmt->fetchAll() as $rep) {
    $otherExcluded = [];
    if (!empty($rep['excluded_city_ids'])) {
        $decoded = json_decode((string) $rep['excluded_city_ids'], true);
        if (is_array($decoded)) {
            $otherExcluded = array_values(array_unique(array_map('intval', $decoded)));
            $otherExcluded = array_values(array_filter($otherExcluded, function ($id) use ($allIds) {
                return in_array($id, $allIds, true);
            }));
        }
    }
    $otherIncluded = array_diff($allIds, $otherExcluded);
    $overlap = array_intersect($included, $otherIncluded);
    foreach ($overlap as $cityId) {
        $conflicts[$cityId] = true;
    }
}

$conflictNames = [];
foreach (array_keys($conflicts) as $cityId) {
    if (isset($nameMap[$cityId])) {
        $conflictNames[] = $nameMap[$cityId];
    }
}

if ($conflictNames) {
    echo json_encode([
        'ok' => false,
        'message' => 'Conflit: ' . implode(', ', array_slice($conflictNames, 0, 8)) . (count($conflictNames) > 8 ? ' ...' : ''),
        'conflicts' => $conflictNames,
        'included_count' => count($included),
        'excluded_count' => count($excludedCities),
        'total' => count($allIds),
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => 'Zone disponible.',
    'conflicts' => [],
    'included_count' => count($included),
    'excluded_count' => count($excludedCities),
    'total' => count($allIds),
]);
