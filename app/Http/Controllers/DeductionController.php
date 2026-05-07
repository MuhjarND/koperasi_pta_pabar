<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\FonnteService;

class DeductionController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->session()->get('auth.role');
        $userId = $request->session()->get('auth.id');
        $isMember = $role === 'anggota';
        $canVerify = in_array($role, ['bendahara_kantor', 'superadmin']);
        $types = config('koperasi.savings_types');
        $monthNames = $this->monthNames();

        $deductions = DB::table('member_deductions')
            ->join('users', 'member_deductions.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.member_no',
                'member_deductions.amount_pokok',
                'member_deductions.amount_wajib',
                'member_deductions.amount_sukarela',
                'member_deductions.is_active'
            )
            ->when($isMember, function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })
            ->orderBy('users.name')
            ->get();

        if ($role === 'bendahara') {
            $this->processMonthlyDeductions($deductions, $request);
        }

        $deductionDefaults = [];
        foreach ($deductions as $deduction) {
            $deductionDefaults[$deduction->id] = [
                'pokok' => (float) $deduction->amount_pokok,
                'wajib' => (float) $deduction->amount_wajib,
                'sukarela' => (float) $deduction->amount_sukarela,
                'is_active' => (bool) $deduction->is_active,
            ];
        }

        $memberIds = $isMember ? [$userId] : $deductions->pluck('id')->all();
        $members = [];

        $savingsRows = DB::table('savings_transactions')
            ->join('users', 'savings_transactions.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.member_no',
                'savings_transactions.type',
                'savings_transactions.amount',
                'savings_transactions.created_at'
            )
            ->when($memberIds, function ($query) use ($memberIds) {
                $query->whereIn('savings_transactions.user_id', $memberIds);
            })
            ->where('savings_transactions.note', 'Potong Gaji')
            ->orderBy('users.name')
            ->orderBy('savings_transactions.created_at')
            ->get();

        foreach ($savingsRows as $row) {
            $this->ensureMember($members, $row->id, $row->name, $row->member_no, $types);
            $monthKey = date('Y-m', strtotime($row->created_at));
            $monthNumber = (int) date('n', strtotime($row->created_at));
            $yearNumber = (int) date('Y', strtotime($row->created_at));
            $monthLabel = ($monthNames[$monthNumber] ?? $monthNumber) . ' ' . $yearNumber;

            $this->ensureMonth($members[$row->id]['months'], $monthKey, $monthLabel, $types);
            $members[$row->id]['months'][$monthKey]['savings'][$row->type] += (float) $row->amount;
            $members[$row->id]['months'][$monthKey]['total'] += (float) $row->amount;
        }

        $paymentRows = DB::table('loan_installment_payments')
            ->join('loans', 'loan_installment_payments.loan_id', '=', 'loans.id')
            ->join('users', 'loans.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.member_no',
                'loan_installment_payments.amount_principal',
                'loan_installment_payments.amount_fee',
                'loan_installment_payments.paid_at'
            )
            ->where('loan_installment_payments.status', 'approved')
            ->where('loan_installment_payments.installment_no', '>', 0)
            ->where('loan_installment_payments.note', 'Potong Gaji')
            ->when($memberIds, function ($query) use ($memberIds) {
                $query->whereIn('loans.user_id', $memberIds);
            })
            ->orderBy('users.name')
            ->orderBy('loan_installment_payments.paid_at')
            ->get();

        foreach ($paymentRows as $row) {
            $this->ensureMember($members, $row->id, $row->name, $row->member_no, $types);
            $monthKey = date('Y-m', strtotime($row->paid_at));
            $monthNumber = (int) date('n', strtotime($row->paid_at));
            $yearNumber = (int) date('Y', strtotime($row->paid_at));
            $monthLabel = ($monthNames[$monthNumber] ?? $monthNumber) . ' ' . $yearNumber;

            $this->ensureMonth($members[$row->id]['months'], $monthKey, $monthLabel, $types);
            $members[$row->id]['months'][$monthKey]['loan_principal'] += (float) $row->amount_principal;
            $members[$row->id]['months'][$monthKey]['loan_fee'] += (float) $row->amount_fee;
            $members[$row->id]['months'][$monthKey]['total'] += (float) $row->amount_principal + (float) $row->amount_fee;
        }

        $loanRows = DB::table('loans')
            ->leftJoin('loan_installment_payments', function ($join) {
                $join->on('loans.id', '=', 'loan_installment_payments.loan_id')
                    ->where('loan_installment_payments.status', '=', 'approved')
                    ->where('loan_installment_payments.installment_no', '>', 0);
            })
            ->select(
                'loans.id',
                'loans.user_id',
                'loans.term_months',
                DB::raw('count(loan_installment_payments.id) as paid_count')
            )
            ->where('loans.status', 'approved_chairman')
            ->when($memberIds, function ($query) use ($memberIds) {
                $query->whereIn('loans.user_id', $memberIds);
            })
            ->groupBy('loans.id', 'loans.user_id', 'loans.term_months')
            ->get();

        $remainingMap = [];
        foreach ($loanRows as $loan) {
            $remaining = max((int) $loan->term_months - (int) $loan->paid_count, 0);
            $remainingMap[$loan->user_id] = ($remainingMap[$loan->user_id] ?? 0) + $remaining;
        }

        foreach ($members as $id => $member) {
            ksort($members[$id]['months']);
            $members[$id]['months'] = array_values($members[$id]['months']);
            $members[$id]['remaining_installments'] = $remainingMap[$id] ?? 0;
        }

        $membersList = $role === 'bendahara'
            ? DB::table('users')
                ->select('id', 'name', 'member_no')
                ->where('role', 'anggota')
                ->orderBy('name')
                ->get()
            : collect();

        $installmentMap = [];
        if ($role === 'bendahara') {
            $memberListIds = $membersList->pluck('id')->all();
            $serviceRate = (float) config('koperasi.service_fee_rate', 0);
            $loanInstallmentRows = DB::table('loans')
                ->leftJoin('loan_installment_payments', function ($join) {
                    $join->on('loans.id', '=', 'loan_installment_payments.loan_id')
                        ->where('loan_installment_payments.status', '=', 'approved')
                        ->where('loan_installment_payments.installment_no', '>', 0);
                })
                ->select(
                    'loans.id',
                    'loans.user_id',
                    'loans.amount',
                    'loans.term_months',
                    DB::raw('count(loan_installment_payments.id) as paid_count')
                )
                ->where('loans.status', 'approved_chairman')
                ->when($memberListIds, function ($query) use ($memberListIds) {
                    $query->whereIn('loans.user_id', $memberListIds);
                })
                ->groupBy('loans.id', 'loans.user_id', 'loans.amount', 'loans.term_months')
                ->get();

            foreach ($loanInstallmentRows as $loan) {
                $remaining = max((int) $loan->term_months - (int) $loan->paid_count, 0);
                if ($remaining < 1) {
                    continue;
                }

                $principal = $loan->amount / max((int) $loan->term_months, 1);
                $fee = $loan->amount * $serviceRate;

                if (!isset($installmentMap[$loan->user_id])) {
                    $installmentMap[$loan->user_id] = [
                        'principal' => 0,
                        'fee' => 0,
                    ];
                }

                $installmentMap[$loan->user_id]['principal'] += (float) $principal;
                $installmentMap[$loan->user_id]['fee'] += (float) $fee;
            }
        }

        $memberSummaries = array_values($members);
        if ($memberIds) {
            foreach ($deductions as $deduction) {
                if (!isset($members[$deduction->id])) {
                    $memberSummaries[] = [
                        'id' => $deduction->id,
                        'name' => $deduction->name,
                        'member_no' => $deduction->member_no,
                        'months' => [],
                        'remaining_installments' => $remainingMap[$deduction->id] ?? 0,
                        'types' => $types,
                    ];
                }
            }
        }

        return view('deductions.index', [
            'members' => $memberSummaries,
            'types' => $types,
            'role' => $role,
            'canVerify' => $canVerify,
            'monthNames' => $monthNames,
            'pendingLogs' => $canVerify
                ? DB::table('deduction_logs')
                    ->join('users', 'deduction_logs.user_id', '=', 'users.id')
                    ->select(
                        'deduction_logs.id',
                        'deduction_logs.month',
                        'deduction_logs.year',
                        'deduction_logs.total_amount',
                        'deduction_logs.processed_at',
                        'users.name',
                        'users.member_no'
                    )
                    ->where('deduction_logs.status', 'pending')
                    ->orderByDesc('deduction_logs.processed_at')
                    ->get()
                : collect(),
            'membersList' => $membersList,
            'deductionDefaults' => $deductionDefaults,
            'installmentMap' => $installmentMap,
        ]);
    }

    public function rekapPdf(Request $request)
    {
        $role = $request->session()->get('auth.role');
        if (in_array($role, ['anggota', 'bendahara_kantor'])) {
            abort(403);
        }

        $types = config('koperasi.savings_types');
        $typeKeys = array_keys($types);
        $monthNames = $this->monthNames();
        $monthParam = $request->query('month', 'all');
        $year = now()->year;

        $members = [];
        $summaryTotals = [
            'types' => array_fill_keys($typeKeys, 0),
            'loan_principal' => 0,
            'loan_fee' => 0,
            'total' => 0,
            'members' => 0,
        ];

        $savingsQuery = DB::table('savings_transactions')
            ->join('users', 'savings_transactions.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.member_no',
                'savings_transactions.type',
                'savings_transactions.amount',
                'savings_transactions.created_at'
            )
            ->where('savings_transactions.note', 'Potong Gaji')
            ->orderBy('users.name')
            ->orderBy('savings_transactions.created_at');

        if ($monthParam !== 'all') {
            $monthValue = (int) $monthParam;
            if ($monthValue >= 1 && $monthValue <= 12) {
                $savingsQuery->whereYear('savings_transactions.created_at', $year)
                    ->whereMonth('savings_transactions.created_at', $monthValue);
            }
        }

        $savingsRows = $savingsQuery->get();

        foreach ($savingsRows as $row) {
            if (!isset($members[$row->id])) {
                $members[$row->id] = [
                    'name' => $row->name,
                    'member_no' => $row->member_no,
                    'savings' => array_fill_keys($typeKeys, 0),
                    'loan_principal' => 0,
                    'loan_fee' => 0,
                    'total' => 0,
                ];
            }

            $members[$row->id]['savings'][$row->type] += (float) $row->amount;
        }

        $paymentQuery = DB::table('loan_installment_payments')
            ->join('loans', 'loan_installment_payments.loan_id', '=', 'loans.id')
            ->join('users', 'loans.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.member_no',
                'loan_installment_payments.amount_principal',
                'loan_installment_payments.amount_fee',
                'loan_installment_payments.paid_at'
            )
            ->where('loan_installment_payments.status', 'approved')
            ->where('loan_installment_payments.installment_no', '>', 0)
            ->where('loan_installment_payments.note', 'Potong Gaji')
            ->orderBy('users.name')
            ->orderBy('loan_installment_payments.paid_at');

        if ($monthParam !== 'all') {
            $monthValue = (int) $monthParam;
            if ($monthValue >= 1 && $monthValue <= 12) {
                $paymentQuery->whereYear('loan_installment_payments.paid_at', $year)
                    ->whereMonth('loan_installment_payments.paid_at', $monthValue);
            }
        }

        $paymentRows = $paymentQuery->get();

        foreach ($paymentRows as $row) {
            if (!isset($members[$row->id])) {
                $members[$row->id] = [
                    'name' => $row->name,
                    'member_no' => $row->member_no,
                    'savings' => array_fill_keys($typeKeys, 0),
                    'loan_principal' => 0,
                    'loan_fee' => 0,
                    'total' => 0,
                ];
            }

            $members[$row->id]['loan_principal'] += (float) $row->amount_principal;
            $members[$row->id]['loan_fee'] += (float) $row->amount_fee;
        }

        foreach ($members as $id => $member) {
            $memberSavingsTotal = array_sum($member['savings']);
            $memberTotal = $memberSavingsTotal + $member['loan_principal'] + $member['loan_fee'];
            $members[$id]['total'] = $memberTotal;

            foreach ($member['savings'] as $type => $amount) {
                $summaryTotals['types'][$type] += (float) $amount;
            }
            $summaryTotals['loan_principal'] += (float) $member['loan_principal'];
            $summaryTotals['loan_fee'] += (float) $member['loan_fee'];
            $summaryTotals['total'] += (float) $memberTotal;
        }

        $summaryTotals['members'] = count($members);

        $periodLabel = 'Januari - ' . now()->translatedFormat('F Y');
        if ($monthParam !== 'all') {
            $monthValue = (int) $monthParam;
            if ($monthValue >= 1 && $monthValue <= 12) {
                $periodLabel = ($monthNames[$monthValue] ?? $monthValue) . ' ' . $year;
            }
        }

        $pdf = PDF::loadView('deductions.rekap_pdf', [
            'members' => array_values($members),
            'types' => $types,
            'summaryTotals' => $summaryTotals,
            'periodLabel' => $periodLabel,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('rekap-pemotongan.pdf');
    }

    public function store(Request $request)
    {
        $types = array_keys(config('koperasi.savings_types'));
        $typeList = implode(',', $types);

        $payload = $request->validate([
            'user_id' => 'required|exists:users,id',
            'types' => 'nullable|array',
            'types.*' => 'in:' . $typeList,
            'amounts' => 'nullable|array',
            'amounts.*' => 'nullable|numeric|min:0',
        ]);

        $selectedTypes = $payload['types'] ?? [];
        $amounts = $payload['amounts'] ?? [];
        $labels = config('koperasi.savings_types');

        if (!$selectedTypes) {
            return back()
                ->withErrors(['types' => 'Pilih minimal satu jenis simpanan.'])
                ->withInput();
        }

        foreach ($selectedTypes as $type) {
            $amount = (float) ($amounts[$type] ?? 0);
            if ($amount <= 0) {
                return back()
                    ->withErrors(['amounts.' . $type => 'Nominal ' . strtolower($labels[$type] ?? $type) . ' harus diisi.'])
                    ->withInput();
            }
        }

        $exists = DB::table('member_deductions')
            ->where('user_id', $payload['user_id'])
            ->exists();

        $data = [
            'amount_pokok' => in_array('pokok', $selectedTypes, true) ? (float) ($amounts['pokok'] ?? 0) : 0,
            'amount_wajib' => in_array('wajib', $selectedTypes, true) ? (float) ($amounts['wajib'] ?? 0) : 0,
            'amount_sukarela' => in_array('sukarela', $selectedTypes, true) ? (float) ($amounts['sukarela'] ?? 0) : 0,
            'is_active' => 1,
            'updated_at' => now(),
        ];

        if ($exists) {
            DB::table('member_deductions')
                ->where('user_id', $payload['user_id'])
                ->update($data);
        } else {
            $data['user_id'] = $payload['user_id'];
            $data['created_by'] = $request->session()->get('auth.id');
            $data['created_at'] = now();
            DB::table('member_deductions')->insert($data);
        }

        return redirect()
            ->route('deductions.index')
            ->with('success', 'Pengaturan pemotongan berhasil disimpan.');
    }

    public function destroy(Request $request, $userId)
    {
        $role = $request->session()->get('auth.role');
        if (!in_array($role, ['bendahara', 'superadmin'])) {
            return redirect()->route('deductions.index');
        }

        DB::table('member_deductions')
            ->where('user_id', $userId)
            ->delete();

        return redirect()
            ->route('deductions.index')
            ->with('success', 'Pengaturan pemotongan berhasil dihapus.');
    }

    private function ensureMember(&$members, $id, $name, $memberNo, $types)
    {
        if (!isset($members[$id])) {
            $members[$id] = [
                'id' => $id,
                'name' => $name,
                'member_no' => $memberNo,
                'months' => [],
                'remaining_installments' => 0,
                'types' => $types,
            ];
        }
    }

    private function ensureMonth(&$months, $key, $label, $types)
    {
        if (!isset($months[$key])) {
            $months[$key] = [
                'label' => $label,
                'savings' => array_fill_keys(array_keys($types), 0),
                'loan_principal' => 0,
                'loan_fee' => 0,
                'total' => 0,
            ];
        }
    }

    private function processMonthlyDeductions($deductions, Request $request)
    {
        $start = Carbon::now()->startOfMonth();
        $month = (int) $start->format('m');
        $year = (int) $start->format('Y');
        $serviceRate = (float) config('koperasi.service_fee_rate', 0);

        foreach ($deductions as $deduction) {
            if (!$deduction->is_active) {
                continue;
            }

            $alreadyProcessed = DB::table('deduction_logs')
                ->where('user_id', $deduction->id)
                ->where('month', $month)
                ->where('year', $year)
                ->exists();

            if ($alreadyProcessed) {
                continue;
            }

            $totalDeduction = 0;
            $createdBy = $request->session()->get('auth.id');
            $createdAt = $start->copy()->addHours(8);

            $savingsItems = [
                'pokok' => (float) $deduction->amount_pokok,
                'wajib' => (float) $deduction->amount_wajib,
                'sukarela' => (float) $deduction->amount_sukarela,
            ];

            if ($savingsItems['pokok'] > 0) {
                $hasPokok = DB::table('savings_transactions')
                    ->where('user_id', $deduction->id)
                    ->where('type', 'pokok')
                    ->exists();

                if ($hasPokok) {
                    $savingsItems['pokok'] = 0;
                }
            }

            foreach ($savingsItems as $type => $amount) {
                if ($amount <= 0) {
                    continue;
                }

                DB::table('savings_transactions')->insert([
                    'user_id' => $deduction->id,
                    'type' => $type,
                    'amount' => $amount,
                    'note' => 'Potong Gaji',
                    'created_by' => $createdBy,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
                $totalDeduction += $amount;
            }

            $loans = DB::table('loans')
                ->where('user_id', $deduction->id)
                ->where('status', 'approved_chairman')
                ->get();

            foreach ($loans as $loan) {
                $alreadyPaidMonth = DB::table('loan_installment_payments')
                    ->where('loan_id', $loan->id)
                    ->where('status', 'approved')
                    ->where('installment_no', '>', 0)
                    ->whereBetween('paid_at', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()])
                    ->exists();

                if ($alreadyPaidMonth) {
                    continue;
                }

                $paidCount = DB::table('loan_installment_payments')
                    ->where('loan_id', $loan->id)
                    ->where('status', 'approved')
                    ->where('installment_no', '>', 0)
                    ->max('installment_no');

                $nextInstallment = ((int) $paidCount) + 1;
                if ($nextInstallment > (int) $loan->term_months) {
                    continue;
                }

                $principal = $loan->amount / max((int) $loan->term_months, 1);
                $fee = $loan->amount * $serviceRate;

                DB::table('loan_installment_payments')->insert([
                    'loan_id' => $loan->id,
                    'installment_no' => $nextInstallment,
                    'paid_at' => $start->toDateString(),
                    'amount_principal' => $principal,
                    'amount_fee' => $fee,
                    'note' => 'Potong Gaji',
                    'created_by' => $createdBy,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $totalDeduction += $principal + $fee;
            }

            $logStatus = $totalDeduction > 0 ? 'pending' : 'verified';
            $verifiedAt = $logStatus === 'verified' ? now() : null;

            DB::table('deduction_logs')->insert([
                'user_id' => $deduction->id,
                'month' => $month,
                'year' => $year,
                'total_amount' => $totalDeduction,
                'processed_at' => now(),
                'status' => $logStatus,
                'verified_at' => $verifiedAt,
                'verified_by' => $verifiedAt ? $createdBy : null,
                'created_by' => $createdBy,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($logStatus === 'pending') {
                $amountLabel = number_format((float) $totalDeduction, 2, ',', '.');
                $memberLabel = $deduction->name . ($deduction->member_no ? ' (' . $deduction->member_no . ')' : '');
                $verifyLink = route('deductions.index');
                $notifier = new FonnteService();
                $notifier->notifyRole(
                    'bendahara_kantor',
                    $notifier->formatMessage([
                        "🧮 Pemotongan gaji menunggu verifikasi.",
                        "Nama: {$deduction->name}",
                        "No. Anggota: " . ($deduction->member_no ?? '-'),
                        "Periode: {$month}/{$year}",
                        "Nominal: Rp {$amountLabel}",
                        "Tindak lanjut: {$verifyLink}",
                    ], '🤝')
                );
            }
        }
    }

    public function verify(Request $request, $id)
    {
        $role = $request->session()->get('auth.role');
        if (!in_array($role, ['bendahara_kantor', 'superadmin'])) {
            return redirect()->route('deductions.index');
        }

        $payload = $request->validate([
            'evidence' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        $log = DB::table('deduction_logs')
            ->where('id', $id)
            ->where('status', 'pending')
            ->first();

        if (!$log) {
            return redirect()
                ->route('deductions.index')
                ->withErrors(['error' => 'Data pemotongan tidak ditemukan atau sudah diverifikasi.']);
        }

        $member = DB::table('users')
            ->select('id', 'name')
            ->where('id', $log->user_id)
            ->first();

        $evidencePath = $this->storeDeductionEvidence($request->file('evidence'));

        $verifiedAt = now();
        $verifierId = $request->session()->get('auth.id');

        DB::transaction(function () use ($log, $member, $evidencePath, $verifiedAt, $verifierId) {
            $entryDate = $log->processed_at ? Carbon::parse($log->processed_at) : $verifiedAt;
            if ((float) $log->total_amount > 0) {
                DB::table('cash_entries')->insert([
                    'entry_date' => $entryDate->toDateString(),
                    'direction' => 'in',
                    'description' => 'Terima dari Bendahara (' . ($member->name ?? 'Anggota') . ')',
                    'amount' => (float) $log->total_amount,
                    'category' => 'potongan',
                    'user_id' => $log->user_id,
                    'evidence_path' => $evidencePath,
                    'status' => 'approved',
                    'created_by' => $verifierId,
                    'created_at' => $verifiedAt,
                    'updated_at' => $verifiedAt,
                ]);
            }

            DB::table('deduction_logs')
                ->where('id', $log->id)
                ->update([
                    'status' => 'verified',
                    'evidence_path' => $evidencePath,
                    'verified_by' => $verifierId,
                    'verified_at' => $verifiedAt,
                    'updated_at' => $verifiedAt,
                ]);
        });

        $deductionLink = route('deductions.index');
        $notifier = new FonnteService();
        $notifier->notifyRole(
            'bendahara',
            $notifier->formatMessage([
                "✅ Pemotongan gaji telah diverifikasi bendahara kantor.",
                "Nama: " . ($member->name ?? 'Anggota'),
                "Periode: " . ($log->month ?? '-') . "/" . ($log->year ?? '-'),
                "Nominal: Rp " . number_format((float) $log->total_amount, 2, ',', '.'),
                "Detail: {$deductionLink}",
            ], '🤝')
        );

        return redirect()
            ->route('deductions.index')
            ->with('success', 'Pemotongan berhasil diverifikasi.');
    }

    public function verifyAll(Request $request)
    {
        $role = $request->session()->get('auth.role');
        if (!in_array($role, ['bendahara_kantor', 'superadmin'])) {
            return redirect()->route('deductions.index');
        }

        $request->validate([
            'evidence' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        $logs = DB::table('deduction_logs as l')
            ->join('users as u', 'l.user_id', '=', 'u.id')
            ->select(
                'l.id',
                'l.user_id',
                'l.month',
                'l.year',
                'l.total_amount',
                'l.processed_at',
                'u.name as member_name'
            )
            ->where('l.status', 'pending')
            ->orderBy('l.id')
            ->get();

        if ($logs->isEmpty()) {
            return redirect()
                ->route('deductions.index')
                ->withErrors(['error' => 'Tidak ada data pemotongan pending untuk divalidasi.']);
        }

        $evidencePath = $this->storeDeductionEvidence($request->file('evidence'));
        $verifiedAt = now();
        $verifierId = $request->session()->get('auth.id');

        DB::transaction(function () use ($logs, $evidencePath, $verifiedAt, $verifierId) {
            foreach ($logs as $log) {
                $entryDate = $log->processed_at ? Carbon::parse($log->processed_at) : $verifiedAt;

                if ((float) $log->total_amount > 0) {
                    DB::table('cash_entries')->insert([
                        'entry_date' => $entryDate->toDateString(),
                        'direction' => 'in',
                        'description' => 'Terima dari Bendahara (' . ($log->member_name ?? 'Anggota') . ')',
                        'amount' => (float) $log->total_amount,
                        'category' => 'potongan',
                        'user_id' => $log->user_id,
                        'evidence_path' => $evidencePath,
                        'status' => 'approved',
                        'created_by' => $verifierId,
                        'created_at' => $verifiedAt,
                        'updated_at' => $verifiedAt,
                    ]);
                }

                DB::table('deduction_logs')
                    ->where('id', $log->id)
                    ->update([
                        'status' => 'verified',
                        'evidence_path' => $evidencePath,
                        'verified_by' => $verifierId,
                        'verified_at' => $verifiedAt,
                        'updated_at' => $verifiedAt,
                    ]);
            }
        });

        $totalNominal = (float) $logs->sum('total_amount');
        $deductionLink = route('deductions.index');
        $notifier = new FonnteService();
        $notifier->notifyRole(
            'bendahara',
            $notifier->formatMessage([
                "Validasi massal pemotongan gaji telah selesai diproses.",
                "Jumlah data: " . $logs->count() . " anggota",
                "Total nominal: Rp " . number_format($totalNominal, 2, ',', '.'),
                "Detail: {$deductionLink}",
            ], '🤝')
        );

        return redirect()
            ->route('deductions.index')
            ->with('success', 'Seluruh pemotongan pending berhasil diverifikasi dengan satu eviden.');
    }

    private function storeDeductionEvidence($file)
    {
        $folder = 'uploads/deductions';
        $publicFolder = public_path($folder);

        if (!is_dir($publicFolder)) {
            mkdir($publicFolder, 0755, true);
        }

        $filename = uniqid('deduction_', true) . '.' . $file->getClientOriginalExtension();
        $file->move($publicFolder, $filename);

        return $folder . '/' . $filename;
    }

    private function monthNames()
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }
}
