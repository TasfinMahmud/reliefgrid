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

// QR code rendering for token cards (client side, no PII in the code)
import QRCode from 'qrcode';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-qr]').forEach((el) => {
        const value = el.getAttribute('data-qr');
        const size = parseInt(el.getAttribute('data-qr-size') || '220', 10);
        QRCode.toCanvas(el, value, { width: size, margin: 1, errorCorrectionLevel: 'M' }).catch(() => {
            el.replaceWith(document.createTextNode(value));
        });
    });

    // Auto-dismiss flash alerts
    document.querySelectorAll('.alert[data-autodismiss]').forEach((el) => {
        setTimeout(() => bootstrap.Alert.getOrCreateInstance(el).close(), 6000);
    });
});
