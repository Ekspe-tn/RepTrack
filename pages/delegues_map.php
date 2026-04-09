<?php

declare(strict_types=1);

require_role('admin');

$basePath = dirname(__DIR__);
$success = '';
$error = '';

// Handle POST for zone editing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $action = (string) ($_POST['action'] ?? '');
    
    if ($action === 'update_zone') {
        $repId = (int) ($_POST['rep_id'] ?? 0);
        $governorateIds = array_map('intval', $_POST['governorate_ids'] ?? []);
        $excludedCities = array_map('intval', $_POST['excluded_city_ids'] ?? []);
        
        if ($repId <= 0) {
            $error = 'Delegue invalide.';
        } else {
            try {
                // Validate overlap
                $excludedJson = $excludedCities ? json_encode($excludedCities) : null;
                $firstGov = $governorateIds[0] ?? 0;
                $governorateJson = json_encode($governorateIds);
                
                $stmt = db()->prepare("UPDATE users SET governorate_id = ?, governorate_ids = ?, excluded_city_ids = ? WHERE id = ? AND role = 'rep'");
                $stmt->execute([$firstGov, $governorateJson, $excludedJson, $repId]);
                $success = 'Zone mise a jour.';
            } catch (Throwable $e) {
                $error = 'Erreur lors de la mise a jour.';
            }
        }
    }
}

// Load GeoJSON data
$geojsonPath = $basePath . '/docs/state-municipality-tunisia-main/tunisia_administrative_province_state_boundary.geojson';
$geojsonData = null;
if (file_exists($geojsonPath)) {
    $json = file_get_contents($geojsonPath);
    $geojsonData = json_decode((string) $json, true);
}

// Load governorate areas data
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
            $govKey = preg_replace('/[^A-Z0-9]/', '', strtoupper($gov['Name']));
            if (!isset($coordIndex[$govKey])) {
                $coordIndex[$govKey] = [];
            }
            foreach ($gov['Delegations'] as $del) {
                if (empty($del['Value']) || !isset($del['Latitude']) || !isset($del['Longitude'])) {
                    continue;
                }
                $cityKey = preg_replace('/[^A-Z0-9]/', '', strtoupper($del['Value']));
                $coordIndex[$govKey][$cityKey] = [
                    'lat' => (float) $del['Latitude'],
                    'lng' => (float) $del['Longitude'],
                    'name' => (string) $del['Name'],
                ];
            }
        }
    }
}

// Fetch delegues data
try {
    $reps = db()->query("SELECT u.id, u.name, u.email, u.governorate_id, u.governorate_ids, u.excluded_city_ids, u.active, g.name_fr AS governorate_name
        FROM users u
        LEFT JOIN governorates g ON g.id = u.governorate_id
        WHERE u.role = 'rep'
        ORDER BY u.id")->fetchAll();
} catch (Throwable $e) {
    $reps = [];
}

// Fetch contacts data for counting (only assigned contacts like delegues page)
try {
    $contacts = db()->query("SELECT c.id, c.type, c.latitude, c.longitude, c.city_id, c.assigned_rep_id, 
        ci.name_fr AS city_name, ci.governorate_id
        FROM contacts c
        LEFT JOIN cities ci ON ci.id = c.city_id
        WHERE c.assigned_rep_id IS NOT NULL 
        AND c.latitude IS NOT NULL AND c.longitude IS NOT NULL
        ORDER BY c.id")->fetchAll();
} catch (Throwable $e) {
    $contacts = [];
}

// Fetch all governorates
try {
    $allGovernorates = db()->query('SELECT id, name_fr FROM governorates ORDER BY name_fr')->fetchAll();
} catch (Throwable $e) {
    $allGovernorates = [];
}

// Build governorate colors
$colors = [
    '#3b82f6', '#22c55e', '#f97316', '#8b5cf6', '#06b6d4',
    '#ec4899', '#84cc16', '#f59e0b', '#64748b', '#14b8a6',
    '#f43f5e', '#a855f7', '#10b981', '#eab308', '#0ea5e9'
];

// Create governorate name to ID map for GeoJSON color matching
$govNameToIdMap = [];
foreach ($allGovernorates as $gov) {
    $govName = (string) $gov['name_fr'];
    $govNameToIdMap[strtoupper($govName)] = (int) $gov['id'];
}

// Process delegue data
$repData = [];
$governorateStats = [];
$governorateColorsMap = [];

foreach ($reps as $index => $rep) {
    $govId = (int) $rep['governorate_id'];
    $govName = (string) ($rep['governorate_name'] ?? '');
    
    // Parse multiple governorates
    $governorateIds = [];
    if (!empty($rep['governorate_ids'])) {
        $decoded = json_decode((string) $rep['governorate_ids'], true);
        if (is_array($decoded)) {
            $governorateIds = array_map('intval', $decoded);
        }
    }
    if (empty($governorateIds) && $govId) {
        $governorateIds = [$govId];
    }
    
    // Get governorate names
    $govNames = [];
    foreach ($governorateIds as $gid) {
        foreach ($allGovernorates as $g) {
            if ((int) $g['id'] === $gid) {
                $govNames[] = $g['name_fr'];
                break;
            }
        }
    }
    
    // Assign unique color to this delegate
    $repColor = $colors[$index % count($colors)];
    
    // Map governorate colors (first governorate gets rep's color)
    foreach ($governorateIds as $gid) {
        if (!isset($governorateColorsMap[$gid])) {
            $governorateColorsMap[$gid] = $repColor;
        }
    }
    
    // Parse excluded cities
    $excludedIds = [];
    if (!empty($rep['excluded_city_ids'])) {
        $decoded = json_decode((string) $rep['excluded_city_ids'], true);
        if (is_array($decoded)) {
            $excludedIds = array_map('intval', $decoded);
        }
    }
    
    // Count contacts in this delegate's zone by governorate and type
    $contactCounts = [];
    $contactPoints = [];
    
    foreach ($contacts as $contact) {
        $contactRepId = (int) ($contact['assigned_rep_id'] ?? 0);
        
        // Check if contact is assigned to this delegate (matches delegues page logic)
        if ($contactRepId !== (int) $rep['id']) {
            continue;
        }
        
        $contactGovId = (int) ($contact['governorate_id'] ?? 0);
        $contactCityId = (int) ($contact['city_id'] ?? 0);
        
        // Check if contact is in delegate's governorates
        if (!in_array($contactGovId, $governorateIds)) {
            continue;
        }
        
        // Check if contact is in excluded cities
        if (in_array($contactCityId, $excludedIds, true)) {
            continue;
        }
        
        // Count by type
        $type = (string) ($contact['type'] ?? 'autre');
        if (!isset($contactCounts[$type])) {
            $contactCounts[$type] = 0;
        }
        $contactCounts[$type]++;
        
        // Add contact point
        if (isset($contact['latitude']) && isset($contact['longitude'])) {
            $contactPoints[] = [
                'name' => $contact['id'],
                'lat' => (float) $contact['latitude'],
                'lng' => (float) $contact['longitude'],
                'type' => $type,
            ];
        }
    }
    
    // Build points more efficiently - fetch all cities at once
    $points = [];
    if (!empty($governorateIds)) {
        $placeholders = str_repeat('?,', count($governorateIds));
        $placeholders = rtrim($placeholders, ',');
        
        try {
            $stmt = db()->prepare("SELECT id, name_fr, governorate_id FROM cities WHERE governorate_id IN ($placeholders)");
            $stmt->execute($governorateIds);
            $cities = $stmt->fetchAll();
            
            // Create a governorate ID to name map
            $govIdToName = [];
            foreach ($allGovernorates as $g) {
                $govIdToName[(int) $g['id']] = (string) $g['name_fr'];
            }
            
            foreach ($cities as $city) {
                $cityId = (int) $city['id'];
                $cityName = (string) $city['name_fr'];
                $govId = (int) $city['governorate_id'];
                
                // Skip excluded cities
                if (in_array($cityId, $excludedIds, true)) {
                    continue;
                }
                
                // Get governorate name and create normalized keys
                $govName = $govIdToName[$govId] ?? '';
                $govKey = preg_replace('/[^A-Z0-9]/', '', strtoupper($govName));
                $cityKey = preg_replace('/[^A-Z0-9]/', '', strtoupper($cityName));
                
                // Add point if coordinates available
                if (isset($coordIndex[$govKey][$cityKey])) {
                    $points[] = [
                        'name' => $cityName,
                        'lat' => $coordIndex[$govKey][$cityKey]['lat'],
                        'lng' => $coordIndex[$govKey][$cityKey]['lng'],
                    ];
                }
            }
        } catch (Throwable $e) {
            // Continue with empty points if query fails
        }
    }
    
    $repData[] = [
        'id' => (int) $rep['id'],
        'name' => (string) $rep['name'],
        'email' => (string) ($rep['email'] ?? ''),
        'governorate_name' => implode(', ', $govNames),
        'governorate_ids' => $governorateIds,
        'active' => (int) $rep['active'] === 1,
        'color' => $repColor,
        'excluded_count' => count($excludedIds),
        'points_count' => count($points),
        'contacts_count' => array_sum($contactCounts),
        'contact_counts' => $contactCounts,
        'points' => $points,
        'contact_points' => $contactPoints,
    ];
    
    // Track governorate coverage
    foreach ($governorateIds as $gid) {
        if (!isset($governorateStats[$gid])) {
            $governorateStats[$gid] = [
                'name' => '',
                'reps' => [],
                'contacts' => [],
                'points' => [],
            ];
        }
        $governorateStats[$gid]['reps'][] = (string) $rep['name'];
        $governorateStats[$gid]['points'] = array_merge($governorateStats[$gid]['points'], $points);
        
        // Add contacts to governorate stats
        foreach ($contactCounts as $type => $count) {
            if (!isset($governorateStats[$gid]['contacts'][$type])) {
                $governorateStats[$gid]['contacts'][$type] = 0;
            }
            $governorateStats[$gid]['contacts'][$type] += $count;
        }
    }
}

// Calculate summary stats
$totalContacts = array_sum(array_map(fn($r) => $r['contacts_count'], $repData));
$summary = [
    'total_reps' => count($repData),
    'active_reps' => count(array_filter($repData, fn($r) => $r['active'])),
    'total_contacts' => $totalContacts,
    'governorates_covered' => count(array_unique(array_merge(...array_map(fn($r) => $r['governorate_ids'], $repData)))),
];

$page_title = 'Carte des zones';
require __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"/>

<div class="space-y-4">
  <?php if ($success !== ''): ?>
    <div class="p-3 rounded-xl bg-green-50 text-green-700 text-sm">
      <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="p-3 rounded-xl bg-red-50 text-red-700 text-sm">
      <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <!-- Summary Stats -->
  <div class="bg-white rounded-2xl shadow-sm p-4">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-base font-semibold text-slate-900">Carte des zones</h2>
      <div class="flex gap-2">
        <a href="/delegues" class="text-xs text-blue-600 hover:text-blue-800">Liste des delegues</a>
        <a href="/delegues/new" class="text-xs text-blue-600 hover:text-blue-800">Creer un delegue</a>
      </div>
    </div>
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
      <div class="rounded-xl border border-slate-100 p-4">
        <div class="text-xs text-slate-500">Total delegues</div>
        <div class="text-2xl font-bold text-slate-900"><?= $summary['total_reps'] ?></div>
      </div>
      <div class="rounded-xl border border-slate-100 p-4">
        <div class="text-xs text-slate-500">Delegues actifs</div>
        <div class="text-2xl font-bold text-green-600"><?= $summary['active_reps'] ?></div>
      </div>
      <div class="rounded-xl border border-slate-100 p-4">
        <div class="text-xs text-slate-500">Total contacts</div>
        <div class="text-2xl font-bold text-blue-600"><?= $summary['total_contacts'] ?></div>
      </div>
      <div class="rounded-xl border border-slate-100 p-4">
        <div class="text-xs text-slate-500">Gouvernorats couverts</div>
        <div class="text-2xl font-bold text-purple-600"><?= $summary['governorates_covered'] ?></div>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="bg-white rounded-2xl shadow-sm p-4">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="block text-xs text-slate-500">Rechercher delegue / delegation</label>
        <input id="repSearch" type="text" placeholder="Nom delegue, delegation, gouvernorat..." 
               class="mt-1 w-full h-10 rounded-lg border border-slate-200 px-3 text-sm">
      </div>
      <div>
        <label class="block text-xs text-slate-500">Gouvernorat</label>
        <select id="govFilter" class="mt-1 w-full h-10 rounded-lg border border-slate-200 px-3 text-sm">
          <option value="">Tous les gouvernorats</option>
          <?php foreach ($allGovernorates as $gov): ?>
            <option value="<?= (int) $gov['id'] ?>"><?= htmlspecialchars($gov['name_fr'], ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-xs text-slate-500">Statut</label>
        <select id="statusFilter" class="mt-1 w-full h-10 rounded-lg border border-slate-200 px-3 text-sm">
          <option value="">Tous</option>
          <option value="active">Actifs</option>
          <option value="inactive">Inactifs</option>
        </select>
      </div>
    </div>
  </div>

  <!-- Map -->
  <div class="bg-white rounded-2xl shadow-sm p-4">
    <div id="delegues-map" class="w-full rounded-xl" style="height: 600px;"></div>
  </div>

  <!-- Legend -->
  <div class="bg-white rounded-2xl shadow-sm p-4">
    <h3 class="text-sm font-semibold text-slate-900 mb-3">Legende des delegues</h3>
    <div id="legend-list" class="flex flex-wrap gap-3 text-xs"></div>
  </div>

  <!-- Delegue Details (shown when clicking on map) -->
  <div id="delegue-details" class="hidden bg-white rounded-2xl shadow-sm p-4">
    <h3 class="text-sm font-semibold text-slate-900 mb-3">Details du delegue</h3>
    <div id="delegue-details-content" class="space-y-3"></div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
  (function () {
    var repData = <?= json_encode($repData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var geojsonData = <?= json_encode($geojsonData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var governorateColors = <?= json_encode($governorateColorsMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    
    // Initialize map
    var map = L.map('delegues-map').setView([34.0, 9.0], 6.5);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
      maxZoom: 12,
      attribution: '&copy; CARTO & OpenStreetMap contributors'
    }).addTo(map);

    // Add governorate boundaries from GeoJSON if available
    if (geojsonData) {
      L.geoJSON(geojsonData, {
        style: function(feature) {
          var govName = feature.properties && feature.properties.name ? feature.properties.name : '';
          var govKey = govName.replace(/[^A-Z0-9]/g, '').toUpperCase();
          var color = governorateColors[govKey] || '#94a3b8';
          
          return {
            color: color,
            weight: 2,
            fillColor: color,
            fillOpacity: 0.3
          };
        },
        onEachFeature: function(feature, layer) {
          if (feature.properties && feature.properties.name) {
            layer.bindTooltip(feature.properties.name, {
              permanent: false,
              direction: 'top'
            });
          }
        }
      }).addTo(map);
    }

    // Create marker clusters for better performance
    var markerClusterGroup = L.markerClusterGroup({
      showCoverageOnHover: false,
      maxClusterRadius: 40
    }).addTo(map);

    // Store delegue layers
    var repLayers = {};
    var repMarkers = {};

    // Add markers for each delegue
    repData.forEach(function(rep) {
      var markers = [];
      
      // Add city points (delegations)
      (rep.points || []).forEach(function(point) {
        var marker = L.circleMarker([point.lat, point.lng], {
          radius: 4,
          color: rep.color,
          fillColor: '#ffffff',
          fillOpacity: 0.9,
          weight: 1
        });
        
        var popupContent = '<div class="text-sm">' +
          '<strong class="text-slate-900">' + rep.name + '</strong><br>' +
          '<span class="text-slate-600">Délégation: ' + point.name + '</span><br>' +
          '<span class="text-xs text-slate-500">' + rep.governorate_name + '</span>' +
          '</div>';
        
        marker.bindPopup(popupContent);
        markers.push(marker);
      });
      
      // Add contact markers
      (rep.contact_points || []).forEach(function(point) {
        var marker = L.circleMarker([point.lat, point.lng], {
          radius: 8,
          color: rep.color,
          fillColor: rep.color,
          fillOpacity: 0.85,
          weight: 2
        });
        
        var popupContent = '<div class="text-sm">' +
          '<strong class="text-slate-900">' + rep.name + '</strong><br>' +
          '<span class="text-slate-600">Contact #' + point.name + '</span><br>' +
          '<span class="text-xs text-slate-500">Type: ' + point.type + '</span>' +
          '</div>';
        
        marker.bindPopup(popupContent);
        
        // Click to show details
        marker.on('click', function() {
          showDelegueDetails(rep);
        });
        
        markers.push(marker);
      });
      
      repMarkers[rep.id] = markers;
      
      // Create layer group for filtering
      var group = L.layerGroup();
      markers.forEach(function(m) {
        m.addTo(group);
      });
      repLayers[rep.id] = group;
    });

    // Add all markers to cluster
    Object.values(repMarkers).forEach(function(markers) {
      markers.forEach(function(m) {
        markerClusterGroup.addLayer(m);
      });
    });

    // Show delegue details panel
    function showDelegueDetails(rep) {
      var container = document.getElementById('delegue-details');
      var content = document.getElementById('delegue-details-content');
      
      container.classList.remove('hidden');
      
      var statusClass = rep.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
      var statusText = rep.active ? 'Actif' : 'Inactif';
      
      // Format contact counts
      var contactInfo = '';
      if (rep.contact_counts) {
        var types = Object.keys(rep.contact_counts).sort();
        var typeLabels = types.map(function(t) {
          return t.charAt(0).toUpperCase() + t.slice(1);
        });
        var counts = types.map(function(t) {
          return t + ': ' + rep.contact_counts[t];
        }).join(', ');
        contactInfo = '<div class="rounded-lg bg-slate-50 p-3">' +
          '<div class="text-xs text-slate-500">Contacts</div>' +
          '<div class="text-sm font-medium text-slate-900">' + counts + '</div>' +
          '</div>';
      }
      
      content.innerHTML = 
        '<div class="flex items-center justify-between">' +
          '<div>' +
            '<div class="text-lg font-semibold text-slate-900">' + rep.name + '</div>' +
            '<div class="text-sm text-slate-500">' + rep.email + '</div>' +
          '</div>' +
          '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' + statusClass + '">' +
            statusText +
          '</span>' +
        '</div>' +
        '<div class="grid grid-cols-2 gap-3 mt-3">' +
          '<div class="rounded-lg bg-slate-50 p-3">' +
            '<div class="text-xs text-slate-500">Gouvernorat(s)</div>' +
            '<div class="text-sm font-medium text-slate-900">' + rep.governorate_name + '</div>' +
          '</div>' +
          '<div class="rounded-lg bg-slate-50 p-3">' +
            '<div class="text-xs text-slate-500">Total contacts</div>' +
            '<div class="text-sm font-medium text-slate-900">' + rep.contacts_count + '</div>' +
          '</div>' +
        '</div>' +
        contactInfo +
        '<div class="flex gap-2 mt-3">' +
          '<a href="/delegues" class="flex-1 h-9 rounded-lg border border-slate-200 text-sm text-center leading-9 hover:bg-slate-50">Voir liste</a>' +
          '<a href="/visits?rep_id=' + rep.id + '" class="flex-1 h-9 rounded-lg border border-slate-200 text-sm text-center leading-9 hover:bg-slate-50">Voir visites</a>' +
        '</div>';
      
      container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Render legend
    function renderLegend(list) {
      var container = document.getElementById('legend-list');
      container.innerHTML = '';
      
      if (!list.length) {
        container.innerHTML = '<div class="text-xs text-slate-500">Aucun delegue pour ce filtre.</div>';
        return;
      }
      
      list.forEach(function(rep) {
        var item = document.createElement('div');
        item.className = 'flex items-center gap-2 bg-slate-50 rounded-lg px-3 py-2 cursor-pointer hover:bg-slate-100 transition-colors';
        item.innerHTML = 
          '<span class="inline-block w-3 h-3 rounded-full" style="background:' + rep.color + '"></span>' +
          '<div class="flex flex-col">' +
            '<span class="font-medium text-slate-900">' + rep.name + '</span>' +
            '<span class="text-slate-500">' + rep.governorate_name + ' (' + rep.contacts_count + ' contacts)</span>' +
          '</div>';
        item.addEventListener('click', function() {
          showDelegueDetails(rep);
        });
        container.appendChild(item);
      });
    }

    // Filter logic
    function matches(rep, term, govId, status) {
      var hay = (rep.name + ' ' + rep.email + ' ' + rep.governorate_name).toLowerCase();
      
      if (term && !hay.includes(term)) {
        var found = false;
        (rep.points || []).forEach(function(p) {
          if (p.name.toLowerCase().includes(term)) {
            found = true;
          }
        });
        if (!found) return false;
      }
      
      if (govId && !rep.governorate_ids.includes(parseInt(govId))) {
        return false;
      }
      
      if (status === 'active' && !rep.active) return false;
      if (status === 'inactive' && rep.active) return false;
      
      return true;
    }

    function applyFilter() {
      var term = (document.getElementById('repSearch').value || '').trim().toLowerCase();
      var govId = document.getElementById('govFilter').value || '';
      var status = document.getElementById('statusFilter').value || '';
      
      var filtered = [];
      var visibleMarkers = [];
      
      repData.forEach(function(rep) {
        var keep = matches(rep, term, govId, status);
        
        if (keep) {
          filtered.push(rep);
          visibleMarkers = visibleMarkers.concat(repMarkers[rep.id] || []);
        }
      });
      
      // Update marker cluster
      markerClusterGroup.clearLayers();
      visibleMarkers.forEach(function(m) {
        markerClusterGroup.addLayer(m);
      });
      
      renderLegend(filtered);
    }

    // Event listeners
    document.getElementById('repSearch').addEventListener('input', applyFilter);
    document.getElementById('govFilter').addEventListener('change', applyFilter);
    document.getElementById('statusFilter').addEventListener('change', applyFilter);

    // Initial render
    renderLegend(repData);
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>