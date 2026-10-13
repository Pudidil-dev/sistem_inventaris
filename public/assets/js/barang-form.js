// Interaksi form barang: pratinjau foto (FileReader) dan pratinjau kode barang (Axios)
(function () {
    'use strict';

    function initFotoPreview() {
        const input = document.getElementById('foto');
        const preview = document.querySelector('[data-foto-preview]');
        const hapusFoto = document.getElementById('hapus_foto');
        if (!input || !preview) {
            return;
        }

        input.addEventListener('change', () => {
            const file = input.files?.[0];
            const valid = window.InvValidation ? window.InvValidation.validateField(input) : true;
            if (!file || !valid) {
                preview.src = preview.dataset.original;
                return;
            }

            const reader = new FileReader();
            reader.addEventListener('load', () => {
                preview.src = String(reader.result);
                if (hapusFoto) {
                    hapusFoto.checked = false;
                }
            });
            reader.readAsDataURL(file);
        });

        hapusFoto?.addEventListener('change', () => {
            preview.src = hapusFoto.checked ? preview.dataset.placeholder : preview.dataset.original;
            if (hapusFoto.checked) {
                input.value = '';
            }
        });
    }

    // Badge kode diperbarui saat kategori/tanggal berubah; respons lama diabaikan
    function initKodePreview() {
        const badge = document.querySelector('[data-kode-preview]');
        const kategori = document.getElementById('kategori_id');
        const tanggal = document.getElementById('tanggal_perolehan');
        if (!badge || !kategori || !window.InvApp?.http) {
            return;
        }

        let latestRequest = 0;
        const update = () => {
            if (!kategori.value) {
                badge.textContent = 'Pilih kategori';
                return;
            }

            const requestId = ++latestRequest;
            badge.textContent = 'Memuat…';
            window.InvApp.http
                .get(badge.dataset.kodeUrl, { params: { kategori_id: kategori.value, tanggal: tanggal?.value ?? '' } })
                .then((response) => {
                    if (requestId === latestRequest) {
                        badge.textContent = response.data.data.kode;
                    }
                })
                .catch(() => {
                    if (requestId === latestRequest) {
                        badge.textContent = 'Tidak tersedia';
                    }
                });
        };

        kategori.addEventListener('change', update);
        tanggal?.addEventListener('change', update);
        update();
    }

    document.addEventListener('DOMContentLoaded', () => {
        initFotoPreview();
        initKodePreview();
    });
})();
