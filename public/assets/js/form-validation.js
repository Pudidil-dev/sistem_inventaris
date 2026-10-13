// Validasi form sisi klien yang generik untuk semua <form class="js-validate">.
// Aturan dibaca dari atribut HTML5 (required, minlength, maxlength, min, max, pattern, type)
// dan atribut kustom: data-rule-not-future, data-max-size, data-allowed-types.
// Pesan: data-msg pada field (sama dengan pesan server di PRD §8.1) atau pesan bawaan.
// Elemen pesan: <div class="invalid-feedback" id="{id-field}-feedback">.
(function () {
    'use strict';

    const MESSAGES = {
        valueMissing: 'Field ini wajib diisi.',
        tooShort: (field) => `Minimal ${field.minLength} karakter.`,
        tooLong: (field) => `Maksimal ${field.maxLength} karakter.`,
        rangeUnderflow: (field) => `Nilai minimal ${field.min}.`,
        rangeOverflow: (field) => `Nilai maksimal ${field.max}.`,
        stepMismatch: 'Format angka tidak valid.',
        typeMismatch: 'Format isian tidak valid.',
        patternMismatch: 'Format isian tidak sesuai.',
        badInput: 'Format isian tidak valid.',
        notFuture: 'Tanggal tidak boleh melebihi hari ini.',
        fileTooLarge: 'Ukuran file terlalu besar.',
        fileType: 'Tipe file tidak didukung.',
    };

    const NATIVE_KEYS = [
        'valueMissing', 'badInput', 'typeMismatch', 'patternMismatch',
        'tooLong', 'tooShort', 'rangeUnderflow', 'rangeOverflow', 'stepMismatch',
    ];

    // Tanggal hari ini (zona waktu lokal) dalam format YYYY-MM-DD
    function todayIso() {
        const now = new Date();
        return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
    }

    function fileErrorKey(field) {
        const file = field.files?.[0];
        if (!file) {
            return null;
        }

        const maxSize = Number(field.dataset.maxSize || 0);
        const allowed = (field.dataset.allowedTypes || '').split(',').map((type) => type.trim()).filter(Boolean);

        if (maxSize > 0 && file.size > maxSize) {
            return 'fileTooLarge';
        }
        if (allowed.length > 0 && !allowed.includes(file.type)) {
            return 'fileType';
        }
        return null;
    }

    // Aturan tambahan yang tidak ditangani HTML5 (spasi saja, minlength programatik, tanggal, file)
    function customErrorKey(field) {
        if (field.type === 'file') {
            return fileErrorKey(field);
        }

        const value = field.value.trim();
        if (field.required && value === '') {
            return 'valueMissing';
        }
        if (field.minLength > 0 && value !== '' && value.length < field.minLength) {
            return 'tooShort';
        }
        if (field.dataset.ruleNotFuture !== undefined && value !== '' && value > todayIso()) {
            return 'notFuture';
        }
        return null;
    }

    function nativeErrorKey(field) {
        return NATIVE_KEYS.find((key) => field.validity[key]) ?? null;
    }

    function messageFor(field, key) {
        if (field.dataset.msg) {
            return field.dataset.msg;
        }
        const message = MESSAGES[key];
        return typeof message === 'function' ? message(field) : message;
    }

    // Validasi satu field dan tampilkan pesannya; mengembalikan true bila valid
    function validateField(field) {
        field.setCustomValidity('');
        const key = customErrorKey(field) ?? nativeErrorKey(field);
        const message = key ? messageFor(field, key) : '';

        field.setCustomValidity(message);
        field.classList.toggle('is-invalid', key !== null);

        const feedback = document.getElementById(`${field.id}-feedback`);
        if (feedback && message) {
            feedback.textContent = message;
        }

        return key === null;
    }

    function setSubmitting(form, submitting) {
        const button = form.querySelector('[type="submit"]');
        if (!button) {
            return;
        }
        button.disabled = submitting;
        button.classList.toggle('opacity-75', submitting);
    }

    function initForm(form) {
        form.setAttribute('novalidate', 'novalidate');
        const fields = Array.from(form.querySelectorAll('input, select, textarea'))
            .filter((field) => field.name && field.type !== 'hidden' && field.type !== 'checkbox' && !field.disabled);

        form.addEventListener('submit', (event) => {
            const invalidFields = fields.filter((field) => !validateField(field));

            if (invalidFields.length > 0) {
                event.preventDefault();
                invalidFields[0].focus();
                return;
            }
            setSubmitting(form, true);
        });

        fields.forEach((field) => {
            const liveEvent = ['SELECT'].includes(field.tagName) || ['file', 'date'].includes(field.type) ? 'change' : 'input';

            // Validasi ulang langsung setelah field pernah dinyatakan tidak valid
            field.addEventListener(liveEvent, () => {
                if (field.classList.contains('is-invalid') || field.type === 'file') {
                    validateField(field);
                }
            });
            field.addEventListener('blur', () => {
                if (field.value !== '') {
                    validateField(field);
                }
            });
        });

        // Aktifkan kembali tombol bila halaman dipulihkan dari cache (tombol Back)
        window.addEventListener('pageshow', () => setSubmitting(form, false));
    }

    window.InvValidation = Object.freeze({ validateField });

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('form.js-validate').forEach(initForm);
    });
})();
