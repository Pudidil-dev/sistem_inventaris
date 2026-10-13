-- =====================================================================
-- Sistem Informasi Inventaris Sekolah (SI Inventaris)
-- Model database NATIVE - satu-satunya sumber kebenaran skema.
-- Target : MySQL 8.0+ / MariaDB 10.4+
-- Import : phpMyAdmin / HeidiSQL -> buka file ini -> jalankan semua.
-- Catatan: Aman dijalankan ulang (CREATE ... IF NOT EXISTS + seed idempoten).
--          Tidak ada ORM / migration tool. Perubahan skema = ubah file ini
--          dan perbarui docs/04-DATABASE.md.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS inventarisdb
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE inventarisdb;

-- ---------------------------------------------------------------------
-- Tabel: users (akun login Admin & Petugas)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL,
    nama          VARCHAR(100) NOT NULL,
    password      VARCHAR(255) NOT NULL COMMENT 'Hash BCRYPT dari password_hash()',
    role          ENUM('admin', 'petugas') NOT NULL DEFAULT 'petugas',
    last_login_at DATETIME     NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_users_username UNIQUE (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabel: kategori (master jenis barang, kode 3 huruf untuk kode barang)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS kategori (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode       CHAR(3)      NOT NULL COMMENT 'Contoh: ELK, MBL, ATK',
    nama       VARCHAR(50)  NOT NULL,
    deskripsi  VARCHAR(255) NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_kategori_kode UNIQUE (kode),
    CONSTRAINT uq_kategori_nama UNIQUE (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabel: lokasi (master ruangan / tempat penyimpanan barang)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS lokasi (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode             VARCHAR(10)  NOT NULL COMMENT 'Contoh: LAB-KOM, R-TU',
    nama             VARCHAR(100) NOT NULL,
    penanggung_jawab VARCHAR(100) NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_lokasi_kode UNIQUE (kode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabel: barang (data inventaris utama)
-- kode_barang format: {KODE_KATEGORI}-{TAHUN_PEROLEHAN}-{URUT 4 DIGIT}
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS barang (
    id                INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    kode_barang       VARCHAR(20)   NOT NULL COMMENT 'Contoh: ELK-2026-0001 (immutable)',
    nama              VARCHAR(150)  NOT NULL,
    kategori_id       INT UNSIGNED  NOT NULL,
    lokasi_id         INT UNSIGNED  NOT NULL,
    merk              VARCHAR(100)  NULL,
    jumlah            INT UNSIGNED  NOT NULL DEFAULT 1,
    satuan            VARCHAR(20)   NOT NULL DEFAULT 'unit',
    kondisi           ENUM('baik', 'rusak_ringan', 'rusak_berat') NOT NULL DEFAULT 'baik',
    sumber_dana       ENUM('BOS', 'APBD', 'Komite', 'Hibah', 'Lainnya') NOT NULL DEFAULT 'BOS',
    tanggal_perolehan DATE          NOT NULL,
    harga_satuan      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    foto              VARCHAR(255)  NULL COMMENT 'Nama file di public/uploads/barang',
    keterangan        TEXT          NULL,
    created_by        INT UNSIGNED  NULL,
    updated_by        INT UNSIGNED  NULL,
    created_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_barang_kode UNIQUE (kode_barang),
    CONSTRAINT fk_barang_kategori FOREIGN KEY (kategori_id) REFERENCES kategori (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_barang_lokasi FOREIGN KEY (lokasi_id) REFERENCES lokasi (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_barang_created_by FOREIGN KEY (created_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_barang_updated_by FOREIGN KEY (updated_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_barang_jumlah CHECK (jumlah >= 1),
    CONSTRAINT chk_barang_harga CHECK (harga_satuan >= 0),
    INDEX idx_barang_nama (nama),
    INDEX idx_barang_kondisi (kondisi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabel: login_attempts (pembatasan percobaan login / rate limiting)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(50) NOT NULL,
    ip_address   VARCHAR(45) NOT NULL,
    attempted_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempts_lookup (username, ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- DATA AWAL (SEED) - hanya untuk development & demo asesmen
-- Akun default: admin / admin123  dan  petugas / petugas123
-- WAJIB diganti sebelum dipakai di server sekolah.
-- =====================================================================

INSERT INTO users (id, username, nama, password, role) VALUES
(1, 'admin',   'Administrator Sarpras', '$2y$10$TL4B997z31z1Tn0Eq9yI7.8H55ff.7EhsnS4lxD6QeNi4ZNj/UyEe', 'admin'),
(2, 'petugas', 'Petugas Inventaris',    '$2y$10$E5NcPcDFusyar3Cf8dqtx.0i1iI/ZxKWlxNlqFVTk66Jm94AzdrQG', 'petugas')
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO kategori (id, kode, nama, deskripsi) VALUES
(1, 'ELK', 'Elektronik',        'Perangkat elektronik seperti komputer, proyektor, dan printer'),
(2, 'MBL', 'Mebel',             'Meja, kursi, lemari, dan perabot ruangan'),
(3, 'ATK', 'Alat Tulis Kantor', 'Perlengkapan administrasi dan alat tulis'),
(4, 'OLR', 'Alat Olahraga',     'Perlengkapan kegiatan olahraga dan ekstrakurikuler'),
(5, 'LAB', 'Alat Laboratorium', 'Peralatan praktikum laboratorium IPA')
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO lokasi (id, kode, nama, penanggung_jawab) VALUES
(1, 'LAB-KOM', 'Laboratorium Komputer', 'Budi Santoso, S.Kom.'),
(2, 'R-TU',    'Ruang Tata Usaha',      'Siti Rahmawati'),
(3, 'R-GURU',  'Ruang Guru',            'Drs. Ahmad Fauzi'),
(4, 'GDG-OR',  'Gudang Olahraga',       'Rudi Hartono, S.Pd.'),
(5, 'LAB-IPA', 'Laboratorium IPA',      'Dewi Lestari, S.Si.'),
(6, 'R-7A',    'Ruang Kelas VII-A',     'Wali Kelas VII-A')
ON DUPLICATE KEY UPDATE id = id;

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
ON DUPLICATE KEY UPDATE id = id;
