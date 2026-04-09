(function () {
  'use strict';

  function loadCities(governorateSelect, citySelect, onLoaded) {
    var governorateId = governorateSelect.value;
    var selectedCity = citySelect.getAttribute('data-city-selected');
    citySelect.innerHTML = '<option value="">Choisir</option>';
    if (!governorateId) {
      if (typeof onLoaded === 'function') {
        onLoaded();
      }
      return;
    }

    fetch('/api/get_cities.php?governorate_id=' + encodeURIComponent(governorateId))
      .then(function (response) { return response.json(); })
      .then(function (data) {
        (data.items || []).forEach(function (item) {
          var option = document.createElement('option');
          option.value = item.id;
          var label = item.name_fr;
          if (item.postal_code) {
            label += ' (' + item.postal_code + ')';
          }
          option.textContent = label;
          if (selectedCity && String(item.id) === String(selectedCity)) {
            option.selected = true;
          }
          citySelect.appendChild(option);
        });
        if (typeof onLoaded === 'function') {
          onLoaded();
        }
      })
      .catch(function () {
        if (typeof onLoaded === 'function') {
          onLoaded();
        }
      });
  }

  function loadContacts(governorateId, cityId, contactSelect) {
    if (!contactSelect) {
      return;
    }

    var selectedContact = contactSelect.getAttribute('data-contact-selected');
    contactSelect.innerHTML = '<option value="">Choisir un contact</option>';
    if (!governorateId || !cityId) {
      return;
    }

    fetch('/api/get_contacts.php?governorate_id=' + encodeURIComponent(governorateId) + '&city_id=' + encodeURIComponent(cityId))
      .then(function (response) { return response.json(); })
      .then(function (data) {
        (data.items || []).forEach(function (item) {
          var option = document.createElement('option');
          option.value = item.id;
          option.textContent = item.name + (item.type ? ' (' + item.type + ')' : '');
          if (selectedContact && String(item.id) === String(selectedContact)) {
            option.selected = true;
          }
          contactSelect.appendChild(option);
        });
        syncContactProfileLink(contactSelect);
      })
      .catch(function () {
        // silent fail for now
      });
  }

  function syncContactProfileLink(contactSelect) {
    if (!contactSelect) {
      return;
    }
    var group = contactSelect.closest('[data-city-group]') || contactSelect.parentElement;
    if (!group) {
      return;
    }
    var link = group.querySelector('[data-contact-profile-link]');
    if (!link) {
      return;
    }
    var contactId = contactSelect.value;
    if (contactId) {
      link.href = '/contacts/view?id=' + encodeURIComponent(contactId);
      link.classList.remove('opacity-50', 'pointer-events-none');
    } else {
      link.href = '#';
      link.classList.add('opacity-50', 'pointer-events-none');
    }
  }

  function initDelegueZoneSelectors() {
    var groups = document.querySelectorAll('[data-delegue-zone]');

    groups.forEach(function (group) {
      var governorateSelect = group.querySelector('[data-governorate-select]');
      var excludedSelect = group.querySelector('[data-excluded-select]');

      if (!governorateSelect || !excludedSelect) {
        return;
      }

      function loadDelegations() {
        var governorateId = governorateSelect.value;
        var selectedRaw = excludedSelect.getAttribute('data-excluded-selected') || '';
        var selectedIds = [];

        if (selectedRaw) {
          try {
            selectedIds = JSON.parse(selectedRaw);
          } catch (e) {
            selectedIds = selectedRaw.split(',');
          }
        }

        excludedSelect.innerHTML = '';
        if (!governorateId) {
          return;
        }

        fetch('/api/get_cities.php?governorate_id=' + encodeURIComponent(governorateId))
          .then(function (response) { return response.json(); })
          .then(function (data) {
            (data.items || []).forEach(function (item) {
              var option = document.createElement('option');
              option.value = item.id;
              option.textContent = item.name_fr;
              if (selectedIds.map(String).includes(String(item.id))) {
                option.selected = true;
              }
              excludedSelect.appendChild(option);
            });
            if (excludedSelect.options.length) {
              excludedSelect.dispatchEvent(new Event('change'));
            }
          })
          .catch(function () {
            // ignore
          });
      }

      governorateSelect.addEventListener('change', function () {
        excludedSelect.setAttribute('data-excluded-selected', '');
        loadDelegations();
      });

      if (governorateSelect.value) {
        loadDelegations();
      }
    });
  }

  function initZoneConflictPreview() {
    var forms = document.querySelectorAll('[data-conflict-form]');

    forms.forEach(function (form) {
      var governorateSelect = form.querySelector('[data-governorate-select]');
      var excludedSelect = form.querySelector('[data-excluded-select]');
      var preview = form.querySelector('[data-conflict-preview]');
      var submitBtn = form.querySelector('[data-conflict-submit]');
      var repId = form.getAttribute('data-conflict-rep') || '0';

      if (!governorateSelect || !excludedSelect) {
        return;
      }

      function getSelectedIds() {
        var values = [];
        Array.prototype.forEach.call(excludedSelect.options, function (opt) {
          if (opt.selected) {
            values.push(opt.value);
          }
        });
        return values;
      }

      function setPreview(ok, message) {
        if (!preview) {
          return;
        }
        if (!message) {
          preview.classList.add('hidden');
          preview.textContent = '';
          return;
        }
        preview.classList.remove('hidden');
        preview.textContent = message;
        if (ok) {
          preview.classList.remove('bg-amber-50', 'border-amber-200', 'text-amber-700');
          preview.classList.add('bg-emerald-50', 'border-emerald-200', 'text-emerald-700');
        } else {
          preview.classList.remove('bg-emerald-50', 'border-emerald-200', 'text-emerald-700');
          preview.classList.add('bg-amber-50', 'border-amber-200', 'text-amber-700');
        }
      }

      function checkConflicts() {
        var governorateId = governorateSelect.value;
        if (!governorateId) {
          setPreview(false, '');
          if (submitBtn) {
            submitBtn.disabled = false;
          }
          return;
        }

        var ids = getSelectedIds();
        var url = '/api/get_zone_conflicts.php?governorate_id=' + encodeURIComponent(governorateId) +
          '&excluded_city_ids=' + encodeURIComponent(ids.join(',')) +
          '&rep_id=' + encodeURIComponent(repId);

        fetch(url)
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (data && data.ok) {
              var msg = data.message || 'Zone disponible.';
              setPreview(true, msg + ' (' + (data.included_count || 0) + ' incluses)');
              if (submitBtn) {
                submitBtn.disabled = false;
              }
            } else {
              setPreview(false, (data && data.message) ? data.message : 'Conflit detecte.');
              if (submitBtn) {
                submitBtn.disabled = true;
              }
            }
          })
          .catch(function () {
            if (submitBtn) {
              submitBtn.disabled = false;
            }
          });
      }

      governorateSelect.addEventListener('change', checkConflicts);
      excludedSelect.addEventListener('change', checkConflicts);

      checkConflicts();
    });
  }

  function initProductRows() {
    var rows = document.querySelectorAll('[data-product-row]');

    rows.forEach(function (row) {
      var checkbox = row.querySelector('[data-product-checkbox]');
      var qtyInput = row.querySelector('[data-product-qty]');
      var minus = row.querySelector('[data-qty-minus]');
      var plus = row.querySelector('[data-qty-plus]');

      if (!checkbox || !qtyInput) {
        return;
      }

      function normalizeQty(value) {
        var max = parseInt(qtyInput.getAttribute('max') || '0', 10);
        var qty = parseInt(value, 10);
        if (isNaN(qty) || qty < 0) {
          qty = 0;
        }
        if (max && qty > max) {
          qty = max;
        }
        return qty;
      }

      function syncCheckbox() {
        var qty = normalizeQty(qtyInput.value);
        qtyInput.value = qty;
        checkbox.checked = qty > 0;
      }

      checkbox.addEventListener('change', function () {
        if (checkbox.checked) {
          if (normalizeQty(qtyInput.value) === 0) {
            qtyInput.value = 1;
          }
        } else {
          qtyInput.value = 0;
        }
        syncCheckbox();
      });

      qtyInput.addEventListener('input', syncCheckbox);

      if (minus) {
        minus.addEventListener('click', function () {
          var qty = normalizeQty(qtyInput.value);
          qtyInput.value = Math.max(0, qty - 1);
          syncCheckbox();
        });
      }

      if (plus) {
        plus.addEventListener('click', function () {
          var qty = normalizeQty(qtyInput.value);
          qtyInput.value = qty + 1;
          syncCheckbox();
        });
      }

      syncCheckbox();
    });
  }

  function initGpsCapture() {
    var forms = document.querySelectorAll('[data-gps-form]');

    forms.forEach(function (form) {
      var button = form.querySelector('[data-gps-button]');
      var latInput = form.querySelector('[data-gps-lat]');
      var lngInput = form.querySelector('[data-gps-lng]');
      var status = form.querySelector('[data-gps-status]');
      var helper = form.closest('.bg-white') ? form.closest('.bg-white').querySelector('[data-gps-helper]') : null;

      if (!button || !latInput || !lngInput) {
        return;
      }

      function setStatus(text) {
        if (status) {
          status.textContent = text || '';
        }
      }

      function showHelper(show) {
        if (!helper) {
          return;
        }
        if (show) {
          helper.classList.remove('hidden');
        } else {
          helper.classList.add('hidden');
        }
      }

      function precheck() {
        if (!window.isSecureContext || !navigator.geolocation) {
          showHelper(true);
          return;
        }
        if (navigator.permissions && navigator.permissions.query) {
          navigator.permissions.query({ name: 'geolocation' }).then(function (status) {
            if (status && status.state === 'denied') {
              showHelper(true);
            }
          }).catch(function () {
            // ignore
          });
        }
      }

      precheck();

      button.addEventListener('click', function (event) {
        event.preventDefault();
        if (!window.isSecureContext) {
          setStatus('Geolocalisation requiert HTTPS (ou localhost).');
          showHelper(true);
          return;
        }
        if (!navigator.geolocation) {
          setStatus('Geolocalisation non supportee.');
          showHelper(true);
          return;
        }

        button.disabled = true;
        showHelper(false);
        setStatus('Localisation en cours...');

        navigator.geolocation.getCurrentPosition(function (pos) {
          latInput.value = pos.coords.latitude.toFixed(7);
          lngInput.value = pos.coords.longitude.toFixed(7);
          setStatus('Coordonnees recuperees.');
          form.submit();
        }, function (err) {
          button.disabled = false;
          if (err && err.code === 1) {
            setStatus('Autorisation GPS refusee.');
            showHelper(true);
          } else if (err && err.code === 2) {
            setStatus('Position indisponible.');
            showHelper(true);
          } else if (err && err.code === 3) {
            setStatus('Delai GPS depasse.');
            showHelper(true);
          } else {
            setStatus('Impossible d\'obtenir la position.');
            showHelper(true);
          }
        }, {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 0
        });
      });
    });
  }


  function initSpecialtyFields() {
    var typeSelects = document.querySelectorAll('[data-type-select]');

    typeSelects.forEach(function (select) {
      var container = select.closest('form') || select.closest('[data-city-group]') || select.parentElement;
      if (!container) {
        return;
      }

      var field = container.querySelector('[data-specialty-field]');
      var input = container.querySelector('[data-specialty-input]');
      if (!field || !input) {
        return;
      }

      function sync() {
        var isDoctor = select.value === 'doctor';
        if (isDoctor) {
          field.classList.remove('hidden');
          input.setAttribute('required', 'required');
        } else {
          field.classList.add('hidden');
          input.removeAttribute('required');
          input.value = '';
        }
      }

      select.addEventListener('change', sync);
      sync();
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var groups = document.querySelectorAll('[data-city-group]');

    groups.forEach(function (group) {
      var governorateSelect = group.querySelector('[data-governorate-select]');
      var citySelect = group.querySelector('[data-city-select]');
      var contactSelect = group.querySelector('[data-contact-select]');

      if (!governorateSelect || !citySelect) {
        return;
      }

      governorateSelect.addEventListener('change', function () {
        citySelect.setAttribute('data-city-selected', '');
        if (contactSelect) {
          contactSelect.setAttribute('data-contact-selected', '');
          contactSelect.innerHTML = '<option value="">Choisir un contact</option>';
        }
        loadCities(governorateSelect, citySelect, function () {
          if (contactSelect && citySelect.value) {
            loadContacts(governorateSelect.value, citySelect.value, contactSelect);
          }
        });
      });

      citySelect.addEventListener('change', function () {
        if (contactSelect) {
          loadContacts(governorateSelect.value, citySelect.value, contactSelect);
        }
      });

      if (contactSelect) {
        contactSelect.addEventListener('change', function () {
          syncContactProfileLink(contactSelect);
        });
      }

      if (governorateSelect.value) {
        loadCities(governorateSelect, citySelect, function () {
          if (contactSelect && citySelect.value) {
            loadContacts(governorateSelect.value, citySelect.value, contactSelect);
          }
        });
      } else if (contactSelect) {
        syncContactProfileLink(contactSelect);
      }
    });

    initProductRows();
    initSpecialtyFields();
    initDelegueZoneSelectors();
    initZoneConflictPreview();
    initGpsCapture();
  });
})();


