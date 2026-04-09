# RepTrack — Cahier des Charges Complet V2
### Application CRM Terrain pour Délégués Médicaux — Secteur Compléments Alimentaires — Tunisie

---

## 1. Vision Produit

**RepTrack** est une application web PHP mobile-first conçue pour les laboratoires de compléments alimentaires en Tunisie. Elle permet à un responsable (admin) de piloter son équipe de délégués médicaux terrain, et à chaque délégué de gérer ses visites, son stock, ses tournées et ses notes de frais depuis son smartphone.

### Valeur métier
| Pour l'Admin | Pour le Délégué |
|---|---|
| Vision temps réel des performances de chaque rep | Dashboard personnel avec KPI vs objectifs |
| Planning de visites imposé par cycle | Planning reçu, suivi pas à pas |
| Comparatif entre délégués | Alertes visites en retard |
| Reporting fiable exportable | Saisie rapide sur mobile terrain |
| Validation notes de frais | Soumission frais avec photo justificatif |

---

## 2. Stack Technique

| Couche | Technologie |
|---|---|
| Backend | PHP 8.x — architecture MVC sans framework |
| Base de données | MySQL 8.x |
| Dependances & Autoload | Composer + autoload PSR-4 + `.env` via `vlucas/phpdotenv` |
| Migrations DB | Phinx (dependance prod) ou runner maison minimal |
| CSS Framework | **Tailwind CSS** — CDN en prototypage, build en production (CLI + purge) |
| Interactivité UI | **Alpine.js** (via CDN) — dropdowns, modals, steppers, toggles |
| Graphiques | Chart.js 4.x |
| Carte interactive | Leaflet.js (OpenStreetMap — 100% gratuit) |
| Icônes | Heroicons (SVG inline) |
| Typographie | Google Fonts — Inter |
| Notifications | In-app (DB polling) + Email (PHPMailer) |
| Upload fichiers | PHP natif — stockage hors web root (`/storage/uploads`) + endpoint sécurisé |
| Auth | Sessions PHP + bcrypt |
| Export | CSV/Excel PHP natif + CSS print |

### Structure des dossiers
```
/reptrack
  /public
    /assets
      /css        → app.css (Tailwind build), mobile.css
      /js         → app.js, charts.js, map.js, notifications.js
    index.php
    logout.php
  /storage
    /uploads
      /contacts   → photos point de vente (hors web root)
      /expenses   → justificatifs notes de frais
      /trainings  → supports de formation (PDF)
  /config
    db.php        → connexion PDO
    auth.php      → helpers session/role
    mailer.php    → config PHPMailer
  /pages
    dashboard.php
    visits.php
    contacts.php
    planning.php
    products.php
    stock.php
    expenses.php
    reports.php
    map.php
    users.php         → admin seulement
    notifications.php
  /includes
    header.php
    navbar_bottom.php → navigation mobile
    footer.php
    kpi_widgets.php
  /api
    get_cities.php
    get_contacts.php
    save_visit.php
    update_stock.php
    get_map_data.php
    get_notifications.php
    mark_notification_read.php
    save_expense.php
    validate_expense.php
    get_dashboard_kpi.php
    download_file.php    → téléchargement sécurisé fichiers uploadés
  /cron
    check_overdue.php     → lance chaque nuit
    send_reminders.php    → emails de rappel
  /migrations
  /vendor
  composer.json
  composer.lock
  .env
  .env.example
```

**Note config serveur** : le web server doit pointer sur `/public` (DocumentRoot). Ainsi, `/storage/uploads` reste hors web root et accessible uniquement via endpoint sécurisé.

**Note migrations (Phinx)** : exemple de workflow minimal : `vendor/bin/phinx create AddUsersTable` puis `vendor/bin/phinx migrate`.

---

## 3. Schema Base de Donnees Complet

### 3.1 `users`
```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'rep') DEFAULT 'rep',
  phone VARCHAR(30),
  zone VARCHAR(100),
  active TINYINT(1) DEFAULT 1,
  last_login DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 3.2 `governorates`
```sql
CREATE TABLE governorates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name_fr VARCHAR(100) NOT NULL,
  name_ar VARCHAR(100),
  latitude DECIMAL(10,7),
  longitude DECIMAL(10,7)
);
```

### 3.3 `cities`
```sql
CREATE TABLE cities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  governorate_id INT NOT NULL,
  name_fr VARCHAR(100) NOT NULL,
  name_ar VARCHAR(100),
  latitude DECIMAL(10,7),
  longitude DECIMAL(10,7),
  FOREIGN KEY (governorate_id) REFERENCES governorates(id)
);
```

### 3.4 `contacts` (Points de vente enrichis)
```sql
CREATE TABLE contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('doctor','pharmacy','parapharmacie','clinic','hospital') NOT NULL,
  name VARCHAR(150) NOT NULL,
  specialty VARCHAR(100),
  establishment VARCHAR(150),
  governorate_id INT NOT NULL,
  city_id INT NOT NULL,
  address TEXT,
  latitude DECIMAL(10,7),
  longitude DECIMAL(10,7),
  phone VARCHAR(30),
  email VARCHAR(150),
  contact_person VARCHAR(100),
  -- Segmentation
  status ENUM('chain','independent','group','hospital_public','clinic_private') DEFAULT 'independent',
  potential ENUM('A','B','C') DEFAULT 'B',
  client_type ENUM('local','tourist','specialized','mixed') DEFAULT 'local',
  collaboration_history ENUM('new','occasional','regular','key_account') DEFAULT 'new',
  plv_present TINYINT(1) DEFAULT 0,
  team_engagement ENUM('low','medium','high') DEFAULT 'medium',
  specific_needs TEXT,
  visit_frequency_days INT DEFAULT 30,
  assigned_rep_id INT,
  added_by INT,
  notes TEXT,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (governorate_id) REFERENCES governorates(id),
  FOREIGN KEY (city_id) REFERENCES cities(id),
  FOREIGN KEY (assigned_rep_id) REFERENCES users(id),
  FOREIGN KEY (added_by) REFERENCES users(id)
);
```

### 3.5 `contact_photos`
```sql
CREATE TABLE contact_photos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  contact_id INT NOT NULL,
  user_id INT NOT NULL,
  visit_id INT,
  filename VARCHAR(255) NOT NULL,
  caption VARCHAR(255),
  taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (contact_id) REFERENCES contacts(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### 3.6 `contact_reminders`
```sql
CREATE TABLE contact_reminders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  contact_id INT NOT NULL,
  user_id INT NOT NULL,
  reminder_type ENUM('visit','plv_return','quote_followup','other') NOT NULL,
  reminder_date DATE NOT NULL,
  note TEXT,
  sent TINYINT(1) DEFAULT 0,
  completed TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (contact_id) REFERENCES contacts(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### 3.7 `products`
```sql
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  unit VARCHAR(50) DEFAULT 'boite',
  category VARCHAR(100),
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 3.8 `stock`
```sql
CREATE TABLE stock (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT DEFAULT 0,
  alert_threshold INT DEFAULT 10,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY (user_id, product_id),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);
```

### 3.9 `stock_movements`
```sql
CREATE TABLE stock_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  movement_type ENUM('restock','visit_distribution','adjustment','return') NOT NULL,
  quantity INT NOT NULL,
  reference_visit_id INT,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);
```

### 3.10 `visit_cycles`
```sql
CREATE TABLE visit_cycles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  user_id INT NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  target_visits INT,
  notes TEXT,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);
```

### 3.11 `planned_visits`
```sql
CREATE TABLE planned_visits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cycle_id INT,
  user_id INT NOT NULL,
  contact_id INT NOT NULL,
  planned_date DATE NOT NULL,
  visit_type ENUM('standard','training','key_account','animation') DEFAULT 'standard',
  objectives TEXT,
  products_to_present TEXT,
  status ENUM('pending','completed','missed','rescheduled') DEFAULT 'pending',
  actual_visit_id INT,
  admin_notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cycle_id) REFERENCES visit_cycles(id),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (contact_id) REFERENCES contacts(id)
);
```

### 3.12 `visits`
```sql
CREATE TABLE visits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  contact_id INT NOT NULL,
  planned_visit_id INT,
  governorate_id INT NOT NULL,
  city_id INT NOT NULL,
  visit_type ENUM('standard','training','key_account','animation') DEFAULT 'standard',
  visited_at DATETIME NOT NULL,
  duration_minutes INT,
  brochure_given TINYINT(1) DEFAULT 0,
  brochure_qty INT DEFAULT 0,
  contact_receptivity ENUM('very_good','good','neutral','difficult') DEFAULT 'good',
  order_taken TINYINT(1) DEFAULT 0,
  order_amount DECIMAL(10,2),
  next_action TEXT,
  notes TEXT,
  latitude DECIMAL(10,7),
  longitude DECIMAL(10,7),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (contact_id) REFERENCES contacts(id),
  FOREIGN KEY (governorate_id) REFERENCES governorates(id),
  FOREIGN KEY (city_id) REFERENCES cities(id),
  FOREIGN KEY (planned_visit_id) REFERENCES planned_visits(id)
);
```

### 3.13 `visit_samples`
```sql
CREATE TABLE visit_samples (
  id INT AUTO_INCREMENT PRIMARY KEY,
  visit_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  FOREIGN KEY (visit_id) REFERENCES visits(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);
```

### 3.14 `visit_training_details`
```sql
CREATE TABLE visit_training_details (
  id INT AUTO_INCREMENT PRIMARY KEY,
  visit_id INT NOT NULL UNIQUE,
  expected_participants INT,
  actual_participants INT,
  modules_covered TEXT,
  questions_raised TEXT,
  support_files TEXT,
  pedagogical_objectives TEXT,
  outcome ENUM('excellent','good','average','poor') DEFAULT 'good',
  FOREIGN KEY (visit_id) REFERENCES visits(id)
);
```

### 3.15 `kpi_objectives`
```sql
CREATE TABLE kpi_objectives (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  period_month INT NOT NULL,
  period_year INT NOT NULL,
  target_visits_per_day DECIMAL(4,1),
  target_visits_per_cycle INT,
  target_new_contacts INT,
  target_samples_distributed INT,
  target_brochures_given INT,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);
```

### 3.16 `expenses`
```sql
CREATE TABLE expenses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  expense_date DATE NOT NULL,
  type ENUM('fuel','meal','parking','toll','accommodation','other') NOT NULL,
  amount DECIMAL(10,3) NOT NULL,
  description TEXT,
  receipt_filename VARCHAR(255),
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  validated_by INT,
  validation_date DATETIME,
  rejection_reason TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (validated_by) REFERENCES users(id)
);
```

### 3.17 `notifications`
```sql
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type ENUM('overdue_visit','low_stock','planned_visit_today','expense_validated',
            'expense_rejected','reminder','new_planning','objective_alert') NOT NULL,
  title VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  link VARCHAR(255),
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);
```

---

## 4. Seed Data — 24 Gouvernorats de Tunisie

```sql
INSERT INTO governorates (name_fr, name_ar, latitude, longitude) VALUES
('Tunis','تونس',36.8190,10.1657),
('Ariana','أريانة',36.8625,10.1956),
('Ben Arous','بن عروس',36.7533,10.2281),
('Manouba','منوبة',36.8100,10.0942),
('Nabeul','نابل',36.4513,10.7357),
('Zaghouan','زغوان',36.4029,10.1427),
('Bizerte','بنزرت',37.2746,9.8739),
('Beja','باجة',36.7256,9.1817),
('Jendouba','جندوبة',36.5011,8.7778),
('Le Kef','الكاف',36.1674,8.7046),
('Siliana','سليانة',36.0849,9.3708),
('Sousse','سوسة',35.8245,10.6346),
('Monastir','المنستير',35.7643,10.8113),
('Mahdia','المهدية',35.5047,11.0622),
('Sfax','صفاقس',34.7406,10.7603),
('Kairouan','القيروان',35.6781,10.0963),
('Kasserine','القصرين',35.1722,8.8305),
('Sidi Bouzid','سيدي بوزيد',35.0381,9.4849),
('Gabes','قابس',33.8833,10.1000),
('Medenine','مدنين',33.3550,10.5053),
('Tataouine','تطاوين',32.9211,10.4519),
('Gafsa','قفصة',34.4250,8.7842),
('Tozeur','توزر',33.9197,8.1335),
('Kebili','قبلي',33.7046,8.9717);
```

---

## 5. Roles & Permissions

| Fonctionnalite | Admin | Delegue (Rep) |
|---|---|---|
| Voir tous les delegues | Oui | Non |
| Creer/modifier utilisateurs | Oui | Non |
| Creer plannings de visite | Oui | Non — recoit seulement |
| Definir objectifs KPI | Oui | Non — voit ses objectifs |
| Valider notes de frais | Oui | Non |
| Voir rapports toute l'equipe | Oui | Ses donnees uniquement |
| Gerer produits et stock global | Oui | Son stock uniquement |
| Ajouter contacts | Oui | Oui |
| Saisir une visite | Oui | Oui |
| Soumettre notes de frais | Oui | Oui |
| Voir carte interactive | Oui — toute l'equipe | Oui — ses visites |

---

## 6. Ecrans & Flux Detailles

### 6.1 Dashboard Delegue (mobile-first)

**Barre superieure**
- Salutation + date du jour
- Cloche notifications avec badge rouge (non lues)

**Zone 1 — Visites du jour (planning)**
- Liste des visites planifiees aujourd'hui par l'admin
- Statut : En attente | Effectuee | En retard
- Bouton "Demarrer visite" sur chaque carte
- Si aucun planning : affiche contacts les plus en retard a visiter

**Zone 2 — KPI du mois vs Objectifs**
```
Visites realisees     : 42 / 60   → barre de progression 70%
Nouveaux contacts     :  3 / 5    → barre de progression 60%
Echantillons donnes   : 87 / 120  → barre de progression 73%
Visites en retard     : 5         → badge rouge cliquable
```

**Zone 3 — Alertes stock**
- Produits sous seuil d'alerte en cartes rouges/oranges
- Bouton "Signaler besoin reapprovisionnement"

**Zone 4 — Rappels du jour**
- Rappels programmees arrivant a echeance
- Clic sur rappel → fiche contact concernee

---

### 6.2 Dashboard Admin

**Rangee 1 — Vue equipe temps reel**
- Carte par delegue : visites aujourd'hui | visites ce mois | retards | % objectif
- Classement performance par % d'objectif atteint

**Rangee 2 — KPI globaux equipe**
- Total visites ce mois (toute l'equipe)
- Total echantillons distribues
- Taux de couverture : % contacts visites ce mois
- Notes de frais en attente de validation (badge urgent)

**Rangee 3 — Carte Leaflet interactive**
- Marqueurs par type de contact (couleurs distinctes)
- Filtre : delegue | gouvernorat | type | statut (visite/retard/jamais visite)
- Clic marqueur → popup : nom, type, derniere visite, delegue, bouton action

**Rangee 4 — Graphiques Chart.js**
- Visites par semaine par delegue (lignes multiples 12 semaines)
- Echantillons par produit ce mois (donut)
- Contacts en retard par gouvernorat (barres horizontales)
- Realise vs Objectif par delegue (barres groupees)

---

### 6.3 Flux Nouvelle Visite (Mobile — 4 etapes)

**Etape 1 — Localisation**
- Selecteur Gouvernorat → Ville (cascade AJAX)
- Option GPS : "Utiliser ma position actuelle"

**Etape 2 — Contact**
- Boutons type (larges, tap-friendly) :
  Medecin | Pharmacie | Parapharmacie | Clinique | Hopital
- Recherche live : filtre ville + type, resultats en cartes
- Badge potentiel A/B/C visible sur chaque carte
- "Nouveau contact" → formulaire inline avec tous les champs de segmentation

**Etape 3 — Details de la visite**
- Type : Standard | Formation | Compte Cle | Animation
- Receptivite : Tres bon | Bon | Neutre | Difficile
- Brochure donnee : Oui/Non + quantite
- Echantillons : selecteur quantite par produit (stock restant visible en temps reel)
- Commande prise : Oui/Non → si oui, montant estime
- Duree de visite (optionnel)
- Notes + prochaine action
- Photo : bouton appareil photo → rattachee au contact ET a la visite

**Etape 3b — Si type = Formation (ecran supplementaire)**
- Participants prevus / participants reels
- Modules abordes (champ texte libre)
- Questions posees (champ texte libre)
- Resultat global : Excellent / Bon / Moyen / Insuffisant
- Pieces jointes : upload PDF ou saisie lien

**Etape 4 — Confirmation & Sauvegarde**
- Recapitulatif complet de la visite
- Date et heure automatiques (non modifiables)
- Deduction stock affichee avant validation
- Bouton "Enregistrer la visite"
- Succes → toast vert + retour dashboard
- Echec stock → avertissement, demande confirmation

---

### 6.4 Fiche Contact Enrichie

**En-tete**
- Nom, type (badge colore), potentiel A/B/C, statut collaboration
- Ville, gouvernorat, adresse + lien carte Leaflet
- Telephone (clic to call), email, interlocuteur principal
- Badge : Prochaine visite dans X jours / EN RETARD (rouge)

**Onglet Informations**
- Tous les champs de segmentation (statut, type clientele, potentiel, PLV, engagement)
- Champ "Besoins specifiques" (champ libre)
- Frequence de visite contractuelle personnalisee
- Delegue attitré

**Onglet Visites**
- Historique chronologique avec type, receptivite, echantillons, notes
- Stats : total visites | frequence reelle | derniere visite
- Prochaine visite due : date calculee en surbrillance

**Onglet Produits**
- Total echantillons recus par produit (tous temps)
- Derniere presentation par produit
- Commandes enregistrees

**Onglet Photos**
- Galerie photos terrain (PLV, vitrine, rayon)
- Date + auteur (delegue)
- Legende editables

**Onglet Rappels**
- Rappels actifs pour ce contact
- Ajouter rappel : date + type (visite / retour PLV / relance / autre) + note
- Historique rappels completes

---

### 6.5 Planning des Visites

**Vue Admin**
- Selecteur : Delegue + Cycle (periode)
- Vue calendrier mensuel : visites planifiees en blocs colores
- Ajouter visite planifiee : contact + date + type + objectifs + produits a presenter
- Statistiques du cycle : planifie | realise | manque | taux de completion

**Vue Delegue (lecture seule)**
- Mon planning du mois en vue liste ET vue calendrier
- Chaque visite planifiee : contact, date, type, objectifs de l'admin
- Statuts visuels : A faire | Fait | Manque | Aujourd'hui
- Bouton "Demarrer" → redirige vers nouvelle visite pre-remplie (contact + type + objectifs)

---

### 6.6 Carte Interactive — Leaflet.js

**Donnees affichees**
- Tous les contacts avec coordonnees GPS (latitude/longitude)
- Marqueurs colores par type :
  Medecin=bleu | Pharmacie=vert | Parapharmacie=violet | Clinique=rouge | Hopital=orange
- Indicateur visite recente : icone check sur le marqueur si visite < 30j
- Indicateur retard : contour rouge si en retard

**Filtres**
- Par gouvernorat (zoom automatique sur la zone)
- Par delegue (admin uniquement : filtre multi-delegues)
- Par type de contact
- Par potentiel (A / B / C)
- Par statut : tous | visites ce mois | en retard | jamais visites

**Interaction**
- Clic sur marqueur → popup : nom, type, derniere visite, delegue attitré, bouton "Nouvelle visite"
- Clustering automatique (Leaflet.markercluster) sur zoom large
- Bouton "Ma position" (GPS) pour centrer sur le rep

**Implementation technique**
```javascript
// Tuiles gratuites OpenStreetMap (aucune cle API)
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

// Centre initial : Tunisie entiere
const map = L.map('map').setView([34.0, 9.0], 7);

// Clustering
const cluster = L.markerClusterGroup();
cluster.addLayer(marker);
map.addLayer(cluster);
```

---

### 6.7 Notes de Frais

**Vue Delegue — Saisie**
- Formulaire : Date | Type | Montant TND | Description | Photo justificatif (upload)
- Types : Carburant | Repas | Parking | Peage | Hebergement | Autre
- Liste mes frais du mois : statut en attente / valide / rejete
- Total valide du mois en haut de page
- Notification push/email lors de validation ou rejet avec motif

**Vue Admin — Validation**
- Liste des frais en attente (toute l'equipe), par delegue
- Clic → detail complet + photo justificatif en grand
- Boutons : Valider | Rejeter (motif obligatoire si rejet)
- Tableau recapitulatif mensuel par delegue : total demande | total valide
- Export CSV des frais valides par mois

---

### 6.8 Rapports

**Onglet 1 — Visites**
- Filtres : periode | gouvernorat | ville | delegue | type de visite
- Table : date | delegue | contact | type | receptivite | echantillons | brochures | commande
- Graphique visites par semaine
- Export CSV

**Onglet 2 — Echantillons**
- Par produit : distribue ce mois | distribue total | stock restant par delegue
- Donut par produit
- Top 10 contacts : plus d'echantillons recus

**Onglet 3 — Couverture Terrain**
- Par gouvernorat : nb contacts total | visites ce mois | jamais visites | en retard
- % de couverture par zone en barres
- Tableau exportable

**Onglet 4 — Performance Delegues** (Admin uniquement)
- Tableau comparatif : delegue | visites | objectif | % | nouveaux contacts | echantillons
- Graphique barres groupees : realise vs objectif par delegue
- Classement mensuel (podium)

**Onglet 5 — Contacts en retard**
- Liste : contact | type | ville | delegue | derniere visite | jours de retard
- Trie par jours de retard decroissant
- Filtre par gouvernorat et delegue
- Export CSV / impression

**Onglet 6 — Formations**
- Visites type formation uniquement
- Participants, modules, resultats
- Contacts formes par produit

---

### 6.9 Gestion Utilisateurs (Admin)

- Table : nom | email | role | zone | actif | derniere connexion | nb visites ce mois
- Creer delegue : nom, email, mot de passe initial, zone assignee, role
- Modifier / Desactiver / Reinitialiser mot de passe
- Mini-KPI inline par delegue : visites ce mois, stock, derniere visite

---

## 7. Logique Metier Cle

### 7.1 Prochaine Visite & Retard
```
frequence = contact.visit_frequency_days  (personnalisee, defaut 30j)
prochaine_visite = derniere_visite.visited_at + frequence
jours_retard = AUJOURD_HUI - prochaine_visite

Couleur : rouge si > 0j retard | orange si dans < 5j | vert si ok
```

### 7.2 Deduction Stock a la Sauvegarde
```php
foreach ($samples as $product_id => $qty) {
    // Deduire
    UPDATE stock SET quantity = quantity - $qty
    WHERE user_id = $rep_id AND product_id = $product_id;

    // Tracer le mouvement
    INSERT INTO stock_movements
    (user_id, product_id, movement_type, quantity, reference_visit_id)
    VALUES ($rep_id, $product_id, 'visit_distribution', -$qty, $visit_id);

    // Verifier seuil d'alerte
    $new_qty = SELECT quantity FROM stock WHERE ...;
    if ($new_qty <= $alert_threshold) {
        INSERT INTO notifications (user_id, type, title, message)
        VALUES ($rep_id, 'low_stock', 'Stock bas - Produit X', "$new_qty unites restantes");
    }
}
```

### 7.3 KPI Realise vs Objectif
```sql
SELECT
  o.target_visits_per_cycle,
  COUNT(v.id) AS actual_visits,
  ROUND(COUNT(v.id) / o.target_visits_per_cycle * 100, 1) AS achievement_pct
FROM kpi_objectives o
LEFT JOIN visits v
  ON v.user_id = o.user_id
  AND MONTH(v.visited_at) = o.period_month
  AND YEAR(v.visited_at) = o.period_year
WHERE o.user_id = :rep_id
  AND o.period_month = MONTH(NOW())
  AND o.period_year = YEAR(NOW());
```

### 7.4 Taux de Couverture par Gouvernorat
```sql
SELECT
  g.name_fr,
  COUNT(DISTINCT c.id) AS total_contacts,
  COUNT(DISTINCT v.contact_id) AS visited_this_month,
  ROUND(COUNT(DISTINCT v.contact_id) / COUNT(DISTINCT c.id) * 100, 1) AS coverage_pct
FROM contacts c
JOIN governorates g ON c.governorate_id = g.id
LEFT JOIN visits v ON v.contact_id = c.id
  AND MONTH(v.visited_at) = MONTH(NOW())
  AND YEAR(v.visited_at) = YEAR(NOW())
GROUP BY g.id
ORDER BY coverage_pct DESC;
```

### 7.5 Frequence Reelle par Zone
```sql
SELECT
  g.name_fr AS governorate,
  COUNT(v.id) AS total_visits,
  COUNT(DISTINCT v.contact_id) AS unique_contacts_visited,
  ROUND(COUNT(v.id) / DATEDIFF(NOW(), MIN(v.visited_at)) * 30, 1) AS avg_visits_per_month
FROM visits v
JOIN governorates g ON v.governorate_id = g.id
WHERE v.user_id = :rep_id
  AND v.visited_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
GROUP BY g.id
ORDER BY total_visits DESC;
```

---

## 8. Systeme de Notifications

### Declencheurs

| Type | Evenement | Destinataire |
|---|---|---|
| overdue_visit | Contact non visite depuis frequence + 3j (cron nuit) | Delegue attitré |
| planned_visit_today | Visite planifiee aujourd'hui (cron 7h matin) | Delegue |
| low_stock | Stock descend sous seuil a l'enregistrement visite | Delegue |
| expense_validated | Admin valide note de frais | Delegue |
| expense_rejected | Admin rejette note de frais | Delegue |
| new_planning | Admin cree ou modifie planning | Delegue |
| reminder | Date d'un rappel contact atteinte (cron nuit) | Delegue |
| objective_alert | Realisation < 50% objectif a J-10 du mois (cron) | Delegue + Admin |

### In-app (polling AJAX)
```javascript
// Verification toutes les 60 secondes
setInterval(function() {
  fetch('/api/get_notifications.php')
    .then(r => r.json())
    .then(data => {
      document.getElementById('notif-badge').textContent = data.unread_count;
      // Mettre a jour la liste dropdown
    });
}, 60000);
```

### Email (PHPMailer + Cron)
- Cron execute `/cron/send_reminders.php` chaque nuit a 7h00
- Email HTML responsive : visites en retard du jour, rappels programmees, alerte objectif

---

## 9. Graphiques Chart.js — Specifications

| Graphique | Type | Page | Source SQL |
|---|---|---|---|
| Visites par semaine 12 semaines | Line multi-series | Dashboard admin | COUNT visits GROUP BY week, user |
| Realise vs Objectif delegues | Bar groupe | Dashboard admin | kpi_objectives vs COUNT visits |
| Echantillons par produit ce mois | Donut | Dashboard | SUM visit_samples GROUP BY product |
| Stock restant par produit | Bar horizontal | Stock | stock.quantity |
| Visites par gouvernorat ce mois | Bar horizontal | Dashboard | COUNT visits GROUP BY governorate |
| Couverture par zone % | Bar + ligne seuil | Rapports | coverage_pct par gouvernorat |
| KPI personnel vs objectif | Barres de progression | Dashboard delegue | realise / objectif per KPI |
| Notes de frais par type | Donut | Depenses | SUM expenses GROUP BY type |

---

## 10. UI Design System Complet

### 10.1 Stack UI Définitif
```html
<!-- Dans chaque <head> PHP -->

<!-- Tailwind CSS Play CDN -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Alpine.js -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Leaflet.js -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Google Fonts : Inter -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Config Tailwind custom -->
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: { sans: ['Inter', 'sans-serif'] },
        colors: {
          primary:  { DEFAULT: '#2563EB', light: '#EFF6FF', dark: '#1D4ED8' },
          success:  { DEFAULT: '#16A34A', light: '#F0FDF4' },
          danger:   { DEFAULT: '#DC2626', light: '#FEF2F2' },
          warning:  { DEFAULT: '#D97706', light: '#FFFBEB' },
          surface:  '#F8FAFC',
          card:     '#FFFFFF',
        }
      }
    }
  }
</script>
```

---

### 10.2 Palette de Couleurs

| Rôle | Couleur | Hex | Usage |
|---|---|---|---|
| **Primaire** | Bleu | `#2563EB` | Boutons principaux, liens, accents |
| **Succès** | Vert | `#16A34A` | Visite effectuée, stock OK, validé |
| **Danger** | Rouge | `#DC2626` | Retard critique, stock vide, rejeté |
| **Warning** | Orange | `#D97706` | Visite bientôt due, stock faible |
| **Info** | Ardoise | `#475569` | Textes secondaires, labels |
| **Surface** | Gris clair | `#F8FAFC` | Fond de page |
| **Card** | Blanc | `#FFFFFF` | Fond des cartes |

**Badges potentiel contacts :**
- `A` → `bg-green-100 text-green-700` (fort potentiel)
- `B` → `bg-blue-100 text-blue-700` (potentiel moyen)
- `C` → `bg-gray-100 text-gray-600` (faible potentiel)

---

### 10.3 Typographie

```css
/* Hiérarchie typographique */
Page title   : text-xl font-bold text-gray-900        (20px)
Section title: text-base font-semibold text-gray-800  (16px)
Card title   : text-sm font-semibold text-gray-700    (14px)
Body text    : text-sm text-gray-600                  (14px)
Caption      : text-xs text-gray-400                  (12px)
Badge        : text-xs font-medium px-2 py-0.5 rounded-full
```

---

### 10.4 Navigation Mobile (Bottom Bar)

```html
<!-- navbar_bottom.php — fixe en bas sur tous les écrans -->
<nav class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200
            flex items-center justify-around h-16 px-2 safe-area-pb"
     x-data="{ active: 'dashboard' }">

  <!-- Dashboard -->
  <a href="/pages/dashboard.php"
     class="flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl
            text-gray-400 hover:text-primary transition-colors">
    <svg class="w-6 h-6"><!-- Heroicon home --></svg>
    <span class="text-[10px] font-medium">Accueil</span>
  </a>

  <!-- Planning -->
  <a href="/pages/planning.php"
     class="flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl text-gray-400">
    <svg class="w-6 h-6"><!-- Heroicon calendar --></svg>
    <span class="text-[10px] font-medium">Planning</span>
  </a>

  <!-- FAB Nouvelle Visite (centre) -->
  <a href="/pages/visits.php?action=new"
     class="flex flex-col items-center -mt-6">
    <div class="w-14 h-14 bg-primary rounded-full flex items-center justify-center
                shadow-lg shadow-blue-300 active:scale-95 transition-transform">
      <svg class="w-7 h-7 text-white"><!-- Heroicon plus --></svg>
    </div>
    <span class="text-[10px] font-medium text-primary mt-0.5">Visite</span>
  </a>

  <!-- Carte -->
  <a href="/pages/map.php"
     class="flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl text-gray-400">
    <svg class="w-6 h-6"><!-- Heroicon map --></svg>
    <span class="text-[10px] font-medium">Carte</span>
  </a>

  <!-- Menu -->
  <a href="/pages/menu.php"
     class="flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl text-gray-400">
    <svg class="w-6 h-6"><!-- Heroicon menu --></svg>
    <span class="text-[10px] font-medium">Menu</span>
  </a>

</nav>
<!-- Padding bas pour ne pas cacher le contenu -->
<div class="h-20"></div>
```

---

### 10.5 Composants UI Réutilisables

#### Card Standard
```html
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
  <!-- contenu -->
</div>
```

#### Card Alerte Retard (rouge)
```html
<div class="bg-red-50 border border-red-200 rounded-2xl p-4 flex items-start gap-3">
  <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
    <svg class="w-5 h-5 text-red-600"><!-- clock icon --></svg>
  </div>
  <div class="flex-1 min-w-0">
    <p class="text-sm font-semibold text-gray-900 truncate">Dr. Mohamed Ben Ali</p>
    <p class="text-xs text-gray-500">Pharmacie Centrale · Tunis</p>
    <p class="text-xs text-red-600 font-medium mt-1">En retard de 12 jours</p>
  </div>
  <a href="#" class="flex-shrink-0 bg-primary text-white text-xs font-medium
                     px-3 py-1.5 rounded-lg active:scale-95 transition-transform">
    Visiter
  </a>
</div>
```

#### Badge Potentiel
```html
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">A</span>
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">B</span>
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">C</span>
```

#### Badge Type Contact
```html
<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">Médecin</span>
<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-700">Pharmacie</span>
<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-pink-100 text-pink-700">Parapharmacie</span>
<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">Clinique</span>
<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Hôpital</span>
```

#### Input Mobile
```html
<div class="space-y-1">
  <label class="block text-sm font-medium text-gray-700">Gouvernorat</label>
  <select class="w-full h-12 px-4 bg-gray-50 border border-gray-200 rounded-xl
                 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary
                 focus:border-transparent appearance-none">
    <option>Sélectionner...</option>
  </select>
</div>
```

#### Bouton Primaire
```html
<button class="w-full h-12 bg-primary hover:bg-primary-dark active:scale-[0.98]
               text-white text-sm font-semibold rounded-xl transition-all
               shadow-sm shadow-blue-200 flex items-center justify-center gap-2">
  <svg class="w-5 h-5"><!-- icon --></svg>
  Enregistrer la visite
</button>
```

#### Barre de Progression KPI
```html
<div class="space-y-1">
  <div class="flex justify-between text-xs">
    <span class="text-gray-600 font-medium">Visites ce mois</span>
    <span class="text-primary font-bold">47 / 60</span>
  </div>
  <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
    <div class="h-full bg-primary rounded-full transition-all duration-500"
         style="width: 78%"></div>
  </div>
  <p class="text-xs text-gray-400">78% de l'objectif atteint</p>
</div>
```

#### Toast Notification (Alpine.js)
```html
<div x-data="{ show: false, message: '', type: 'success' }"
     @toast.window="show = true; message = $event.detail.message;
                    type = $event.detail.type; setTimeout(() => show = false, 3000)">
  <div x-show="show" x-transition
       :class="type === 'success' ? 'bg-green-600' : 'bg-red-600'"
       class="fixed top-4 left-4 right-4 z-50 text-white text-sm font-medium
              px-4 py-3 rounded-xl shadow-lg flex items-center gap-2">
    <svg class="w-5 h-5 flex-shrink-0"><!-- check icon --></svg>
    <span x-text="message"></span>
  </div>
</div>

<!-- Déclenchement depuis JS -->
<script>
  window.dispatchEvent(new CustomEvent('toast', {
    detail: { message: 'Visite enregistrée avec succès !', type: 'success' }
  }));
</script>
```

#### Stepper Visite (4 étapes — Alpine.js)
```html
<div x-data="{ step: 1, maxStep: 4 }">
  <!-- Indicateur étapes -->
  <div class="flex items-center justify-between px-4 mb-6">
    <template x-for="i in maxStep" :key="i">
      <div class="flex items-center">
        <div :class="i <= step ? 'bg-primary text-white' : 'bg-gray-200 text-gray-400'"
             class="w-8 h-8 rounded-full flex items-center justify-center
                    text-xs font-bold transition-all">
          <span x-text="i"></span>
        </div>
        <div x-show="i < maxStep"
             :class="i < step ? 'bg-primary' : 'bg-gray-200'"
             class="h-0.5 w-full flex-1 mx-1 transition-all"></div>
      </div>
    </template>
  </div>

  <!-- Étape 1 : Localisation -->
  <div x-show="step === 1" x-transition><!-- contenu --></div>
  <!-- Étape 2 : Contact -->
  <div x-show="step === 2" x-transition><!-- contenu --></div>
  <!-- Étape 3 : Type visite -->
  <div x-show="step === 3" x-transition><!-- contenu --></div>
  <!-- Étape 4 : Confirmation -->
  <div x-show="step === 4" x-transition><!-- contenu --></div>

  <!-- Navigation étapes -->
  <div class="flex gap-3 px-4 mt-6">
    <button x-show="step > 1" @click="step--"
            class="flex-1 h-12 bg-gray-100 text-gray-700 rounded-xl text-sm font-semibold">
      Retour
    </button>
    <button @click="step < maxStep ? step++ : submitVisit()"
            class="flex-1 h-12 bg-primary text-white rounded-xl text-sm font-semibold">
      <span x-text="step < maxStep ? 'Continuer' : 'Enregistrer'"></span>
    </button>
  </div>
</div>
```

---

### 10.6 Header Page Mobile

```html
<!-- includes/header.php -->
<header class="sticky top-0 z-40 bg-white border-b border-gray-100">
  <div class="flex items-center justify-between px-4 h-14">
    <!-- Bouton retour ou logo -->
    <div class="w-10">
      <?php if ($show_back ?? false): ?>
        <a href="javascript:history.back()"
           class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100">
          <svg class="w-5 h-5 text-gray-600"><!-- chevron-left --></svg>
        </a>
      <?php else: ?>
        <span class="text-primary font-black text-lg">RT</span>
      <?php endif; ?>
    </div>

    <!-- Titre page -->
    <h1 class="text-base font-semibold text-gray-900">
      <?= htmlspecialchars($page_title ?? 'RepTrack') ?>
    </h1>

    <!-- Actions droite (notif + éventuellement autre) -->
    <div class="w-10 flex justify-end">
      <a href="/pages/notifications.php"
         class="relative w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100">
        <svg class="w-5 h-5 text-gray-600"><!-- bell icon --></svg>
        <?php if ($unread_notifications > 0): ?>
          <span class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white
                       text-[9px] font-bold rounded-full flex items-center justify-center">
            <?= $unread_notifications ?>
          </span>
        <?php endif; ?>
      </a>
    </div>
  </div>
</header>
```

---

### 10.7 Skeleton Loader (chargement AJAX)

```html
<div class="animate-pulse space-y-3">
  <div class="h-20 bg-gray-100 rounded-2xl"></div>
  <div class="h-20 bg-gray-100 rounded-2xl"></div>
  <div class="h-20 bg-gray-100 rounded-2xl"></div>
</div>
```

---

### 10.8 Prompt Claude Code (à copier-coller)

> Ajouter au début de chaque prompt envoyé à Claude Code :

```
Stack UI obligatoire :
- Tailwind CSS via CDN en prototypage, build en production (CLI + purge)
- Alpine.js via CDN pour toute interactivité
- Heroicons en SVG inline pour les icônes
- Google Fonts Inter pour la typographie
- PHP pur côté serveur (pas de framework)
- Composer + autoload PSR-4 + dotenv (`vlucas/phpdotenv`)
- Design mobile-first Android, langue française
- Couleur primaire : #2563EB | Succès : #16A34A | Danger : #DC2626 | Warning : #D97706
- Fond de page : #F8FAFC | Cards : #FFFFFF avec rounded-2xl et shadow-sm
- Tous les boutons : h-12 minimum, rounded-xl, active:scale-[0.98]
- Navigation : Bottom Bar fixe avec FAB "Nouvelle Visite" au centre
- Header sticky avec titre centré + cloche notifications à droite
```

---

## 11. Securite

- PDO avec requetes preparees — zero injection SQL
- htmlspecialchars() sur tout output HTML
- Tokens CSRF sur tous les formulaires POST
- Regeneration session apres login : session_regenerate_id(true)
- Verification role sur chaque page ET chaque endpoint API
- Uploads : verification type MIME, extension whitelist (jpg/png/pdf), renommage UUID
- Uploads stockes hors web root + endpoint de download avec contrôle d’accès
- Secrets en `.env` (non versionne) + `.env.example`
- Mots de passe : password_hash(PASSWORD_BCRYPT) + password_verify()
- Logs d'activite : actions importantes tracees (connexion, visite, validation frais)
- Rate limiting sur les endpoints API sensibles

---

## 12. Phases de Developpement

| Phase | Contenu | Priorite |
|---|---|---|
| Phase 1 | BDD complete, seed 24 gouvernorats, auth login/logout, gestion utilisateurs | Critique |
| Phase 2 | CRUD contacts enrichi (segmentation complete), recherche live AJAX | Critique |
| Phase 3 | Flux nouvelle visite complet (4 etapes) + deduction stock | Critique |
| Phase 4 | Dashboard delegue : KPI vs objectifs, alertes retard, stock | Critique |
| Phase 5 | Planning admin (creation) + vue calendrier delegue | Haute |
| Phase 6 | Carte Leaflet interactive (marqueurs, filtres, clustering) | Haute |
| Phase 7 | Dashboard admin + graphiques Chart.js comparatifs | Haute |
| Phase 8 | Notes de frais + validation admin + notifications | Moyenne |
| Phase 9 | Photos terrain rattachees aux contacts | Moyenne |
| Phase 10 | Rapports complets + exports CSV | Moyenne |
| Phase 11 | Visites formation (detail pedagogique), rappels automatiques, cron emails | V1 finale |
| Phase 12 | PWA, offline-first, optimisation mobile avancee | V2 |

---

## 13. Roadmap V2 (Futures Evolutions)

- PWA installable sur smartphone (icone home screen, mode offline)
- Geolocatisation automatique au demarrage de visite (GPS)
- OCR justificatifs de frais (lecture automatique du montant)
- Signature electronique lors des visites formation
- Envoi compte-rendu automatique par email/WhatsApp apres visite
- Module commandes et suivi des ventes par point de vente
- API REST pour integration logiciel comptabilite
- Tableau de bord multi-marques
- Application mobile native (React Native) basee sur cette API

---

*RepTrack V3 — Cahier des Charges Complet avec Design System*
*Specification pour développement PHP/MySQL — Tunisie*
*Stack UI : Tailwind CSS + Alpine.js + Chart.js + Leaflet.js*
*Prêt pour Claude Code — Développement par phases*
*Version 3.0 — Mars 2026*
