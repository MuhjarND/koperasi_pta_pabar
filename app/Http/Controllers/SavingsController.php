<?php

namespace App\Http\Controllers;

use App\Support\SavingsRunningBalance;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavingsController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->session()->get('auth.role');
        $userId = $request->session()->get('auth.id');
        $types = array_keys(config('koperasi.savings_types'));
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

        $rows = DB::table('savings_transactions')
            ->join('users', 'savings_transactions.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.member_no',
                'savings_transactions.type',
                'savings_transactions.amount',
                'savings_transactions.note',
                'savings_transactions.posted_at',
                'savings_transactions.created_at'
            )
            ->when($role === 'anggota', function ($query) use ($userId) {
                $query->where('savings_transactions.user_id', $userId);
            })
            ->orderBy('users.name')
            ->orderBy('savings_transactions.created_at')
            ->get();

        $memberSummaries = [];
        $memberTypeTotals = array_fill_keys($types, 0);

        foreach ($rows as $row) {
            if (!isset($memberSummaries[$row->id])) {
                $memberSummaries[$row->id] = [
                    'id' => $row->id,
                    'name' => $row->name,
                    'member_no' => $row->member_no,
                    'total_amount' => 0,
                    'months' => [],
                ];
            }

            $monthKey = date('Y-m', strtotime($row->created_at));
            $monthNumber = (int) date('n', strtotime($row->created_at));
            $yearNumber = (int) date('Y', strtotime($row->created_at));
            $monthLabel = ($monthNames[$monthNumber] ?? $monthNumber) . ' ' . $yearNumber;

            if (!isset($memberSummaries[$row->id]['months'][$monthKey])) {
                $memberSummaries[$row->id]['months'][$monthKey] = [
                    'label' => $monthLabel,
                    'types' => array_fill_keys($types, 0),
                    'manual_types' => array_fill_keys($types, 0),
                    'manual_total' => 0,
                    'has_manual' => false,
                    'has_posted_manual' => false,
                    'total' => 0,
                ];
            }

            $memberSummaries[$row->id]['months'][$monthKey]['types'][$row->type] += (float) $row->amount;
            $memberSummaries[$row->id]['months'][$monthKey]['total'] += (float) $row->amount;
            $memberSummaries[$row->id]['total_amount'] += (float) $row->amount;

            if (isset($memberTypeTotals[$row->type])) {
                $memberTypeTotals[$row->type] += (float) $row->amount;
            }

            $isManual = $row->note !== 'Potong Gaji';
            if ($isManual) {
                $memberSummaries[$row->id]['months'][$monthKey]['manual_types'][$row->type] += (float) $row->amount;
                $memberSummaries[$row->id]['months'][$monthKey]['manual_total'] += (float) $row->amount;
                $memberSummaries[$row->id]['months'][$monthKey]['has_manual'] = true;
                if (!empty($row->posted_at)) {
                    $memberSummaries[$row->id]['months'][$monthKey]['has_posted_manual'] = true;
                }
            }
        }

        foreach ($memberSummaries as $id => $member) {
            $memberSummaries[$id]['months'] = SavingsRunningBalance::applyToMonths(
                $memberSummaries[$id]['months'],
                $types
            );

            foreach ($memberSummaries[$id]['months'] as $key => $month) {
                $hasManual = $month['has_manual'] ?? false;
                $hasPostedManual = $month['has_posted_manual'] ?? false;
                $memberSummaries[$id]['months'][$key]['key'] = $key;
                $memberSummaries[$id]['months'][$key]['editable'] = $hasManual && !$hasPostedManual;
            }
            $memberSummaries[$id]['months'] = array_values($memberSummaries[$id]['months']);
        }

        $members = [];
        $pendingSavings = [
            'count' => 0,
            'total' => 0,
        ];
        if ($role === 'bendahara') {
            $members = DB::table('users')
                ->select('id', 'name', 'member_no')
                ->where('role', 'anggota')
                ->orderBy('name')
                ->get();

            $pendingSavings = [
                'count' => DB::table('savings_transactions')
                    ->whereNull('posted_at')
                    ->where(function ($query) {
                        $query->whereNull('note')
                            ->orWhere('note', '!=', 'Potong Gaji');
                    })
                    ->count(),
                'total' => DB::table('savings_transactions')
                    ->whereNull('posted_at')
                    ->where(function ($query) {
                        $query->whereNull('note')
                            ->orWhere('note', '!=', 'Potong Gaji');
                    })
                    ->sum('amount'),
            ];
        }

        $summaryTotals = [
            'total' => array_sum($memberTypeTotals),
            'types' => $memberTypeTotals,
            'members' => count($memberSummaries),
        ];

        return view('savings.index', [
            'role' => $role,
            'types' => config('koperasi.savings_types'),
            'members' => $members,
            'memberSummaries' => array_values($memberSummaries),
            'pendingSavings' => $pendingSavings,
            'memberTypeTotals' => $role === 'anggota' ? $memberTypeTotals : [],
            'summaryTotals' => $summaryTotals,
            'monthNames' => $monthNames,
        ]);
    }

    public function rekapPdf(Request $request)
    {
        $role = $request->session()->get('auth.role');
        if (in_array($role, ['anggota', 'bendahara_kantor'])) {
            abort(403);
        }

        $types = array_keys(config('koperasi.savings_types'));
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

        $monthParam = $request->query('month', 'all');
        $year = now()->year;
        $rowsQuery = DB::table('savings_transactions')
            ->join('users', 'savings_transactions.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.member_no',
                'savings_transactions.type',
                'savings_transactions.amount',
                'savings_transactions.created_at'
            )
            ->orderBy('users.name')
            ->orderBy('savings_transactions.created_at');

        if ($monthParam !== 'all') {
            $monthValue = (int) $monthParam;
            if ($monthValue >= 1 && $monthValue <= 12) {
                $periodEnd = Carbon::create($year, $monthValue, 1, 0, 0, 0)->endOfMonth()->endOfDay();
                $rowsQuery->where('savings_transactions.created_at', '<=', $periodEnd->toDateTimeString());
            }
        }

        $rows = $rowsQuery->get();

        $memberSummaries = [];
        $memberTypeTotals = array_fill_keys($types, 0);

        foreach ($rows as $row) {
            if (!isset($memberSummaries[$row->id])) {
                $memberSummaries[$row->id] = [
                    'id' => $row->id,
                    'name' => $row->name,
                    'member_no' => $row->member_no,
                    'total_amount' => 0,
                    'months' => [],
                ];
            }

            $monthKey = date('Y-m', strtotime($row->created_at));
            $monthNumber = (int) date('n', strtotime($row->created_at));
            $yearNumber = (int) date('Y', strtotime($row->created_at));
            $monthLabel = ($monthNames[$monthNumber] ?? $monthNumber) . ' ' . $yearNumber;

            if (!isset($memberSummaries[$row->id]['months'][$monthKey])) {
                $memberSummaries[$row->id]['months'][$monthKey] = [
                    'label' => $monthLabel,
                    'types' => array_fill_keys($types, 0),
                    'total' => 0,
                ];
            }

            $memberSummaries[$row->id]['months'][$monthKey]['types'][$row->type] += (float) $row->amount;
            $memberSummaries[$row->id]['months'][$monthKey]['total'] += (float) $row->amount;
            $memberSummaries[$row->id]['total_amount'] += (float) $row->amount;

            if (isset($memberTypeTotals[$row->type])) {
                $memberTypeTotals[$row->type] += (float) $row->amount;
            }
        }

        foreach ($memberSummaries as $id => $member) {
            $memberSummaries[$id]['months'] = SavingsRunningBalance::applyToMonths(
                $memberSummaries[$id]['months'],
                $types
            );
            $memberSummaries[$id]['months'] = array_values($memberSummaries[$id]['months']);
        }

        $summaryTotals = [
            'total' => array_sum($memberTypeTotals),
            'types' => $memberTypeTotals,
            'members' => count($memberSummaries),
        ];

        $periodLabel = 'Januari - ' . now()->translatedFormat('F Y');
        if ($monthParam !== 'all') {
            $monthValue = (int) $monthParam;
            if ($monthValue >= 1 && $monthValue <= 12) {
                $periodLabel = 'Sampai ' . ($monthNames[$monthValue] ?? $monthValue) . ' ' . $year;
            }
        }

        $pdf = PDF::loadView('savings.rekap_pdf', [
            'memberSummaries' => array_values($memberSummaries),
            'types' => config('koperasi.savings_types'),
            'summaryTotals' => $summaryTotals,
            'periodLabel' => $periodLabel,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('rekap-simpanan.pdf');
    }

    public function store(Request $request)
    {
        $types = implode(',', array_keys(config('koperasi.savings_types')));

        $payload = $request->validate([
            'user_id' => 'required|exists:users,id',
            'types' => 'required|array',
            'types.*' => 'in:' . $types,
            'amounts' => 'required|array',
            'amounts.*' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        $entries = [];
        $selectedTypes = $payload['types'] ?? [];
        $amounts = $payload['amounts'] ?? [];
        $timestamp = now();

        foreach ($selectedTypes as $type) {
            $amount = (float) ($amounts[$type] ?? 0);
            if ($amount <= 0) {
                return back()
                    ->withErrors(['amounts.' . $type => 'Nominal ' . $type . ' harus diisi.'])
                    ->withInput();
            }

            if ($type === 'pokok') {
                $exists = DB::table('savings_transactions')
                    ->where('user_id', $payload['user_id'])
                    ->where('type', 'pokok')
                    ->exists();

                if ($exists) {
                    return back()
                        ->withErrors(['types' => 'Simpanan pokok hanya untuk anggota baru.'])
                        ->withInput();
                }
            }

            $entries[] = [
                'user_id' => $payload['user_id'],
                'type' => $type,
                'amount' => $amount,
                'note' => $payload['note'] ?? null,
                'created_by' => $request->session()->get('auth.id'),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        if (!$entries) {
            return back()
                ->withErrors(['types' => 'Pilih minimal satu jenis simpanan.'])
                ->withInput();
        }

        DB::table('savings_transactions')->insert($entries);

        return redirect()
            ->route('savings.index')
            ->with('success', 'Transaksi simpanan berhasil dicatat.');
    }

    public function postToCash(Request $request)
    {
        $payload = $request->validate([
            'evidence' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $pendingRows = DB::table('savings_transactions')
            ->join('users', 'savings_transactions.user_id', '=', 'users.id')
            ->select(
                'savings_transactions.user_id',
                'users.name',
                DB::raw('sum(savings_transactions.amount) as total')
            )
            ->whereNull('savings_transactions.posted_at')
            ->where(function ($query) {
                $query->whereNull('savings_transactions.note')
                    ->orWhere('savings_transactions.note', '!=', 'Potong Gaji');
            })
            ->groupBy('savings_transactions.user_id', 'users.name')
            ->get();

        if ($pendingRows->isEmpty()) {
            return redirect()
                ->route('savings.index')
                ->withErrors(['error' => 'Tidak ada simpanan yang menunggu masuk arus kas.']);
        }

        $evidencePath = null;
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

        $now = now();
        $createdBy = $request->session()->get('auth.id');

        DB::transaction(function () use ($pendingRows, $now, $createdBy, $evidencePath) {
            foreach ($pendingRows as $row) {
                DB::table('cash_entries')->insert([
                    'entry_date' => $now->toDateString(),
                    'direction' => 'in',
                    'description' => 'Terima dari Bendahara (' . $row->name . ')',
                    'amount' => (float) $row->total,
                    'category' => 'simpanan',
                    'user_id' => $row->user_id,
                    'evidence_path' => $evidencePath,
                    'status' => 'pending',
                    'created_by' => $createdBy,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('savings_transactions')
                ->whereNull('posted_at')
                ->where(function ($query) {
                    $query->whereNull('note')
                        ->orWhere('note', '!=', 'Potong Gaji');
                })
                ->update([
                    'posted_at' => $now,
                    'posted_by' => $createdBy,
                    'updated_at' => $now,
                ]);
        });

        return redirect()
            ->route('savings.index')
            ->with('success', 'Simpanan berhasil dimasukkan ke arus kas.');
    }

    public function updateMonth(Request $request, $userId, $month)
    {
        $role = $request->session()->get('auth.role');
        if (!in_array($role, ['bendahara', 'superadmin'])) {
            return redirect()->route('savings.index');
        }

        $types = array_keys(config('koperasi.savings_types'));
        $typeList = implode(',', $types);

        $payload = $request->validate([
            'types' => 'required|array',
            'types.*' => 'in:' . $typeList,
            'amounts' => 'required|array',
            'amounts.*' => 'nullable|numeric|min:0',
        ]);

        $selectedTypes = $payload['types'] ?? [];
        $amounts = $payload['amounts'] ?? [];
        $monthDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->startOfDay();
        $monthEnd = $monthDate->copy()->endOfMonth()->endOfDay();
        $createdBy = $request->session()->get('auth.id');
        $timestamp = $monthDate->copy()->addHours(8);

        $manualQuery = DB::table('savings_transactions')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$monthDate->toDateTimeString(), $monthEnd->toDateTimeString()])
            ->where(function ($query) {
                $query->whereNull('note')
                    ->orWhere('note', '!=', 'Potong Gaji');
            });

        $hasPostedManual = (clone $manualQuery)
            ->whereNotNull('posted_at')
            ->exists();

        if ($hasPostedManual) {
            return redirect()
                ->route('savings.index')
                ->withErrors(['error' => 'Simpanan bulan tersebut sudah masuk arus kas dan tidak bisa diubah.']);
        }

        if (in_array('pokok', $selectedTypes, true)) {
            $hasOtherPokok = DB::table('savings_transactions')
                ->where('user_id', $userId)
                ->where('type', 'pokok')
                ->where(function ($query) use ($monthDate, $monthEnd) {
                    $query->where('created_at', '<', $monthDate->toDateTimeString())
                        ->orWhere('created_at', '>', $monthEnd->toDateTimeString());
                })
                ->exists();

            if ($hasOtherPokok) {
                return redirect()
                    ->route('savings.index')
                    ->withErrors(['error' => 'Simpanan pokok hanya untuk anggota baru.']);
            }
        }

        $entries = [];
        foreach ($selectedTypes as $type) {
            $amount = (float) ($amounts[$type] ?? 0);
            if ($amount <= 0) {
                return redirect()
                    ->route('savings.index')
                    ->withErrors(['error' => 'Nominal simpanan tidak boleh kosong.']);
            }

            $entries[] = [
                'user_id' => $userId,
                'type' => $type,
                'amount' => $amount,
                'note' => null,
                'created_by' => $createdBy,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::transaction(function () use ($manualQuery, $entries) {
            $manualQuery->whereNull('posted_at')->delete();
            if ($entries) {
                DB::table('savings_transactions')->insert($entries);
            }
        });

        return redirect()
            ->route('savings.index')
            ->with('success', 'Simpanan bulanan berhasil diperbarui.');
    }

    public function destroyMonth(Request $request, $userId, $month)
    {
        $role = $request->session()->get('auth.role');
        if (!in_array($role, ['bendahara', 'superadmin'])) {
            return redirect()->route('savings.index');
        }

        $monthDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->startOfDay();
        $monthEnd = $monthDate->copy()->endOfMonth()->endOfDay();

        $manualQuery = DB::table('savings_transactions')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$monthDate->toDateTimeString(), $monthEnd->toDateTimeString()])
            ->where(function ($query) {
                $query->whereNull('note')
                    ->orWhere('note', '!=', 'Potong Gaji');
            });

        $hasPostedManual = (clone $manualQuery)
            ->whereNotNull('posted_at')
            ->exists();

        if ($hasPostedManual) {
            return redirect()
                ->route('savings.index')
                ->withErrors(['error' => 'Simpanan bulan tersebut sudah masuk arus kas dan tidak bisa dihapus.']);
        }

        $manualQuery->whereNull('posted_at')->delete();

        return redirect()
            ->route('savings.index')
            ->with('success', 'Simpanan bulanan berhasil dihapus.');
    }
}
