import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';

window.Alpine = Alpine;
window.flatpickr = flatpickr;

Alpine.store('toasts', {
    items: [],
    add(message, type = 'success') {
        const id = Date.now();
        this.items.push({ id, message, type });
        setTimeout(() => {
            this.items = this.items.filter(i => i.id !== id);
        }, 4000);
    },
    remove(id) {
        this.items = this.items.filter(i => i.id !== id);
    }
});

Alpine.store('confirm', {
    open: false,
    title: 'Are you sure?',
    message: 'This action cannot be undone.',
    confirmText: 'Confirm',
    confirmClass: 'bg-red-600 hover:bg-red-700 text-white',
    actionUrl: '',
    actionMethod: 'POST',
    show({ title, message, confirmText, confirmClass, url, method } = {}) {
        if (title) this.title = title;
        if (message) this.message = message;
        if (confirmText) this.confirmText = confirmText;
        if (confirmClass) this.confirmClass = confirmClass;
        this.actionUrl = url || '';
        this.actionMethod = method || 'POST';
        this.open = true;
    },
    close() {
        this.open = false;
    }
});

document.addEventListener('DOMContentLoaded', function() {
    flatpickr('.datepicker', {
        dateFormat: 'Y-m-d',
        allowInput: true,
    });

    flatpickr('.datepicker-range', {
        mode: 'range',
        dateFormat: 'Y-m-d',
        allowInput: true,
    });
});

Alpine.start();