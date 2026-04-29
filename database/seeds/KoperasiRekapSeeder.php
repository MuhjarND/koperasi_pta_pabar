<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Str;

class KoperasiRekapSeeder extends Seeder
{
    private $userMap = [];

    private $userAliasMap = [
        'pandi' => 'drs pandi',
        'muh faishol h' => 'moh faishol hasanuddin',
        'h basyirun' => 'basyirun',
        'muh takdir' => 'muhammad takdir',
        'takdir' => 'muhammad takdir',
        'imran' => 'imran',
        'khoiriyah' => 'khoiriyah',
        'hj khoiriyah' => 'khoiriyah',
        'musa sholawat' => 'musa sholawat',
        'syamsul bahri' => 'syamsul bahri',
        'raswin' => 'raswin',
        'manik rokhmani' => 'manik rochmani',
        'manik rokhmani' => 'manik rochmani',
        'ummu mukhlisa' => 'ummu mukhlisa',
        'suriakencana' => 'suria kencana',
        'suria kencana' => 'suria kencana',
        'akram' => 'akram',
        'h akram' => 'akram',
        'zubaidah hi h' => 'zubaidah hi hamzah',
        'missah hamzah s' => 'missah hamzah suara',
        'muslim amin' => 'muslim amin',
        'ahmad nur fajri' => 'ahmad nur fajri',
        'akbar' => 'akbar',
        'muhammad lutfi h' => 'muhammad lutfi khakim',
        'muhammad lutfi hakim' => 'muhammad lutfi khakim',
        'gilang arief m' => 'gilang arief maulana',
        'gilang arif maulana' => 'gilang arief maulana',
        'muhjar nias dani' => 'muhjar nias dani',
        'yudhis s pradana' => 'yudhis salvania pradana',
        'yudhis slavania pradana' => 'yudhis salvania pradana',
        'hamzah syam' => 'hamzah syam',
        'iksan ohorella' => 'iksan ohorella',
        'ikhsan ohrella' => 'iksan ohorella',
        'rafid bahar' => 'ravid bahar',
        'ravid bahar' => 'ravid bahar',
        'hari supriyanto' => 'hari supritanto',
        'hari suprianto' => 'hari supritanto',
        'salasa said' => 'said salasa',
        'said salasa' => 'said salasa',
        'nur komariah' => 'nur komariah',
        'yatino' => 'yatino',
        'zilva kurnia a' => 'zilva kurnia aji',
        'sitti suriyani tuahuns' => 'sitti suriyani tuahuns',
        'zubaidah hi hamzah' => 'zubaidah hi hamzah',
        'rahmat farid' => 'rahmat farid',
        'nurmansyah' => 'nurmansyah',
    ];

    public function run()
    {
        $path = (string) config('koperasi.rekap_import.xlsx_path');
        $sheetKeuName = (string) config('koperasi.rekap_import.sheet_keu', 'REKAP KEU KOP  2026');
        $sheetPotonganName = (string) config('koperasi.rekap_import.sheet_pemotongan', 'REKAP PEMOTONGAN');
        $sheetPinjamanName = (string) config('koperasi.rekap_import.sheet_peminjaman', 'REKAP PEMINJAMAN');
        $sheetSimpananName = (string) config('koperasi.rekap_import.sheet_simpanan', 'REKAP SIMPANAN ANGGOTA');
        $targetYear = (int) config('koperasi.rekap_import.year', 2026);

        if (!is_file($path)) {
            $this->command->error('[KoperasiRekapSeeder] File XLSX tidak ditemukan: ' . $path);
            return;
        }

        $spreadsheet = IOFactory::load($path);
        $sheetKeu = $this->findSheetCaseInsensitive($spreadsheet, $sheetKeuName);
        $sheetPotongan = $this->findSheetCaseInsensitive($spreadsheet, $sheetPotonganName);
        $sheetPinjaman = $this->findSheetCaseInsensitive($spreadsheet, $sheetPinjamanName);
        $sheetSimpanan = $this->findSheetCaseInsensitive($spreadsheet, $sheetSimpananName);

        if (!$sheetKeu || !$sheetPotongan || !$sheetPinjaman || !$sheetSimpanan) {
            $this->command->error('[KoperasiRekapSeeder] Sheet utama tidak lengkap. Pastikan sheet Rekap Keu, Rekap Pemotongan, Rekap Peminjaman, dan Rekap Simpanan ada.');
            return;
        }

        $superadminId = (int) DB::table('users')->where('role', 'superadmin')->orderBy('id')->value('id');
        $bendaharaKantorId = (int) (DB::table('users')->where('role', 'bendahara_kantor')->orderBy('id')->value('id') ?: $superadminId);
        $sekretarisId = DB::table('users')->where('role', 'sekretaris')->orderBy('id')->value('id');
        $bendaharaId = DB::table('users')->where('role', 'bendahara')->orderBy('id')->value('id');
        $ketuaId = DB::table('users')->where('role', 'ketua')->orderBy('id')->value('id');

        if (!$superadminId) {
            $this->command->error('[KoperasiRekapSeeder] User superadmin tidak ditemukan.');
            return;
        }

        $this->buildUserMap();
        $userImportStats = $this->ensureUsersFromFinalSavingsSheet($sheetSimpanan);
        if ($userImportStats['created_count'] > 0) {
            $this->userMap = [];
            $this->buildUserMap();
        }

        $keuData = $this->parseKeuSheet($sheetKeu, $targetYear);
        $historicalSavingsStats = $this->importHistoricalSavingsSheet(
            $sheetSimpanan,
            $targetYear,
            $superadminId,
            $keuData['refund_dates']
        );
        $potonganStats = $this->importPotonganSheet(
            $sheetPotongan,
            $targetYear,
            $superadminId,
            $bendaharaKantorId,
            $keuData['bendahara_rows']
        );

        $loanStats = $this->importPinjamanSheet(
            $sheetPinjaman,
            $targetYear,
            $superadminId,
            $sekretarisId,
            $bendaharaId,
            $ketuaId,
            $potonganStats['month_dates']
        );

        $manualSavingsDetails = $this->collectMonthlySavingsFromFinalSavingsSheet($sheetSimpanan);
        $cashStats = $this->importKeuCashEntries(
            $keuData['cash_rows'],
            $targetYear,
            $superadminId,
            $manualSavingsDetails
        );

        $adjustmentStats = $this->reconcileFinalSavingsBalances(
            $sheetSimpanan,
            $targetYear,
            $superadminId
        );

        $this->command->info('[KoperasiRekapSeeder] Import rekap selesai.');
        $this->command->line(sprintf(
            'User Rekap Baru => created: %d',
            $userImportStats['created_count']
        ));
        $this->command->line(sprintf(
            'Saldo Historis Simpanan => opening: %d | refunds: %d | unmatched users: %d',
            $historicalSavingsStats['opening_count'],
            $historicalSavingsStats['refund_count'],
            count($historicalSavingsStats['missing_users'])
        ));
        $this->command->line(sprintf(
            'Potongan => savings: %d | logs: %d | cash: %d | unmatched users: %d',
            $potonganStats['savings_count'],
            $potonganStats['log_count'],
            $potonganStats['cash_count'],
            count($potonganStats['missing_users'])
        ));
        $this->command->line(sprintf(
            'Pinjaman => loans: %d | angsuran: %d | unmatched users: %d',
            $loanStats['loan_count'],
            $loanStats['installment_count'],
            count($loanStats['missing_users'])
        ));
        $this->command->line(sprintf(
            'Arus Kas Rekap => inserted/updated: %d | manual sukarela savings: %d | unmatched users: %d',
            $cashStats['cash_count'],
            $cashStats['manual_savings_count'],
            count($cashStats['missing_users'])
        ));
        $this->command->line(sprintf(
            'Penyesuaian Saldo Simpanan Akhir => adjusted: %d | removed: %d | unmatched users: %d',
            $adjustmentStats['adjusted_count'],
            $adjustmentStats['removed_count'],
            count($adjustmentStats['missing_users'])
        ));

        if (!empty($historicalSavingsStats['missing_users'])) {
            $this->command->warn('[KoperasiRekapSeeder] User tidak cocok di Rekap Simpanan:');
            foreach (array_unique($historicalSavingsStats['missing_users']) as $name) {
                $this->command->line(' - ' . $name);
            }
        }
        if (!empty($potonganStats['missing_users'])) {
            $this->command->warn('[KoperasiRekapSeeder] User tidak cocok di Rekap Pemotongan:');
            foreach (array_unique($potonganStats['missing_users']) as $name) {
                $this->command->line(' - ' . $name);
            }
        }
        if (!empty($loanStats['missing_users'])) {
            $this->command->warn('[KoperasiRekapSeeder] User tidak cocok di Rekap Peminjaman:');
            foreach (array_unique($loanStats['missing_users']) as $name) {
                $this->command->line(' - ' . $name);
            }
        }
        if (!empty($cashStats['missing_users'])) {
            $this->command->warn('[KoperasiRekapSeeder] User tidak cocok di Rekap Keuangan:');
            foreach (array_unique($cashStats['missing_users']) as $name) {
                $this->command->line(' - ' . $name);
            }
        }
        if (!empty($adjustmentStats['missing_users'])) {
            $this->command->warn('[KoperasiRekapSeeder] User tidak cocok saat penyesuaian saldo simpanan akhir:');
            foreach (array_unique($adjustmentStats['missing_users']) as $name) {
                $this->command->line(' - ' . $name);
            }
        }
    }

    private function ensureUsersFromFinalSavingsSheet(Worksheet $sheet)
    {
        $stats = [
            'created_count' => 0,
        ];

        $blocks = $this->findSavingsBlocks($sheet);
        if (empty($blocks)) {
            return $stats;
        }

        $existingUsers = DB::table('users')->select('name', 'email', 'member_no')->get();
        $existingNames = [];
        $existingEmails = [];
        $maxMemberNo = 0;

        foreach ($existingUsers as $user) {
            $normalized = $this->normalizePersonName($user->name);
            if ($normalized !== '') {
                $existingNames[$normalized] = true;
            }
            $existingEmails[strtolower($user->email)] = true;
            if (preg_match('/^A-(\d+)$/', (string) $user->member_no, $matches)) {
                $maxMemberNo = max($maxMemberNo, (int) $matches[1]);
            }
        }

        $lastBlock = $this->resolveFinalSavingsBlock($blocks);
        $rowStart = $lastBlock['data_start_row'];
        $rowEnd = $this->resolveBlockEnd($blocks, $lastBlock, $sheet->getHighestDataRow());
        $timestamp = now();

        for ($row = $rowStart; $row <= $rowEnd; $row++) {
            $name = trim((string) $sheet->getCell('C' . $row)->getFormattedValue());
            if ($name === '' || !is_numeric(trim((string) $sheet->getCell('B' . $row)->getFormattedValue()))) {
                continue;
            }

            $normalized = $this->normalizePersonName($name);
            if ($normalized === '') {
                continue;
            }

            if ($this->matchUser($name)) {
                continue;
            }

            if (isset($this->userAliasMap[$normalized])) {
                $normalized = $this->userAliasMap[$normalized];
            }

            if (isset($existingNames[$normalized])) {
                continue;
            }

            $email = $this->makeRekapUserEmail($normalized, $existingEmails);
            $existingEmails[$email] = true;
            $existingNames[$normalized] = true;
            $maxMemberNo++;

            DB::table('users')->insert([
                'name' => $name,
                'email' => $email,
                'email_verified_at' => $timestamp,
                'password' => Hash::make('password'),
                'role' => 'anggota',
                'member_no' => sprintf('A-%03d', $maxMemberNo),
                'status' => 'active',
                'remember_token' => Str::random(10),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            $stats['created_count']++;
        }

        return $stats;
    }

    private function makeRekapUserEmail($normalizedName, array $existingEmails)
    {
        $base = preg_replace('/[^a-z0-9]+/', '', strtolower($normalizedName));
        if ($base === '') {
            $base = 'anggota';
        }

        $email = $base . '@pta-papuabarat.go.id';
        $suffix = 2;
        while (isset($existingEmails[$email])) {
            $email = $base . $suffix . '@pta-papuabarat.go.id';
            $suffix++;
        }

        return $email;
    }

    private function parseKeuSheet(Worksheet $sheet, $targetYear)
    {
        $highestRow = $sheet->getHighestDataRow();
        $bendaharaRows = [];
        $cashRows = [];
        $refundDates = [];

        for ($row = 1; $row <= $highestRow; $row++) {
            $no = trim((string) $sheet->getCell('A' . $row)->getFormattedValue());
            $description = trim((string) $sheet->getCell('C' . $row)->getFormattedValue());
            if ($description === '' || !is_numeric($no)) {
                continue;
            }

            $date = $this->parseKeuDate($sheet->getCell('B' . $row), $targetYear);
            if (!$date) {
                continue;
            }

            $incomeTotal = $this->parseMoney($sheet->getCell('J' . $row)->getFormattedValue());
            $expenseTotal = $this->parseMoney($sheet->getCell('K' . $row)->getFormattedValue());
            $month = (int) $date->format('n');

            if ($description === 'Terima dari Bendahara') {
                $bendaharaRows[$month][] = [
                    'date' => $date->toDateString(),
                    'amount' => $incomeTotal,
                ];
                continue;
            }

            if (stripos($description, 'Pengembalian Simpanan Sukarela') !== false && $expenseTotal > 0) {
                $refundDates[$month] = $date->toDateString();
            }

            if ($description === 'Saldo' && $month !== 1) {
                continue;
            }

            if ($incomeTotal <= 0 && $expenseTotal <= 0) {
                continue;
            }

            $cashRows[] = [
                'date' => $date->toDateString(),
                'month' => $month,
                'description' => $description,
                'income' => $incomeTotal,
                'expense' => $expenseTotal,
            ];
        }

        return [
            'bendahara_rows' => $bendaharaRows,
            'cash_rows' => $cashRows,
            'refund_dates' => $refundDates,
        ];
    }

    private function importHistoricalSavingsSheet(Worksheet $sheet, $targetYear, $superadminId, array $refundDates)
    {
        $stats = [
            'opening_count' => 0,
            'refund_count' => 0,
            'missing_users' => [],
        ];

        $blocks = $this->findSavingsBlocks($sheet);
        if (empty($blocks)) {
            return $stats;
        }

        $openingBlock = $blocks[0];
        $openingEnd = isset($blocks[1]) ? ($blocks[1]['start_row'] - 1) : $sheet->getHighestDataRow();
        $openingDate = Carbon::create($targetYear - 1, 12, 31, 0, 0, 0)->toDateString();

        for ($row = $openingBlock['data_start_row']; $row <= $openingEnd; $row++) {
            $name = trim((string) $sheet->getCell('C' . $row)->getFormattedValue());
            if ($name === '' || !is_numeric(trim((string) $sheet->getCell('B' . $row)->getFormattedValue()))) {
                continue;
            }

            $user = $this->matchUser($name);
            if (!$user) {
                $stats['missing_users'][] = $name;
                continue;
            }

            $openingValues = [
                'pokok' => $this->parseMoney($sheet->getCell('D' . $row)->getFormattedValue()),
                'wajib' => $this->parseMoney($sheet->getCell('E' . $row)->getFormattedValue()),
                'sukarela' => $this->parseMoney($sheet->getCell('F' . $row)->getFormattedValue()),
            ];

            foreach ($openingValues as $type => $amount) {
                if ($amount <= 0) {
                    continue;
                }

                DB::table('savings_transactions')->updateOrInsert(
                    [
                        'user_id' => $user['id'],
                        'type' => $type,
                        'note' => 'Saldo Awal Historis Rekap',
                        'created_at' => $openingDate . ' 00:00:00',
                    ],
                    [
                        'amount' => $amount,
                        'posted_at' => $openingDate . ' 00:00:00',
                        'posted_by' => $superadminId,
                        'created_by' => $superadminId,
                        'updated_at' => $openingDate . ' 00:00:00',
                    ]
                );
                $stats['opening_count']++;
            }
        }

        foreach ($blocks as $index => $block) {
            $month = $block['month'];
            $rowEnd = isset($blocks[$index + 1]) ? ($blocks[$index + 1]['start_row'] - 1) : $sheet->getHighestDataRow();
            $refundDate = $refundDates[$month] ?? Carbon::create($targetYear, $month, 28, 0, 0, 0)->endOfMonth()->toDateString();

            for ($row = $block['data_start_row']; $row <= $rowEnd; $row++) {
                $name = trim((string) $sheet->getCell('C' . $row)->getFormattedValue());
                if ($name === '' || !is_numeric(trim((string) $sheet->getCell('B' . $row)->getFormattedValue()))) {
                    continue;
                }

                $refundAmount = $this->parseMoney($sheet->getCell('N' . $row)->getFormattedValue());
                if ($refundAmount <= 0) {
                    continue;
                }

                $user = $this->matchUser($name);
                if (!$user) {
                    $stats['missing_users'][] = $name;
                    continue;
                }

                DB::table('savings_transactions')->updateOrInsert(
                    [
                        'user_id' => $user['id'],
                        'type' => 'sukarela',
                        'note' => 'Pengembalian Sukarela Rekap',
                        'created_at' => $refundDate . ' 00:00:00',
                    ],
                    [
                        'amount' => $refundAmount * -1,
                        'posted_at' => $refundDate . ' 00:00:00',
                        'posted_by' => $superadminId,
                        'created_by' => $superadminId,
                        'updated_at' => $refundDate . ' 00:00:00',
                    ]
                );
                $stats['refund_count']++;
            }
        }

        return $stats;
    }

    private function importPotonganSheet(Worksheet $sheet, $targetYear, $superadminId, $bendaharaKantorId, array $bendaharaRows)
    {
        $stats = [
            'savings_count' => 0,
            'log_count' => 0,
            'cash_count' => 0,
            'missing_users' => [],
            'month_dates' => [],
        ];

        $blocks = $this->findPotonganBlocks($sheet);
        $postedMonthLimit = $this->postedMonthLimit();
        foreach ($blocks as $index => $block) {
            $month = $block['month'];
            $shouldPostFinancial = $month <= $postedMonthLimit;
            $rowStart = $block['data_start_row'];
            $nextBlock = $blocks[$index + 1] ?? null;
            $rowEnd = $nextBlock ? ($nextBlock['start_row'] - 1) : $sheet->getHighestDataRow();

            $monthEntries = [];
            $monthTotal = 0;

            for ($row = $rowStart; $row <= $rowEnd; $row++) {
                $name = trim((string) $sheet->getCell('B' . $row)->getFormattedValue());
                if ($name === '') {
                    continue;
                }

                $user = $this->matchUser($name);
                if (!$user) {
                    $stats['missing_users'][] = $name;
                    continue;
                }

                $pokok = $this->parseMoney($sheet->getCell('C' . $row)->getFormattedValue());
                $wajib = $this->parseMoney($sheet->getCell('D' . $row)->getFormattedValue());
                $sukarela = $this->parseMoney($sheet->getCell('E' . $row)->getFormattedValue());
                $principal = $this->parseMoney($sheet->getCell('F' . $row)->getFormattedValue());
                $fee = $this->parseMoney($sheet->getCell('G' . $row)->getFormattedValue());
                $other = $this->parseMoney($sheet->getCell('H' . $row)->getFormattedValue());
                $note = trim((string) $sheet->getCell('I' . $row)->getFormattedValue());

                $total = $pokok + $wajib + $sukarela + $principal + $fee + $other;
                if ($total <= 0) {
                    continue;
                }

                $userId = $user['id'];
                if (!isset($monthEntries[$userId])) {
                    $monthEntries[$userId] = [
                        'user' => $user,
                        'pokok' => 0,
                        'wajib' => 0,
                        'sukarela' => 0,
                        'principal' => 0,
                        'fee' => 0,
                        'other' => 0,
                        'notes' => [],
                        'total' => 0,
                    ];
                }

                $monthEntries[$userId]['pokok'] += $pokok;
                $monthEntries[$userId]['wajib'] += $wajib;
                $monthEntries[$userId]['sukarela'] += $sukarela;
                $monthEntries[$userId]['principal'] += $principal;
                $monthEntries[$userId]['fee'] += $fee;
                $monthEntries[$userId]['other'] += $other;
                $monthEntries[$userId]['total'] += $total;

                if ($note !== '') {
                    $monthEntries[$userId]['notes'][$note] = $note;
                }

                $monthTotal += $total;
            }

            $entryDate = $this->resolveMonthCashDate($month, $monthTotal, $bendaharaRows, $targetYear);
            $stats['month_dates'][$month] = $entryDate;

            foreach ($monthEntries as $entry) {
                $userId = $entry['user']['id'];
                if ($shouldPostFinancial) {
                    foreach (['pokok', 'wajib', 'sukarela'] as $type) {
                        if ($entry[$type] <= 0) {
                            continue;
                        }

                        DB::table('savings_transactions')->updateOrInsert(
                            [
                                'user_id' => $userId,
                                'type' => $type,
                                'note' => 'Potong Gaji',
                                'created_at' => $entryDate . ' 00:00:00',
                            ],
                            [
                                'amount' => $entry[$type],
                                'posted_at' => $entryDate . ' 00:00:00',
                                'posted_by' => $superadminId,
                                'created_by' => $superadminId,
                                'updated_at' => $entryDate . ' 00:00:00',
                            ]
                        );
                        $stats['savings_count']++;
                    }
                }

                DB::table('deduction_logs')->updateOrInsert(
                    [
                        'user_id' => $userId,
                        'month' => $month,
                        'year' => $targetYear,
                    ],
                    [
                        'total_amount' => $entry['total'],
                        'processed_at' => $entryDate . ' 00:00:00',
                        'status' => 'verified',
                        'verified_by' => $bendaharaKantorId,
                        'verified_at' => $entryDate . ' 00:00:00',
                        'created_by' => $superadminId,
                        'updated_at' => $entryDate . ' 00:00:00',
                        'created_at' => $entryDate . ' 00:00:00',
                    ]
                );
                $stats['log_count']++;

                if ($shouldPostFinancial) {
                    DB::table('cash_entries')->updateOrInsert(
                        [
                            'entry_date' => $entryDate,
                            'direction' => 'in',
                            'description' => 'Terima dari Bendahara (' . $entry['user']['name'] . ')',
                            'category' => 'potongan',
                            'user_id' => $userId,
                        ],
                        [
                            'amount' => $entry['total'],
                            'created_by' => $superadminId,
                            'status' => 'approved',
                            'verified_by' => $bendaharaKantorId,
                            'verified_at' => $entryDate . ' 00:00:00',
                            'updated_at' => $entryDate . ' 00:00:00',
                            'created_at' => $entryDate . ' 00:00:00',
                        ]
                    );
                    $stats['cash_count']++;
                }
            }
        }

        return $stats;
    }

    private function importPinjamanSheet(Worksheet $sheet, $targetYear, $superadminId, $sekretarisId, $bendaharaId, $ketuaId, array $monthDates)
    {
        $stats = [
            'loan_count' => 0,
            'installment_count' => 0,
            'missing_users' => [],
        ];

        $monthColumns = [
            1 => ['principal' => 'E', 'fee' => 'F'],
            2 => ['principal' => 'G', 'fee' => 'H'],
            3 => ['principal' => 'I', 'fee' => 'J'],
            4 => ['principal' => 'K', 'fee' => 'L'],
            5 => ['principal' => 'M', 'fee' => 'N'],
            6 => ['principal' => 'O', 'fee' => 'P'],
            7 => ['principal' => 'Q', 'fee' => 'R'],
            8 => ['principal' => 'S', 'fee' => 'T'],
            9 => ['principal' => 'U', 'fee' => 'V'],
            10 => ['principal' => 'W', 'fee' => 'X'],
            11 => ['principal' => 'Y', 'fee' => 'Z'],
            12 => ['principal' => 'AA', 'fee' => 'AB'],
        ];

        $highestRow = $sheet->getHighestDataRow();
        for ($row = 6; $row <= $highestRow; $row++) {
            $name = trim((string) $sheet->getCell('B' . $row)->getFormattedValue());
            $loanCell = trim((string) $sheet->getCell('C' . $row)->getFormattedValue());
            if ($name === '' || $loanCell === '') {
                continue;
            }

            $loanDate = $this->parseLoanCellDate($loanCell);
            if (!$loanDate) {
                continue;
            }

            $user = $this->matchUser($name);
            if (!$user) {
                $stats['missing_users'][] = $name;
                continue;
            }

            $loanYear = (int) $loanDate->format('Y');
            $originalAmount = $this->parseMoneyFromParentheses($loanCell);
            $openingOutstanding = $this->parseMoney($sheet->getCell('D' . $row)->getFormattedValue());
            $termHint = $this->parseTermHint((string) $sheet->getCell('AD' . $row)->getFormattedValue());

            $monthlyPayments = [];
            foreach ($monthColumns as $month => $columns) {
                $principal = $this->parseMoney($sheet->getCell($columns['principal'] . $row)->getFormattedValue());
                $fee = $this->parseMoney($sheet->getCell($columns['fee'] . $row)->getFormattedValue());
                if ($principal <= 0 && $fee <= 0) {
                    continue;
                }
                $monthlyPayments[$month] = [
                    'principal' => $principal,
                    'fee' => $fee,
                ];
            }

            $loanAmount = $loanYear < $targetYear
                ? ($openingOutstanding > 0 ? $openingOutstanding : $originalAmount)
                : ($originalAmount > 0 ? $originalAmount : $openingOutstanding);

            if ($loanAmount <= 0) {
                continue;
            }

            $termMonths = $loanYear < $targetYear
                ? max(1, count($monthlyPayments))
                : max(1, $termHint ?: count($monthlyPayments));

            $loanCode = 'rekap-' . $row . '-' . $user['id'];
            $loanData = [
                'user_id' => $user['id'],
                'member_no' => $user['member_no'],
                'applicant_name' => $user['name'],
                'nip' => $user['nip'],
                'unit_kerja' => $user['unit_kerja'],
                'phone' => $user['phone'],
                'amount' => $loanAmount,
                'term_months' => $termMonths,
                'purpose' => 'Import Rekap Peminjaman 2026',
                'status' => 'approved_chairman',
                'sekretaris_id' => $sekretarisId,
                'bendahara_id' => $bendaharaId,
                'ketua_id' => $ketuaId,
                'reviewed_at' => $loanDate->toDateTimeString(),
                'treasurer_approved_at' => $loanDate->toDateTimeString(),
                'chairman_approved_at' => $loanDate->toDateTimeString(),
                'transfered_at' => $loanDate->toDateTimeString(),
                'transfered_by' => $superadminId,
                'import_loan_code' => $loanCode,
                'updated_at' => $loanDate->toDateTimeString(),
            ];

            $existingLoan = DB::table('loans')->where('import_loan_code', $loanCode)->first();
            if ($existingLoan) {
                DB::table('loans')->where('id', $existingLoan->id)->update($loanData);
                $loanId = $existingLoan->id;
            } else {
                $loanData['created_at'] = $loanDate->toDateTimeString();
                $loanId = DB::table('loans')->insertGetId($loanData);
            }
            $stats['loan_count']++;

            $installmentNo = 1;
            $postedMonthLimit = $this->postedMonthLimit();
            foreach ($monthlyPayments as $month => $payment) {
                if ($month > $postedMonthLimit) {
                    continue;
                }

                $paidDate = isset($monthDates[$month])
                    ? Carbon::parse($monthDates[$month])
                    : Carbon::create($targetYear, $month, 1, 0, 0, 0);

                DB::table('loan_installment_payments')->updateOrInsert(
                    [
                        'loan_id' => $loanId,
                        'installment_no' => $installmentNo,
                    ],
                    [
                        'paid_at' => $paidDate->toDateString(),
                        'amount_principal' => $payment['principal'],
                        'amount_fee' => $payment['fee'],
                        'note' => 'Potong Gaji',
                        'created_by' => $superadminId,
                        'validated_by' => $superadminId,
                        'validated_at' => $paidDate->toDateTimeString(),
                        'status' => 'approved',
                        'is_settlement' => false,
                        'updated_at' => $paidDate->toDateTimeString(),
                        'created_at' => $paidDate->toDateTimeString(),
                    ]
                );
                $stats['installment_count']++;
                $installmentNo++;
            }
        }

        return $stats;
    }

    private function collectMonthlySavingsFromFinalSavingsSheet(Worksheet $sheet)
    {
        $details = [];
        $blocks = $this->findSavingsBlocks($sheet);
        if (empty($blocks)) {
            return $details;
        }

        $finalBlock = $this->resolveFinalSavingsBlock($blocks);
        $rowStart = $finalBlock['data_start_row'];
        $rowEnd = $this->resolveBlockEnd($blocks, $finalBlock, $sheet->getHighestDataRow());

        for ($row = $rowStart; $row <= $rowEnd; $row++) {
            $name = trim((string) $sheet->getCell('C' . $row)->getFormattedValue());
            if ($name === '' || !is_numeric(trim((string) $sheet->getCell('B' . $row)->getFormattedValue()))) {
                continue;
            }

            $user = $this->matchUser($name);
            if (!$user) {
                continue;
            }

            $details[$user['id']] = [
                'pokok' => $this->parseMoney($sheet->getCell('G' . $row)->getFormattedValue()),
                'wajib' => $this->parseMoney($sheet->getCell('H' . $row)->getFormattedValue()),
                'sukarela' => $this->parseMoney($sheet->getCell('I' . $row)->getFormattedValue()),
            ];
        }

        return $details;
    }

    private function importKeuCashEntries(array $rows, $targetYear, $superadminId, array $manualSavingsDetails = [])
    {
        $stats = [
            'cash_count' => 0,
            'manual_savings_count' => 0,
            'missing_users' => [],
        ];

        foreach ($rows as $row) {
            $direction = $row['income'] > 0 ? 'in' : 'out';
            $amount = $direction === 'in' ? $row['income'] : $row['expense'];
            if ($amount <= 0) {
                continue;
            }

            $description = $row['description'];
            $user = null;
            $category = null;
            $manualSavingsTypes = null;

            if ($description === 'Saldo') {
                $category = 'saldo_awal';
            } elseif (stripos($description, 'Terima Simpanan Sukarela an.') === 0) {
                $category = 'simpanan';
                $memberName = trim(substr($description, strlen('Terima Simpanan Sukarela an.')));
                $user = $this->matchUser($memberName);
                if (!$user) {
                    $stats['missing_users'][] = $memberName;
                }
                $manualSavingsTypes = ['sukarela' => $amount];
            } elseif (stripos($description, 'Penerimaan Anggota Baru') === 0) {
                $category = 'simpanan';
                $memberName = trim(substr($description, strlen('Penerimaan Anggota Baru')));
                $user = $this->matchUser($memberName);
                if (!$user) {
                    $stats['missing_users'][] = $memberName;
                } else {
                    $detail = $manualSavingsDetails[$user['id']] ?? null;
                    $detailTotal = $detail
                        ? ((float) ($detail['pokok'] ?? 0) + (float) ($detail['wajib'] ?? 0) + (float) ($detail['sukarela'] ?? 0))
                        : 0;
                    $manualSavingsTypes = $detailTotal > 0
                        ? $detail
                        : ['sukarela' => $amount];
                }
            } elseif (stripos($description, 'Pelunasan') === 0) {
                $category = 'pelunasan';
                $memberName = $this->extractNameAfterKeyword($description, ['Pelunasan Peminjaman an.', 'Pelunasan']);
                $user = $this->matchUser($memberName);
                if (!$user && $memberName !== '') {
                    $stats['missing_users'][] = $memberName;
                }
            } elseif (preg_match('/^(Pinjaman|Peminjaman)/i', $description)) {
                $category = 'peminjaman';
                $memberName = $this->extractNameAfterKeyword($description, ['Peminjaman', 'Pinjaman']);
                $user = $this->matchUser($memberName);
                if (!$user && $memberName !== '') {
                    $stats['missing_users'][] = $memberName;
                }
            } elseif ($direction === 'out') {
                $category = $this->mapExpenseCategory($description);
            }

            DB::table('cash_entries')->updateOrInsert(
                [
                    'entry_date' => $row['date'],
                    'direction' => $direction,
                    'description' => $description,
                    'amount' => $amount,
                    'category' => $category,
                    'user_id' => $user['id'] ?? null,
                ],
                [
                    'created_by' => $superadminId,
                    'status' => 'approved',
                    'verified_by' => $superadminId,
                    'verified_at' => $row['date'] . ' 00:00:00',
                    'updated_at' => $row['date'] . ' 00:00:00',
                    'created_at' => $row['date'] . ' 00:00:00',
                ]
            );
            $stats['cash_count']++;

            if ($category === 'simpanan' && $direction === 'in' && $user) {
                $manualSavingsTypes = $manualSavingsTypes ?: ['sukarela' => $amount];
                foreach (['pokok', 'wajib', 'sukarela'] as $type) {
                    $typeAmount = (float) ($manualSavingsTypes[$type] ?? 0);
                    if ($typeAmount <= 0) {
                        continue;
                    }

                    DB::table('savings_transactions')->updateOrInsert(
                        [
                            'user_id' => $user['id'],
                            'type' => $type,
                            'note' => 'Import Rekap Keu',
                            'created_at' => $row['date'] . ' 00:00:00',
                        ],
                        [
                            'amount' => $typeAmount,
                            'posted_at' => $row['date'] . ' 00:00:00',
                            'posted_by' => $superadminId,
                            'created_by' => $superadminId,
                            'updated_at' => $row['date'] . ' 00:00:00',
                        ]
                    );
                    $stats['manual_savings_count']++;
                }
            }
        }

        return $stats;
    }

    private function reconcileFinalSavingsBalances(Worksheet $sheet, $targetYear, $superadminId)
    {
        $stats = [
            'adjusted_count' => 0,
            'removed_count' => 0,
            'missing_users' => [],
        ];

        $blocks = $this->findSavingsBlocks($sheet);
        if (empty($blocks)) {
            return $stats;
        }

        $lastBlock = $this->resolveFinalSavingsBlock($blocks);
        $rowStart = $lastBlock['data_start_row'];
        $rowEnd = $this->resolveBlockEnd($blocks, $lastBlock, $sheet->getHighestDataRow());
        $adjustmentDate = Carbon::create($targetYear, $lastBlock['month'], 1, 0, 0, 0)->endOfMonth()->toDateString();
        $adjustmentNote = 'Penyesuaian Rekap Simpanan Akhir';

        $expectedByUser = [];
        for ($row = $rowStart; $row <= $rowEnd; $row++) {
            $name = trim((string) $sheet->getCell('C' . $row)->getFormattedValue());
            if ($name === '' || !is_numeric(trim((string) $sheet->getCell('B' . $row)->getFormattedValue()))) {
                continue;
            }

            $user = $this->matchUser($name);
            if (!$user) {
                $stats['missing_users'][] = $name;
                continue;
            }

            $expectedByUser[$user['id']] = [
                'name' => $user['name'],
                'pokok' => $this->parseMoney($sheet->getCell('J' . $row)->getFormattedValue()),
                'wajib' => $this->parseMoney($sheet->getCell('K' . $row)->getFormattedValue()),
                'sukarela' => $this->parseMoney($sheet->getCell('L' . $row)->getFormattedValue()),
            ];
        }

        foreach ($expectedByUser as $userId => $expected) {
            $actualRows = DB::table('savings_transactions')
                ->select('type', DB::raw('SUM(amount) as total'))
                ->where('user_id', $userId)
                ->groupBy('type')
                ->get()
                ->keyBy('type');

            foreach (['pokok', 'wajib', 'sukarela'] as $type) {
                $actual = isset($actualRows[$type]) ? (float) $actualRows[$type]->total : 0.0;
                $expectedAmount = (float) $expected[$type];
                $diff = round($expectedAmount - $actual, 2);

                $key = [
                    'user_id' => $userId,
                    'type' => $type,
                    'note' => $adjustmentNote,
                    'created_at' => $adjustmentDate . ' 00:00:00',
                ];

                if (abs($diff) < 0.01) {
                    $deleted = DB::table('savings_transactions')->where($key)->delete();
                    if ($deleted > 0) {
                        $stats['removed_count'] += $deleted;
                    }
                    continue;
                }

                DB::table('savings_transactions')->updateOrInsert(
                    $key,
                    [
                        'amount' => $diff,
                        'posted_at' => $adjustmentDate . ' 00:00:00',
                        'posted_by' => $superadminId,
                        'created_by' => $superadminId,
                        'updated_at' => $adjustmentDate . ' 00:00:00',
                    ]
                );

                $stats['adjusted_count']++;
            }
        }

        $existingUserIds = DB::table('savings_transactions')
            ->distinct()
            ->pluck('user_id')
            ->all();

        foreach ($existingUserIds as $userId) {
            if (isset($expectedByUser[$userId])) {
                continue;
            }

            $actualRows = DB::table('savings_transactions')
                ->select('type', DB::raw('SUM(amount) as total'))
                ->where('user_id', $userId)
                ->groupBy('type')
                ->get()
                ->keyBy('type');

            foreach (['pokok', 'wajib', 'sukarela'] as $type) {
                $actual = isset($actualRows[$type]) ? (float) $actualRows[$type]->total : 0.0;
                if (abs($actual) < 0.01) {
                    continue;
                }

                DB::table('savings_transactions')->updateOrInsert(
                    [
                        'user_id' => $userId,
                        'type' => $type,
                        'note' => $adjustmentNote,
                        'created_at' => $adjustmentDate . ' 00:00:00',
                    ],
                    [
                        'amount' => round($actual * -1, 2),
                        'posted_at' => $adjustmentDate . ' 00:00:00',
                        'posted_by' => $superadminId,
                        'created_by' => $superadminId,
                        'updated_at' => $adjustmentDate . ' 00:00:00',
                    ]
                );

                $stats['adjusted_count']++;
            }
        }

        return $stats;
    }

    private function buildUserMap()
    {
        $users = DB::table('users')
            ->select('id', 'name', 'member_no', 'nip', 'unit_kerja', 'phone')
            ->orderBy('id')
            ->get();

        foreach ($users as $user) {
            $normalized = $this->normalizePersonName($user->name);
            if ($normalized === '') {
                continue;
            }
            $this->userMap[$normalized] = [
                'id' => $user->id,
                'name' => $user->name,
                'member_no' => $user->member_no,
                'nip' => $user->nip,
                'unit_kerja' => $user->unit_kerja,
                'phone' => $user->phone,
            ];
        }
    }

    private function matchUser($name)
    {
        $normalized = $this->normalizePersonName($name);
        if ($normalized === '') {
            return null;
        }

        if (isset($this->userAliasMap[$normalized])) {
            $normalized = $this->userAliasMap[$normalized];
        }

        if (isset($this->userMap[$normalized])) {
            return $this->userMap[$normalized];
        }

        $tokens = explode(' ', $normalized);
        $best = null;
        $bestScore = 0;

        foreach ($this->userMap as $candidateName => $candidateUser) {
            $candidateTokens = explode(' ', $candidateName);
            $intersect = array_intersect($tokens, $candidateTokens);
            $score = count($intersect);

            if ($score > $bestScore && $score > 0) {
                $bestScore = $score;
                $best = $candidateUser;
            }
        }

        return $bestScore > 0 ? $best : null;
    }

    private function findPotonganBlocks(Worksheet $sheet)
    {
        $blocks = [];
        $highestRow = $sheet->getHighestDataRow();
        for ($row = 1; $row <= $highestRow; $row++) {
            $title = trim((string) $sheet->getCell('A' . $row)->getFormattedValue());
            if (stripos($title, 'REKAP PEMOTONGAN KOPERASI BULAN') === 0) {
                $month = $this->monthNumberFromTitle($title);
                if ($month) {
                    $blocks[] = [
                        'start_row' => $row,
                        'data_start_row' => $row + 5,
                        'month' => $month,
                    ];
                }
            }
        }

        return $blocks;
    }

    private function findSavingsBlocks(Worksheet $sheet)
    {
        $blocks = [];
        $highestRow = $sheet->getHighestDataRow();
        for ($row = 1; $row <= $highestRow; $row++) {
            $title = trim((string) $sheet->getCell('B' . $row)->getFormattedValue());
            if (preg_match('/^([A-Za-z]{3})-\d{2}$/', $title, $matches)) {
                $month = $this->monthNumberFromShort($matches[1]);
                if ($month) {
                    $blocks[] = [
                        'start_row' => $row,
                        'data_start_row' => $row + 4,
                        'month' => $month,
                    ];
                }
            }
        }

        return $blocks;
    }

    private function resolveFinalSavingsBlock(array $blocks)
    {
        $postedMonthLimit = $this->postedMonthLimit();
        $fallback = end($blocks);

        foreach (array_reverse($blocks) as $block) {
            if ((int) $block['month'] <= $postedMonthLimit) {
                return $block;
            }
        }

        return $fallback;
    }

    private function resolveBlockEnd(array $blocks, array $selectedBlock, $highestRow)
    {
        foreach ($blocks as $index => $block) {
            if ((int) $block['start_row'] === (int) $selectedBlock['start_row']) {
                return isset($blocks[$index + 1])
                    ? ((int) $blocks[$index + 1]['start_row'] - 1)
                    : (int) $highestRow;
            }
        }

        return (int) $highestRow;
    }

    private function postedMonthLimit()
    {
        $limit = (int) config('koperasi.rekap_import.posted_month_limit', 12);
        if ($limit < 1) {
            return 1;
        }
        if ($limit > 12) {
            return 12;
        }

        return $limit;
    }

    private function resolveMonthCashDate($month, $expectedTotal, array $bendaharaRows, $year)
    {
        if (!empty($bendaharaRows[$month])) {
            foreach ($bendaharaRows[$month] as $row) {
                if ((float) $row['amount'] === (float) $expectedTotal) {
                    return $row['date'];
                }
            }

            return $bendaharaRows[$month][0]['date'];
        }

        return Carbon::create($year, $month, 1, 0, 0, 0)->toDateString();
    }

    private function parseKeuDate($value, $yearFallback)
    {
        if ($value instanceof Cell) {
            $rawValue = $value->getValue();
            if (is_numeric($rawValue)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($rawValue))->startOfDay();
            }

            $value = $value->getFormattedValue();
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/(20\d{2})年(\d{1,2})月(\d{1,2})日/u', $value, $matches)) {
            return Carbon::create((int) $matches[1], (int) $matches[2], (int) $matches[3], 0, 0, 0);
        }

        if (preg_match('/(\d{1,2})\/(\d{1,2})\/(20\d{2})/', $value, $matches)) {
            return Carbon::create((int) $matches[3], (int) $matches[2], (int) $matches[1], 0, 0, 0);
        }

        return null;
    }

    private function parseLoanCellDate($value)
    {
        $value = trim((string) $value);
        if (preg_match('/(\d{1,2})\/(\d{1,2})\/(20\d{2})/', $value, $matches)) {
            return Carbon::create((int) $matches[3], (int) $matches[2], (int) $matches[1], 0, 0, 0);
        }

        if (preg_match('/(20\d{2})年(\d{1,2})月(\d{1,2})日/u', $value, $matches)) {
            return Carbon::create((int) $matches[1], (int) $matches[2], (int) $matches[3], 0, 0, 0);
        }

        return null;
    }

    private function parseMoneyFromParentheses($value)
    {
        if (preg_match('/\(([\d\.,]+)\)/', (string) $value, $matches)) {
            return $this->parseMoney($matches[1]);
        }

        return 0.0;
    }

    private function parseTermHint($value)
    {
        if (preg_match('/(\d+)/', (string) $value, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function parseMoney($value)
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '-' || stripos($value, 'Rp -') !== false) {
            return 0.0;
        }

        $negative = strpos($value, '-') !== false;
        $clean = preg_replace('/[^0-9]/', '', $value);
        if ($clean === '' || $clean === '-') {
            return 0.0;
        }

        $amount = (float) $clean;
        return $negative ? ($amount * -1) : $amount;
    }

    private function normalizePersonName($value)
    {
        $value = strtolower((string) $value);
        $value = preg_replace('/\(\d+\)/', ' ', $value);
        $value = preg_replace('/\b\d+\b/', ' ', $value);
        $value = str_replace(['.', ',', ';', ':', '-', "\n", "\r", "\t", "'"], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        $value = trim($value);

        $stopWords = [
            'dr', 'drs', 'h', 'hj', 's', 'sh', 'mh', 'm', 'sag', 'shi', 'se', 'spd', 'sd',
            'amd', 'ab', 'kom', 'st', 'ssi', 'sikom', 'a', 'md', 'mhum', 'pta', 'papua', 'barat',
        ];
        $tokens = array_values(array_filter(explode(' ', $value), function ($token) use ($stopWords) {
            return $token !== '' && !in_array($token, $stopWords, true);
        }));

        return trim(implode(' ', $tokens));
    }

    private function monthNumberFromTitle($title)
    {
        $map = [
            'JANUARI' => 1,
            'FEBRUARI' => 2,
            'MARET' => 3,
            'APRIL' => 4,
            'MEI' => 5,
            'JUNI' => 6,
            'JULI' => 7,
            'AGUSTUS' => 8,
            'SEPTEMBER' => 9,
            'OKTOBER' => 10,
            'NOVEMBER' => 11,
            'DESEMBER' => 12,
        ];

        foreach ($map as $name => $number) {
            if (stripos($title, $name) !== false) {
                return $number;
            }
        }

        return null;
    }

    private function monthNumberFromShort($short)
    {
        $map = [
            'jan' => 1,
            'feb' => 2,
            'mar' => 3,
            'apr' => 4,
            'may' => 5,
            'jun' => 6,
            'jul' => 7,
            'aug' => 8,
            'sep' => 9,
            'oct' => 10,
            'nov' => 11,
            'dec' => 12,
        ];

        $short = strtolower($short);
        return $map[$short] ?? null;
    }

    private function extractNameAfterKeyword($description, array $keywords)
    {
        foreach ($keywords as $keyword) {
            if (stripos($description, $keyword) === 0) {
                return trim(substr($description, strlen($keyword)));
            }
        }

        return '';
    }

    private function mapExpenseCategory($description)
    {
        $description = strtolower($description);

        if (strpos($description, 'rat') !== false) {
            return 'rat';
        }
        if (strpos($description, 'adm transfer') !== false || strpos($description, 'transfer') !== false) {
            return 'adm_transfer';
        }
        if (strpos($description, 'adm') !== false) {
            return 'adm';
        }
        if (strpos($description, 'atk') !== false) {
            return 'atk';
        }

        return 'lain-lain';
    }

    private function findSheetCaseInsensitive($spreadsheet, $targetName)
    {
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if (mb_strtolower($sheet->getTitle()) === mb_strtolower($targetName)) {
                return $sheet;
            }
        }

        return null;
    }
}
