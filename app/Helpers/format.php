<?php
declare(strict_types=1);

// Helper format tampilan: angka, rupiah, tanggal Indonesia, potong teks

const NAMA_BULAN = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

const NAMA_HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

function format_angka(int|float|string|null $number): string
{
    return number_format((float) ($number ?? 0), 0, ',', '.');
}

function format_rupiah(int|float|string|null $amount): string
{
    return 'Rp ' . format_angka($amount);
}

function format_tanggal(?string $date, bool $withTime = false): string
{
    if ($date === null || $date === '') {
        return '-';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    $tanggal = sprintf(
        '%d %s %s',
        (int) date('j', $timestamp),
        NAMA_BULAN[(int) date('n', $timestamp)],
        date('Y', $timestamp)
    );

    return $withTime ? $tanggal . ', ' . date('H:i', $timestamp) . ' WIB' : $tanggal;
}

// Contoh: "Selasa, 13 Oktober 2026"
function format_tanggal_lengkap(?string $date): string
{
    $timestamp = $date === null ? false : strtotime($date);
    if ($timestamp === false) {
        return format_tanggal($date);
    }

    return NAMA_HARI[(int) date('w', $timestamp)] . ', ' . format_tanggal($date);
}

function str_limit(?string $text, int $limit = 60): string
{
    $clean = trim((string) $text);

    return mb_strlen($clean) <= $limit ? $clean : rtrim(mb_substr($clean, 0, $limit)) . '…';
}
