/* ==========================================================================
   InvenTrack — validation.js
   Client-side form validation helpers.
   Auto-attaches to every form.needs-validation on DOMContentLoaded.
   ========================================================================== */

/* -------------------------------------------------------------------------- */
/* Helper: show / clear error on a field                                       */
/* -------------------------------------------------------------------------- */

/**
 * showError(input, message)
 * Marks the field as invalid and inserts (or updates) a .field-error span
 * immediately after the input element.
 */
function showError(input, message) {
  input.classList.add('invalid');

  // Look for an existing error span that belongs to this input
  var existingError = input.parentNode.querySelector('.field-error[data-for="' + input.name + '"]');

  if (existingError) {
    existingError.textContent = message;
  } else {
    var span = document.createElement('span');
    span.className = 'field-error';
    span.setAttribute('data-for', input.name || '');
    span.textContent = message;
    input.parentNode.insertBefore(span, input.nextSibling);
  }
}

/**
 * clearError(input)
 * Removes the invalid state and deletes the .field-error span if present.
 */
function clearError(input) {
  input.classList.remove('invalid');

  var errorSpan = input.parentNode.querySelector('.field-error[data-for="' + input.name + '"]');
  if (errorSpan) errorSpan.remove();
}

/* -------------------------------------------------------------------------- */
/* Validators                                                                  */
/* -------------------------------------------------------------------------- */

/**
 * validateRequired(input)
 * Fails if the trimmed value is empty.
 * Returns true if valid, false otherwise.
 */
function validateRequired(input) {
  if (input.value.trim() === '') {
    showError(input, 'This field is required.');
    return false;
  }
  clearError(input);
  return true;
}

/**
 * validateEmail(input)
 * Fails if the value is not a recognisable email address.
 * Returns true if valid, false otherwise.
 */
function validateEmail(input) {
  var value = input.value.trim();

  // Allow empty — let validateRequired handle that separately
  if (value === '') {
    clearError(input);
    return true;
  }

  var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(value)) {
    showError(input, 'Please enter a valid email address.');
    return false;
  }

  clearError(input);
  return true;
}

/**
 * validatePositiveNumber(input)
 * Fails if the value is not a number greater than 0.
 * Returns true if valid, false otherwise.
 */
function validatePositiveNumber(input) {
  var value = input.value.trim();

  // Allow empty — let validateRequired handle that separately
  if (value === '') {
    clearError(input);
    return true;
  }

  var num = parseFloat(value);
  if (isNaN(num) || num <= 0) {
    showError(input, 'Please enter a number greater than 0.');
    return false;
  }

  clearError(input);
  return true;
}

/**
 * validateMinLength(input, min)
 * Fails if trimmed value length is below `min`.
 * Returns true if valid, false otherwise.
 */
function validateMinLength(input, min) {
  var value = input.value.trim();

  if (value.length > 0 && value.length < min) {
    showError(input, 'Must be at least ' + min + ' characters long.');
    return false;
  }

  clearError(input);
  return true;
}

/**
 * validateMaxLength(input, max)
 * Fails if trimmed value length exceeds `max`.
 * Returns true if valid, false otherwise.
 */
function validateMaxLength(input, max) {
  var value = input.value.trim();

  if (value.length > max) {
    showError(input, 'Cannot exceed ' + max + ' characters.');
    return false;
  }

  clearError(input);
  return true;
}

/* -------------------------------------------------------------------------- */
/* validateForm(formElement) — runs all relevant validators                    */
/* -------------------------------------------------------------------------- */

/**
 * validateForm(formElement)
 *
 * Runs checks on every interactive field inside formElement:
 *   - [required]            → validateRequired
 *   - [type="email"]        → validateEmail
 *   - [data-min="1"] or
 *     [data-validate="positive-number"] → validatePositiveNumber
 *   - [data-minlength="N"]  → validateMinLength
 *   - [data-maxlength="N"]  → validateMaxLength
 *
 * Returns true if ALL fields pass, false if any fail.
 * Scrolls the first invalid field into view.
 */
function validateForm(formElement) {
  var valid = true;
  var firstInvalid = null;

  var fields = formElement.querySelectorAll('input, select, textarea');

  fields.forEach(function (field) {
    // Skip hidden, submit, button, reset, file inputs
    var skipTypes = ['hidden', 'submit', 'button', 'reset', 'file'];
    if (skipTypes.indexOf(field.type) !== -1) return;

    // Skip disabled fields — server also ignores them
    if (field.disabled) return;

    var fieldValid = true;

    // Required check
    if (field.hasAttribute('required')) {
      if (!validateRequired(field)) {
        fieldValid = false;
      }
    }

    // Email check (only if it passed required or is not required)
    if (fieldValid && field.type === 'email') {
      if (!validateEmail(field)) {
        fieldValid = false;
      }
    }

    // Positive number check
    if (fieldValid) {
      var minAttr = field.getAttribute('data-min');
      var isPositive = field.getAttribute('data-validate') === 'positive-number';

      if (isPositive || minAttr === '1') {
        if (!validatePositiveNumber(field)) {
          fieldValid = false;
        }
      }
    }

    // Min length
    if (fieldValid) {
      var minLen = parseInt(field.getAttribute('data-minlength'), 10);
      if (!isNaN(minLen)) {
        if (!validateMinLength(field, minLen)) {
          fieldValid = false;
        }
      }
    }

    // Max length
    if (fieldValid) {
      var maxLen = parseInt(field.getAttribute('data-maxlength'), 10);
      if (!isNaN(maxLen)) {
        if (!validateMaxLength(field, maxLen)) {
          fieldValid = false;
        }
      }
    }

    if (!fieldValid) {
      valid = false;
      if (!firstInvalid) firstInvalid = field;
    }
  });

  if (firstInvalid) {
    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
    firstInvalid.focus();
  }

  return valid;
}

/* -------------------------------------------------------------------------- */
/* Auto-attach to .needs-validation forms                                      */
/* -------------------------------------------------------------------------- */

document.addEventListener('DOMContentLoaded', function () {

  document.querySelectorAll('form.needs-validation').forEach(function (form) {

    // Live feedback: clear errors as user types / changes value
    form.querySelectorAll('input, select, textarea').forEach(function (field) {
      var eventName = (field.tagName === 'SELECT') ? 'change' : 'input';

      field.addEventListener(eventName, function () {
        // Re-validate just this field on change
        if (field.hasAttribute('required')) {
          validateRequired(field);
        }
        if (field.type === 'email') {
          validateEmail(field);
        }
        if (
          field.getAttribute('data-validate') === 'positive-number' ||
          field.getAttribute('data-min') === '1'
        ) {
          validatePositiveNumber(field);
        }
      });
    });

    // Block submit if validation fails
    form.addEventListener('submit', function (e) {
      if (!validateForm(form)) {
        e.preventDefault();
      }
    });
  });

});
