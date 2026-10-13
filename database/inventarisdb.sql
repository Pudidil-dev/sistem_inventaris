-- =====================================================================
-- SI Inventaris Sekolah: skema dan data awal untuk SQLite (satu file).
-- Sumber kebenaran skema. Aman dijalankan ulang:
--   CREATE ... IF NOT EXISTS, seed memakai ON CONFLICT DO NOTHING,
--   dan UPDATE foto hanya mengisi kolom yang masih kosong.
-- Cara pakai: php scripts/init-db.php
-- Tanggal dan waktu disimpan sebagai teks "YYYY-MM-DD HH:MM:SS" (waktu lokal).
-- =====================================================================

PRAGMA foreign_keys = ON;

-- Akun pengguna (login Admin dan Petugas)
CREATE TABLE IF NOT EXISTS users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    username      TEXT    NOT NULL COLLATE NOCASE,
    nama          TEXT    NOT NULL,
    password      TEXT    NOT NULL,
    role          TEXT    NOT NULL DEFAULT 'petugas',
    last_login_at TEXT    NULL,
    created_at    TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at    TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT uq_users_username UNIQUE (username),
    CONSTRAINT chk_users_role CHECK (role IN ('admin', 'petugas'))
);

CREATE TRIGGER IF NOT EXISTS trg_users_updated_at AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    UPDATE users SET updated_at = datetime('now', 'localtime') WHERE id = NEW.id;
END;

-- Master kategori (awalan kode barang tiga huruf)
CREATE TABLE IF NOT EXISTS kategori (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    kode       TEXT NOT NULL COLLATE NOCASE,
    nama       TEXT NOT NULL COLLATE NOCASE,
    deskripsi  TEXT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT uq_kategori_kode UNIQUE (kode),
    CONSTRAINT uq_kategori_nama UNIQUE (nama)
);

CREATE TRIGGER IF NOT EXISTS trg_kategori_updated_at AFTER UPDATE ON kategori
FOR EACH ROW
BEGIN
    UPDATE kategori SET updated_at = datetime('now', 'localtime') WHERE id = NEW.id;
END;

-- Master lokasi (ruangan dan tempat penyimpanan)
CREATE TABLE IF NOT EXISTS lokasi (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    kode             TEXT NOT NULL COLLATE NOCASE,
    nama             TEXT NOT NULL,
    penanggung_jawab TEXT NULL,
    created_at       TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at       TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT uq_lokasi_kode UNIQUE (kode)
);

CREATE TRIGGER IF NOT EXISTS trg_lokasi_updated_at AFTER UPDATE ON lokasi
FOR EACH ROW
BEGIN
    UPDATE lokasi SET updated_at = datetime('now', 'localtime') WHERE id = NEW.id;
END;

-- Data inventaris utama. kode_barang = KAT-TAHUN-URUT dan tidak pernah berubah.
CREATE TABLE IF NOT EXISTS barang (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    kode_barang       TEXT    NOT NULL COLLATE NOCASE,
    nama              TEXT    NOT NULL,
    kategori_id       INTEGER NOT NULL,
    lokasi_id         INTEGER NOT NULL,
    merk              TEXT    NULL,
    jumlah            INTEGER NOT NULL DEFAULT 1,
    satuan            TEXT    NOT NULL DEFAULT 'unit',
    kondisi           TEXT    NOT NULL DEFAULT 'baik',
    sumber_dana       TEXT    NOT NULL DEFAULT 'BOS',
    tanggal_perolehan TEXT    NOT NULL,
    harga_satuan      NUMERIC NOT NULL DEFAULT 0,
    foto              TEXT    NULL,
    keterangan        TEXT    NULL,
    created_by        INTEGER NULL,
    updated_by        INTEGER NULL,
    created_at        TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at        TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    CONSTRAINT uq_barang_kode UNIQUE (kode_barang),
    CONSTRAINT fk_barang_kategori FOREIGN KEY (kategori_id) REFERENCES kategori (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_barang_lokasi FOREIGN KEY (lokasi_id) REFERENCES lokasi (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_barang_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_barang_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_barang_jumlah CHECK (jumlah >= 1),
    CONSTRAINT chk_barang_harga CHECK (harga_satuan >= 0),
    CONSTRAINT chk_barang_kondisi CHECK (kondisi IN ('baik', 'rusak_ringan', 'rusak_berat')),
    CONSTRAINT chk_barang_sumber_dana CHECK (sumber_dana IN ('BOS', 'APBD', 'Komite', 'Hibah', 'Lainnya'))
);

CREATE INDEX IF NOT EXISTS idx_barang_nama ON barang (nama);
CREATE INDEX IF NOT EXISTS idx_barang_kondisi ON barang (kondisi);
CREATE INDEX IF NOT EXISTS idx_barang_kategori ON barang (kategori_id);
CREATE INDEX IF NOT EXISTS idx_barang_lokasi ON barang (lokasi_id);

CREATE TRIGGER IF NOT EXISTS trg_barang_updated_at AFTER UPDATE ON barang
FOR EACH ROW
BEGIN
    UPDATE barang SET updated_at = datetime('now', 'localtime') WHERE id = NEW.id;
END;

-- Catatan percobaan login gagal (pembatasan 5x per 15 menit)
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    username     TEXT NOT NULL COLLATE NOCASE,
    ip_address   TEXT NOT NULL,
    attempted_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

CREATE INDEX IF NOT EXISTS idx_login_attempts_lookup ON login_attempts (username, ip_address, attempted_at);

-- DATA AWAL (SEED) - hanya untuk development & demo asesmen
-- Akun default: admin / admin123  dan  petugas / petugas123
-- WAJIB diganti sebelum dipakai di server sekolah.
-- =====================================================================

INSERT INTO users (id, username, nama, password, role) VALUES
(1, 'admin',   'Administrator Sarpras', '$2y$10$TL4B997z31z1Tn0Eq9yI7.8H55ff.7EhsnS4lxD6QeNi4ZNj/UyEe', 'admin'),
(2, 'petugas', 'Petugas Inventaris',    '$2y$10$E5NcPcDFusyar3Cf8dqtx.0i1iI/ZxKWlxNlqFVTk66Jm94AzdrQG', 'petugas')
ON CONFLICT DO NOTHING;

INSERT INTO kategori (id, kode, nama, deskripsi) VALUES
(1, 'ELK', 'Elektronik',        'Perangkat elektronik seperti komputer, proyektor, dan printer'),
(2, 'MBL', 'Mebel',             'Meja, kursi, lemari, dan perabot ruangan'),
(3, 'ATK', 'Alat Tulis Kantor', 'Perlengkapan administrasi dan alat tulis'),
(4, 'OLR', 'Alat Olahraga',     'Perlengkapan kegiatan olahraga dan ekstrakurikuler'),
(5, 'LAB', 'Alat Laboratorium', 'Peralatan praktikum laboratorium IPA')
ON CONFLICT DO NOTHING;

INSERT INTO lokasi (id, kode, nama, penanggung_jawab) VALUES
(1, 'LAB-KOM', 'Laboratorium Komputer', 'Budi Santoso, S.Kom.'),
(2, 'R-TU',    'Ruang Tata Usaha',      'Siti Rahmawati'),
(3, 'R-GURU',  'Ruang Guru',            'Drs. Ahmad Fauzi'),
(4, 'GDG-OR',  'Gudang Olahraga',       'Rudi Hartono, S.Pd.'),
(5, 'LAB-IPA', 'Laboratorium IPA',      'Dewi Lestari, S.Si.'),
(6, 'R-7A',    'Ruang Kelas VII-A',     'Wali Kelas VII-A')
ON CONFLICT DO NOTHING;

INSERT INTO barang
    (kode_barang, nama, kategori_id, lokasi_id, merk, jumlah, satuan, kondisi, sumber_dana, tanggal_perolehan, harga_satuan, keterangan, created_by)
VALUES
('ELK-2023-0001', 'Komputer PC Desktop',       1, 1, 'Lenovo ThinkCentre M70s', 30, 'unit',  'baik',         'BOS',    '2023-07-15', 8500000.00,  'Untuk praktik siswa kelas TIK', 1),
('ELK-2023-0002', 'Proyektor LCD',             1, 1, 'Epson EB-X51',             2, 'unit',  'rusak_ringan', 'BOS',    '2023-08-01', 6200000.00,  'Lampu redup, perlu servis', 1),
('ELK-2024-0001', 'Printer Multifungsi',       1, 2, 'Epson L5290',              2, 'unit',  'baik',         'BOS',    '2024-01-20', 3900000.00,  NULL, 1),
('ELK-2024-0002', 'Laptop Guru',               1, 3, 'ASUS Vivobook 14',         5, 'unit',  'baik',         'APBD',   '2024-03-11', 7800000.00,  'Dipinjamkan untuk administrasi guru', 1),
('ELK-2025-0001', 'Access Point WiFi',         1, 1, 'TP-Link EAP225',           4, 'unit',  'baik',         'Komite', '2025-02-05', 1150000.00,  NULL, 1),
('MBL-2022-0001', 'Meja Siswa',                2, 6, 'Olympic',                 36, 'unit',  'baik',         'BOS',    '2022-07-10', 450000.00,   NULL, 1),
('MBL-2022-0002', 'Kursi Siswa',               2, 6, 'Chitose',                 36, 'unit',  'rusak_ringan', 'BOS',    '2022-07-10', 350000.00,   'Beberapa kaki kursi longgar', 1),
('MBL-2023-0001', 'Lemari Arsip Besi',         2, 2, 'Brother',                  3, 'unit',  'baik',         'APBD',   '2023-02-14', 2750000.00,  NULL, 1),
('ATK-2025-0001', 'Kertas HVS A4 70gsm',       3, 2, 'Sinar Dunia',             25, 'rim',   'baik',         'BOS',    '2025-07-01', 48000.00,    NULL, 1),
('ATK-2025-0002', 'Spidol Whiteboard',         3, 3, 'Snowman',                 10, 'lusin', 'baik',         'BOS',    '2025-07-01', 85000.00,    NULL, 1),
('OLR-2024-0001', 'Bola Sepak',                4, 4, 'Mikasa',                  10, 'buah',  'baik',         'Komite', '2024-08-17', 275000.00,   NULL, 1),
('OLR-2024-0002', 'Net Bola Voli',             4, 4, 'Molten',                   2, 'buah',  'rusak_berat',  'BOS',    '2024-08-17', 350000.00,   'Robek, menunggu penghapusan aset', 1),
('LAB-2021-0001', 'Kit Praktikum Kelistrikan', 5, 5, 'KIT IPA',                  6, 'set',   'rusak_berat',  'Hibah',  '2021-09-09', 1750000.00,  'Komponen tidak lengkap', 1),
('LAB-2023-0001', 'Mikroskop Binokuler',       5, 5, 'Olympus CX23',             8, 'unit',  'baik',         'Hibah',  '2023-10-02', 12500000.00, NULL, 1),
('LAB-2024-0001', 'Gelas Ukur 100 ml',         5, 5, 'Pyrex',                   20, 'buah',  'baik',         'BOS',    '2024-02-19', 65000.00,    NULL, 1)
ON CONFLICT DO NOTHING;

-- Foto contoh (ilustrasi) untuk demo. Hanya mengisi kolom foto yang masih kosong,
-- sehingga aman dijalankan ulang. Ganti dengan foto asli lewat menu Edit barang.
UPDATE barang SET foto = 'sample-elk-2023-0001.png' WHERE kode_barang = 'ELK-2023-0001' AND foto IS NULL;
UPDATE barang SET foto = 'sample-elk-2023-0002.png' WHERE kode_barang = 'ELK-2023-0002' AND foto IS NULL;
UPDATE barang SET foto = 'sample-elk-2024-0001.png' WHERE kode_barang = 'ELK-2024-0001' AND foto IS NULL;
UPDATE barang SET foto = 'sample-elk-2024-0002.png' WHERE kode_barang = 'ELK-2024-0002' AND foto IS NULL;
UPDATE barang SET foto = 'sample-elk-2025-0001.png' WHERE kode_barang = 'ELK-2025-0001' AND foto IS NULL;
UPDATE barang SET foto = 'sample-mbl-2022-0001.png' WHERE kode_barang = 'MBL-2022-0001' AND foto IS NULL;
UPDATE barang SET foto = 'sample-mbl-2022-0002.png' WHERE kode_barang = 'MBL-2022-0002' AND foto IS NULL;
UPDATE barang SET foto = 'sample-mbl-2023-0001.png' WHERE kode_barang = 'MBL-2023-0001' AND foto IS NULL;
UPDATE barang SET foto = 'sample-atk-2025-0001.png' WHERE kode_barang = 'ATK-2025-0001' AND foto IS NULL;
UPDATE barang SET foto = 'sample-atk-2025-0002.png' WHERE kode_barang = 'ATK-2025-0002' AND foto IS NULL;
UPDATE barang SET foto = 'sample-olr-2024-0001.png' WHERE kode_barang = 'OLR-2024-0001' AND foto IS NULL;
UPDATE barang SET foto = 'sample-olr-2024-0002.png' WHERE kode_barang = 'OLR-2024-0002' AND foto IS NULL;
UPDATE barang SET foto = 'sample-lab-2021-0001.png' WHERE kode_barang = 'LAB-2021-0001' AND foto IS NULL;
UPDATE barang SET foto = 'sample-lab-2023-0001.png' WHERE kode_barang = 'LAB-2023-0001' AND foto IS NULL;
UPDATE barang SET foto = 'sample-lab-2024-0001.png' WHERE kode_barang = 'LAB-2024-0001' AND foto IS NULL;
