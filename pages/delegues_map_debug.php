<?php

declare(strict_types=1);

require_role('admin');

$basePath = dirname(__DIR__);

// Load areas data
$coordIndex = [];
$areasFile = $basePath . '/docs/state-municipality-tunisia-main/state-municipality-areas.json';
if (file_exists($areasFile)) {
    $json = file_get_contents($areasFile);
    $areas = json_decode((string) $json, true);
    if (is_array($areas)) {
        foreach ($areas as $gov) {
            if (empty($gov['Name']) || empty($gov['Delegations']) || !is_array($gov['Delegations'])) {
                continue;
            }
            $govKey = strtoupper(preg_replace('/[^A-Z0-9]/', '', $gov['Name']));
            if (!isset($coordIndex[$govKey])) {
                $coordIndex[$govKey] = [];
            }
            foreach ($gov['Delegations'] as $del) {
                if (empty($del['Value']) || !isset($del['Latitude']) || !isset($del['Longitude'])) {
                    continue;
                }
                $cityKey = strtoupper(preg_replace('/[^A-Z0-9]/', '', $del['Value']));
                $coordIndex[$govKey][$cityKey] = [
                    'original_name' => (string) $del['Name'],
                    'value_name' => (string) $del['Value'],
                    'lat' => (float) $del['Latitude'],
                    'lng' => (float) $del['Longitude'],
                ];
            }
        }
    }
}

// Debug: Show available governorate keys
$availableGovKeys = array_keys($coordIndex);

// Fetch delegue data
$repId = (int) ($_GET['rep_id'] ?? 0);
if ($repId <= 0) {
    echo "Please provide rep_id parameter";
    exit;
}

try {
    $stmt = db()->prepare("SELECT u.id, u.name, u.governorate_id, u.governorate_ids, u.excluded_city_ids, g.name_fr AS governorate_name
        FROM users u
        LEFT JOIN governorates g ON g.id = u.governorate_id
        WHERE u.id = ? AND u.role = 'rep'");
    $stmt->execute([$repId]);
    $rep = $stmt->fetch();
} catch (Throwable $e) {
    echo "Error fetching delegue: " . $e->getMessage();
    exit;
}

if (!$rep) {
    echo "Delegue not found";
    exit;
}

// Parse governorate IDs
$governorateIds = [];
if (!empty($rep['governorate_ids'])) {
    $decoded = json_decode((string) $rep['governorate_ids'], true);
    if (is_array($decoded)) {
        $governorateIds = array_map('intval', $decoded);
    }
}
if (empty($governorateIds) && $rep['governorate_id']) {
    $governorateIds = [(int) $rep['governorate_id']];
}

// Parse excluded cities
$excludedIds = [];
if (!empty($rep['excluded_city_ids'])) {
    $decoded = json_decode((string) $rep['excluded_city_ids'], true);
    if (is_array($decoded)) {
        $excludedIds = array_map('intval', $decoded);
    }
}

// Get all governorates
try {
    $allGovernorates = db()->query('SELECT id, name_fr FROM governorates ORDER BY name_fr')->fetchAll();
} catch (Throwable $e) {
    $allGovernorates = [];
}

// Get cities for all governorates
$allCities = [];
if (!empty($governorateIds)) {
    $placeholders = str_repeat('?,', count($governorateIds));
    $placeholders = rtrim($placeholders, ',');
    
    try {
        $stmt = db()->prepare("SELECT id, name_fr, governorate_id FROM cities WHERE governorate_id IN ($placeholders) ORDER BY governorate_id, name_fr");
        $stmt->execute($governorateIds);
        $allCities = $stmt->fetchAll();
    } catch (Throwable $e) {
    }
}

// Create governorate ID to name map
$govIdToName = [];
foreach ($allGovernorates as $g) {
    $govIdToName[(int) $g['id']] = (string) $g['name_fr'];
}

// Analyze each city
$results = [];
foreach ($allCities as $city) {
    $cityId = (int) $city['id'];
    $cityName = (string) $city['name_fr'];
    $govId = (int) $city['governorate_id'];
    
    // Check if excluded
    $isExcluded = in_array($cityId, $excludedIds, true);
    
    // Get governorate name
    $govName = $govIdToName[$govId] ?? '';
    
    // Normalize names (simple approach without iconv for Windows compatibility)
    $govKey = strtoupper(preg_replace('/[^A-Z0-9]/', '', $govName));
    $cityKey = strtoupper(preg_replace('/[^A-Z0-9]/', '', $cityName));
    
    // Check if coordinate exists
    $hasCoord = isset($coordIndex[$govKey][$cityKey]);
    
    // Get matched name if available
    $matchedName = '';
    if ($hasCoord) {
        $matchedName = $coordIndex[$govKey][$cityKey]['original_name'];
    }
    
    $results[] = [
        'governorate' => $govName,
        'city' => $cityName,
        'gov_key' => $govKey,
        'city_key' => $cityKey,
        'is_excluded' => $isExcluded,
        'has_coord' => $hasCoord,
        'matched_name' => $matchedName,
        'gov_has_cities' => isset($coordIndex[$govKey]),
    ];
}

$page_title = 'Debug: Delegations for ' . htmlspecialchars($rep['name'], ENT_QUOTES, 'UTF-8');
require __DIR__ . '/../includes/header.php';
?>

<div class="space-y-4">
  <div class="bg-white rounded-2xl shadow-sm p-4">
    <h2 class="text-base font-semibold text-slate-900 mb-4">Debug: <?= htmlspecialchars($rep['name'], ENT_QUOTES, 'UTF-8') ?></h2>
    
    <div class="mb-4">
      <p class="text-sm text-slate-600">
        Governorates: <?= htmlspecialchars($rep['governorate_name'], ENT_QUOTES, 'UTF-8') ?><br>
        Excluded delegations: <?= count($excludedIds) ?><br>
        Total cities in database: <?= count($allCities) ?><br>
        Available governorate keys in areas.json: <?= count($availableGovKeys) ?>
      </p>
      <?php if (!empty($availableGovKeys)): ?>
        <div class="mt-2 p-3 rounded-lg bg-slate-50 text-xs">
          <strong>Available governorate keys:</strong> <?= htmlspecialchars(implode(', ', $availableGovKeys), ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>
    </div>
    
    <h3 class="text-sm font-semibold text-slate-900 mb-3">Cities Analysis</h3>
    
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-2 text-left text-slate-700">Governorate</th>
            <th class="px-4 py-2 text-left text-slate-700">City (DB)</th>
            <th class="px-4 py-2 text-left text-slate-700">City (Areas.json)</th>
            <th class="px-4 py-2 text-left text-slate-700">Status</th>
            <th class="px-4 py-2 text-left text-slate-700">Matched?</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($results as $row): ?>
            <tr class="border-t border-slate-100">
              <td class="px-4 py-2 text-slate-900"><?= htmlspecialchars($row['governorate'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="px-4 py-2">
                <div class="font-medium text-slate-900"><?= htmlspecialchars($row['city'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-xs text-slate-500">Key: <?= htmlspecialchars($row['city_key'], ENT_QUOTES, 'UTF-8') ?></div>
              </td>
              <td class="px-4 py-2 text-slate-600">
                <?php if ($row['matched_name']): ?>
                  <?= htmlspecialchars($row['matched_name'], ENT_QUOTES, 'UTF-8') ?>
                <?php else: ?>
                  <span class="text-red-600">Not found</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-2">
                <?php if ($row['is_excluded']): ?>
                  <span class="px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-800">Excluded</span>
                <?php else: ?>
                  <span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">Included</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-2">
                <?php if (!$row['gov_has_cities']): ?>
                  <span class="text-red-600 font-medium">Gov not in areas.json</span>
                <?php elseif ($row['has_coord']): ?>
                  <span class="text-green-600 font-medium">✓ Match</span>
                <?php else: ?>
                  <span class="text-red-600 font-medium">✗ No match</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    
    <div class="mt-4 p-4 rounded-xl bg-slate-50 text-xs text-slate-600">
      <strong>How to fix mismatches:</strong><br>
      1. If "Gov not in areas.json": The governorate name doesn't match - check spelling in areas.json<br>
      2. If "No match": The city name doesn't match - check spelling in areas.json<br>
      3. Common issues: Different spellings, abbreviations, accents, naming conventions<br>
      4. The code normalizes names by removing accents and special characters
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>