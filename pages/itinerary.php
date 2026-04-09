<?php

declare(strict_types=1);

require_login();

$basePath = dirname(__DIR__);
$success = '';
$error = '';

// Fetch all governorates
try {
    $governorates = db()->query('SELECT id, name_fr FROM governorates ORDER BY name_fr')->fetchAll();
} catch (Throwable $e) {
    $governorates = [];
}

// Fetch contacts with GPS coordinates
try {
    $contacts = db()->query("SELECT c.id, c.name, c.type, c.latitude, c.longitude, c.city_id, 
        ci.name_fr AS city_name, ci.governorate_id, g.name_fr AS governorate_name
        FROM contacts c
        LEFT JOIN cities ci ON ci.id = c.city_id
        LEFT JOIN governorates g ON g.id = ci.governorate_id
        WHERE c.latitude IS NOT NULL AND c.longitude IS NOT NULL
        ORDER BY c.name")->fetchAll();
} catch (Throwable $e) {
    $contacts = [];
}

// Group contacts by governorate and city
$contactsByGovernorate = [];
foreach ($contacts as $contact) {
    $govId = (int) $contact['governorate_id'];
    $cityId = (int) $contact['city_id'];
    
    if (!isset($contactsByGovernorate[$govId])) {
        $contactsByGovernorate[$govId] = [
            'name' => $contact['governorate_name'],
            'cities' => []
        ];
    }
    
    if (!isset($contactsByGovernorate[$govId]['cities'][$cityId])) {
        $contactsByGovernorate[$govId]['cities'][$cityId] = [
            'id' => $cityId,
            'name' => $contact['city_name'],
            'contacts' => []
        ];
    }
    
    $contactsByGovernorate[$govId]['cities'][$cityId]['contacts'][] = [
        'id' => (int) $contact['id'],
        'name' => $contact['name'],
        'type' => $contact['type'],
        'lat' => (float) $contact['latitude'],
        'lng' => (float) $contact['longitude']
    ];
}

// Reformat cities to array
foreach ($contactsByGovernorate as &$gov) {
    $gov['cities'] = array_values($gov['cities']);
}
unset($gov);

$page_title = 'Planificateur d\'Itineraire';
require __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<div class="space-y-4">
  <!-- Header -->
  <div class="bg-white rounded-2xl shadow-sm p-4">
    <h1 class="text-xl font-bold text-slate-900">Planificateur d'Itineraire</h1>
    <p class="text-sm text-slate-500 mt-1">Planifiez vos visites et optimisez votre route avec l'aide de l'IA</p>
  </div>

  <?php if ($success !== ''): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-2xl p-4 text-sm flex items-center gap-3">
      <i class="fas fa-check-circle text-xl"></i>
      <span><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  <?php endif; ?>

  <?php if ($error !== ''): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-4 text-sm flex items-center gap-3">
      <i class="fas fa-exclamation-circle text-xl"></i>
      <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  <?php endif; ?>

  <!-- Selection Panel -->
  <div class="bg-white rounded-2xl shadow-sm p-4">
    <h2 class="text-base font-semibold text-slate-900 mb-4">Selection</h2>
    
    <div class="space-y-4">
      <!-- Governorate Selection -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-2">
          Gouvernorat <span class="text-red-500">*</span>
        </label>
        <select id="governorateSelect" class="w-full h-12 rounded-xl border border-slate-200 px-3 text-sm">
          <option value="">-- Selectionner un gouvernorat --</option>
          <?php foreach ($governorates as $gov): ?>
            <option value="<?= (int) $gov['id'] ?>">
              <?= htmlspecialchars($gov['name_fr'], ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Delegation Selection -->
      <div id="delegationContainer" class="hidden">
        <label class="block text-sm font-medium text-slate-700 mb-2">
          Delegations <span class="text-xs text-slate-500">(Multiple)</span>
        </label>
        <div id="delegationList" class="space-y-2 max-h-64 overflow-y-auto">
          <!-- Will be populated by JavaScript -->
        </div>
        <button type="button" id="selectAllDelegations" class="mt-2 text-xs text-blue-600 hover:text-blue-800">
          Tout selectionner / Tout deselectionner
        </button>
      </div>

      <!-- Contact Type Filter -->
      <div id="contactTypeContainer" class="hidden">
        <label class="block text-sm font-medium text-slate-700 mb-2">
          Type de contacts
        </label>
        <div class="flex gap-2">
          <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" id="typePharmacie" value="pharmacie" checked class="w-4 h-4 rounded border-slate-300">
            <span>Pharmacie</span>
          </label>
          <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" id="typeParapharmacie" value="parapharmacie" checked class="w-4 h-4 rounded border-slate-300">
            <span>Parapharmacie</span>
          </label>
          <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" id="typeMedecin" value="medecin" checked class="w-4 h-4 rounded border-slate-300">
            <span>Medecin</span>
          </label>
        </div>
      </div>

      <!-- Contact List -->
      <div id="contactContainer" class="hidden">
        <label class="block text-sm font-medium text-slate-700 mb-2">
          Contacts <span class="text-xs text-slate-500">(Multiple)</span>
        </label>
        <div id="contactList" class="space-y-2 max-h-64 overflow-y-auto">
          <!-- Will be populated by JavaScript -->
        </div>
        <button type="button" id="selectAllContacts" class="mt-2 text-xs text-blue-600 hover:text-blue-800">
          Tout selectionner / Tout deselectionner
        </button>
      </div>

      <!-- Optimize Button -->
      <div>
        <button type="button" id="optimizeRoute" class="w-full h-12 rounded-xl bg-green-600 text-white font-semibold flex items-center justify-center gap-2 hover:bg-green-700 active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed" disabled>
          <i class="fas fa-route"></i>
          <span>Optimiser la route avec l'IA</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Map & Route -->
  <div id="routeResult" class="hidden space-y-4">
    <div class="bg-white rounded-2xl shadow-sm p-4">
      <h2 class="text-base font-semibold text-slate-900 mb-4">Itineraire Optimise</h2>
      
      <div id="itineraryMap" class="w-full rounded-xl" style="height: 500px;"></div>
      
      <div class="mt-4">
        <h3 class="text-sm font-semibold text-slate-900 mb-2">Ordre de visite</h3>
        <div id="visitOrder" class="space-y-2">
          <!-- Will be populated by JavaScript -->
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-4">
      <h2 class="text-base font-semibold text-slate-900 mb-4">Statistiques de Route</h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div class="rounded-lg border border-slate-100 p-3">
          <div class="text-xs text-slate-500">Distance totale</div>
          <div id="totalDistance" class="text-lg font-bold text-blue-600">--</div>
        </div>
        <div class="rounded-lg border border-slate-100 p-3">
          <div class="text-xs text-slate-500">Temps estime</div>
          <div id="estimatedTime" class="text-lg font-bold text-green-600">--</div>
        </div>
        <div class="rounded-lg border border-slate-100 p-3">
          <div class="text-xs text-slate-500">Nombre d'arrets</div>
          <div id="totalStops" class="text-lg font-bold text-purple-600">--</div>
        </div>
        <div class="rounded-lg border border-slate-100 p-3">
          <div class="text-xs text-slate-500">Type de contacts</div>
          <div id="contactTypes" class="text-lg font-bold text-amber-600">--</div>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-4">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-semibold text-slate-900">Actions</h2>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <a href="/visits/new" class="h-12 rounded-xl bg-blue-600 text-white font-semibold flex items-center justify-center gap-2 hover:bg-blue-700">
          <i class="fas fa-plus"></i>
          <span>Nouvelle visite</span>
        </a>
        <button type="button" id="openInGoogleMaps" class="h-12 rounded-xl bg-green-600 text-white font-semibold flex items-center justify-center gap-2 hover:bg-green-700">
          <i class="fab fa-google"></i>
          <span>Ouvrir dans Google Maps</span>
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const contactsData = <?= json_encode($contactsByGovernorate, JSON_UNESCAPED_UNICODE) ?>;
let map = null;
let routeLayer = null;
let markers = [];
let selectedContacts = [];
let optimizedRoute = [];

// Governorate selection
document.getElementById('governorateSelect').addEventListener('change', function() {
    const govId = parseInt(this.value);
    const delegationContainer = document.getElementById('delegationContainer');
    const contactTypeContainer = document.getElementById('contactTypeContainer');
    const contactContainer = document.getElementById('contactContainer');
    
    if (!govId) {
        delegationContainer.classList.add('hidden');
        contactTypeContainer.classList.add('hidden');
        contactContainer.classList.add('hidden');
        document.getElementById('routeResult').classList.add('hidden');
        return;
    }
    
    const governorate = contactsData[govId];
    if (!governorate) return;
    
    // Show delegations
    delegationContainer.classList.remove('hidden');
    const delegationList = document.getElementById('delegationList');
    delegationList.innerHTML = '';
    
    governorate.cities.forEach(city => {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 p-2 rounded-lg border border-slate-200 hover:bg-slate-50';
        div.innerHTML = `
            <input type="checkbox" class="delegation-checkbox w-4 h-4 rounded border-slate-300" data-city-id="${city.id}" data-city-name="${city.name}">
            <span class="text-sm text-slate-700">${city.name}</span>
            <span class="text-xs text-slate-500">(${city.contacts.length} contacts)</span>
        `;
        delegationList.appendChild(div);
    });
    
    // Show contact type filter
    contactTypeContainer.classList.remove('hidden');
    contactContainer.classList.add('hidden');
    document.getElementById('routeResult').classList.add('hidden');
    
    selectedContacts = [];
});

// Select all delegations toggle
document.getElementById('selectAllDelegations').addEventListener('click', function() {
    const checkboxes = document.querySelectorAll('.delegation-checkbox');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
    
    // Trigger change to update contact list
    updateContactList();
});

// Contact type filter change
['typePharmacie', 'typeParapharmacie', 'typeMedecin'].forEach(id => {
    document.getElementById(id).addEventListener('change', updateContactList);
});

function updateContactList() {
    const checkedCities = Array.from(document.querySelectorAll('.delegation-checkbox:checked'))
        .map(cb => parseInt(cb.dataset.cityId));
    
    if (checkedCities.length === 0) {
        document.getElementById('contactContainer').classList.add('hidden');
        document.getElementById('routeResult').classList.add('hidden');
        return;
    }
    
    const govId = parseInt(document.getElementById('governorateSelect').value);
    const governorate = contactsData[govId];
    if (!governorate) return;
    
    // Get selected types
    const selectedTypes = [];
    if (document.getElementById('typePharmacie').checked) selectedTypes.push('pharmacie');
    if (document.getElementById('typeParapharmacie').checked) selectedTypes.push('parapharmacie');
    if (document.getElementById('typeMedecin').checked) selectedTypes.push('medecin');
    
    // Collect contacts from selected cities and types
    const contactList = [];
    checkedCities.forEach(cityId => {
        const city = governorate.cities.find(c => c.id === cityId);
        if (!city) return;
        
        city.contacts.forEach(contact => {
            if (selectedTypes.includes(contact.type)) {
                contactList.push(contact);
            }
        });
    });
    
    // Display contacts
    const contactContainer = document.getElementById('contactContainer');
    contactContainer.classList.remove('hidden');
    const listDiv = document.getElementById('contactList');
    listDiv.innerHTML = '';
    
    contactList.forEach(contact => {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 p-2 rounded-lg border border-slate-200 hover:bg-slate-50';
        div.innerHTML = `
            <input type="checkbox" class="contact-checkbox w-4 h-4 rounded border-slate-300" 
                data-contact-id="${contact.id}" 
                data-lat="${contact.lat}" 
                data-lng="${contact.lng}"
                data-name="${contact.name}"
                data-type="${contact.type}">
            <div class="flex-1">
                <span class="text-sm text-slate-900">${contact.name}</span>
                <span class="text-xs text-slate-500 ml-2">(${contact.type})</span>
            </div>
            <i class="fas fa-map-marker-alt text-slate-400"></i>
        `;
        listDiv.appendChild(div);
    });
    
    document.getElementById('optimizeRoute').disabled = contactList.length < 2;
}

// Select all contacts toggle
document.getElementById('selectAllContacts').addEventListener('click', function() {
    const checkboxes = document.querySelectorAll('.contact-checkbox');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
});

// Optimize route button
document.getElementById('optimizeRoute').addEventListener('click', function() {
    const checkedContacts = Array.from(document.querySelectorAll('.contact-checkbox:checked'))
        .map(cb => ({
            id: parseInt(cb.dataset.contactId),
            lat: parseFloat(cb.dataset.lat),
            lng: parseFloat(cb.dataset.lng),
            name: cb.dataset.name,
            type: cb.dataset.type
        }));
    
    if (checkedContacts.length < 2) {
        alert('Selectionnez au moins 2 contacts pour optimiser la route.');
        return;
    }
    
    selectedContacts = checkedContacts;
    optimizedRoute = optimizeRouteWithNearestNeighbor(selectedContacts);
    displayRoute();
});

// Nearest neighbor algorithm for route optimization
function optimizeRouteWithNearestNeighbor(contacts) {
    if (contacts.length < 2) return contacts;
    
    const unvisited = [...contacts];
    const route = [unvisited.shift()];
    
    while (unvisited.length > 0) {
        const current = route[route.length - 1];
        let nearest = null;
        let minDistance = Infinity;
        let nearestIndex = 0;
        
        for (let i = 0; i < unvisited.length; i++) {
            const distance = calculateDistance(
                current.lat, current.lng,
                unvisited[i].lat, unvisited[i].lng
            );
            
            if (distance < minDistance) {
                minDistance = distance;
                nearest = unvisited[i];
                nearestIndex = i;
            }
        }
        
        route.push(nearest);
        unvisited.splice(nearestIndex, 1);
    }
    
    return route;
}

// Calculate distance between two points (Haversine formula)
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth's radius in km
    const dLat = toRadians(lat2 - lat1);
    const dLon = toRadians(lon2 - lon1);
    
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distance = R * c;
    
    return distance;
}

function toRadians(degrees) {
    return degrees * (Math.PI / 180);
}

// Display route on map
function displayRoute() {
    document.getElementById('routeResult').classList.remove('hidden');
    
    // Initialize map if not already done
    if (!map) {
        map = L.map('itineraryMap').setView([optimizedRoute[0].lat, optimizedRoute[0].lng], 11);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 17,
            attribution: '&copy; CARTO & OpenStreetMap contributors'
        }).addTo(map);
    } else {
        // Clear existing markers and route
        markers.forEach(m => map.removeLayer(m));
        if (routeLayer) {
            map.removeLayer(routeLayer);
        }
    }
    
    // Calculate route coordinates and total distance
    const routeCoords = optimizedRoute.map(c => [c.lat, c.lng]);
    let totalDistance = 0;
    
    for (let i = 0; i < optimizedRoute.length - 1; i++) {
        totalDistance += calculateDistance(
            optimizedRoute[i].lat, optimizedRoute[i].lng,
            optimizedRoute[i + 1].lat, optimizedRoute[i + 1].lng
        );
    }
    
    // Draw route line
    routeLayer = L.polyline(routeCoords, {
        color: '#22c55e',
        weight: 4,
        opacity: 0.8,
        dashArray: '10, 10'
    }).addTo(map);
    
    // Add markers for each stop
    markers = optimizedRoute.map((contact, index) => {
        const isStart = index === 0;
        const isEnd = index === optimizedRoute.length - 1;
        
        let color = '#3b82f6';
        if (isStart) color = '#22c55e';
        if (isEnd) color = '#ef4444';
        
        const marker = L.circleMarker([contact.lat, contact.lng], {
            radius: 10,
            color: color,
            fillColor: color,
            fillOpacity: 0.9,
            weight: 3
        });
        
        const popupContent = `
            <div class="text-sm">
                <strong class="text-slate-900">${index + 1}. ${contact.name}</strong><br>
                <span class="text-slate-600">${contact.type}</span><br>
                ${isStart ? '<span class="text-green-600 font-medium">Depart</span>' : ''}
                ${isEnd ? '<span class="text-red-600 font-medium">Arrivee</span>' : ''}
            </div>
        `;
        
        marker.bindPopup(popupContent);
        marker.addTo(map);
        
        return marker;
    });
    
    // Fit map to show all markers
    const group = L.featureGroup(markers);
    map.fitBounds(group.getBounds().pad(0.1));
    
    // Update visit order
    const visitOrder = document.getElementById('visitOrder');
    visitOrder.innerHTML = '';
    
    optimizedRoute.forEach((contact, index) => {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-3 p-3 rounded-lg border border-slate-200 bg-slate-50';
        div.innerHTML = `
            <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm">
                ${index + 1}
            </div>
            <div class="flex-1">
                <div class="text-sm font-medium text-slate-900">${contact.name}</div>
                <div class="text-xs text-slate-500">${contact.type}</div>
            </div>
            <a href="/visits/new?contact_id=${contact.id}" class="text-xs text-blue-600 hover:text-blue-800">
                <i class="fas fa-plus"></i> Visite
            </a>
        `;
        visitOrder.appendChild(div);
    });
    
    // Update statistics
    document.getElementById('totalDistance').textContent = totalDistance.toFixed(1) + ' km';
    document.getElementById('estimatedTime').textContent = Math.round(totalDistance * 1.5) + ' min';
    document.getElementById('totalStops').textContent = optimizedRoute.length;
    
    // Count contact types
    const typeCount = {};
    optimizedRoute.forEach(c => {
        typeCount[c.type] = (typeCount[c.type] || 0) + 1;
    });
    const typeLabels = Object.entries(typeCount).map(([type, count]) => `${type}: ${count}`).join(', ');
    document.getElementById('contactTypes').textContent = typeLabels;
    
    // Scroll to results
    document.getElementById('routeResult').scrollIntoView({ behavior: 'smooth' });
}

// Open in Google Maps
document.getElementById('openInGoogleMaps').addEventListener('click', function() {
    if (optimizedRoute.length < 2) return;
    
    const origin = `${optimizedRoute[0].lat},${optimizedRoute[0].lng}`;
    const destination = `${optimizedRoute[optimizedRoute.length - 1].lat},${optimizedRoute[optimizedRoute.length - 1].lng}`;
    const waypoints = optimizedRoute.slice(1, -1).map(c => `${c.lat},${c.lng}`).join('|');
    
    const url = `https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${destination}&waypoints=${waypoints}&travelmode=driving`;
    window.open(url, '_blank');
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>