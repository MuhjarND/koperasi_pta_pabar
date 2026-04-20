<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\FonnteService;

class BalanceController extends Controller
{
    public function index(Request $request)
    {
        if (($request->session()->get('auth.role') ?? '') === 'anggota') {
            return redirect()
                ->route('dashboard')
                ->withErrors(['error' => 'Halaman saldo koperasi tidak dapat diakses oleh anggota.']);
        }

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
            ->whereIn('category', ['potongan', 'simpanan', 'pelunasan', 'saldo_awal'])
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

        $amount = (float) $savings
            + (float) ($loanReceipts ?? 0)
            + (float) $cashIn
            - (float) $cashOut;
        $types = config('koperasi.savings_types');
        $typeTotals = DB::table('savings_transactions')
            ->select('type', DB::raw('sum(amount) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);

        if ($month < 1 || $month > 12) {
            $month = now()->month;
        }

        $ledgerData = $this->ledgerData($month, $year, $types);

        $lastTransaction = DB::table('savings_transactions')
            ->leftJoin('users', 'savings_transactions.created_by', '=', 'users.id')
            ->select('savings_transactions.created_at', 'users.name as created_by_name')
            ->orderByDesc('savings_transactions.created_at')
            ->first();

        $pendingEntries = [];
        if (in_array($request->session()->get('auth.role'), ['sekretaris', 'superadmin'])) {
            $pendingEntries = DB::table('cash_entries')
                ->leftJoin('users', 'cash_entries.created_by', '=', 'users.id')
                ->leftJoin('users as editors', 'cash_entries.edited_by', '=', 'editors.id')
                ->select(
                    'cash_entries.id',
                    'cash_entries.entry_date',
                    'cash_entries.direction',
                    'cash_entries.description',
                    'cash_entries.edit_note',
                    'cash_entries.edited_at',
                    'cash_entries.amount',
                    'cash_entries.category',
                    'cash_entries.evidence_path',
                    'users.name as created_by_name',
                    'editors.name as edited_by_name'
                )
                ->where('cash_entries.status', 'pending')
                ->orderByDesc('cash_entries.entry_date')
                ->orderByDesc('cash_entries.created_at')
                ->get();
        }

        return view('balance.index', [
            'amount' => $amount,
            'lastTransaction' => $lastTransaction,
            'types' => $types,
            'typeTotals' => $typeTotals,
            'role' => $request->session()->get('auth.role'),
            'pendingEntries' => $pendingEntries,
            'ledgerRows' => $ledgerData['ledgerRows'],
            'month' => $month,
            'year' => $year,
            'monthNames' => $ledgerData['monthNames'],
            'availableYears' => $ledgerData['availableYears'],
        ]);
    }

    public function export(Request $request)
    {
        if (($request->session()->get('auth.role') ?? '') === 'anggota') {
            return redirect()
                ->route('dashboard')
                ->withErrors(['error' => 'Export arus kas tidak dapat diakses oleh anggota.']);
        }

        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);
        $types = config('koperasi.savings_types');

        if ($month < 1 || $month > 12) {
            $month = now()->month;
        }

        $ledgerData = $this->ledgerData($month, $year, $types);
        $ledgerRows = $ledgerData['ledgerRows'];
        $monthNames = $ledgerData['monthNames'];

        $filename = 'arus-kas-' . $year . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '.xls';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()
            ->view('balance.export', [
                'ledgerRows' => $ledgerRows,
                'month' => $month,
                'year' => $year,
                'monthNames' => $monthNames,
            ], 200, $headers);
    }

    public function store(Request $request)
    {
        $direction = $request->input('direction');
        $rules = [
            'direction' => 'required|in:in,out',
            'entry_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
        ];

        if ($direction === 'out') {
            $rules['category'] = 'required|in:rat,adm,adm_transfer,atk,lain-lain';
            $rules['description'] = 'required|string|max:255';
            $rules['evidence'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:2048';
        } else {
            $rules['description'] = 'required|string|max:255';
            $rules['evidence'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:2048';
        }

        $payload = $request->validate($rules);

        $category = null;
        $description = $payload['description'] ?? null;
        $evidencePath = null;

        if (!empty($payload['category'])) {
            $category = $payload['category'];
        } elseif ($payload['direction'] === 'in') {
            $category = 'saldo_awal';
        }

        if ($request->hasFile('evidence')) {
            $folder = 'uploads/evidence';
            $publicFolder = public_path($folder);
            if (!is_dir($publicFolder)) {
                mkdir($publicFolder, 0755, true);
            }
            $file = $request->file('evidence');
            $filename = uniqid('evidence_', true) . '.' . $file->getClientOriginalExtension();
            $file->move($publicFolder, $filename);
            $evidencePath = $folder . '/' . $filename;
        }

        DB::table('cash_entries')->insert([
            'entry_date' => $payload['entry_date'],
            'direction' => $payload['direction'],
            'description' => $description,
            'amount' => $payload['amount'],
            'category' => $category,
            'evidence_path' => $evidencePath,
            'status' => 'pending',
            'created_by' => $request->session()->get('auth.id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $amountLabel = number_format((float) $payload['amount'], 2, ',', '.');
        $directionLabel = $payload['direction'] === 'in' ? 'Pemasukan' : 'Pengeluaran';
        $descLabel = $description ?: ($payload['category'] ?? 'Transaksi');
        $sekretarisLink = route('saldo.index');
        $notifier = new FonnteService();
        $notifier->notifyRole(
            'sekretaris',
            $notifier->formatMessage([
                ($directionLabel === 'Pemasukan' ? '💰' : '📤') . " {$directionLabel} baru menunggu verifikasi.",
                "Kategori: " . ($category ?? '-'),
                "Uraian: {$descLabel}",
                "Nominal: Rp {$amountLabel}",
                "Tanggal: {$payload['entry_date']}",
                "Tindak lanjut: {$sekretarisLink}",
            ], '🤝')
        );

        $message = $payload['direction'] === 'in'
            ? 'Pemasukan berhasil dikirim dan menunggu verifikasi sekretaris.'
            : 'Pengeluaran berhasil dikirim dan menunggu verifikasi sekretaris.';

        return redirect()
            ->route('saldo.index')
            ->with('success', $message);
    }

    public function verify(Request $request, $id)
    {
        $role = $request->session()->get('auth.role');
        if (!in_array($role, ['sekretaris', 'superadmin'])) {
            return redirect()->route('saldo.index');
        }

        $entry = DB::table('cash_entries')
            ->where('id', $id)
            ->where('status', 'pending')
            ->first();

        if (!$entry) {
            return redirect()
                ->route('saldo.index')
                ->withErrors(['error' => 'Data tidak ditemukan atau sudah diverifikasi.']);
        }

        DB::table('cash_entries')
            ->where('id', $id)
            ->update([
                'status' => 'approved',
                'verified_by' => $request->session()->get('auth.id'),
                'verified_at' => now(),
                'updated_at' => now(),
            ]);

        $recipientName = null;
        if (!empty($entry->created_by)) {
            $recipientName = DB::table('users')->where('id', $entry->created_by)->value('name');
        }
        $saldoLink = route('saldo.index');
        $notifier = new FonnteService();
        $notifier->notifyUser(
            $entry->created_by ?? null,
            $notifier->formatMessage([
                "✅ Transaksi telah diverifikasi sekretaris.",
                "Uraian: " . ($entry->description ?: 'Pemasukan/Pengeluaran'),
                "Nominal: Rp " . number_format((float) $entry->amount, 2, ',', '.'),
                "Tanggal: {$entry->entry_date}",
                "Detail: {$saldoLink}",
            ], '🙏', $recipientName)
        );

        return redirect()
            ->route('saldo.index')
            ->with('success', 'Pemasukan/pengeluaran berhasil diverifikasi.');
    }

    public function updateEntry(Request $request, $id)
    {
        $role = $request->session()->get('auth.role');
        if ($role !== 'bendahara') {
            return redirect()->route('saldo.index');
        }

        $entry = DB::table('cash_entries')->where('id', $id)->first();
        if (!$entry) {
            return redirect()
                ->route('saldo.index')
                ->withErrors(['error' => 'Transaksi arus kas tidak ditemukan.']);
        }

        $authId = (int) $request->session()->get('auth.id');
        if (!$this->isEditableCashEntry($entry, $authId)) {
            return redirect()
                ->route('saldo.index')
                ->withErrors(['error' => 'Transaksi ini tidak dapat diubah oleh bendahara.']);
        }

        $rules = [
            'entry_date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'edit_note' => 'required|string|max:500',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ];
        if ($entry->direction === 'out') {
            $rules['category'] = 'required|in:rat,adm,adm_transfer,atk,lain-lain';
        }

        $payload = $request->validate($rules);

        $evidencePath = $entry->evidence_path;
        if ($request->hasFile('evidence')) {
            $folder = 'uploads/evidence';
            $publicFolder = public_path($folder);
            if (!is_dir($publicFolder)) {
                mkdir($publicFolder, 0755, true);
            }
            $file = $request->file('evidence');
            $filename = uniqid('evidence_', true) . '.' . $file->getClientOriginalExtension();
            $file->move($publicFolder, $filename);
            $evidencePath = $folder . '/' . $filename;
        }

        $updateData = [
            'entry_date' => $payload['entry_date'],
            'description' => $payload['description'],
            'amount' => (float) $payload['amount'],
            'edit_note' => $payload['edit_note'],
            'evidence_path' => $evidencePath,
            'status' => 'pending',
            'verified_by' => null,
            'verified_at' => null,
            'edited_by' => $authId,
            'edited_at' => now(),
            'updated_at' => now(),
        ];
        if ($entry->direction === 'out') {
            $updateData['category'] = $payload['category'] ?? $entry->category;
        }

        DB::table('cash_entries')
            ->where('id', $entry->id)
            ->update($updateData);

        $amountLabel = number_format((float) ($payload['amount'] ?? 0), 2, ',', '.');
        $sekretarisLink = route('saldo.index');
        $notifier = new FonnteService();
        $notifier->notifyRole(
            'sekretaris',
            $notifier->formatMessage([
                'Transaksi arus kas diperbarui bendahara dan menunggu verifikasi ulang.',
                'Jenis: ' . ($entry->direction === 'in' ? 'Pemasukan' : 'Pengeluaran'),
                'Uraian: ' . ($payload['description'] ?? '-'),
                'Nominal: Rp ' . $amountLabel,
                'Catatan edit: ' . ($payload['edit_note'] ?? '-'),
                'Tindak lanjut: ' . $sekretarisLink,
            ], '🤝')
        );

        return redirect()
            ->route('saldo.index')
            ->with('success', 'Perubahan transaksi berhasil dikirim untuk verifikasi sekretaris.');
    }

    private function ledgerData($month, $year, $types)
    {
        $monthNames = $this->monthNames();
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();

        $openingLoan = DB::table('loan_installment_payments')
            ->where('status', 'approved')
            ->where('installment_no', '>', 0)
            ->where('paid_at', '<', $start->toDateString())
            ->where(function ($query) {
                $query->whereNull('note')
                    ->orWhereNotIn('note', ['Potong Gaji', 'Pelunasan']);
            })
            ->select(DB::raw('sum(amount_principal + amount_fee) as total'))
            ->value('total');
        $openingReceipts = (float) ($openingLoan ?? 0);

        $openingOther = DB::table('cash_entries')
            ->where('direction', 'in')
            ->whereIn('category', ['potongan', 'simpanan', 'pelunasan', 'saldo_awal'])
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'approved');
            })
            ->where('entry_date', '<', $start->toDateString())
            ->sum('amount');
        $openingExpenses = DB::table('cash_entries')
            ->where('direction', 'out')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'approved');
            })
            ->where('entry_date', '<', $start->toDateString())
            ->sum('amount');

        $openingBalance = $openingReceipts + (float) $openingOther - (float) $openingExpenses;

        $transactions = [];
        $sequence = 0;

        $loanRows = DB::table('loan_installment_payments')
            ->join('loans', 'loan_installment_payments.loan_id', '=', 'loans.id')
            ->join('users', 'loans.user_id', '=', 'users.id')
            ->select(
                'loan_installment_payments.paid_at',
                'loan_installment_payments.amount_principal',
                'loan_installment_payments.amount_fee',
                'loan_installment_payments.evidence_path',
                'users.name'
            )
            ->where('loan_installment_payments.status', 'approved')
            ->where('loan_installment_payments.installment_no', '>', 0)
            ->where(function ($query) {
                $query->whereNull('loan_installment_payments.note')
                    ->orWhereNotIn('loan_installment_payments.note', ['Potong Gaji', 'Pelunasan']);
            })
            ->whereBetween('loan_installment_payments.paid_at', [$start->toDateString(), $end->toDateString()])
            ->orderBy('loan_installment_payments.paid_at')
            ->get();

        foreach ($loanRows as $row) {
            $date = Carbon::parse($row->paid_at)->toDateString();
            $timestamp = Carbon::parse($row->paid_at)->getTimestamp();
            $receipts = [
                'pokok' => 0,
                'wajib' => 0,
                'sukarela' => 0,
                'principal' => (float) $row->amount_principal,
                'fee' => (float) $row->amount_fee,
                'other' => 0,
                'other_items' => [],
            ];

            $receiptsTotal = (float) $row->amount_principal + (float) $row->amount_fee;

            $transactions[] = [
                'date' => $date,
                'sort_time' => $timestamp,
                'sort_order' => 1,
                'sequence' => $sequence++,
                'description' => 'Angsuran (' . $row->name . ')',
                'description_items' => [],
                'receipts_total' => $receiptsTotal,
                'receipts' => $receipts,
                'expenses_total' => 0,
                'expenses' => null,
                'evidence_path' => $row->evidence_path ?? null,
                'note' => '-',
                'source_type' => 'loan',
                'source_id' => null,
                'can_edit' => false,
            ];
        }

        $cashRows = DB::table('cash_entries')
            ->select('id', 'entry_date', 'direction', 'description', 'edit_note', 'amount', 'category', 'user_id', 'created_at', 'created_by', 'evidence_path')
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'approved');
            })
            ->where(function ($query) {
                $query->where('direction', 'out')
                    ->orWhere(function ($inner) {
                        $inner->where('direction', 'in')
                            ->whereIn('category', ['potongan', 'simpanan', 'pelunasan', 'saldo_awal']);
                    });
            })
            ->orderBy('entry_date')
            ->orderBy('created_at')
            ->get();

        $potonganBreakdown = [];
        $potonganUserIds = $cashRows
            ->filter(function ($row) {
                return $row->direction === 'in' && $row->category === 'potongan' && !empty($row->user_id);
            })
            ->pluck('user_id')
            ->unique()
            ->values();

        $savingsBreakdownMap = [];
        $savingsUserIds = $cashRows
            ->filter(function ($row) {
                return $row->direction === 'in' && $row->category === 'simpanan' && !empty($row->user_id);
            })
            ->pluck('user_id')
            ->unique()
            ->values();

        if ($potonganUserIds->isNotEmpty()) {
            $potonganSavingsRows = DB::table('savings_transactions')
                ->select(
                    'user_id',
                    DB::raw("date_format(created_at, '%Y-%m') as entry_month"),
                    'type',
                    DB::raw('sum(amount) as total')
                )
                ->whereIn('user_id', $potonganUserIds->all())
                ->where('note', 'Potong Gaji')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('user_id', DB::raw("date_format(created_at, '%Y-%m')"), 'type')
                ->get();

            foreach ($potonganSavingsRows as $item) {
                $entryMonth = $item->entry_month;
                if (!isset($potonganBreakdown[$item->user_id][$entryMonth])) {
                    $potonganBreakdown[$item->user_id][$entryMonth] = [
                        'pokok' => 0,
                        'wajib' => 0,
                        'sukarela' => 0,
                        'principal' => 0,
                        'fee' => 0,
                    ];
                }

                if (isset($potonganBreakdown[$item->user_id][$entryMonth][$item->type])) {
                    $potonganBreakdown[$item->user_id][$entryMonth][$item->type] += (float) $item->total;
                }
            }

            $loanBreakdown = DB::table('loan_installment_payments')
                ->join('loans', 'loan_installment_payments.loan_id', '=', 'loans.id')
                ->select(
                    'loans.user_id',
                    DB::raw("date_format(loan_installment_payments.paid_at, '%Y-%m') as entry_month"),
                    DB::raw('sum(loan_installment_payments.amount_principal) as principal_total'),
                    DB::raw('sum(loan_installment_payments.amount_fee) as fee_total')
                )
                ->whereIn('loans.user_id', $potonganUserIds->all())
                ->where('loan_installment_payments.note', 'Potong Gaji')
                ->where('loan_installment_payments.status', 'approved')
                ->where('loan_installment_payments.installment_no', '>', 0)
                ->whereBetween('loan_installment_payments.paid_at', [$start->toDateString(), $end->toDateString()])
                ->groupBy('loans.user_id', DB::raw("date_format(loan_installment_payments.paid_at, '%Y-%m')"))
                ->get();

            foreach ($loanBreakdown as $item) {
                $entryMonth = $item->entry_month;
                if (!isset($potonganBreakdown[$item->user_id][$entryMonth])) {
                    $potonganBreakdown[$item->user_id][$entryMonth] = [
                        'pokok' => 0,
                        'wajib' => 0,
                        'sukarela' => 0,
                        'principal' => 0,
                        'fee' => 0,
                    ];
                }

                $potonganBreakdown[$item->user_id][$entryMonth]['principal'] += (float) $item->principal_total;
                $potonganBreakdown[$item->user_id][$entryMonth]['fee'] += (float) $item->fee_total;
            }
        }

        $potonganEvidenceMap = [];
        $startKey = ((int) $start->format('Y')) * 100 + (int) $start->format('m');
        $endKey = ((int) $end->format('Y')) * 100 + (int) $end->format('m');
        $potonganEvidenceRows = DB::table('deduction_logs')
            ->select('user_id', 'month', 'year', 'evidence_path')
            ->where('status', 'verified')
            ->whereNotNull('evidence_path')
            ->whereRaw('(year * 100 + month) between ? and ?', [$startKey, $endKey])
            ->get();

        foreach ($potonganEvidenceRows as $row) {
            $monthKey = sprintf('%04d-%02d', (int) $row->year, (int) $row->month);
            if (!isset($potonganEvidenceMap[$row->user_id])) {
                $potonganEvidenceMap[$row->user_id] = [];
            }
            $potonganEvidenceMap[$row->user_id][$monthKey] = $row->evidence_path;
        }

        if ($savingsUserIds->isNotEmpty()) {
            $savingsPostedRows = DB::table('savings_transactions')
                ->select(
                    'user_id',
                    DB::raw('date(posted_at) as entry_date'),
                    'type',
                    DB::raw('sum(amount) as total')
                )
                ->whereIn('user_id', $savingsUserIds->all())
                ->whereNotNull('posted_at')
                ->where(function ($query) {
                    $query->whereNull('note')
                        ->orWhere('note', '!=', 'Potong Gaji');
                })
                ->whereBetween('posted_at', [$start, $end])
                ->groupBy('user_id', DB::raw('date(posted_at)'), 'type')
                ->get();

            foreach ($savingsPostedRows as $item) {
                $entryDate = $item->entry_date;
                if (!isset($savingsBreakdownMap[$item->user_id][$entryDate])) {
                    $savingsBreakdownMap[$item->user_id][$entryDate] = [
                        'pokok' => 0,
                        'wajib' => 0,
                        'sukarela' => 0,
                    ];
                }

                if (isset($savingsBreakdownMap[$item->user_id][$entryDate][$item->type])) {
                    $savingsBreakdownMap[$item->user_id][$entryDate][$item->type] += (float) $item->total;
                }
            }
        }

        foreach ($cashRows as $row) {
            $date = Carbon::parse($row->entry_date)->toDateString();
            $entryDateKey = $date;
            $monthKey = Carbon::parse($row->entry_date)->format('Y-m');
            $timestamp = Carbon::parse($row->created_at ?? $row->entry_date)->getTimestamp();
            $isLoanOut = $row->direction === 'out' && $row->category === 'peminjaman';
            $sortOrder = $isLoanOut ? 0 : ($row->direction === 'in' ? 3 : 4);
            $evidencePath = $row->evidence_path ?? null;
            if (!$evidencePath && $row->direction === 'in' && $row->category === 'potongan' && !empty($row->user_id)) {
                $evidencePath = $potonganEvidenceMap[$row->user_id][$monthKey] ?? null;
            }

            if ($row->direction === 'in') {
                $receipts = [
                    'pokok' => 0,
                    'wajib' => 0,
                    'sukarela' => 0,
                    'principal' => 0,
                    'fee' => 0,
                    'other' => (float) $row->amount,
                    'other_items' => [
                        ['label' => $row->description, 'amount' => (float) $row->amount],
                    ],
                ];
                $potonganTotals = null;

                if ($row->category === 'simpanan' && !empty($row->user_id)) {
                    $detail = $savingsBreakdownMap[$row->user_id][$entryDateKey] ?? null;
                    if ($detail) {
                        $detailTotal = (float) ($detail['pokok'] ?? 0)
                            + (float) ($detail['wajib'] ?? 0)
                            + (float) ($detail['sukarela'] ?? 0);

                        $otherAmount = (float) $row->amount - $detailTotal;
                        if ($otherAmount < 0) {
                            $otherAmount = 0;
                        }

                        $receipts = [
                            'pokok' => (float) ($detail['pokok'] ?? 0),
                            'wajib' => (float) ($detail['wajib'] ?? 0),
                            'sukarela' => (float) ($detail['sukarela'] ?? 0),
                            'principal' => 0,
                            'fee' => 0,
                            'other' => $otherAmount,
                            'other_items' => $otherAmount > 0
                                ? [['label' => 'Selisih', 'amount' => $otherAmount]]
                                : [],
                        ];
                    }
                }

                if ($row->category === 'potongan' && !empty($row->user_id)) {
                    $detail = $potonganBreakdown[$row->user_id][$monthKey] ?? null;
                    if ($detail) {
                        $detailTotal = (float) ($detail['pokok'] ?? 0)
                            + (float) ($detail['wajib'] ?? 0)
                            + (float) ($detail['sukarela'] ?? 0)
                            + (float) ($detail['principal'] ?? 0)
                            + (float) ($detail['fee'] ?? 0);

                        if ($detailTotal > 0) {
                            $otherAmount = (float) $row->amount - $detailTotal;
                            if ($otherAmount < 0) {
                                $otherAmount = 0;
                            }

                            $receipts = [
                                'pokok' => (float) ($detail['pokok'] ?? 0),
                                'wajib' => (float) ($detail['wajib'] ?? 0),
                                'sukarela' => (float) ($detail['sukarela'] ?? 0),
                                'principal' => (float) ($detail['principal'] ?? 0),
                                'fee' => (float) ($detail['fee'] ?? 0),
                                'other' => $otherAmount,
                                'other_items' => $otherAmount > 0
                                    ? [['label' => 'Selisih', 'amount' => $otherAmount]]
                                    : [],
                            ];

                            $potonganTotals = [
                                'savings' => (float) ($detail['wajib'] ?? 0) + (float) ($detail['sukarela'] ?? 0),
                                'installment' => (float) ($detail['principal'] ?? 0) + (float) ($detail['fee'] ?? 0),
                            ];
                        }
                    }
                }

                $transactions[] = [
                    'date' => $date,
                    'sort_time' => $timestamp,
                    'sort_order' => $sortOrder,
                    'sequence' => $sequence++,
                    'description' => $row->description,
                    'description_items' => [],
                    'receipts_total' => (float) $row->amount,
                    'receipts' => $receipts,
                    'expenses_total' => 0,
                    'expenses' => null,
                    'potongan_totals' => $potonganTotals,
                    'evidence_path' => $evidencePath,
                    'note' => '-',
                    'source_type' => 'cash',
                    'source_id' => $row->id,
                    'can_edit' => $this->isEditableCashEntry($row),
                    'entry_direction' => $row->direction,
                    'entry_category' => $row->category,
                    'entry_edit_note' => $row->edit_note,
                ];
            } else {
                $transactions[] = [
                    'date' => $date,
                    'sort_time' => $timestamp,
                    'sort_order' => $sortOrder,
                    'sequence' => $sequence++,
                    'description' => $row->description,
                    'description_items' => [],
                    'receipts_total' => 0,
                    'receipts' => null,
                    'expenses_total' => (float) $row->amount,
                    'expenses' => [
                        'total' => (float) $row->amount,
                        'items' => [
                            ['label' => $row->description, 'amount' => (float) $row->amount],
                        ],
                    ],
                    'potongan_totals' => null,
                    'evidence_path' => $evidencePath,
                    'note' => '-',
                    'source_type' => 'cash',
                    'source_id' => $row->id,
                    'can_edit' => $this->isEditableCashEntry($row),
                    'entry_direction' => $row->direction,
                    'entry_category' => $row->category,
                    'entry_edit_note' => $row->edit_note,
                ];
            }
        }

        usort($transactions, function ($a, $b) {
            if ($a['date'] === $b['date']) {
                if ($a['sort_order'] === $b['sort_order']) {
                    if ($a['sort_time'] === $b['sort_time']) {
                        return $a['sequence'] <=> $b['sequence'];
                    }
                    return $a['sort_time'] <=> $b['sort_time'];
                }
                return $a['sort_order'] <=> $b['sort_order'];
            }
            return strcmp($a['date'], $b['date']);
        });

        $ledgerRows = [];
        $balance = $openingBalance;

        $ledgerRows[] = [
            'date' => $start->toDateString(),
            'description' => 'Saldo Awal',
            'description_items' => [],
            'receipts_total' => 0,
            'receipts' => null,
            'expenses_total' => 0,
            'expenses' => null,
            'balance' => $balance,
            'evidence_path' => null,
            'note' => '-',
            'source_type' => 'opening',
            'source_id' => null,
            'can_edit' => false,
        ];

        foreach ($transactions as $entry) {
            $balance += (float) $entry['receipts_total'];
            $balance -= (float) $entry['expenses_total'];
            $entry['balance'] = $balance;
            unset($entry['sort_time'], $entry['sequence'], $entry['sort_order']);
            if (!array_key_exists('potongan_totals', $entry)) {
                $entry['potongan_totals'] = null;
            }
            $ledgerRows[] = $entry;
        }

        $years = collect();
        $years = $years->merge(DB::table('loan_installment_payments')->selectRaw('distinct year(paid_at) as year')->pluck('year'));
        $years = $years->merge(DB::table('cash_entries')->selectRaw('distinct year(entry_date) as year')->pluck('year'));
        $availableYears = $years->filter()->unique()->sortDesc()->values()->all();

        if (!$availableYears) {
            $availableYears = [now()->year];
        }

        return [
            'ledgerRows' => $ledgerRows,
            'monthNames' => $monthNames,
            'availableYears' => $availableYears,
        ];
    }

    private function isEditableCashEntry($entry, $userId = null)
    {
        if (!$entry) {
            return false;
        }

        $blockedCategories = ['potongan', 'simpanan', 'pelunasan', 'peminjaman'];
        if (in_array((string) ($entry->category ?? ''), $blockedCategories, true)) {
            return false;
        }

        $direction = (string) ($entry->direction ?? '');
        if (!in_array($direction, ['in', 'out'], true)) {
            return false;
        }

        if ($direction === 'out') {
            $allowedOut = ['rat', 'adm', 'adm_transfer', 'atk', 'lain-lain'];
            if (!in_array((string) ($entry->category ?? ''), $allowedOut, true)) {
                return false;
            }
        }

        if ($direction === 'in' && !in_array((string) ($entry->category ?? ''), ['saldo_awal', null, ''], true)) {
            return false;
        }

        if ($userId !== null && (int) ($entry->created_by ?? 0) !== (int) $userId) {
            return false;
        }

        return true;
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
