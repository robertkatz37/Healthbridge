// HealthsBridge Frontend Application
// Bootstrap 5.3 + AlpineJS + custom modules

import 'bootstrap';
import Alpine from 'alpinejs';
import axios from 'axios';

// ─── Axios CSRF Setup ──────────────────────────────────────────────────────────
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
window.axios = axios;

// ─── AlpineJS ─────────────────────────────────────────────────────────────────
window.Alpine = Alpine;

// ─── Auth Helpers ─────────────────────────────────────────────────────────────
Alpine.data('authForm', () => ({
    loading: false,
    showPassword: false,
    showConfirm: false,

    submit(e) {
        this.loading = true;
        // Form submits normally; loading state gives visual feedback
        // Reset on browser back or error
        window.addEventListener('pageshow', () => { this.loading = false; });
    },

    togglePassword() { this.showPassword = !this.showPassword; },
    toggleConfirm()  { this.showConfirm  = !this.showConfirm; },
}));

// ─── Password Strength ────────────────────────────────────────────────────────
Alpine.data('passwordStrength', () => ({
    strength: 0,
    label: '',
    color: '',

    check(value) {
        let score = 0;
        if (value.length >= 8)  score++;
        if (value.length >= 12) score++;
        if (/[A-Z]/.test(value)) score++;
        if (/[0-9]/.test(value)) score++;
        if (/[^A-Za-z0-9]/.test(value)) score++;

        this.strength = Math.min(score, 4);
        const levels = [
            { label: '', color: '' },
            { label: 'Weak', color: '#D64545' },
            { label: 'Fair', color: '#E0A526' },
            { label: 'Good', color: '#2E78C7' },
            { label: 'Strong', color: '#1E9E6B' },
        ];
        this.label = levels[this.strength].label;
        this.color = levels[this.strength].color;
    },

    get width() { return (this.strength / 4) * 100 + '%'; },
}));

// ─── Role Selector ────────────────────────────────────────────────────────────
Alpine.data('roleSelector', (initial = 'family') => ({
    selected: initial,
    select(role) { this.selected = role; },
}));

// ─── OTP Input Auto-advance ───────────────────────────────────────────────────
Alpine.data('otpInput', () => ({
    init() {
        const inputs = this.$el.querySelectorAll('input[data-otp]');
        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                const val = e.target.value.replace(/\D/g, '');
                e.target.value = val.slice(-1);
                if (val && index < inputs.length - 1) inputs[index + 1].focus();
                input.classList.toggle('filled', !!val);
                this.syncHidden(inputs);
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !input.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                text.split('').forEach((char, i) => {
                    if (inputs[i]) { inputs[i].value = char; inputs[i].classList.add('filled'); }
                });
                this.syncHidden(inputs);
                const next = inputs[Math.min(text.length, inputs.length - 1)];
                if (next) next.focus();
            });
        });
    },
    syncHidden(inputs) {
        const hidden = this.$el.querySelector('input[name="code"]');
        if (hidden) hidden.value = Array.from(inputs).map(i => i.value).join('');
    },
}));

// ─── Flash Message Auto-dismiss ───────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
        const delay = parseInt(el.dataset.autoDismiss || '4000');
        setTimeout(() => {
            el.style.transition = 'opacity 0.4s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 400);
        }, delay);
    });
});

Alpine.start();
