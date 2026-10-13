// Dashboard: animasi count-up angka statistik dan grafik Chart.js dari API (Axios)
(function () {
    'use strict';

    const numberFormat = new Intl.NumberFormat('id-ID');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let charts = [];

    // Hitung naik dari 0 ke nilai akhir selama ±1 detik dengan easing ease-out
    function animateCount(element) {
        const target = Number(element.dataset.countUp || 0);
        if (reducedMotion || target === 0) {
            element.textContent = numberFormat.format(target);
            return;
        }

        const duration = 1000;
        const start = performance.now();
        const step = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            element.textContent = numberFormat.format(Math.round(target * eased));
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    // Animasi dimulai ketika kartu terlihat di layar
    function initCountUp() {
        const elements = document.querySelectorAll('[data-count-up]');
        if (!('IntersectionObserver' in window)) {
            elements.forEach(animateCount);
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.filter((entry) => entry.isIntersecting).forEach((entry) => {
                animateCount(entry.target);
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.4 });

        elements.forEach((element) => observer.observe(element));
    }

    function createKondisiChart(kondisi) {
        return new window.Chart(document.getElementById('chartKondisi'), {
            type: 'doughnut',
            data: {
                labels: kondisi.labels,
                datasets: [{ data: kondisi.values, backgroundColor: kondisi.colors, borderColor: '#fff', borderWidth: 2 }],
            },
            options: {
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: (ctx) => ` ${ctx.label}: ${numberFormat.format(ctx.parsed)} unit` } },
                },
            },
        });
    }

    function createKategoriChart(kategori) {
        return new window.Chart(document.getElementById('chartKategori'), {
            type: 'bar',
            data: {
                labels: kategori.labels,
                datasets: [{
                    label: 'Jumlah unit',
                    data: kategori.values,
                    backgroundColor: 'rgba(30, 64, 175, 0.85)',
                    hoverBackgroundColor: '#f59e0b',
                    borderRadius: 8,
                    maxBarThickness: 48,
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } },
            },
        });
    }

    function toggleSkeleton(loading) {
        document.querySelectorAll('[data-chart-skeleton]').forEach((element) => {
            element.classList.toggle('is-hidden', !loading);
        });
    }

    function toggleError(visible) {
        const box = document.querySelector('[data-chart-error]');
        box?.classList.toggle('d-none', !visible);
        box?.classList.toggle('d-flex', visible);
    }

    function loadStats() {
        const root = document.querySelector('[data-stats-url]');
        if (!root || !window.InvApp?.http || typeof window.Chart === 'undefined') {
            return;
        }

        toggleSkeleton(true);
        toggleError(false);
        window.InvApp.http.get(root.dataset.statsUrl)
            .then((response) => {
                charts.forEach((chart) => chart.destroy());
                charts = [createKondisiChart(response.data.data.kondisi), createKategoriChart(response.data.data.kategori)];
                root.dataset.chartsReady = 'true';
            })
            .catch(() => toggleError(true))
            .finally(() => toggleSkeleton(false));
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (reducedMotion && window.Chart) {
            window.Chart.defaults.animation = false;
        }
        initCountUp();
        loadStats();
        document.querySelector('[data-chart-retry]')?.addEventListener('click', loadStats);
    });
})();
