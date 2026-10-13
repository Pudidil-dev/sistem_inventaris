// Fungsi global: instance Axios ber-CSRF, toast, alert otomatis, penghitung karakter,
// tombol cetak, dan tombol tampil/sembunyi password.
(function () {
    'use strict';

    const readMeta = (name) => document.querySelector(`meta[name="${name}"]`)?.getAttribute('content') ?? '';

    // Instance Axios bersama: header CSRF & penanda AJAX selalu terkirim
    function createHttpClient() {
        if (typeof window.axios === 'undefined') {
            return null;
        }

        return window.axios.create({
            baseURL: readMeta('base-url'),
            timeout: 15000,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': readMeta('csrf-token'),
                Accept: 'application/json',
            },
        });
    }

    // Notifikasi kecil pojok kanan atas; titleText = teks biasa (aman dari injeksi HTML)
    function toast(type, message) {
        if (typeof window.Swal === 'undefined') {
            window.alert(message);
            return;
        }

        window.Swal.fire({
            toast: true,
            position: 'top-end',
            icon: type,
            titleText: message,
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
        });
    }

    // Pesan error dari respons JSON {success:false, error:"..."}
    function errorMessage(error, fallback) {
        return error?.response?.data?.error ?? fallback ?? 'Terjadi kesalahan. Silakan coba lagi.';
    }

    function initAutoDismissAlerts() {
        document.querySelectorAll('.js-auto-dismiss').forEach((alertElement) => {
            window.setTimeout(() => {
                if (window.bootstrap?.Alert) {
                    window.bootstrap.Alert.getOrCreateInstance(alertElement).close();
                } else {
                    alertElement.remove();
                }
            }, 5000);
        });
    }

    // <textarea maxlength="1000" data-char-counter="#idPenghitung">
    function initCharCounters() {
        document.querySelectorAll('[data-char-counter]').forEach((field) => {
            const counter = document.querySelector(field.dataset.charCounter);
            if (!counter) {
                return;
            }

            const update = () => {
                counter.textContent = `${field.value.length}/${field.maxLength}`;
            };
            field.addEventListener('input', update);
            update();
        });
    }

    function initPrintButtons() {
        document.querySelectorAll('[data-action="print"]').forEach((button) => {
            button.addEventListener('click', () => window.print());
        });
    }

    // <button data-toggle-password="#password">
    function initPasswordToggles() {
        document.querySelectorAll('[data-toggle-password]').forEach((button) => {
            const input = document.querySelector(button.dataset.togglePassword);
            const icon = button.querySelector('i');
            if (!input) {
                return;
            }

            button.addEventListener('click', () => {
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.setAttribute('aria-pressed', String(show));
                icon?.classList.toggle('bi-eye', !show);
                icon?.classList.toggle('bi-eye-slash', show);
            });
        });
    }

    window.InvApp = Object.freeze({
        http: createHttpClient(),
        toast,
        errorMessage,
        csrfToken: () => readMeta('csrf-token'),
    });

    document.addEventListener('DOMContentLoaded', () => {
        initAutoDismissAlerts();
        initCharCounters();
        initPrintButtons();
        initPasswordToggles();
    });
})();
