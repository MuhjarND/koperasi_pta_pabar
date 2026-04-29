<?php

return [
    'roles' => [
        'superadmin' => 'Super Admin',
        'sekretaris' => 'Sekretaris',
        'bendahara' => 'Bendahara',
        'bendahara_kantor' => 'Bendahara Kantor',
        'ketua' => 'Ketua',
        'anggota' => 'Anggota',
    ],
    'status_labels' => [
        'submitted' => 'Menunggu Review Sekretaris',
        'approved_treasurer' => 'Menunggu Persetujuan Ketua',
        'reviewed' => 'Menunggu Persetujuan Bendahara',
        'approved_chairman' => 'Disetujui Bendahara',
        'rejected' => 'Ditolak',
    ],
    'status_badges' => [
        'submitted' => 'warning',
        'reviewed' => 'warning',
        'approved_treasurer' => 'warning',
        'approved_chairman' => 'success',
        'rejected' => 'danger',
    ],
    'service_fee_rate' => 0.015,
    'savings_types' => [
        'pokok' => 'Simpanan Pokok',
        'wajib' => 'Simpanan Wajib',
        'sukarela' => 'Simpanan Sukarela',
    ],
    'excel_import' => [
        'xlsx_path' => env('KOPERASI_IMPORT_XLSX_PATH', 'H:/My Drive/TASK HARIAN/DATA PEGAWAI/Koperasi.xlsx'),
        'sheet_anggota' => env('KOPERASI_IMPORT_SHEET_ANGGOTA', 'Anggota'),
        'sheet_simpanan' => env('KOPERASI_IMPORT_SHEET_SIMPANAN', 'Simpanan'),
        'sheet_pinjaman' => env('KOPERASI_IMPORT_SHEET_PINJAMAN', 'Pinjaman'),
        'sheet_angsuran' => env('KOPERASI_IMPORT_SHEET_ANGSURAN', 'Angsuran'),
        'import_year_angsuran' => (int) env('KOPERASI_IMPORT_YEAR_ANGSURAN', date('Y')),
        'include_historical_disbursement_cash' => filter_var(env('KOPERASI_IMPORT_INCLUDE_HISTORICAL_DISBURSEMENT_CASH', false), FILTER_VALIDATE_BOOLEAN),
        'default_password' => env('KOPERASI_IMPORT_DEFAULT_PASSWORD', 'ptapabar'),
        'default_date' => env('KOPERASI_IMPORT_DEFAULT_DATE', '2026-01-01'),
    ],
    'rekap_import' => [
        'xlsx_path' => env('KOPERASI_REKAP_XLSX_PATH', 'C:/Users/rubik/Documents/KEADAAN KEUANGAN KOPERASI AS-SALAM 2026.xlsx'),
        'sheet_keu' => env('KOPERASI_REKAP_SHEET_KEU', 'REKAP KEU KOP  2026'),
        'sheet_pemotongan' => env('KOPERASI_REKAP_SHEET_PEMOTONGAN', 'REKAP PEMOTONGAN'),
        'sheet_peminjaman' => env('KOPERASI_REKAP_SHEET_PEMINJAMAN', 'REKAP PEMINJAMAN'),
        'sheet_simpanan' => env('KOPERASI_REKAP_SHEET_SIMPANAN', 'REKAP SIMPANAN ANGGOTA'),
        'year' => (int) env('KOPERASI_REKAP_YEAR', 2026),
        'posted_month_limit' => (int) env('KOPERASI_REKAP_POSTED_MONTH_LIMIT', 4),
    ],
];
