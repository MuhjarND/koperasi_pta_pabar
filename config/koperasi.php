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
        'reviewed' => 'Menunggu Persetujuan Bendahara',
        'approved_treasurer' => 'Menunggu Persetujuan Ketua',
        'approved_chairman' => 'Disetujui Ketua',
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
];
