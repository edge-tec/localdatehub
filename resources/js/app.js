import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Only start Alpine if it hasn't already been started by Livewire
if (!window.__alpineStarted) {
    window.__alpineStarted = true;
    Alpine.start();
}
