// Notifications dropdown behavior (modular)
// Normalized path under resources/js/Sistemas_IT/components

const SELECTOR_TRIGGER = 'button[aria-label="Abrir notificaciones"]';
const SELECTOR_PANEL = 'button[aria-label="Abrir notificaciones"] + div';

function initPlainToggle(trigger, panel) {
  if (!trigger || !panel) return;
  if (!panel.hasAttribute('x-show')) {
    panel.style.display = 'none';
    const open = () => { panel.style.display = 'block'; trigger.setAttribute('aria-expanded', 'true'); };
    const close = () => { panel.style.display = 'none'; trigger.setAttribute('aria-expanded', 'false'); };
    const toggle = () => panel.style.display === 'none' ? open() : close();
    trigger.addEventListener('click', (e) => { e.stopPropagation(); toggle(); });
    document.addEventListener('click', (e) => { if (!panel.contains(e.target) && !trigger.contains(e.target)) { close(); } });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
  }
}

export function initNotificationDropdown() {
  try {
    const trigger = document.querySelector(SELECTOR_TRIGGER);
    const panel = document.querySelector(SELECTOR_PANEL);
    initPlainToggle(trigger, panel);
  } catch (err) {
    console.warn('[notifications] init error', err);
  }
}

// Auto-init
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initNotificationDropdown);
} else {
  initNotificationDropdown();
}

export default { initNotificationDropdown };
