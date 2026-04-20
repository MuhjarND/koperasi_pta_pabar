<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KoperasiExcelSeeder extends Seeder
{
    public function run()
    {
        $path = (string) config('koperasi.excel_import.xlsx_path');
        $sheetAnggotaName = (string) config('koperasi.excel_import.sheet_anggota', 'Anggota');
        $sheetSimpananName = (string) config('koperasi.excel_import.sheet_simpanan', 'Simpanan');
        $sheetPinjamanName = (string) config('koperasi.excel_import.sheet_pinjaman', 'Pinjaman');
        $sheetAngsuranName = (string) config('koperasi.excel_import.sheet_angsuran', 'Angsuran');
        $importYearAngsuran = (int) config('koperasi.excel_import.import_year_angsuran', (int) date('Y'));
        $includeDisbursementCash = (bool) config('koperasi.excel_import.include_historical_disbursement_cash', false);
        $defaultPassword = (string) config('koperasi.excel_import.default_password', 'ptapabar');
        $defaultDate = (string) config('koperasi.excel_import.default_date', '2026-01-01');

        if (!is_file($path)) {
            $this->command->error('[KoperasiExcelSeeder] File XLSX tidak ditemukan: ' . $path);
            return;
        }

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable $e) {
            $this->command->error('[KoperasiExcelSeeder] Gagal membuka XLSX: ' . $e->getMessage());
            return;
        }

        $sheetAnggota = $this->findSheetCaseInsensitive($spreadsheet, $sheetAnggotaName);
        $sheetSimpanan = $this->findSheetCaseInsensitive($spreadsheet, $sheetSimpananName);
        $sheetPinjaman = $this->findSheetCaseInsensitive($spreadsheet, $sheetPinjamanName);
        $sheetAngsuran = $this->findSheetCaseInsensitive($spreadsheet, $sheetAngsuranName);

        if (!$sheetAnggota && !$sheetSimpanan && !$sheetPinjaman && !$sheetAngsuran) {
            $this->command->error('[KoperasiExcelSeeder] Tidak ada sheet import yang ditemukan (Anggota/Simpanan/Pinjaman/Angsuran).');
            return;
        }

        $userStats = [
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];
        if ($sheetAnggota) {
            $userStats = $this->seedAnggota($sheetAnggota, $defaultPassword);
        } else {
            $this->command->warn('[KoperasiExcelSeeder] Sheet anggota tidak ditemukan, import user dilewati.');
        }
        $superAdminId = DB::table('users')
            ->where('role', 'superadmin')
            ->orderBy('id')
            ->value('id');

        if (!$superAdminId) {
            $this->command->error('[KoperasiExcelSeeder] User superadmin tidak ditemukan. Import dibatalkan.');
            return;
        }

        $savingsStats = [
            'savings_inserted' => 0,
            'savings_updated' => 0,
            'savings_deleted' => 0,
            'skipped' => 0,
            'cash_inserted' => 0,
            'cash_updated' => 0,
            'cash_deleted' => 0,
            'missing_users' => [],
        ];
        if ($sheetSimpanan) {
            $savingsStats = $this->seedSimpanan($sheetSimpanan, $defaultDate, $superAdminId);
        } else {
            $this->command->warn('[KoperasiExcelSeeder] Sheet simpanan tidak ditemukan, import simpanan dilewati.');
        }

        $loanStats = [
            'loan_inserted' => 0,
            'loan_updated' => 0,
            'loan_skipped' => 0,
            'disbursement_cash_inserted' => 0,
            'disbursement_cash_updated' => 0,
            'disbursement_cash_deleted' => 0,
            'missing_users' => [],
            'loan_code_map' => [],
        ];
        if ($sheetPinjaman) {
            $loanStats = $this->seedPinjaman($sheetPinjaman, $defaultDate, $superAdminId, $includeDisbursementCash);
        } else {
            $this->command->warn('[KoperasiExcelSeeder] Sheet pinjaman tidak ditemukan, import pinjaman dilewati.');
        }

        $installmentStats = [
            'installment_inserted' => 0,
            'installment_updated' => 0,
            'installment_skipped' => 0,
            'missing_loan_codes' => [],
            'settlement_cash_inserted' => 0,
            'settlement_cash_updated' => 0,
        ];
        if ($sheetAngsuran) {
            $installmentStats = $this->seedAngsuran($sheetAngsuran, $defaultDate, $superAdminId, $importYearAngsuran);
        } else {
            $this->command->warn('[KoperasiExcelSeeder] Sheet angsuran tidak ditemukan, import angsuran dilewati.');
        }

        $this->command->info('[KoperasiExcelSeeder] Ringkasan import selesai.');
        $this->command->line(sprintf(
            'Users    => inserted: %d | updated: %d | skipped: %d',
            $userStats['inserted'],
            $userStats['updated'],
            $userStats['skipped']
        ));
        $this->command->line(sprintf(
            'Simpanan => inserted: %d | updated: %d | deleted: %d | skipped: %d',
            $savingsStats['savings_inserted'],
            $savingsStats['savings_updated'],
            $savingsStats['savings_deleted'],
            $savingsStats['skipped']
        ));
        $this->command->line(sprintf(
            'Arus Kas => inserted: %d | updated: %d | deleted: %d',
            $savingsStats['cash_inserted'],
            $savingsStats['cash_updated'],
            $savingsStats['cash_deleted']
        ));
        $this->command->line('Mode Pokok => fixed date 2026-01-01 aktif');
        $this->command->line(sprintf(
            'Pinjaman => inserted: %d | updated: %d | skipped: %d',
            $loanStats['loan_inserted'],
            $loanStats['loan_updated'],
            $loanStats['loan_skipped']
        ));
        $this->command->line(sprintf(
            'Arus Kas Pencairan => inserted: %d | updated: %d | deleted: %d',
            $loanStats['disbursement_cash_inserted'],
            $loanStats['disbursement_cash_updated'],
            $loanStats['disbursement_cash_deleted']
        ));
        $this->command->line('Mode Cash Pencairan Historis => ' . ($includeDisbursementCash ? 'AKTIF' : 'NONAKTIF'));
        $this->command->line(sprintf(
            'Angsuran (%d) => inserted: %d | updated: %d | skipped: %d',
            $importYearAngsuran,
            $installmentStats['installment_inserted'],
            $installmentStats['installment_updated'],
            $installmentStats['installment_skipped']
        ));
        $this->command->line(sprintf(
            'Arus Kas Pelunasan => inserted: %d | updated: %d',
            $installmentStats['settlement_cash_inserted'],
            $installmentStats['settlement_cash_updated']
        ));

        if (!empty($savingsStats['missing_users'])) {
            $this->command->warn('[KoperasiExcelSeeder] Email simpanan tidak ditemukan di users:');
            foreach ($savingsStats['missing_users'] as $email) {
                $this->command->line(' - ' . $email);
            }
        }
        if (!empty($loanStats['missing_users'])) {
            $this->command->warn('[KoperasiExcelSeeder] Email pinjaman tidak ditemukan di users:');
            foreach ($loanStats['missing_users'] as $email) {
                $this->command->line(' - ' . $email);
            }
        }
        if (!empty($installmentStats['missing_loan_codes'])) {
            $this->command->warn('[KoperasiExcelSeeder] Kode pinjaman angsuran tidak ditemukan:');
            foreach ($installmentStats['missing_loan_codes'] as $code) {
                $this->command->line(' - ' . $code);
            }
        }
    }

    private function seedAnggota(Worksheet $sheet, $defaultPassword)
    {
        $stats = [
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        $headerMap = $this->headerMap($sheet);
        $highestRow = $sheet->getHighestDataRow();
        $nextMemberSeq = $this->nextMemberSequence();

        for ($row = 2; $row <= $highestRow; $row++) {
            $record = $this->rowData($sheet, $headerMap, $row);

            $email = strtolower((string) $this->cleanText($this->valueFromKeys($record, ['email'])));
            if ($email === '') {
                $stats['skipped']++;
                continue;
            }

            $role = $this->normalizeRole((string) $this->valueFromKeys($record, ['role']));
            $status = $this->normalizeStatus((string) $this->valueFromKeys($record, ['status']));
            $plainPassword = (string) $this->cleanText($this->valueFromKeys($record, ['password']));
            if ($plainPassword === '') {
                $plainPassword = $defaultPassword;
            }

            $existing = DB::table('users')->where('email', $email)->first();
            $memberNo = null;
            if ($role === 'anggota') {
                if ($existing && !empty($existing->member_no)) {
                    $memberNo = (string) $existing->member_no;
                } else {
                    $memberNo = sprintf('A-%03d', $nextMemberSeq);
                    $nextMemberSeq++;
                }
            }

            $name = (string) $this->cleanText($this->valueFromKeys($record, ['nama', 'name']));
            if ($name === '') {
                $name = $existing ? (string) $existing->name : 'Tanpa Nama';
            }

            $now = now();
            $payload = [
                'name' => $name,
                'email' => $email,
                'email_verified_at' => $existing && !empty($existing->email_verified_at)
                    ? $existing->email_verified_at
                    : $now,
                'password' => Hash::make($plainPassword),
                'role' => $role,
                'member_no' => $memberNo,
                'nip' => $this->cleanText($this->valueFromKeys($record, ['nip'])),
                'unit_kerja' => $this->cleanText($this->valueFromKeys($record, ['unit_kerja', 'unit kerja'])),
                'phone' => $this->cleanText($this->valueFromKeys($record, ['phone', 'no_hp', 'no hp'])),
                'status' => $status,
                'remember_token' => $existing && !empty($existing->remember_token)
                    ? $existing->remember_token
                    : Str::random(10),
                'updated_at' => $now,
            ];

            if ($existing) {
                DB::table('users')
                    ->where('id', $existing->id)
                    ->update($payload);
                $stats['updated']++;
            } else {
                $payload['created_at'] = $now;
                DB::table('users')->insert($payload);
                $stats['inserted']++;
            }
        }

        return $stats;
    }

    private function seedSimpanan(Worksheet $sheet, $defaultDate, $superAdminId)
    {
        $stats = [
            'savings_inserted' => 0,
            'savings_updated' => 0,
            'savings_deleted' => 0,
            'skipped' => 0,
            'cash_inserted' => 0,
            'cash_updated' => 0,
            'cash_deleted' => 0,
            'missing_users' => [],
        ];

        $headerMap = $this->headerMap($sheet);
        $highestRow = $sheet->getHighestDataRow();
        $missingUsers = [];
        $syncSavings = [];
        $syncSavingsKeyLookup = [];
        $syncCashMap = [];
        $syncCashKeyLookup = [];
        $syncUserIds = [];
        $fixedPokokDate = Carbon::parse('2026-01-01')->startOfDay();

        for ($row = 2; $row <= $highestRow; $row++) {
            $record = $this->rowData($sheet, $headerMap, $row);

            $email = strtolower((string) $this->cleanText($this->valueFromKeys($record, ['email_user', 'email'])));
            if ($email === '') {
                $stats['skipped']++;
                continue;
            }

            $user = DB::table('users')->where('email', $email)->first();
            if (!$user) {
                $missingUsers[$email] = true;
                $stats['skipped']++;
                continue;
            }

            $variableDate = $this->parseDateValue($this->valueFromKeys($record, ['tanggal', 'date']), $defaultDate);
            $note = (string) $this->cleanText($this->valueFromKeys($record, ['note', 'catatan']));
            $amountMap = [
                'pokok' => $this->parseAmount($this->valueFromKeys($record, ['pokok'])),
                'wajib' => $this->parseAmount($this->valueFromKeys($record, ['wajib'])),
                'sukarela' => $this->parseAmount($this->valueFromKeys($record, ['sukarela'])),
            ];

            $syncUserIds[$user->id] = true;

            $typeDateMap = [
                'pokok' => $fixedPokokDate,
                'wajib' => $variableDate,
                'sukarela' => $variableDate,
            ];

            foreach ($typeDateMap as $type => $typeDate) {
                $amount = (float) ($amountMap[$type] ?? 0);
                if ($amount <= 0) {
                    continue;
                }

                $entryDate = $typeDate->toDateString();
                $savingKey = $user->id . '|' . $type . '|' . $entryDate;
                if (!isset($syncSavings[$savingKey])) {
                    $syncSavings[$savingKey] = [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'type' => $type,
                        'entry_date' => $entryDate,
                        'created_at' => $typeDate->copy()->startOfDay()->toDateTimeString(),
                        'notes' => [],
                        'amount' => 0.0,
                    ];
                }
                $syncSavings[$savingKey]['amount'] += $amount;

                if ($note !== '') {
                    $syncSavings[$savingKey]['notes'][$note] = true;
                }

                $cashKey = $user->id . '|' . $entryDate;
                if (!isset($syncCashMap[$cashKey])) {
                    $syncCashMap[$cashKey] = [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'entry_date' => $entryDate,
                        'created_at' => $typeDate->copy()->startOfDay()->toDateTimeString(),
                        'amount' => 0.0,
                    ];
                }
                $syncCashMap[$cashKey]['amount'] += $amount;
            }
        }

        $syncSavingsKeyLookup = array_fill_keys(array_keys($syncSavings), true);
        $syncCashKeyLookup = array_fill_keys(array_keys($syncCashMap), true);
        $syncUserIdsList = array_values(array_keys($syncUserIds));

        if (!empty($syncUserIdsList)) {
            $staleSavingsIds = [];
            $existingSavingsRows = DB::table('savings_transactions')
                ->select('id', 'user_id', 'type', DB::raw('DATE(created_at) as entry_date'))
                ->where('created_by', $superAdminId)
                ->where('posted_by', $superAdminId)
                ->whereIn('type', ['pokok', 'wajib', 'sukarela'])
                ->whereIn('user_id', $syncUserIdsList)
                ->get();

            foreach ($existingSavingsRows as $row) {
                $key = $row->user_id . '|' . $row->type . '|' . $row->entry_date;
                if (!isset($syncSavingsKeyLookup[$key])) {
                    $staleSavingsIds[] = $row->id;
                }
            }

            if (!empty($staleSavingsIds)) {
                $deleted = DB::table('savings_transactions')
                    ->whereIn('id', $staleSavingsIds)
                    ->delete();
                $stats['savings_deleted'] += (int) $deleted;
            }

            $staleCashIds = [];
            $existingCashRows = DB::table('cash_entries')
                ->select('id', 'user_id', 'entry_date')
                ->where('created_by', $superAdminId)
                ->where('direction', 'in')
                ->where('category', 'simpanan')
                ->whereIn('user_id', $syncUserIdsList)
                ->get();

            foreach ($existingCashRows as $row) {
                $key = $row->user_id . '|' . $row->entry_date;
                if (!isset($syncCashKeyLookup[$key])) {
                    $staleCashIds[] = $row->id;
                }
            }

            if (!empty($staleCashIds)) {
                $deletedCash = DB::table('cash_entries')
                    ->whereIn('id', $staleCashIds)
                    ->delete();
                $stats['cash_deleted'] += (int) $deletedCash;
            }
        }

        foreach ($syncSavings as $syncData) {
            $now = now();
            $note = $this->buildImportNote(array_keys($syncData['notes']));
            $amount = round((float) ($syncData['amount'] ?? 0), 2);

            $baseQuery = DB::table('savings_transactions')
                ->where('user_id', $syncData['user_id'])
                ->where('type', $syncData['type'])
                ->whereDate('created_at', $syncData['entry_date'])
                ->where('created_by', $superAdminId)
                ->where('posted_by', $superAdminId);

            if ($amount > 0) {
                $existing = (clone $baseQuery)
                    ->orderBy('id')
                    ->first();

                $payload = [
                    'amount' => $amount,
                    'note' => $note,
                    'posted_at' => $syncData['created_at'],
                    'posted_by' => $superAdminId,
                    'updated_at' => $now,
                ];

                if ($existing) {
                    DB::table('savings_transactions')
                        ->where('id', $existing->id)
                        ->update($payload);
                    $stats['savings_updated']++;

                    $duplicateIds = (clone $baseQuery)
                        ->where('id', '<>', $existing->id)
                        ->pluck('id')
                        ->all();
                    if (!empty($duplicateIds)) {
                        $deleted = DB::table('savings_transactions')
                            ->whereIn('id', $duplicateIds)
                            ->delete();
                        $stats['savings_deleted'] += (int) $deleted;
                    }
                } else {
                    DB::table('savings_transactions')->insert([
                        'user_id' => $syncData['user_id'],
                        'type' => $syncData['type'],
                        'amount' => $amount,
                        'note' => $note,
                        'created_by' => $superAdminId,
                        'created_at' => $syncData['created_at'],
                        'updated_at' => $now,
                        'posted_at' => $syncData['created_at'],
                        'posted_by' => $superAdminId,
                    ]);
                    $stats['savings_inserted']++;
                }
            } else {
                $deleted = (clone $baseQuery)->delete();
                $stats['savings_deleted'] += (int) $deleted;
            }
        }

        foreach ($syncCashMap as $cashData) {
            $now = now();
            $rowTotal = round((float) ($cashData['amount'] ?? 0), 2);
            $cashBaseQuery = DB::table('cash_entries')
                ->where('entry_date', $cashData['entry_date'])
                ->where('direction', 'in')
                ->where('category', 'simpanan')
                ->where('user_id', $cashData['user_id'])
                ->where('created_by', $superAdminId);

            if ($rowTotal > 0) {
                $description = 'Terima dari Bendahara (' . $cashData['user_name'] . ')';
                $existingCash = (clone $cashBaseQuery)
                    ->orderBy('id')
                    ->first();

                $cashPayload = [
                    'amount' => round($rowTotal, 2),
                    'description' => $description,
                    'status' => 'approved',
                    'verified_by' => $superAdminId,
                    'verified_at' => $cashData['created_at'],
                    'updated_at' => $now,
                ];

                if ($existingCash) {
                    DB::table('cash_entries')
                        ->where('id', $existingCash->id)
                        ->update($cashPayload);
                    $stats['cash_updated']++;

                    $duplicateCashIds = (clone $cashBaseQuery)
                        ->where('id', '<>', $existingCash->id)
                        ->pluck('id')
                        ->all();
                    if (!empty($duplicateCashIds)) {
                        $deletedCash = DB::table('cash_entries')
                            ->whereIn('id', $duplicateCashIds)
                            ->delete();
                        $stats['cash_deleted'] += (int) $deletedCash;
                    }
                } else {
                    DB::table('cash_entries')->insert([
                        'entry_date' => $cashData['entry_date'],
                        'direction' => 'in',
                        'description' => $description,
                        'amount' => round($rowTotal, 2),
                        'category' => 'simpanan',
                        'user_id' => $cashData['user_id'],
                        'evidence_path' => null,
                        'status' => 'approved',
                        'verified_by' => $superAdminId,
                        'verified_at' => $cashData['created_at'],
                        'created_by' => $superAdminId,
                        'created_at' => $cashData['created_at'],
                        'updated_at' => $now,
                    ]);
                    $stats['cash_inserted']++;
                }
            } else {
                $deletedCash = (clone $cashBaseQuery)->delete();
                $stats['cash_deleted'] += (int) $deletedCash;
            }
        }

        $stats['missing_users'] = array_values(array_keys($missingUsers));
        return $stats;
    }

    private function seedPinjaman(Worksheet $sheet, $defaultDate, $superAdminId, $includeDisbursementCash = false)
    {
        $stats = [
            'loan_inserted' => 0,
            'loan_updated' => 0,
            'loan_skipped' => 0,
            'disbursement_cash_inserted' => 0,
            'disbursement_cash_updated' => 0,
            'disbursement_cash_deleted' => 0,
            'missing_users' => [],
            'loan_code_map' => [],
        ];

        $headerMap = $this->headerMap($sheet);
        $highestRow = $sheet->getHighestDataRow();
        $missingUsers = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            $record = $this->rowData($sheet, $headerMap, $row);

            $loanCode = strtoupper((string) $this->cleanText($this->valueFromKeys($record, ['kode_pinjaman', 'kode', 'loan_code'])));
            $email = strtolower((string) $this->cleanText($this->valueFromKeys($record, ['email_user', 'email'])));
            if ($loanCode === '' || $email === '') {
                $stats['loan_skipped']++;
                continue;
            }

            $user = DB::table('users')->where('email', $email)->first();
            if (!$user) {
                $missingUsers[$email] = true;
                $stats['loan_skipped']++;
                continue;
            }

            $amount = round((float) $this->parseAmount($this->valueFromKeys($record, ['nominal', 'amount'])), 2);
            $termMonths = $this->parsePositiveInteger($this->valueFromKeys($record, ['tenor_bulan', 'tenor', 'term_months']));
            $purpose = (string) $this->cleanText($this->valueFromKeys($record, ['tujuan', 'purpose']));
            $appliedAt = $this->parseDateValueOrNull($this->valueFromKeys($record, ['tanggal_pinjaman', 'tanggal_pengajuan', 'created_at']));
            $disbursedAt = $this->parseDateValueOrNull($this->valueFromKeys($record, ['tanggal_cair', 'transfered_at', 'disbursed_at']));

            if ($amount <= 0 || $termMonths <= 0 || $purpose === '' || !$disbursedAt) {
                $stats['loan_skipped']++;
                continue;
            }

            if (!$appliedAt) {
                $appliedAt = $this->parseDateValue($defaultDate, $defaultDate);
            }

            $createdAt = $appliedAt->copy()->startOfDay()->toDateTimeString();
            $approvedAt = $appliedAt->copy()->startOfDay()->toDateTimeString();
            $disbursedAtText = $disbursedAt->copy()->startOfDay()->toDateTimeString();
            $disbursedDate = $disbursedAt->copy()->toDateString();
            $memberNo = (string) $this->cleanText($this->valueFromKeys($record, ['member_no']));
            if ($memberNo === '') {
                $memberNo = (string) ($user->member_no ?? null);
            }

            $loanPayload = [
                'import_loan_code' => $loanCode,
                'user_id' => $user->id,
                'member_no' => $memberNo,
                'applicant_name' => $user->name,
                'nip' => $user->nip,
                'unit_kerja' => $user->unit_kerja,
                'phone' => $user->phone,
                'amount' => $amount,
                'term_months' => $termMonths,
                'purpose' => $purpose,
                'status' => 'approved_chairman',
                'sekretaris_id' => $superAdminId,
                'sekretaris_note' => $this->cleanText($this->valueFromKeys($record, ['catatan_sekretaris', 'sekretaris_note'])),
                'reviewed_at' => $approvedAt,
                'bendahara_id' => $superAdminId,
                'bendahara_note' => $this->cleanText($this->valueFromKeys($record, ['catatan_bendahara', 'bendahara_note'])),
                'treasurer_approved_at' => $approvedAt,
                'ketua_id' => $superAdminId,
                'ketua_note' => $this->cleanText($this->valueFromKeys($record, ['catatan_ketua', 'ketua_note'])),
                'chairman_approved_at' => $approvedAt,
                'rejected_at' => null,
                'transfered_at' => $disbursedAtText,
                'transfered_by' => $superAdminId,
                'updated_at' => $createdAt,
            ];

            $existingLoan = DB::table('loans')
                ->where('import_loan_code', $loanCode)
                ->first();

            if ($existingLoan) {
                DB::table('loans')
                    ->where('id', $existingLoan->id)
                    ->update($loanPayload);
                $loanId = (int) $existingLoan->id;
                $stats['loan_updated']++;
            } else {
                $loanPayload['created_at'] = $createdAt;
                DB::table('loans')->insert($loanPayload);
                $loanId = (int) DB::table('loans')->where('import_loan_code', $loanCode)->value('id');
                $stats['loan_inserted']++;
            }

            if ($loanId > 0) {
                $stats['loan_code_map'][$loanCode] = $loanId;
            }

            $cashDescription = 'Peminjaman (' . ($user->name ?? 'Anggota') . ') [' . $loanCode . ']';
            $cashBaseQuery = DB::table('cash_entries')
                ->where('direction', 'out')
                ->where('category', 'peminjaman')
                ->where('created_by', $superAdminId)
                ->where('user_id', $user->id)
                ->where('description', $cashDescription);

            if ($includeDisbursementCash) {
                $cashPayload = [
                    'entry_date' => $disbursedDate,
                    'direction' => 'out',
                    'description' => $cashDescription,
                    'amount' => $amount,
                    'category' => 'peminjaman',
                    'user_id' => $user->id,
                    'evidence_path' => null,
                    'status' => 'approved',
                    'verified_by' => $superAdminId,
                    'verified_at' => $disbursedAtText,
                    'updated_at' => $disbursedAtText,
                ];

                $existingCash = (clone $cashBaseQuery)->orderBy('id')->first();
                if ($existingCash) {
                    DB::table('cash_entries')
                        ->where('id', $existingCash->id)
                        ->update($cashPayload);
                    $stats['disbursement_cash_updated']++;

                    $duplicateIds = (clone $cashBaseQuery)
                        ->where('id', '<>', $existingCash->id)
                        ->pluck('id')
                        ->all();
                    if (!empty($duplicateIds)) {
                        $deletedDuplicates = DB::table('cash_entries')->whereIn('id', $duplicateIds)->delete();
                        $stats['disbursement_cash_deleted'] += (int) $deletedDuplicates;
                    }
                } else {
                    DB::table('cash_entries')->insert([
                        'entry_date' => $disbursedDate,
                        'direction' => 'out',
                        'description' => $cashDescription,
                        'amount' => $amount,
                        'category' => 'peminjaman',
                        'user_id' => $user->id,
                        'evidence_path' => null,
                        'status' => 'approved',
                        'verified_by' => $superAdminId,
                        'verified_at' => $disbursedAtText,
                        'created_by' => $superAdminId,
                        'created_at' => $disbursedAtText,
                        'updated_at' => $disbursedAtText,
                    ]);
                    $stats['disbursement_cash_inserted']++;
                }
            } else {
                $deletedCash = (clone $cashBaseQuery)->delete();
                $stats['disbursement_cash_deleted'] += (int) $deletedCash;
            }
        }

        $stats['missing_users'] = array_values(array_keys($missingUsers));
        return $stats;
    }

    private function seedAngsuran(Worksheet $sheet, $defaultDate, $superAdminId, $importYear)
    {
        $stats = [
            'installment_inserted' => 0,
            'installment_updated' => 0,
            'installment_skipped' => 0,
            'missing_loan_codes' => [],
            'settlement_cash_inserted' => 0,
            'settlement_cash_updated' => 0,
        ];

        $headerMap = $this->headerMap($sheet);
        $highestRow = $sheet->getHighestDataRow();
        $missingLoanCodes = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            $record = $this->rowData($sheet, $headerMap, $row);

            $loanCode = strtoupper((string) $this->cleanText($this->valueFromKeys($record, ['kode_pinjaman', 'kode', 'loan_code'])));
            if ($loanCode === '') {
                $stats['installment_skipped']++;
                continue;
            }

            $loan = DB::table('loans')
                ->select('id', 'user_id')
                ->where('import_loan_code', $loanCode)
                ->first();
            if (!$loan) {
                $missingLoanCodes[$loanCode] = true;
                $stats['installment_skipped']++;
                continue;
            }

            $installmentNo = $this->parsePositiveInteger($this->valueFromKeys($record, ['nomor_angsuran', 'installment_no']));
            if ($installmentNo <= 0) {
                $stats['installment_skipped']++;
                continue;
            }

            $paidAtRaw = $this->valueFromKeys($record, ['tanggal_bayar', 'paid_at']);
            $paidAtFormatted = $this->valueFromKeysFormatted($sheet, $headerMap, $row, ['tanggal_bayar', 'paid_at']);
            $paidAtSource = $paidAtFormatted !== null && trim((string) $paidAtFormatted) !== ''
                ? $paidAtFormatted
                : $paidAtRaw;
            $paidAt = $this->parseDateIndonesiaOrNull($paidAtSource);
            if (!$paidAt) {
                $stats['installment_skipped']++;
                continue;
            }

            if ((int) $paidAt->format('Y') !== (int) $importYear) {
                continue;
            }

            $amountPrincipal = round((float) $this->parseAmount($this->valueFromKeys($record, ['pokok_bayar', 'amount_principal'])), 2);
            $amountFee = round((float) $this->parseAmount($this->valueFromKeys($record, ['jasa_bayar', 'amount_fee'])), 2);
            if ($amountPrincipal <= 0 && $amountFee <= 0) {
                $stats['installment_skipped']++;
                continue;
            }

            $isSettlement = $this->parseBooleanValue($this->valueFromKeys($record, ['is_pelunasan', 'pelunasan'])) ? 1 : 0;
            $note = (string) $this->cleanText($this->valueFromKeys($record, ['catatan', 'note']));
            if ($isSettlement === 1) {
                $note = 'Pelunasan';
            }

            $paidAtDate = $paidAt->copy()->toDateString();
            $paidAtText = $paidAt->copy()->startOfDay()->toDateTimeString();
            $paymentPayload = [
                'paid_at' => $paidAtDate,
                'amount_principal' => $amountPrincipal,
                'amount_fee' => $amountFee,
                'note' => $note !== '' ? $note : null,
                'status' => 'approved',
                'is_settlement' => 0,
                'created_by' => $superAdminId,
                'validated_by' => $superAdminId,
                'validated_at' => $paidAtText,
                'updated_at' => $paidAtText,
            ];

            $existingPayment = DB::table('loan_installment_payments')
                ->where('loan_id', $loan->id)
                ->where('installment_no', $installmentNo)
                ->first();

            if ($existingPayment) {
                DB::table('loan_installment_payments')
                    ->where('id', $existingPayment->id)
                    ->update($paymentPayload);
                $stats['installment_updated']++;
            } else {
                DB::table('loan_installment_payments')->insert([
                    'loan_id' => $loan->id,
                    'installment_no' => $installmentNo,
                    'paid_at' => $paidAtDate,
                    'amount_principal' => $amountPrincipal,
                    'amount_fee' => $amountFee,
                    'note' => $note !== '' ? $note : null,
                    'evidence_path' => null,
                    'status' => 'approved',
                    'is_settlement' => 0,
                    'created_by' => $superAdminId,
                    'validated_by' => $superAdminId,
                    'validated_at' => $paidAtText,
                    'created_at' => $paidAtText,
                    'updated_at' => $paidAtText,
                ]);
                $stats['installment_inserted']++;
            }

            if ($isSettlement === 1) {
                $memberName = DB::table('users')->where('id', $loan->user_id)->value('name');
                $cashDescription = 'Pelunasan Angsuran (' . ($memberName ?: 'Anggota') . ') [' . $loanCode . '#' . $installmentNo . ']';
                $settlementTotal = round((float) $amountPrincipal + (float) $amountFee, 2);

                if ($settlementTotal > 0) {
                    $cashBaseQuery = DB::table('cash_entries')
                        ->where('direction', 'in')
                        ->where('category', 'pelunasan')
                        ->where('created_by', $superAdminId)
                        ->where('user_id', $loan->user_id)
                        ->where('description', $cashDescription);

                    $cashPayload = [
                        'entry_date' => $paidAtDate,
                        'description' => $cashDescription,
                        'amount' => $settlementTotal,
                        'status' => 'approved',
                        'verified_by' => $superAdminId,
                        'verified_at' => $paidAtText,
                        'updated_at' => $paidAtText,
                    ];

                    $existingCash = (clone $cashBaseQuery)->orderBy('id')->first();
                    if ($existingCash) {
                        DB::table('cash_entries')
                            ->where('id', $existingCash->id)
                            ->update($cashPayload);
                        $stats['settlement_cash_updated']++;
                    } else {
                        DB::table('cash_entries')->insert([
                            'entry_date' => $paidAtDate,
                            'direction' => 'in',
                            'description' => $cashDescription,
                            'amount' => $settlementTotal,
                            'category' => 'pelunasan',
                            'user_id' => $loan->user_id,
                            'evidence_path' => null,
                            'status' => 'approved',
                            'verified_by' => $superAdminId,
                            'verified_at' => $paidAtText,
                            'created_by' => $superAdminId,
                            'created_at' => $paidAtText,
                            'updated_at' => $paidAtText,
                        ]);
                        $stats['settlement_cash_inserted']++;
                    }
                }
            }
        }

        $stats['missing_loan_codes'] = array_values(array_keys($missingLoanCodes));
        return $stats;
    }

    private function buildImportNote(array $notes)
    {
        $cleanNotes = [];
        foreach ($notes as $note) {
            $value = $this->cleanText($note);
            if ($value !== null) {
                $cleanNotes[$value] = true;
            }
        }

        $list = array_keys($cleanNotes);
        if (empty($list)) {
            return 'Import Excel Koperasi';
        }

        return implode(' | ', $list);
    }

    private function findSheetCaseInsensitive($spreadsheet, $targetName)
    {
        $target = strtolower(trim((string) $targetName));
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            if (strtolower(trim((string) $worksheet->getTitle())) === $target) {
                return $worksheet;
            }
        }
        return null;
    }

    private function headerMap(Worksheet $sheet)
    {
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        $map = [];

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $rawHeader = $sheet->getCellByColumnAndRow($col, 1)->getValue();
            $header = $this->normalizeHeader($rawHeader);
            if ($header !== '') {
                $map[$header] = $col;
            }
        }

        return $map;
    }

    private function rowData(Worksheet $sheet, array $headerMap, $row)
    {
        $rowData = [];
        foreach ($headerMap as $header => $col) {
            $rowData[$header] = $sheet->getCellByColumnAndRow($col, $row)->getValue();
        }
        return $rowData;
    }

    private function valueFromKeys(array $row, array $keys)
    {
        foreach ($keys as $key) {
            $normalized = $this->normalizeHeader($key);
            if (array_key_exists($normalized, $row)) {
                return $row[$normalized];
            }
        }
        return null;
    }

    private function valueFromKeysFormatted(Worksheet $sheet, array $headerMap, $row, array $keys)
    {
        foreach ($keys as $key) {
            $normalized = $this->normalizeHeader($key);
            if (array_key_exists($normalized, $headerMap)) {
                $col = $headerMap[$normalized];
                return $sheet->getCellByColumnAndRow($col, $row)->getFormattedValue();
            }
        }
        return null;
    }

    private function normalizeHeader($value)
    {
        $text = $this->cleanText($value);
        if ($text === null) {
            return '';
        }

        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/i', '_', $text);
        return trim((string) $text, '_');
    }

    private function normalizeRole($roleRaw)
    {
        $role = strtolower(trim((string) $this->cleanText($roleRaw)));
        $role = str_replace(['-', '.'], ' ', $role);
        $role = preg_replace('/\s+/', ' ', $role);

        $map = [
            'anggota' => 'anggota',
            'sekretaris' => 'sekretaris',
            'bendahara' => 'bendahara',
            'bendahara kantor' => 'bendahara_kantor',
            'ketua' => 'ketua',
            'super admin' => 'superadmin',
            'superadmin' => 'superadmin',
        ];

        return $map[$role] ?? 'anggota';
    }

    private function normalizeStatus($statusRaw)
    {
        $status = strtolower(trim((string) $this->cleanText($statusRaw)));
        return $status !== '' ? $status : 'active';
    }

    private function cleanText($value)
    {
        if ($value === null) {
            return null;
        }

        $text = (string) $value;
        $text = str_replace("\xC2\xA0", ' ', $text);
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);
        $text = trim($text);

        return $text === '' ? null : $text;
    }

    private function parseAmount($value)
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return max(0.0, (float) $value);
        }

        $text = strtolower((string) $value);
        $text = str_replace(['rp', ' '], '', $text);
        if ($text === '' || $text === '-' || $text === 'rp-' || $text === 'rp') {
            return 0.0;
        }

        $text = preg_replace('/[^0-9,.\-]/', '', $text);
        if ($text === '' || $text === '-') {
            return 0.0;
        }

        if (strpos($text, ',') !== false && strpos($text, '.') !== false) {
            if (strrpos($text, ',') > strrpos($text, '.')) {
                $text = str_replace('.', '', $text);
                $text = str_replace(',', '.', $text);
            } else {
                $text = str_replace(',', '', $text);
            }
        } elseif (strpos($text, ',') !== false) {
            $parts = explode(',', $text);
            if (count($parts) === 2 && strlen($parts[1]) <= 2) {
                $text = str_replace('.', '', $parts[0]) . '.' . $parts[1];
            } else {
                $text = str_replace(',', '', $text);
            }
        } elseif (strpos($text, '.') !== false) {
            $parts = explode('.', $text);
            if (count($parts) > 2) {
                $text = implode('', $parts);
            } elseif (count($parts) === 2 && strlen($parts[1]) === 3) {
                $text = $parts[0] . $parts[1];
            }
        }

        return max(0.0, (float) $text);
    }

    private function parseDateValue($value, $defaultDate)
    {
        $fallback = Carbon::parse($defaultDate)->startOfDay();

        if ($value === null || $value === '') {
            return $fallback;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
            } catch (\Throwable $e) {
                return $fallback;
            }
        }

        $parsed = $this->parseDateTextWithPriority($value);
        return $parsed ?: $fallback;
    }

    private function parseDateValueOrNull($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
            } catch (\Throwable $e) {
                return null;
            }
        }

        return $this->parseDateTextWithPriority($value);
    }

    private function parseDateTextWithPriority($value)
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        // Prioritas Indonesia: hari/bulan/tahun (termasuk 1 digit)
        $formats = [
            'd/m/Y',
            'j/n/Y',
            'd-m-Y',
            'j-n-Y',
            'd.m.Y',
            'j.n.Y',
            'Y-m-d',
            'Y/m/d',
            'm/d/Y',
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $text);
                if ($date !== false) {
                    return $date->startOfDay();
                }
            } catch (\Throwable $e) {
                // continue
            }
        }

        try {
            return Carbon::parse($text)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parseDateIndonesiaOrNull($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
            } catch (\Throwable $e) {
                return null;
            }
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        $normalized = str_replace(['-', '.'], '/', $text);
        $formats = ['d/m/Y', 'j/n/Y', 'd/m/y', 'j/n/y'];
        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $normalized);
                if ($date !== false) {
                    return $date->startOfDay();
                }
            } catch (\Throwable $e) {
                // continue
            }
        }

        return null;
    }

    private function parsePositiveInteger($value)
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            $integer = (int) round((float) $value);
            return $integer > 0 ? $integer : 0;
        }

        $text = preg_replace('/[^0-9]/', '', (string) $value);
        if ($text === '') {
            return 0;
        }

        $integer = (int) $text;
        return $integer > 0 ? $integer : 0;
    }

    private function parseBooleanValue($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        $text = strtolower(trim((string) $value));
        return in_array($text, ['1', 'true', 'ya', 'yes', 'y', 'on'], true);
    }

    private function nextMemberSequence()
    {
        $rows = DB::table('users')
            ->whereNotNull('member_no')
            ->pluck('member_no');

        $max = 0;
        foreach ($rows as $memberNo) {
            $text = strtoupper(trim((string) $memberNo));
            if (preg_match('/^A-(\d+)$/', $text, $matches)) {
                $value = (int) $matches[1];
                if ($value > $max) {
                    $max = $value;
                }
            }
        }

        return $max + 1;
    }
}
