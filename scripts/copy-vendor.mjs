// Menyalin file distribusi library frontend dari node_modules ke public/assets/vendor
// agar aplikasi berjalan offline tanpa CDN.
import { cpSync, existsSync, mkdirSync, rmSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const nodeModules = join(root, 'node_modules');
const vendorDir = join(root, 'public', 'assets', 'vendor');

// [sumber di node_modules, tujuan di public/assets/vendor]
const assets = [
    ['bootstrap/dist/css/bootstrap.min.css', 'bootstrap/bootstrap.min.css'],
    ['bootstrap/dist/js/bootstrap.bundle.min.js', 'bootstrap/bootstrap.bundle.min.js'],
    ['bootstrap-icons/font/bootstrap-icons.min.css', 'bootstrap-icons/bootstrap-icons.min.css'],
    ['bootstrap-icons/font/fonts', 'bootstrap-icons/fonts'],
    ['axios/dist/axios.min.js', 'axios/axios.min.js'],
    ['chart.js/dist/chart.umd.js', 'chartjs/chart.umd.js'],
    ['sweetalert2/dist/sweetalert2.min.js', 'sweetalert2/sweetalert2.min.js'],
    ['sweetalert2/dist/sweetalert2.min.css', 'sweetalert2/sweetalert2.min.css'],
];

rmSync(vendorDir, { recursive: true, force: true });

for (const [source, target] of assets) {
    const from = join(nodeModules, source);
    const to = join(vendorDir, target);

    if (!existsSync(from)) {
        process.stderr.write(`File vendor tidak ditemukan: ${source}. Jalankan "npm install" dulu.\n`);
        process.exit(1);
    }

    mkdirSync(dirname(to), { recursive: true });
    cpSync(from, to, { recursive: true });
    process.stdout.write(`✓ ${target}\n`);
}
