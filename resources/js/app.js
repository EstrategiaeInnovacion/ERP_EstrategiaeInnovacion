import './bootstrap';

import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';
import 'flatpickr/dist/flatpickr.min.css';

window.flatpickr = flatpickr;
window.flatpickr.l10ns.es = Spanish;

// Import component modules
import { initNotificationDropdown } from './Sistemas_IT/components/notifications.js';
import { registerAdminNotificationCenter } from './Sistemas_IT/components/admin-notification-center.js';

window.Alpine = Alpine;

// Initialize components
if (typeof initNotificationDropdown === 'function') {
    initNotificationDropdown();
}

if (typeof registerAdminNotificationCenter === 'function') {
    registerAdminNotificationCenter();
}

Alpine.start();
