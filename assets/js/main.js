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
      if (role === 'seller') {
        profileLabel.textContent = 'Business name';
        profileInput.placeholder = 'Example: Mama Nandi Spaza';
      } else if (role === 'admin') {
        profileLabel.textContent = 'Admin note';
        profileInput.placeholder = 'Optional internal note';
      } else {
        profileLabel.textContent = 'Delivery address';
        profileInput.placeholder = 'Street, area, city';
      }

      if (permissionSelect) {
        permissionSelect.disabled = role !== 'admin' || roleSelect.disabled;
      }
    }

    roleSelect.addEventListener('change', updateLabels);
    updateLabels();
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
    bindSellerProductHighlight();
  });
})();
