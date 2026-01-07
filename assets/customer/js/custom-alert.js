// Custom Alert System

class CustomAlert {
  constructor() {
    this.container = null;
    this.initContainer();
  }

  initContainer() {
    // Check if container already exists
    this.container = document.getElementById('custom-alert-container');
    if (!this.container) {
      this.container = document.createElement('div');
      this.container.id = 'custom-alert-container';
      this.container.className = 'custom-alert-container';
      document.body.appendChild(this.container);
    }
  }

  show(message, type = 'info', duration = 4000) {
    const alertId = 'alert-' + Date.now();
    const alert = document.createElement('div');
    alert.id = alertId;
    alert.className = `custom-alert ${type}`;

    // Create icon based on type
    const iconSVG = this.getIconSVG(type);
    const iconDiv = document.createElement('div');
    iconDiv.className = 'alert-icon';
    iconDiv.innerHTML = iconSVG;

    // Create message
    const messageDiv = document.createElement('div');
    messageDiv.className = 'alert-message';
    messageDiv.textContent = message;

    // Create close button
    const closeBtn = document.createElement('button');
    closeBtn.className = 'alert-close';
    closeBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
    closeBtn.onclick = () => this.remove(alertId);

    // Append elements
    alert.appendChild(iconDiv);
    alert.appendChild(messageDiv);
    alert.appendChild(closeBtn);

    // Add to container
    this.container.appendChild(alert);

    // Auto remove after duration
    if (duration > 0) {
      setTimeout(() => this.remove(alertId), duration);
    }

    return alertId;
  }

  getIconSVG(type) {
    const icons = {
      success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>',
      error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>',
      warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3.05h16.94a2 2 0 0 0 1.71-3.05L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
      info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
    };
    return icons[type] || icons.info;
  }

  remove(alertId) {
    const alert = document.getElementById(alertId);
    if (alert) {
      alert.classList.add('removing');
      setTimeout(() => {
        alert.remove();
      }, 300);
    }
  }

  success(message, duration = 4000) {
    return this.show(message, 'success', duration);
  }

  error(message, duration = 4000) {
    return this.show(message, 'error', duration);
  }

  warning(message, duration = 4000) {
    return this.show(message, 'warning', duration);
  }

  info(message, duration = 4000) {
    return this.show(message, 'info', duration);
  }
}

// Create global instance
const alert = new CustomAlert();
