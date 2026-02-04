export function registerAdminNotificationCenter() {
    if (!window.Alpine || typeof window.Alpine.data !== 'function') {
        return false;
    }

    window.Alpine.data('adminNotificationCenter', () => ({
        open: false,
        unreadCount: 0,
        notifications: [],
        loading: false,

        init() {
            this.loadNotifications();
            setInterval(() => this.loadNotifications(), 30000);
        },

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.loadNotifications();
            }
        },

        close() {
            this.open = false;
        },

        async loadNotifications() {
            if (this.loading) {
                return;
            }

            this.loading = true;

            try {
                const response = await fetch('/api/notifications/unread');
                if (!response.ok) {
                    throw new Error('Error al obtener notificaciones');
                }

                const data = await response.json();
                this.notifications = data.tickets || [];
                this.unreadCount = data.count || 0;
            } catch (error) {
                console.error('Error loading notifications:', error);
            } finally {
                this.loading = false;
            }
        },

        async markAsRead(ticketId) {
            try {
                const response = await fetch(`/api/notifications/${ticketId}/read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                });

                if (response.ok) {
                    await this.loadNotifications();
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        },

        async markAllAsRead() {
            try {
                const response = await fetch('/api/notifications/mark-all-read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                });

                if (response.ok) {
                    await this.loadNotifications();
                    this.showToast('Todas las notificaciones marcadas como leídas');
                }
            } catch (error) {
                console.error('Error marking all notifications as read:', error);
            }
        },

        async openTicket(notification) {
            await this.markAsRead(notification.id);
            window.location.href = notification.url;
        },

        showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300';
            toast.textContent = message;

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-x-full');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, 3000);
        },
    }));

    return true;
}

// Register immediately if Alpine already started, otherwise on alpine:init
if (!registerAdminNotificationCenter()) {
    document.addEventListener('alpine:init', () => {
        registerAdminNotificationCenter();
    });
}

// Safety: hide any orphaned panel if Alpine fails to init
document.addEventListener('DOMContentLoaded', () => {
    const orphanPanel = document.querySelector('[x-data="adminNotificationCenter()"] [x-cloak]');
    if (orphanPanel && !window.Alpine) {
        orphanPanel.style.display = 'none';
    }
});

export default { registerAdminNotificationCenter };
