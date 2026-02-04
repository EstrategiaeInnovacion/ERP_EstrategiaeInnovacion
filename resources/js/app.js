import './bootstrap';

import Alpine from 'alpinejs';

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
