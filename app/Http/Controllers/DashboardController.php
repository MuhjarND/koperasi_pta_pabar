<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->session()->get('auth.role');

        return redirect()->route('dashboard.' . $role);
    }

    public function superadmin()
    {
        $balance = $this->currentBalance();
        $roleCounts = DB::table('users')
            ->select('role', DB::raw('count(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        $loanCounts = DB::table('loans')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentLoans = DB::table('loans')
            ->join('users', 'loans.user_id', '=', 'users.id')
            ->select('loans.id', 'users.name', 'loans.amount', 'loans.status', 'loans.created_at')
            ->orderByDesc('loans.created_at')
            ->limit(5)
            ->get();

        return view('dashboard.superadmin', [
            'balance' => $balance,
            'roleCounts' => $roleCounts,
            'loanCounts' => $loanCounts,
            'recentLoans' => $recentLoans,
            'statusLabels' => config('koperasi.status_labels'),
            'roleLabels' => config('koperasi.roles'),
        ]);
    }

    public function sekretaris()
    {
        $balance = $this->currentBalance();
        $pendingCount = DB::table('loans')->where('status', 'submitted')->count();
        $pendingCashCount = DB::table('cash_entries')
            ->where('status', 'pending')
            ->count();

        $queue = DB::table('loans')
            ->join('users', 'loans.user_id', '=', 'users.id')
            ->select('loans.id', 'users.name', 'loans.amount', 'loans.created_at')
            ->where('loans.status', 'submitted')
            ->orderByDesc('loans.created_at')
            ->limit(5)
            ->get();

        $pendingCashEntries = DB::table('cash_entries')
            ->leftJoin('users', 'cash_entries.created_by', '=', 'users.id')
            ->select(
                'cash_entries.id',
                'cash_entries.entry_date',
                'cash_entries.direction',
                'cash_entries.description',
                'cash_entries.amount',
                'users.name as created_by_name'
            )
            ->where('cash_entries.status', 'pending')
            ->orderByDesc('cash_entries.entry_date')
            ->orderByDesc('cash_entries.created_at')
            ->limit(5)
            ->get();

        return view('dashboard.sekretaris', [
            'balance' => $balance,
            'pendingCount' => $pendingCount,
            'queue' => $queue,
            'pendingCashCount' => $pendingCashCount,
            'pendingCashEntries' => $pendingCashEntries,
        ]);
    }

    public function bendahara()
    {
        $balance = $this->currentBalance();
        $pendingCount = DB::table('loans')->where('status', 'reviewed')->count();

        $queue = DB::table('loans')
            ->join('users', 'loans.user_id', '=', 'users.id')
            ->select('loans.id', 'users.name', 'loans.amount', 'loans.created_at')
            ->where('loans.status', 'reviewed')
            ->orderByDesc('loans.created_at')
            ->limit(5)
            ->get();

        return view('dashboard.bendahara', [
            'balance' => $balance,
            'pendingCount' => $pendingCount,
            'queue' => $queue,
        ]);
    }

    public function ketua()
    {
        $balance = $this->currentBalance();
        $pendingCount = DB::table('loans')->where('status', 'approved_treasurer')->count();

        $queue = DB::table('loans')
            ->join('users', 'loans.user_id', '=', 'users.id')
            ->select('loans.id', 'users.name', 'loans.amount', 'loans.created_at')
            ->where('loans.status', 'approved_treasurer')
            ->orderByDesc('loans.created_at')
            ->limit(5)
            ->get();

        return view('dashboard.ketua', [
            'balance' => $balance,
            'pendingCount' => $pendingCount,
            'queue' => $queue,
        ]);
    }

    public function bendaharaKantor()
    {
        $balance = $this->currentBalance();
        $pendingVerifications = DB::table('deduction_logs')
            ->where('status', 'pending')
            ->count();

        $latestVerifications = DB::table('deduction_logs')
            ->join('users', 'deduction_logs.user_id', '=', 'users.id')
            ->select('deduction_logs.id', 'users.name', 'deduction_logs.total_amount', 'deduction_logs.processed_at')
            ->where('deduction_logs.status', 'pending')
            ->orderByDesc('deduction_logs.processed_at')
            ->limit(5)
            ->get();

        return view('dashboard.bendahara_kantor', [
            'balance' => $balance,
            'pendingVerifications' => $pendingVerifications,
            'latestVerifications' => $latestVerifications,
        ]);
    }

    public function anggota(Request $request)
    {
        $balance = $this->currentBalance();
        $userId = $request->session()->get('auth.id');

        $totalLoans = DB::table('loans')->where('user_id', $userId)->count();

        $recentLoans = DB::table('loans')
            ->select('id', 'amount', 'term_months', 'status', 'created_at')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $loanStats = DB::table('loans')
            ->leftJoin('loan_installment_payments', function ($join) {
                $join->on('loans.id', '=', 'loan_installment_payments.loan_id')
                    ->where('loan_installment_payments.status', '=', 'approved')
                    ->where('loan_installment_payments.installment_no', '>', 0);
            })
            ->select(
                'loans.id',
                'loans.amount',
                'loans.term_months',
                DB::raw('count(loan_installment_payments.id) as paid_count'),
                DB::raw('max(loan_installment_payments.paid_at) as last_paid_at')
            )
            ->where('loans.user_id', $userId)
            ->where('loans.status', 'approved_chairman')
            ->groupBy('loans.id', 'loans.amount', 'loans.term_months')
            ->orderByDesc('loans.created_at')
            ->get();

        $recentPayments = DB::table('loan_installment_payments')
            ->join('loans', 'loan_installment_payments.loan_id', '=', 'loans.id')
            ->select(
                'loan_installment_payments.installment_no',
                'loan_installment_payments.paid_at',
                'loan_installment_payments.amount_principal',
                'loan_installment_payments.amount_fee',
                'loans.amount'
            )
            ->where('loans.user_id', $userId)
            ->where('loan_installment_payments.status', 'approved')
            ->where('loan_installment_payments.installment_no', '>', 0)
            ->orderByDesc('loan_installment_payments.paid_at')
            ->limit(6)
            ->get();

        $savingsTypes = config('koperasi.savings_types');
        $monthNames = [
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

        $savingsRows = DB::table('savings_transactions')
            ->select('type', 'amount', 'created_at')
            ->where('user_id', $userId)
            ->orderBy('created_at')
            ->get();

        $savingsMonths = [];
        $savingsTotal = 0;

        foreach ($savingsRows as $row) {
            $monthKey = date('Y-m', strtotime($row->created_at));
            $monthNumber = (int) date('n', strtotime($row->created_at));
            $yearNumber = (int) date('Y', strtotime($row->created_at));
            $monthLabel = ($monthNames[$monthNumber] ?? $monthNumber) . ' ' . $yearNumber;

            if (!isset($savingsMonths[$monthKey])) {
                $savingsMonths[$monthKey] = [
                    'label' => $monthLabel,
                    'types' => array_fill_keys(array_keys($savingsTypes), 0),
                    'total' => 0,
                ];
            }

            $savingsMonths[$monthKey]['types'][$row->type] += (float) $row->amount;
            $savingsMonths[$monthKey]['total'] += (float) $row->amount;
            $savingsTotal += (float) $row->amount;
        }

        $memberProfile = DB::table('users')
            ->select('name', 'member_no')
            ->where('id', $userId)
            ->first();

        $savingsSummary = [
            'name' => $memberProfile->name ?? 'Anggota',
            'member_no' => $memberProfile->member_no ?? null,
            'months' => array_values($savingsMonths),
            'total_amount' => $savingsTotal,
        ];

        return view('dashboard.anggota', [
            'balance' => $balance,
            'totalLoans' => $totalLoans,
            'recentLoans' => $recentLoans,
            'loanStats' => $loanStats,
            'recentPayments' => $recentPayments,
            'statusLabels' => config('koperasi.status_labels'),
            'savingsSummary' => $savingsSummary,
            'savingsTypes' => $savingsTypes,
        ]);
    }

    private function currentBalance()
    {
        $savings = 0;
        $loanReceipts = DB::table('loan_installment_payments')
            ->where('status', 'approved')
            ->where('installment_no', '>', 0)
            ->where(function ($query) {
                $query->whereNull('note')
                    ->orWhereNotIn('note', ['Potong Gaji', 'Pelunasan']);
            })
            ->select(DB::raw('sum(amount_principal + amount_fee) as total'))
            ->value('total');
        $cashIn = DB::table('cash_entries')
            ->where('direction', 'in')
            ->whereIn('category', ['potongan', 'simpanan', 'pelunasan'])
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'approved');
            })
            ->sum('amount');
        $cashOut = DB::table('cash_entries')
            ->where('direction', 'out')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'approved');
            })
            ->sum('amount');

        $balance = (float) $savings
            + (float) ($loanReceipts ?? 0)
            + (float) $cashIn
            - (float) $cashOut;

        return $balance;
    }
}
