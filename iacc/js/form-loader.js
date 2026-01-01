/**
 * Form Loader System
 * Handles form submission states with loading indicators and feedback
 * 
 * Usage:
 *   const form = document.getElementById('myForm');
 *   const loader = new FormLoader(form, { message: 'Processing...' });
 */

class FormLoader {
  constructor(formElement, options = {}) {
    this.form = formElement;
    this.options = {
      message: options.message || 'Processing...',
      showSpinner: options.showSpinner !== false,
      disableButtons: options.disableButtons !== false,
      disableInputs: options.disableInputs !== false,
      overlay: options.overlay !== false,
      onStart: options.onStart || null,
      onEnd: options.onEnd || null,
      onSuccess: options.onSuccess || null,
      onError: options.onError || null,
      successCallback: options.successCallback || null,
      errorCallback: options.errorCallback || null
    };

    this.isLoading = false;
    this.originalButtonStates = new Map();
    this.originalInputStates = new Map();

    this.init();
  }

  init() {
    if (!this.form) {
      console.warn('FormLoader: No form element provided');
      return;
    }

    // Handle form submission
    this.form.addEventListener('submit', (e) => {
      // Only auto-handle if using traditional form submission
      // For AJAX forms, call start/end manually
      if (this.form.method && this.form.method.toUpperCase() === 'POST') {
        this.start();
      }
    });

    // Optional: Handle AJAX form submissions
    this.setupAjaxHandler();
  }

  setupAjaxHandler() {
    // Override fetch if needed for automatic handling
    const originalFetch = window.fetch;
    const self = this;

    // Store original fetch for restoration
    window.fetchOriginal = originalFetch;
  }

  /**
   * Start loading state
   */
  start() {
    if (this.isLoading) {
      return; // Already loading
    }

    this.isLoading = true;

    // Call start callback
    if (typeof this.options.onStart === 'function') {
      this.options.onStart();
    }

    // Save original button states and disable
    if (this.options.disableButtons) {
      this.disableButtons();
    }

    // Save original input states and disable
    if (this.options.disableInputs) {
      this.disableInputs();
    }

    // Show overlay
    if (this.options.overlay) {
      this.showOverlay();
    }
  }

  /**
   * End loading state - success
   */
  end(success = true, message = null) {
    if (!this.isLoading) {
      return;
    }

    this.isLoading = false;

    // Remove overlay
    if (this.options.overlay) {
      this.hideOverlay();
    }

    // Restore buttons
    if (this.options.disableButtons) {
      this.restoreButtons();
    }

    // Restore inputs
    if (this.options.disableInputs) {
      this.restoreInputs();
    }

    // Call appropriate callback
    if (success) {
      if (typeof this.options.onSuccess === 'function') {
        this.options.onSuccess(message);
      }
      if (typeof this.options.successCallback === 'function') {
        this.options.successCallback(message);
      }
    } else {
      if (typeof this.options.onError === 'function') {
        this.options.onError(message);
      }
      if (typeof this.options.errorCallback === 'function') {
        this.options.errorCallback(message);
      }
    }

    // Call generic end callback
    if (typeof this.options.onEnd === 'function') {
      this.options.onEnd();
    }
  }

  /**
   * Disable all buttons in form
   */
  disableButtons() {
    const buttons = this.form.querySelectorAll('button, input[type="button"], input[type="submit"]');

    buttons.forEach((button) => {
      // Store original state
      this.originalButtonStates.set(button, {
        disabled: button.disabled,
        innerHTML: button.innerHTML,
        classList: Array.from(button.classList)
      });

      // Disable button
      button.disabled = true;

      // Add loading class
      button.classList.add('is-loading');

      // Update content if showing spinner
      if (this.options.showSpinner) {
        const originalText = button.textContent.trim();
        button.dataset.originalText = originalText;
        button.innerHTML = this.getSpinnerHTML() + ' ' + (this.options.message || originalText);
      }
    });
  }

  /**
   * Restore button original state
   */
  restoreButtons() {
    this.originalButtonStates.forEach((state, button) => {
      button.disabled = state.disabled;
      button.innerHTML = state.innerHTML;
      button.classList.remove('is-loading');

      // Restore original classes if needed
      state.classList.forEach((cls) => {
        if (!button.classList.contains(cls)) {
          button.classList.add(cls);
        }
      });
    });

    this.originalButtonStates.clear();
  }

  /**
   * Disable all inputs in form
   */
  disableInputs() {
    const inputs = this.form.querySelectorAll('input, textarea, select');

    inputs.forEach((input) => {
      // Don't disable hidden inputs
      if (input.type === 'hidden') {
        return;
      }

      this.originalInputStates.set(input, {
        disabled: input.disabled
      });

      input.disabled = true;
    });
  }

  /**
   * Restore input original state
   */
  restoreInputs() {
    this.originalInputStates.forEach((state, input) => {
      input.disabled = state.disabled;
    });

    this.originalInputStates.clear();
  }

  /**
   * Show overlay over form
   */
  showOverlay() {
    let overlay = this.form.querySelector('.form-loader-overlay');

    if (!overlay) {
      overlay = document.createElement('div');
      overlay.className = 'form-loader-overlay';

      if (this.options.showSpinner) {
        overlay.innerHTML = '<div class="form-loader-spinner"></div>';
      }

      if (this.options.message) {
        const message = document.createElement('div');
        message.className = 'form-loader-message';
        message.textContent = this.options.message;
        overlay.appendChild(message);
      }

      // Make form position relative for overlay positioning
      if (getComputedStyle(this.form).position === 'static') {
        this.form.style.position = 'relative';
      }

      this.form.appendChild(overlay);
    }

    // Show overlay
    setTimeout(() => {
      overlay.classList.add('show');
    }, 0);
  }

  /**
   * Hide overlay
   */
  hideOverlay() {
    const overlay = this.form.querySelector('.form-loader-overlay');

    if (overlay) {
      overlay.classList.remove('show');
      setTimeout(() => {
        if (overlay.parentNode) {
          overlay.parentNode.removeChild(overlay);
        }
      }, 300);
    }
  }

  /**
   * Get spinner HTML
   */
  getSpinnerHTML() {
    return '<span class="form-loader-spinner-inline"></span>';
  }

  /**
   * Reset form to initial state
   */
  reset() {
    if (this.isLoading) {
      this.end(false, 'Form reset while loading');
    }

    this.form.reset();
  }

  /**
   * Destroy loader and clean up
   */
  destroy() {
    if (this.isLoading) {
      this.end(false, 'FormLoader destroyed');
    }

    // Clean up
    this.originalButtonStates.clear();
    this.originalInputStates.clear();
  }

  /**
   * Check if currently loading
   */
  isLoadingNow() {
    return this.isLoading;
  }
}

/**
 * Convenience function for jQuery-style usage
 * Usage: $.formLoader(form, options).start();
 */
if (typeof jQuery !== 'undefined') {
  jQuery.fn.formLoader = function(options) {
    return new FormLoader(this[0], options);
  };
}

/**
 * Global convenience function
 * Usage: FormLoader.start(form);
 */
FormLoader.start = function(form) {
  if (!form._loader) {
    form._loader = new FormLoader(form);
  }
  form._loader.start();
  return form._loader;
};

FormLoader.end = function(form, success = true, message = null) {
  if (form._loader) {
    form._loader.end(success, message);
  }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
  module.exports = FormLoader;
}
