# Contact Profiles, GPS Capture, and Visit Links

Date: 2026-04-09

## Summary

We introduced dedicated profile pages for contacts (medecin, pharmacie, parapharmacie, etc.), made contact names clickable across the app, and added a GPS capture button to store coordinates for mapping and routing use cases. The UI mirrors the stock page style.

## What Changed

- New contact profile page with KPI cards, detailed info, GPS card, and recent visits.
- Contact names link to profiles from:
  - Contacts list
  - Visits list
  - Visit detail
  - New visit flow (profile link activates after contact selection)
- GPS capture button uses browser geolocation and saves `latitude`/`longitude` to the contact record.
- Visit flow can prefill governorate/city/contact when opened from a profile.
- Visits list can be filtered by `contact_id`.

## Files Updated

- `pages/contact_profile.php` (new)
- `public/index.php`
- `pages/contacts.php`
- `pages/visits.php`
- `pages/visits_list.php`
- `pages/visit_detail.php`
- `public/assets/js/app.js`

## New Route

- `GET /contacts/view?id=CONTACT_ID`

## Notes

- GPS capture requires browser permission (geolocation).
- Coordinates are stored on the existing `contacts.latitude` / `contacts.longitude` fields.
- Map integration and route optimization can be built on top of these coordinates.

---

## GPS Capture Fix + Manual Edit

Date: 2026-04-09

### Summary

Improved the GPS capture UX on contact profiles by adding manual latitude/longitude inputs and clearer error handling. The GPS button now warns when the app is not running in a secure context (HTTPS/localhost) and provides specific failure messages.

### Files Updated

- `pages/contact_profile.php`
- `public/assets/js/app.js`

---

## GPS Permission Helper Banner

Date: 2026-04-09

### Summary

Added an in-app GPS permission helper banner and a one-click guide link. The banner appears when geolocation is blocked or unsupported and directs users to a quick help page.

### Files Updated

- `public/index.php`
- `pages/help_gps.php`
- `pages/contact_profile.php`
- `public/assets/js/app.js`

---

## Contact Specialty Dropdown + Map UI Updates

Date: 2026-04-09

### Summary

Replaced the free-text doctor specialty field with a fixed dropdown list that appears only when the contact type is "Medecin". Updated the zones map UI to show contacts per governorate and delegation with clickable cards, improved governorate coloring, and aligned filtering to contact locations.

### Files Updated

- `pages/contacts_new.php`
- `pages/delegues_map.php`

---

## Map Counts and GPS Contact Visibility Fix

Date: 2026-04-09

### Summary

Adjusted the delegues map contact query and counting logic so assigned contact totals are accurate (not limited to GPS-only records) and contacts with GPS coordinates appear regardless of zone filtering. Contact governorate names are now sourced directly from the contacts table for consistency.

### Files Updated

- `pages/delegues_map.php`

---

## Unassigned GPS Contacts + Missing GPS Badge

Date: 2026-04-09

### Summary

Displayed unassigned contacts with GPS on the map using a distinct marker style and added a “Sans GPS” badge in the delegue legend for quick visibility into missing coordinates.

### Files Updated

- `pages/delegues_map.php`
