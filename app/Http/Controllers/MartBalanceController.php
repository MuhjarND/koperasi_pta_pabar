<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MartBalanceController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->session()->get('auth.role');

        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);

        if ($month < 1 || $month > 12) {
            $month = now()->month;
        }

        $cashIn = DB::table('mart_cash_entries')
            ->where('direction', 'in')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'approved');
            })
            ->sum('amount');

        $cashOut = DB::table('mart_cash_entries')
            ->where('direction', 'out')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'approved');
            })
            ->sum('amount');

        $balance = (float) $cashIn - (float) $cashOut;

        $lastTransaction = DB::table('mart_cash_entries')
            ->leftJoin('users', 'mart_cash_entries.created_by', '=', 'users.id')
            ->select('mart_cash_entries.created_at', 'users.name as created_by_name')
            ->orderByDesc('mart_cash_entries.created_at')
            ->first();

        $ledgerData = $this->ledgerData($month, $year);
        $entries = $ledgerData['entries'];

        $pendingEntries = [];
        if (in_array($role, ['sekretaris', 'superadmin'])) {
            $pendingEntries = DB::table('mart_cash_entries')
                ->leftJoin('users', 'mart_cash_entries.created_by', '=', 'users.id')
                ->select(
                    'mart_cash_entries.id',
                    'mart_cash_entries.entry_date',
                    'mart_cash_entries.direction',
                    'mart_cash_entries.description',
                    'mart_cash_entries.amount',
                    'mart_cash_entries.category',
                    'mart_cash_entries.evidence_path',
                    'users.name as created_by_name'
                )
                ->where('mart_cash_entries.status', 'pending')
                ->orderByDesc('mart_cash_entries.entry_date')
                ->orderByDesc('mart_cash_entries.created_at')
                ->get();
        }

        return view('mart.balance', [
            'balance' => $balance,
            'lastTransaction' => $lastTransaction,
            'entries' => $entries,
            'pendingEntries' => $pendingEntries,
            'role' => $role,
            'ledgerRows' => $ledgerData['ledgerRows'],
            'month' => $month,
            'year' => $year,
            'monthNames' => $ledgerData['monthNames'],
            'availableYears' => $ledgerData['availableYears'],
        ]);
    }

    public function store(Request $request)
    {
        $direction = $request->input('direction');
        $rules = [
            'direction' => 'required|in:in,out',
            'entry_date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'evidence' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ];

        $payload = $request->validate($rules);

        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            $folder = 'uploads/mart-evidence';
            $publicFolder = public_path($folder);
            if (!is_dir($publicFolder)) {
                mkdir($publicFolder, 0755, true);
            }
            $file = $request->file('evidence');
            $filename = uniqid('mart_', true) . '.' . $file->getClientOriginalExtension();
            $file->move($publicFolder, $filename);
            $evidencePath = $folder . '/' . $filename;
        }

        DB::table('mart_cash_entries')->insert([
            'entry_date' => $payload['entry_date'],
            'direction' => $payload['direction'],
            'description' => $payload['description'],
            'amount' => $payload['amount'],
            'evidence_path' => $evidencePath,
            'status' => 'pending',
            'created_by' => $request->session()->get('auth.id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $message = $payload['direction'] === 'in'
            ? 'Pemasukan koperasi mart berhasil dikirim dan menunggu verifikasi.'
            : 'Pengeluaran koperasi mart berhasil dikirim dan menunggu verifikasi.';

        return redirect()
            ->route('mart.balance.index')
            ->with('success', $message);
    }

    public function verify(Request $request, $id)
    {
        $role = $request->session()->get('auth.role');
        if (!in_array($role, ['sekretaris', 'superadmin'])) {
            return redirect()->route('mart.balance.index');
        }

        $entry = DB::table('mart_cash_entries')
            ->where('id', $id)
            ->where('status', 'pending')
            ->first();

        if (!$entry) {
            return redirect()
                ->route('mart.balance.index')
                ->withErrors(['error' => 'Data tidak ditemukan atau sudah diverifikasi.']);
        }

        DB::table('mart_cash_entries')
            ->where('id', $id)
            ->update([
                'status' => 'approved',
                'verified_by' => $request->session()->get('auth.id'),
                'verified_at' => now(),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('mart.balance.index')
            ->with('success', 'Pemasukan/pengeluaran koperasi mart berhasil diverifikasi.');
    }

    private function ledgerData($month, $year)
    {
        $monthNames = $this->monthNames();
        $start = now()->setDate($year, $month, 1)->startOfDay();
        $end = now()->setDate($year, $month, 1)->endOfMonth()->endOfDay();

        $openingIn = DB::table('mart_cash_entries')
            ->where('direction', 'in')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'approved');
            })
            ->where('entry_date', '<', $start->toDateString())
            ->sum('amount');

        $openingOut = DB::table('mart_cash_entries')
            ->where('direction', 'out')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'approved');
            })
            ->where('entry_date', '<', $start->toDateString())
            ->sum('amount');

        $openingBalance = (float) $openingIn - (float) $openingOut;

        $rows = DB::table('mart_cash_entries')
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'approved');
            })
            ->orderBy('entry_date')
            ->orderBy('created_at')
            ->get();

        $transactions = [];
        foreach ($rows as $row) {
            $transactions[] = [
                'date' => $row->entry_date,
                'description' => $row->description,
                'receipts_total' => $row->direction === 'in' ? (float) $row->amount : 0,
                'expenses_total' => $row->direction === 'out' ? (float) $row->amount : 0,
                'evidence_path' => $row->evidence_path,
                'status' => $row->status ?? 'approved',
                'created_at' => $row->created_at,
            ];
        }

        $ledgerRows = [];
        $balance = $openingBalance;
        $ledgerRows[] = [
            'date' => $start->toDateString(),
            'description' => 'Saldo Awal',
            'receipts_total' => 0,
            'expenses_total' => 0,
            'balance' => $balance,
            'evidence_path' => null,
            'status' => '-',
        ];

        foreach ($transactions as $entry) {
            $balance += (float) $entry['receipts_total'];
            $balance -= (float) $entry['expenses_total'];
            $entry['balance'] = $balance;
            $ledgerRows[] = $entry;
        }

        $years = DB::table('mart_cash_entries')
            ->selectRaw('distinct year(entry_date) as year')
            ->pluck('year')
            ->filter()
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        if (!$years) {
            $years = [now()->year];
        }

        return [
            'ledgerRows' => $ledgerRows,
            'monthNames' => $monthNames,
            'availableYears' => $years,
            'entries' => $rows,
        ];
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
