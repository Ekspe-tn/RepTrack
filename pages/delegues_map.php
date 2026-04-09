<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);
$reps = [];
$repData = [];
$colors = ['#2563eb', '#16a34a', '#f97316', '#8b5cf6', '#0ea5e9', '#14b8a6', '#f59e0b', '#ec4899', '#84cc16', '#64748b'];

function normalize_key(string $value): string
{
    $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
    $value = strtoupper(preg_replace('/[^A-Z0-9]/', '', $value));
    return $value ?? '';
}

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
            $govKey = normalize_key((string) $gov['Name']);
            if (!isset($coordIndex[$govKey])) {
                $coordIndex[$govKey] = [];
            }
            foreach ($gov['Delegations'] as $del) {
                if (empty($del['Name']) || !isset($del['Latitude']) || !isset($del['Longitude'])) {
                    continue;
                }
                $cityKey = normalize_key((string) $del['Name']);
                $coordIndex[$govKey][$cityKey] = [
                    'lat' => (float) $del['Latitude'],
                    'lng' => (float) $del['Longitude'],
                    'name' => (string) $del['Name'],
                ];
            }
        }
    }
}

try {
    $reps = db()->query("SELECT u.id, u.name, u.governorate_id, u.excluded_city_ids, g.name_fr AS governorate_name
        FROM users u
        LEFT JOIN governorates g ON g.id = u.governorate_id
        WHERE u.role = 'rep' AND u.governorate_id IS NOT NULL
        ORDER BY u.id")->fetchAll();
} catch (Throwable $e) {
    $reps = [];
}

$govIds = [];
foreach ($reps as $rep) {
    if (!empty($rep['governorate_id'])) {
        $govIds[] = (int) $rep['governorate_id'];
    }
}
$govIds = array_values(array_unique(array_filter($govIds)));
$govCities = [];
if ($govIds) {
    $placeholders = implode(',', array_fill(0, count($govIds), '?'));
    try {
        $stmt = db()->prepare("SELECT id, name_fr, governorate_id FROM cities WHERE governorate_id IN ($placeholders)");
        $stmt->execute($govIds);
        foreach ($stmt->fetchAll() as $row) {
            $gid = (int) $row['governorate_id'];
            if (!isset($govCities[$gid])) {
                $govCities[$gid] = [];
            }
            $govCities[$gid][(int) $row['id']] = (string) $row['name_fr'];
        }
    } catch (Throwable $e) {
        $govCities = [];
    }
}

foreach ($reps as $index => $rep) {
    $govId = (int) $rep['governorate_id'];
    $govName = (string) ($rep['governorate_name'] ?? '');
    $govKey = normalize_key($govName);

    $excludedIds = [];
    if (!empty($rep['excluded_city_ids'])) {
        $decoded = json_decode((string) $rep['excluded_city_ids'], true);
        if (is_array($decoded)) {
            $excludedIds = array_map('intval', $decoded);
        }
    }

    $excludedNames = [];
    $points = [];
    $cityMap = $govCities[$govId] ?? [];
    $allIds = array_keys($cityMap);
    $excludedIds = array_values(array_intersect($excludedIds, $allIds));
    $includedIds = array_diff($allIds, $excludedIds);

    foreach ($excludedIds as $id) {
        if (isset($cityMap[$id])) {
            $excludedNames[] = $cityMap[$id];
        }
    }

    foreach ($includedIds as $id) {
        if (!isset($cityMap[$id])) {
            continue;
        }
        $cityName = $cityMap[$id];
        $cityKey = normalize_key($cityName);
        if (isset($coordIndex[$govKey][$cityKey])) {
            $points[] = [
                'name' => $cityName,
                'lat' => $coordIndex[$govKey][$cityKey]['lat'],
                'lng' => $coordIndex[$govKey][$cityKey]['lng'],
            ];
        }
    }

    $repData[] = [
        'id' => (int) $rep['id'],
        'name' => (string) $rep['name'],
        'governorate_name' => $govName,
        'color' => $colors[$index % count($colors)],
        'excluded' => $excludedNames,
        'points' => $points,
    ];
}

$page_title = 'Carte des zones';
require __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<div class="space-y-4">
  <div class="bg-white rounded-2xl shadow-sm p-4">
    <h2 class="text-base font-semibold text-slate-900">Zones par delegue</h2>
    <p class="text-xs text-slate-500 mt-1">Les delegations sont affichees en points colores. Utilisez le filtre pour isoler un delegue ou un gouvernorat.</p>
    <div class="mt-3 grid grid-cols-1 gap-3">
      <div>
        <label class="block text-xs text-slate-500">Recherche delegue / delegation</label>
        <input id="repSearch" type="text" placeholder="Nom delegue, delegation" class="mt-1 w-full h-10 rounded-xl border border-slate-200 px-3 text-sm">
      </div>
      <div>
        <label class="block text-xs text-slate-500">Gouvernorat</label>
        <select id="govFilter" class="mt-1 w-full h-10 rounded-xl border border-slate-200 px-3 text-sm">
          <option value="">Tous</option>
          <?php
          $govOptions = array_values(array_unique(array_filter(array_map(function ($rep) {
              return $rep['governorate_name'] ?? '';
          }, $repData))));
          sort($govOptions);
          foreach ($govOptions as $gov):
          ?>
            <option value="<?= htmlspecialchars($gov, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($gov, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow-sm p-4">
    <div id="delegues-map" class="w-full" style="height: 520px;"></div>
  </div>

  <div class="bg-white rounded-2xl shadow-sm p-4">
    <h3 class="text-sm font-semibold text-slate-900">Legende</h3>
    <div id="legend-list" class="mt-3 flex flex-wrap gap-3 text-xs"></div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  (function () {
    var repData = <?= json_encode($repData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    var map = L.map('delegues-map').setView([34.0, 9.0], 6.5);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
      maxZoom: 10,
      attribution: '&copy; CARTO'
    }).addTo(map);

    fetch('/assets/geo/tunisia_governorates.geojson')
      .then(function (res) { return res.json(); })
      .then(function (geojson) {
        L.geoJSON(geojson, {
          style: { color: '#cbd5f5', fillColor: '#e2e8f0', weight: 1, fillOpacity: 0.2 }
        }).addTo(map);
      });

    var repLayers = {};
    repData.forEach(function (rep) {
      var group = L.layerGroup();
      (rep.points || []).forEach(function (point) {
        var marker = L.circleMarker([point.lat, point.lng], {
          radius: 5,
          color: rep.color,
          fillColor: rep.color,
          fillOpacity: 0.85,
          weight: 1
        });
        marker.bindPopup('<strong>' + rep.name + '</strong><br>' + point.name);
        marker.addTo(group);
      });
      repLayers[rep.id] = group;
      group.addTo(map);
    });

    function renderLegend(list) {
      var container = document.getElementById('legend-list');
      container.innerHTML = '';
      if (!list.length) {
        var empty = document.createElement('div');
        empty.className = 'text-xs text-slate-500';
        empty.textContent = 'Aucun delegue pour ce filtre.';
        container.appendChild(empty);
        return;
      }
      list.forEach(function (rep) {
        var item = document.createElement('div');
        item.className = 'flex items-center gap-2';
        item.innerHTML = '<span class="inline-block w-3 h-3 rounded-full" style="background:' + rep.color + '"></span>' +
          '<span>' + rep.name + ' — ' + (rep.governorate_name || '') + '</span>';
        container.appendChild(item);
      });
    }

    function matches(rep, term, gov) {
      var hay = (rep.name + ' ' + (rep.governorate_name || '')).toLowerCase();
      if (term && !hay.includes(term)) {
        var found = false;
        (rep.points || []).forEach(function (p) {
          if (p.name.toLowerCase().includes(term)) {
            found = true;
          }
        });
        if (!found) {
          return false;
        }
      }
      if (gov && (rep.governorate_name || '') !== gov) {
        return false;
      }
      return true;
    }

    function applyFilter() {
      var term = (document.getElementById('repSearch').value || '').trim().toLowerCase();
      var gov = document.getElementById('govFilter').value || '';
      var filtered = [];
      repData.forEach(function (rep) {
        var keep = matches(rep, term, gov);
        if (keep) {
          filtered.push(rep);
          if (!map.hasLayer(repLayers[rep.id])) {
            repLayers[rep.id].addTo(map);
          }
        } else if (map.hasLayer(repLayers[rep.id])) {
          map.removeLayer(repLayers[rep.id]);
        }
      });
      renderLegend(filtered);
    }

    document.getElementById('repSearch').addEventListener('input', applyFilter);
    document.getElementById('govFilter').addEventListener('change', applyFilter);
    renderLegend(repData);
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
