<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\FonnteService;

class LoanPaymentController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->session()->get('auth.role');
        $userId = $request->session()->get('auth.id');
        $isMember = $role === 'anggota';
        $search = trim((string) $request->query('q', ''));
        $statusFilter = $request->query('status');
        $serviceRate = (float) config('koperasi.service_fee_rate', 0);
        $monthNames = $this->monthNames();

        $loanQuery = DB::table('loans')
            ->join('users', 'loans.user_id', '=', 'users.id')
            ->select(
                'loans.id',
                'loans.user_id',
                'users.name',
                'users.member_no',
                'loans.amount',
                'loans.term_months',
                'loans.created_at'
            )
            ->where('loans.status', 'approved_chairman')
            ->orderBy('users.name')
            ->orderByDesc('loans.created_at');

        if ($isMember) {
            $loanQuery->where('loans.user_id', $userId);
        }

        if ($search !== '') {
            $loanQuery->where(function ($query) use ($search) {
                $query->where('users.name', 'like', '%' . $search . '%')
                    ->orWhere('users.member_no', 'like', '%' . $search . '%');
            });
        }

        $loans = $loanQuery->get();
        $loanIds = $loans->pluck('id')->all();
        $paymentsByLoan = [];
        $settlementRequests = collect();

        if ($loanIds) {
            $paymentRows = DB::table('loan_installment_payments')
                ->whereIn('loan_id', $loanIds)
                ->where('status', 'approved')
                ->where('installment_no', '>', 0)
                ->orderBy('installment_no')
                ->get();

            foreach ($paymentRows as $payment) {
                $paymentsByLoan[$payment->loan_id][] = $payment;
            }

            $settlementRequests = DB::table('loan_installment_payments')
                ->whereIn('loan_id', $loanIds)
                ->where('is_settlement', 1)
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('loan_id');
        }

        $members = [];

        foreach ($loans as $loan) {
            if (!isset($members[$loan->user_id])) {
                $members[$loan->user_id] = [
                    'id' => $loan->user_id,
                    'name' => $loan->name,
                    'member_no' => $loan->member_no,
                    'settled_installments' => 0,
                    'unsettled_installments' => 0,
                    'total_installments' => 0,
                    'total_paid_amount' => 0,
                    'loans' => [],
                ];
            }

            $termMonths = max((int) $loan->term_months, 1);
            $principalPerMonth = $loan->amount / $termMonths;
            $feePerMonth = $loan->amount * $serviceRate;
            $startDate = Carbon::parse($loan->created_at)->startOfMonth();

            $paymentRows = $paymentsByLoan[$loan->id] ?? [];
            $paymentMap = collect($paymentRows)->keyBy('installment_no');
            $paidCount = $paymentMap->count();

            $installments = [];
            $paymentOptions = [];
            $totalPaidPrincipal = 0;
            $totalPaidFee = 0;

            foreach ($paymentRows as $payment) {
                $totalPaidPrincipal += (float) $payment->amount_principal;
                $totalPaidFee += (float) $payment->amount_fee;
            }

            for ($i = 1; $i <= $termMonths; $i++) {
                $dueDate = $startDate->copy()->addMonthsNoOverflow($i - 1);
                $monthLabel = $monthNames[(int) $dueDate->format('n')] ?? $dueDate->format('F');
                $payment = $paymentMap->get($i);
                $status = $payment ? 'Lunas' : 'Belum Lunas';

                $installments[] = [
                    'no' => $i,
                    'month' => $monthLabel,
                    'date' => $dueDate->format('d/m/Y'),
                    'principal' => $payment ? $payment->amount_principal : $principalPerMonth,
                    'fee' => $payment ? $payment->amount_fee : $feePerMonth,
                    'status' => $status,
                ];

                if (!$payment) {
                    $paymentOptions[] = [
                        'no' => $i,
                        'label' => 'Angsuran ke-' . $i . ' (' . $monthLabel . ' ' . $dueDate->format('Y') . ')',
                        'principal' => $principalPerMonth,
                        'fee' => $feePerMonth,
                    ];
                }
            }

            $members[$loan->user_id]['settled_installments'] += $paidCount;
            $members[$loan->user_id]['unsettled_installments'] += max($termMonths - $paidCount, 0);
            $members[$loan->user_id]['total_installments'] += $termMonths;
            $loanPaidTotal = $totalPaidPrincipal + $totalPaidFee;
            $loanDueTotal = ($paidCount === $termMonths)
                ? $loanPaidTotal
                : ($loan->amount + ($loan->amount * $serviceRate * $termMonths));

            $members[$loan->user_id]['total_paid_amount'] += $loanPaidTotal;
            $members[$loan->user_id]['total_due_amount'] = ($members[$loan->user_id]['total_due_amount'] ?? 0)
                + $loanDueTotal;

            $members[$loan->user_id]['loans'][] = [
                'id' => $loan->id,
                'amount' => $loan->amount,
                'term_months' => $termMonths,
                'created_at' => $loan->created_at,
                'installments' => $installments,
                'payment_options' => $paymentOptions,
                'payments' => $paymentRows,
                'paid_count' => $paidCount,
                'remaining_count' => max($termMonths - $paidCount, 0),
                'remaining_principal_total' => $principalPerMonth * max($termMonths - $paidCount, 0),
                'remaining_fee_total' => max($termMonths - $paidCount, 0) > 0 ? $feePerMonth : 0,
                'total_paid_principal' => $totalPaidPrincipal,
                'total_paid_fee' => $totalPaidFee,
                'settlement_request' => collect($settlementRequests->get($loan->id, []))
                    ->firstWhere('status', 'pending'),
            ];
        }

        if ($statusFilter === 'lunas') {
            $members = array_filter($members, function ($member) {
                return $member['total_installments'] > 0 && $member['unsettled_installments'] === 0;
            });
        } elseif ($statusFilter === 'belum') {
            $members = array_filter($members, function ($member) {
                return $member['unsettled_installments'] > 0;
            });
        }

        $memberSummary = null;
        if ($isMember) {
            $memberSummary = collect($members)->first();
        }

        return view('loans.payments', [
            'members' => array_values($members),
            'isMember' => $isMember,
            'role' => $role,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'memberSummary' => $memberSummary,
            'monthNames' => $monthNames,
        ]);
    }

    public function rekapPdf(Request $request)
    {
        $role = $request->session()->get('auth.role');
        if (in_array($role, ['anggota', 'bendahara_kantor'])) {
            abort(403);
        }

        $serviceRate = (float) config('koperasi.service_fee_rate', 0);
        $monthNames = $this->monthNames();
        $monthParam = $request->query('month', 'all');
        $year = now()->year;

        $loanQuery = DB::table('loans')
            ->join('users', 'loans.user_id', '=', 'users.id')
            ->select(
                'loans.id',
                'loans.user_id',
                'users.name',
                'users.member_no',
                'loans.amount',
                'loans.term_months',
                'loans.created_at'
            )
            ->where('loans.status', 'approved_chairman')
            ->orderBy('users.name')
            ->orderByDesc('loans.created_at');

        if ($monthParam !== 'all') {
            $monthValue = (int) $monthParam;
            if ($monthValue >= 1 && $monthValue <= 12) {
                $loanQuery->whereYear('loans.created_at', $year)
                    ->whereMonth('loans.created_at', $monthValue);
            }
        }

        $loans = $loanQuery->get();
        $loanIds = $loans->pluck('id')->all();
        $paymentsByLoan = [];

        if ($loanIds) {
            $paymentRows = DB::table('loan_installment_payments')
                ->whereIn('loan_id', $loanIds)
                ->where('status', 'approved')
                ->where('installment_no', '>', 0)
                ->orderBy('installment_no')
                ->get();

            foreach ($paymentRows as $payment) {
                $paymentsByLoan[$payment->loan_id][] = $payment;
            }
        }

        $members = [];
        $summaryTotals = [
            'loan_amount' => 0,
            'paid' => 0,
            'due' => 0,
            'remaining' => 0,
            'members' => 0,
        ];

        foreach ($loans as $loan) {
            if (!isset($members[$loan->user_id])) {
                $members[$loan->user_id] = [
                    'name' => $loan->name,
                    'member_no' => $loan->member_no,
                    'loan_amount' => 0,
                    'paid' => 0,
                    'due' => 0,
                    'remaining' => 0,
                    'settled' => 0,
                    'total_installments' => 0,
                ];
            }

            $termMonths = max((int) $loan->term_months, 1);
            $paymentRows = $paymentsByLoan[$loan->id] ?? [];
            $paidCount = count($paymentRows);
            $totalPaidPrincipal = 0;
            $totalPaidFee = 0;

            foreach ($paymentRows as $payment) {
                $totalPaidPrincipal += (float) $payment->amount_principal;
                $totalPaidFee += (float) $payment->amount_fee;
            }

            $loanPaidTotal = $totalPaidPrincipal + $totalPaidFee;
            $loanDueTotal = ($paidCount === $termMonths)
                ? $loanPaidTotal
                : ($loan->amount + ($loan->amount * $serviceRate * $termMonths));
            $loanRemaining = max($loanDueTotal - $loanPaidTotal, 0);

            $members[$loan->user_id]['loan_amount'] += (float) $loan->amount;
            $members[$loan->user_id]['paid'] += (float) $loanPaidTotal;
            $members[$loan->user_id]['due'] += (float) $loanDueTotal;
            $members[$loan->user_id]['remaining'] += (float) $loanRemaining;
            $members[$loan->user_id]['settled'] += $paidCount;
            $members[$loan->user_id]['total_installments'] += $termMonths;
        }

        foreach ($members as $member) {
            $summaryTotals['loan_amount'] += (float) $member['loan_amount'];
            $summaryTotals['paid'] += (float) $member['paid'];
            $summaryTotals['due'] += (float) $member['due'];
            $summaryTotals['remaining'] += (float) $member['remaining'];
        }

        $summaryTotals['members'] = count($members);

        $periodLabel = 'Januari - ' . now()->translatedFormat('F Y');
        if ($monthParam !== 'all') {
            $monthValue = (int) $monthParam;
            if ($monthValue >= 1 && $monthValue <= 12) {
                $periodLabel = ($monthNames[$monthValue] ?? $monthValue) . ' ' . $year;
            }
        }

        $pdf = PDF::loadView('loans.rekap_pdf', [
            'members' => array_values($members),
            'summaryTotals' => $summaryTotals,
            'periodLabel' => $periodLabel,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('rekap-peminjaman.pdf');
    }

    public function store(Request $request)
    {
        $isSettlement = (bool) $request->input('is_settlement');
        $rules = [
            'loan_id' => 'required|exists:loans,id',
            'paid_at' => 'required|date',
            'amount_principal' => 'required|numeric|min:0',
            'amount_fee' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:500',
            'evidence' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'is_settlement' => 'nullable|boolean',
        ];

        if (!$isSettlement) {
            $rules['installment_no'] = 'required|integer|min:1';
        }

        $payload = $request->validate($rules);

        $loan = DB::table('loans')->where('id', $payload['loan_id'])->first();
        if (!$loan || $loan->status !== 'approved_chairman') {
            return back()
                ->withErrors(['loan_id' => 'Pinjaman belum disetujui ketua.'])
                ->withInput();
        }

        $role = $request->session()->get('auth.role');
        $userId = $request->session()->get('auth.id');
        if ($role === 'anggota' && (int) $loan->user_id !== (int) $userId) {
            return back()
                ->withErrors(['loan_id' => 'Pinjaman ini bukan milik Anda.'])
                ->withInput();
        }

        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            $file = $request->file('evidence');
            $folder = 'uploads/loan-payments';
            $publicFolder = public_path($folder);

            if (!is_dir($publicFolder)) {
                mkdir($publicFolder, 0755, true);
            }

            $filename = uniqid('payment_', true) . '.' . $file->getClientOriginalExtension();
            $file->move($publicFolder, $filename);
            $evidencePath = $folder . '/' . $filename;
        }

        if ($isSettlement) {
            if ($role !== 'anggota') {
                return back()
                    ->withErrors(['is_settlement' => 'Pelunasan hanya bisa diajukan oleh anggota.'])
                    ->withInput();
            }

            $existingSettlement = DB::table('loan_installment_payments')
                ->where('loan_id', $payload['loan_id'])
                ->where('is_settlement', 1)
                ->exists();

            if ($existingSettlement) {
                return back()
                    ->withErrors(['is_settlement' => 'Pelunasan sudah diajukan atau diproses.'])
                    ->withInput();
            }

            $paidInstallments = DB::table('loan_installment_payments')
                ->where('loan_id', $payload['loan_id'])
                ->where('status', 'approved')
                ->where('installment_no', '>', 0)
                ->pluck('installment_no')
                ->all();

            $remainingInstallments = array_values(array_diff(
                range(1, (int) $loan->term_months),
                $paidInstallments
            ));

            if (empty($remainingInstallments)) {
                return back()
                    ->withErrors(['is_settlement' => 'Pinjaman ini sudah lunas.'])
                    ->withInput();
            }

            $serviceRate = (float) config('koperasi.service_fee_rate', 0);
            $principalPerMonth = $loan->amount / max((int) $loan->term_months, 1);
            $feePerMonth = $loan->amount * $serviceRate;
            $totalPrincipal = $principalPerMonth * count($remainingInstallments);
            $totalFee = $feePerMonth;

            DB::table('loan_installment_payments')->insert([
                'loan_id' => $payload['loan_id'],
                'installment_no' => 0,
                'paid_at' => $payload['paid_at'],
                'amount_principal' => $totalPrincipal,
                'amount_fee' => $totalFee,
                'note' => $payload['note'] ?? 'Permohonan Pelunasan',
                'evidence_path' => $evidencePath,
                'status' => 'pending',
                'is_settlement' => 1,
                'created_by' => $request->session()->get('auth.id'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $member = DB::table('users')
                ->select('name', 'member_no')
                ->where('id', $loan->user_id)
                ->first();
            $memberLabel = ($member->name ?? 'Anggota') . ($member->member_no ? ' (' . $member->member_no . ')' : '');
            $bendaharaLink = route('bendahara.loans.payments');
            $totalAmount = $totalPrincipal + $totalFee;
            $notifier = new FonnteService();
            $notifier->notifyRole(
                'bendahara',
                $notifier->formatMessage([
                    "🧾 Permohonan pelunasan pinjaman menunggu validasi bendahara.",
                    "Nama: " . ($member->name ?? 'Anggota'),
                    "No. Anggota: " . ($member->member_no ?? '-'),
                    "Nominal: Rp " . number_format((float) $totalAmount, 2, ',', '.'),
                    "Tanggal: {$payload['paid_at']}",
                    "Tindak lanjut: {$bendaharaLink}",
                ], '🤝')
            );
        } else {
            $installmentNo = (int) $payload['installment_no'];
            if ($installmentNo > (int) $loan->term_months) {
                return back()
                    ->withErrors(['installment_no' => 'Nomor angsuran tidak valid.'])
                    ->withInput();
            }

            $existingSettlement = DB::table('loan_installment_payments')
                ->where('loan_id', $payload['loan_id'])
                ->where('is_settlement', 1)
                ->exists();

            if ($existingSettlement) {
                return back()
                    ->withErrors(['installment_no' => 'Pelunasan sudah diajukan atau diproses.'])
                    ->withInput();
            }

            $exists = DB::table('loan_installment_payments')
                ->where('loan_id', $payload['loan_id'])
                ->where('installment_no', $installmentNo)
                ->where('status', 'approved')
                ->exists();

            if ($exists) {
                return back()
                    ->withErrors(['installment_no' => 'Angsuran ini sudah dibayar.'])
                    ->withInput();
            }

            DB::table('loan_installment_payments')->insert([
                'loan_id' => $payload['loan_id'],
                'installment_no' => $installmentNo,
                'paid_at' => $payload['paid_at'],
                'amount_principal' => $payload['amount_principal'],
                'amount_fee' => $payload['amount_fee'],
                'note' => $payload['note'] ?? null,
                'evidence_path' => $evidencePath,
                'status' => 'approved',
                'is_settlement' => 0,
                'created_by' => $request->session()->get('auth.id'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($role === 'anggota') {
                $member = DB::table('users')
                    ->select('name', 'member_no')
                    ->where('id', $loan->user_id)
                    ->first();
                $memberLabel = ($member->name ?? 'Anggota') . ($member->member_no ? ' (' . $member->member_no . ')' : '');
                $bendaharaLink = route('bendahara.loans.payments');
                $notifier = new FonnteService();
                $notifier->notifyRole(
                    'bendahara',
                    $notifier->formatMessage([
                        "💳 Pembayaran angsuran diinput oleh anggota.",
                        "Nama: " . ($member->name ?? 'Anggota'),
                        "No. Anggota: " . ($member->member_no ?? '-'),
                        "Angsuran Ke: {$installmentNo}",
                        "Nominal: Rp " . number_format((float) ($payload['amount_principal'] + $payload['amount_fee']), 2, ',', '.'),
                        "Tanggal: {$payload['paid_at']}",
                        "Tindak lanjut: {$bendaharaLink}",
                    ], '🤝')
                );
            }
        }

        $redirectRoute = $request->routeIs('anggota.loans.payments.store')
            ? 'anggota.loans.payments'
            : 'bendahara.loans.payments';

        return redirect()
            ->route($redirectRoute, ['loan_id' => $payload['loan_id']])
            ->with('success', $isSettlement
                ? 'Permohonan pelunasan berhasil dikirim. Menunggu validasi bendahara.'
                : 'Pembayaran angsuran berhasil disimpan.');
    }

    public function approveSettlement(Request $request, $id)
    {
        $role = $request->session()->get('auth.role');
        if ($role !== 'bendahara') {
            return redirect()->route('bendahara.loans.payments');
        }

        $settlement = DB::table('loan_installment_payments')
            ->where('id', $id)
            ->where('is_settlement', 1)
            ->where('status', 'pending')
            ->first();

        if (!$settlement) {
            return redirect()
                ->route('bendahara.loans.payments')
                ->with('error', 'Permohonan pelunasan tidak ditemukan.');
        }

        $loan = DB::table('loans')->where('id', $settlement->loan_id)->first();
        if (!$loan || $loan->status !== 'approved_chairman') {
            return redirect()
                ->route('bendahara.loans.payments')
                ->with('error', 'Pinjaman tidak valid untuk pelunasan.');
        }

        $member = DB::table('users')
            ->select('id', 'name')
            ->where('id', $loan->user_id)
            ->first();

        $paidInstallments = DB::table('loan_installment_payments')
            ->where('loan_id', $loan->id)
            ->where('status', 'approved')
            ->where('installment_no', '>', 0)
            ->pluck('installment_no')
            ->all();

        $remainingInstallments = array_values(array_diff(
            range(1, (int) $loan->term_months),
            $paidInstallments
        ));

        $serviceRate = (float) config('koperasi.service_fee_rate', 0);
        $principalPerMonth = $loan->amount / max((int) $loan->term_months, 1);
        $feePerMonth = $loan->amount * $serviceRate;
        $totalPrincipal = $principalPerMonth * count($remainingInstallments);
        $totalFee = $feePerMonth;
        $totalAmount = $totalPrincipal + $totalFee;

        DB::transaction(function () use (
            $remainingInstallments,
            $loan,
            $settlement,
            $principalPerMonth,
            $feePerMonth,
            $totalAmount,
            $member,
            $request
        ) {
            $validatorId = $request->session()->get('auth.id');
            $paidAt = $settlement->paid_at;
            $createdAt = now();

            foreach ($remainingInstallments as $index => $installmentNo) {
                $feeForInstallment = $index === 0 ? $feePerMonth : 0;
                DB::table('loan_installment_payments')->insert([
                    'loan_id' => $loan->id,
                    'installment_no' => $installmentNo,
                    'paid_at' => $paidAt,
                    'amount_principal' => $principalPerMonth,
                    'amount_fee' => $feeForInstallment,
                    'note' => 'Pelunasan',
                    'evidence_path' => $settlement->evidence_path,
                    'status' => 'approved',
                    'is_settlement' => 0,
                    'created_by' => $validatorId,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            $loanOrderIds = DB::table('loans')
                ->where('user_id', $loan->user_id)
                ->orderBy('created_at')
                ->orderBy('id')
                ->pluck('id')
                ->all();
            $loanSequence = array_search($loan->id, $loanOrderIds, true);
            $sequenceLabel = $loanSequence !== false ? $loanSequence + 1 : $loan->id;

            if ($totalAmount > 0) {
                DB::table('cash_entries')->insert([
                    'entry_date' => $paidAt,
                    'direction' => 'in',
                    'description' => 'Pelunasan pinjaman ke-' . $sequenceLabel . ' (' . ($member->name ?? 'Anggota') . ')',
                    'amount' => $totalAmount,
                    'category' => 'pelunasan',
                    'user_id' => $loan->user_id,
                    'evidence_path' => $settlement->evidence_path,
                    'status' => 'approved',
                    'created_by' => $validatorId,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            DB::table('loan_installment_payments')
                ->where('id', $settlement->id)
                ->update([
                    'status' => 'approved',
                    'validated_by' => $validatorId,
                    'validated_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
        });

        $memberLink = route('anggota.loans.payments');
        $notifier = new FonnteService();
        $notifier->notifyUser(
            $loan->user_id,
            $notifier->formatMessage([
                "✅ Pelunasan pinjaman Anda telah diverifikasi bendahara.",
                "Nominal: Rp " . number_format((float) $totalAmount, 2, ',', '.'),
                "Tanggal: {$settlement->paid_at}",
                "Detail: {$memberLink}",
            ], '🎉', $member->name ?? null)
        );

        return redirect()
            ->route('bendahara.loans.payments')
            ->with('success', 'Pelunasan berhasil divalidasi.');
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
