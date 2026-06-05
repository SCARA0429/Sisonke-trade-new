(function () {
  'use strict';

  function showBootstrapValidation(form) {
    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      return false;
    }
    return true;
  }

  function bindLoginForm() {
    var form = document.getElementById('login-form');
    if (!form || !window.fetch) {
      return;
    }

    var apiUrl = form.getAttribute('data-login-api');
    if (!apiUrl) {
      return;
    }

    var alertEl = document.getElementById('login-alert');
    var submitBtn = document.getElementById('login-submit');
    var spinnerEl = submitBtn ? submitBtn.querySelector('.login-submit-spinner') : null;

    form.addEventListener('submit', function (e) {
      if (!showBootstrapValidation(form)) {
        e.preventDefault();
        return;
      }

      e.preventDefault();

      if (alertEl) {
        alertEl.classList.add('d-none');
        alertEl.textContent = '';
      }

      var emailInput = form.querySelector('[name="email"]');
      var passwordInput = form.querySelector('[name="password"]');
      var returnInput = form.querySelector('[name="return"]');
      var payload = {
        email: emailInput ? emailInput.value : '',
        password: passwordInput ? passwordInput.value : '',
        return: returnInput ? returnInput.value : ''
      };

      if (submitBtn) {
        submitBtn.disabled = true;
      }
      if (spinnerEl) {
        spinnerEl.classList.remove('d-none');
      }

      fetch(apiUrl, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload),
        credentials: 'same-origin'
      })
        .then(function (res) {
          return res.json().then(function (data) {
            return { ok: res.ok, status: res.status, data: data };
          });
        })
        .then(function (result) {
          var data = result.data || {};
          if (result.ok && data.success && data.redirect) {
            window.location.href = data.redirect;
            return;
          }
          var msg = (data && data.message) ? data.message : 'Unable to log in. Please try again.';
          if (alertEl) {
            alertEl.textContent = msg;
            alertEl.classList.remove('d-none');
          }
        })
        .catch(function () {
          form.submit();
        })
        .finally(function () {
          if (submitBtn) {
            submitBtn.disabled = false;
          }
          if (spinnerEl) {
            spinnerEl.classList.add('d-none');
          }
        });
    });
  }

  function bindRoleFormHints() {
    var roleSelect = document.querySelector('.js-role-select');
    var profileInput = document.getElementById('profile_value');
    var profileLabel = document.querySelector('label[for="profile_value"]');
    var permissionSelect = document.getElementById('permission_level');

    if (!roleSelect || !profileInput || !profileLabel) {
      return;
    }

    function updateLabels() {
      var role = roleSelect.value;
      if (role === 'admin') {
        profileLabel.textContent = 'Admin note';
        profileInput.placeholder = 'Optional internal note';
      } else {
        profileLabel.textContent = 'Business / delivery';
        profileInput.placeholder = 'Business name or delivery address';
      }

      if (permissionSelect) {
        permissionSelect.disabled = role !== 'admin' || roleSelect.disabled;
      }
    }

    roleSelect.addEventListener('change', updateLabels);
    updateLabels();
  }

  function formatMoney(amount) {
    var value = Number(amount);
    if (!isFinite(value) || value < 0) {
      return '—';
    }
    return 'R ' + value.toFixed(2);
  }

  function bindCampaignDiscountPreview() {
    document.querySelectorAll('.js-campaign-discount').forEach(function (panel) {
      var enable = panel.querySelector('.js-campaign-discount-enable');
      var fields = panel.querySelector('.js-campaign-discount-fields');
      var typeSelect = panel.querySelector('.js-campaign-discount-type');
      var valueInput = panel.querySelector('.js-campaign-discount-value');
      var preview = panel.querySelector('.js-campaign-discount-preview-amount');
      var basePriceInput = panel.closest('form')
        ? panel.closest('form').querySelector('.js-campaign-base-price')
        : null;

      if (!enable || !fields || !typeSelect || !valueInput || !preview || !basePriceInput) {
        return;
      }

      function syncValueLimits() {
        var isPercent = typeSelect.value === 'percent';
        valueInput.min = isPercent ? '1' : '0.01';
        valueInput.max = isPercent ? '90' : '';
        valueInput.step = isPercent ? '1' : '0.01';
        if (isPercent && Number(valueInput.value) > 90) {
          valueInput.value = '10';
        }
      }

      function updatePreview() {
        var base = Number(basePriceInput.value);
        if (!enable.checked || !isFinite(base) || base <= 0) {
          preview.textContent = '—';
          return;
        }

        var type = typeSelect.value;
        var discountValue = Number(valueInput.value);
        var sale = base;

        if (type === 'percent') {
          if (discountValue >= 1 && discountValue <= 90) {
            sale = base * (1 - discountValue / 100);
          }
        } else if (discountValue > 0 && discountValue < base) {
          sale = base - discountValue;
        }

        preview.textContent = formatMoney(sale);
      }

      function toggleFields() {
        fields.hidden = !enable.checked;
        valueInput.disabled = !enable.checked;
        typeSelect.disabled = !enable.checked;
        updatePreview();
      }

      enable.addEventListener('change', toggleFields);
      typeSelect.addEventListener('change', function () {
        syncValueLimits();
        updatePreview();
      });
      valueInput.addEventListener('input', updatePreview);
      basePriceInput.addEventListener('input', updatePreview);
      syncValueLimits();
      toggleFields();
    });
  }

  function bindSellerProductHighlight() {
    var page = document.querySelector('[data-highlight-product]');
    if (!page) {
      return;
    }

    var productId = page.getAttribute('data-highlight-product');
    if (!productId) {
      return;
    }

    var row = document.getElementById('product-' + productId);
    if (!row) {
      return;
    }

    window.setTimeout(function () {
      row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 120);
  }

  document.addEventListener('DOMContentLoaded', function () {
    bindLoginForm();
    bindRoleFormHints();
    bindCampaignDiscountPreview();
    bindSellerProductHighlight();
  });
})();
