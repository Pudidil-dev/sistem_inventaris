// Konfirmasi hapus dengan SweetAlert2 untuk <form class="js-confirm-delete" data-item-name="...">.
// data-ajax="true"  → dikirim via Axios, baris [data-row] memudar lalu dihapus dari tabel.
// data-redirect="url" → setelah sukses AJAX pindah ke url (dipakai di halaman detail).
// Tanpa data-ajax → form dikirim biasa setelah dikonfirmasi.
(function () {
    'use strict';

    function confirmDelete(itemName) {
        if (typeof window.Swal === 'undefined') {
            return Promise.resolve(window.confirm(`Hapus "${itemName}"? Tindakan ini tidak dapat dibatalkan.`));
        }

        return window.Swal.fire({
            icon: 'warning',
            titleText: `Hapus "${itemName}"?`,
            text: 'Data yang dihapus tidak dapat dikembalikan.',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc3545',
            reverseButtons: true,
            focusCancel: true,
        }).then((result) => result.isConfirmed);
    }

    // Animasi baris memudar; muat ulang halaman bila tabel menjadi kosong
    function removeRow(form) {
        const row = form.closest('[data-row]');
        if (!row) {
            window.location.reload();
            return;
        }

        const tbody = row.parentElement;
        row.classList.add('inv-row-removing');
        row.addEventListener('animationend', () => {
            row.remove();
            if (tbody && tbody.querySelectorAll('[data-row]').length === 0) {
                window.location.reload();
            }
        }, { once: true });
    }

    function deleteWithAjax(form) {
        const { http, toast, errorMessage } = window.InvApp;
        const button = form.querySelector('[type="submit"]');
        if (button) {
            button.disabled = true;
        }

        return http.post(form.getAttribute('action'), new FormData(form))
            .then((response) => {
                toast('success', response.data.data.message);
                if (form.dataset.redirect) {
                    window.setTimeout(() => window.location.assign(form.dataset.redirect), 900);
                } else {
                    removeRow(form);
                }
            })
            .catch((error) => {
                toast('error', errorMessage(error, 'Data gagal dihapus.'));
                if (button) {
                    button.disabled = false;
                }
            });
    }

    function handleSubmit(event) {
        event.preventDefault();
        const form = event.currentTarget;

        confirmDelete(form.dataset.itemName || 'data ini').then((confirmed) => {
            if (!confirmed) {
                return;
            }
            if (form.dataset.ajax === 'true' && window.InvApp?.http) {
                deleteWithAjax(form);
            } else {
                form.submit();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('form.js-confirm-delete').forEach((form) => {
            form.addEventListener('submit', handleSubmit);
        });
    });
})();
