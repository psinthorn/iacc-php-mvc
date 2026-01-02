/**
 * Toast Notifications System
 * Modern replacement for JavaScript alerts with styled notifications
 * 
 * Usage:
 *   Toast.success('Operation completed successfully!');
 *   Toast.error('An error occurred. Please try again.');
 *   Toast.warning('Are you sure about this action?');
 *   Toast.info('This is an informational message.');
 */

const Toast = (function() {
  'use strict';

  // Configuration
  const config = {
    duration: 4000, // Auto-dismiss after 4 seconds (0 = no auto-dismiss)
    position: 'top-right', // top-left, top-center, top-right, bottom-left, bottom-center, bottom-right
    maxToasts: 5, // Maximum toasts to show at once
    closeButton: true,
    pauseOnHover: true,
    animation: 'fadeInOut' // fadeInOut, slideInOut, popIn
  };

  // Types and their icons
  const types = {
    success: {
      class: 'toast-success',
      icon: '✓',
      ariaLabel: 'Success'
    },
    error: {
      class: 'toast-error',
      icon: '✕',
      ariaLabel: 'Error'
    },
    warning: {
      class: 'toast-warning',
      icon: '⚠',
      ariaLabel: 'Warning'
    },
    info: {
      class: 'toast-info',
      icon: 'ℹ',
      ariaLabel: 'Information'
    }
  };

  // Get or create toast container
  function getToastContainer() {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      container.className = 'toast-container toast-position-' + config.position;
      document.body.appendChild(container);
    }
    return container;
  }

  // Create toast element
  function createToastElement(message, type, options) {
    const typeConfig = types[type] || types.info;
    const toast = document.createElement('div');
    toast.className = 'toast ' + typeConfig.class + ' toast-' + config.animation;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-label', typeConfig.ariaLabel);

    let html = '<div class="toast-inner">';
    html += '<div class="toast-icon">' + typeConfig.icon + '</div>';
    html += '<div class="toast-content">';
    html += '<div class="toast-message">' + escapeHtml(message) + '</div>';
    
    if (options && options.description) {
      html += '<div class="toast-description">' + escapeHtml(options.description) + '</div>';
    }
    
    html += '</div>';

    if (config.closeButton) {
      html += '<button class="toast-close" aria-label="Close notification">&times;</button>';
    }

    html += '</div>';

    toast.innerHTML = html;

    // Close button handler
    const closeButton = toast.querySelector('.toast-close');
    if (closeButton) {
      closeButton.addEventListener('click', function() {
        removeToast(toast);
      });
    }

    // Pause timer on hover
    if (config.pauseOnHover) {
      let timeoutId = null;

      toast.addEventListener('mouseenter', function() {
        if (timeoutId !== null) {
          clearTimeout(timeoutId);
        }
      });

      toast.addEventListener('mouseleave', function() {
        if (config.duration > 0) {
          timeoutId = setTimeout(function() {
            removeToast(toast);
          }, config.duration);
        }
      });
    }

    return toast;
  }

  // Remove toast with animation
  function removeToast(toast) {
    toast.classList.add('toast-removing');
    setTimeout(function() {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }, 300);
  }

  // Show toast
  function show(message, type, options) {
    options = options || {};

    // Sanitize message
    if (!message || typeof message !== 'string') {
      console.warn('Toast message must be a non-empty string');
      return null;
    }

    // Get or create container
    const container = getToastContainer();

    // Check max toasts limit
    const activeToasts = container.querySelectorAll('.toast').length;
    if (activeToasts >= config.maxToasts) {
      // Remove oldest toast
      const oldestToast = container.querySelector('.toast');
      if (oldestToast) {
        removeToast(oldestToast);
      }
    }

    // Create and add toast
    const toast = createToastElement(message, type, options);
    container.appendChild(toast);

    // Trigger animation
    setTimeout(function() {
      toast.classList.add('toast-show');
    }, 10);

    // Auto-dismiss
    if (config.duration > 0) {
      setTimeout(function() {
        removeToast(toast);
      }, config.duration);
    }

    return toast;
  }

  // Escape HTML to prevent XSS
  function escapeHtml(text) {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) {
      return map[m];
    });
  }

  // Public API
  return {
    // Configuration
    configure: function(options) {
      Object.assign(config, options);
    },

    // Show methods
    success: function(message, options) {
      return show(message, 'success', options);
    },

    error: function(message, options) {
      return show(message, 'error', options);
    },

    warning: function(message, options) {
      return show(message, 'warning', options);
    },

    info: function(message, options) {
      return show(message, 'info', options);
    },

    // Generic show method
    show: function(message, type, options) {
      return show(message, type || 'info', options);
    },

    // Clear all toasts
    clear: function() {
      const container = document.getElementById('toast-container');
      if (container) {
        const toasts = container.querySelectorAll('.toast');
        toasts.forEach(function(toast) {
          removeToast(toast);
        });
      }
    },

    // Remove specific toast
    remove: function(toast) {
      if (toast && toast.parentNode) {
        removeToast(toast);
      }
    },

    // Get configuration
    getConfig: function() {
      return Object.assign({}, config);
    }
  };
})();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
  module.exports = Toast;
}
