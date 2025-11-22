// Import Tailwind CSS
import './styles/app.css';

// Service Worker registration (CSP compliant - no inline scripts)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => console.log('Service Worker registered'))
            .catch(error => console.log('Service Worker registration failed:', error));
    });
}

// SportOase IServ Module loaded
console.log('SportOase IServ Module loaded');
