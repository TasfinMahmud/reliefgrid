import './bootstrap';

// Bootstrap 5 (CSS + JS bundle with Popper)
import 'bootstrap/dist/css/bootstrap.min.css';
import '../css/app.css'; // ReliefGrid styles, loaded after Bootstrap so they win the cascade
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// Alpine.js for small bits of interactivity
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    // Auto-dismiss flash alerts
    document.querySelectorAll('.alert[data-autodismiss]').forEach((el) => {
        setTimeout(() => bootstrap.Alert.getOrCreateInstance(el).close(), 6000);
    });
});
