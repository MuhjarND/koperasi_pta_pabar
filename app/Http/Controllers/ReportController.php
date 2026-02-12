<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function type(Request $request, $type)
    {
        $reportTypes = $this->reportTypes();

        if (!array_key_exists($type, $reportTypes)) {
            abort(404);
        }

        $period = $request->get('period', 'monthly');
        if (!in_array($period, ['monthly', 'yearly'], true)) {
            $period = 'monthly';
        }

        $year = now()->year;
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = now()->endOfMonth();
        $month = (int) now()->month;
        $periodLabel = 'Januari - ' . now()->translatedFormat('F Y');
        $multiplier = $month;

        $data = $this->buildReportData($start, $end, $multiplier);

        return view('reports.type', array_merge($data, [
            'type' => $type,
            'typeLabel' => $reportTypes[$type],
            'period' => $period,
            'periodLabel' => $periodLabel,
            'month' => $month,
            'year' => $year,
            'reportTypes' => $reportTypes,
            'types' => config('koperasi.savings_types'),
        ]));
    }

    public function monthly(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        return redirect()->route('reports.type', [
            'type' => 'shu',
            'period' => 'monthly',
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function yearly(Request $request)
    {
        $year = (int) $request->get('year', now()->year);

        return redirect()->route('reports.type', [
            'type' => 'shu',
            'period' => 'yearly',
            'year' => $year,
        ]);
    }

    public function shuPdf(Request $request, $section)
    {
        $section = (int) $section;
        if (!in_array($section, [1, 2, 3], true)) {
            abort(404);
        }

        $year = now()->year;
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = now()->endOfMonth();
        $periodLabel = 'Januari - ' . now()->translatedFormat('F Y');

        $data = $this->buildReportData($start, $end, 1);

        $view = 'reports.shu_section1_pdf';
        $orientation = 'landscape';
        if ($section === 2) {
            $view = 'reports.shu_section2_pdf';
        }
        if ($section === 3) {
            $view = 'reports.shu_section3_pdf';
            $orientation = 'portrait';
        }

        $pdf = \Barryvdh\DomPDF\Facade::loadView($view, array_merge($data, [
            'periodLabel' => $periodLabel,
        ]))->setPaper('a4', $orientation);

        return $pdf->stream('laporan-shu-bagian-' . $section . '.pdf');
    }

    public function labaRugiPdf(Request $request)
    {
        $year = now()->year;
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = now()->endOfMonth();
        $periodLabel = 'Januari - ' . now()->translatedFormat('F Y');

        $data = $this->buildReportData($start, $end, 1);

        $pdf = \Barryvdh\DomPDF\Facade::loadView('reports.laba_rugi_pdf', array_merge($data, [
            'periodLabel' => $periodLabel,
        ]))->setPaper('a4', 'portrait');

        return $pdf->stream('laporan-laba-rugi.pdf');
    }

    private function sumItems(array $items)
    {
        return collect($items)->sum('amount');
    }

    private function toCents($amount)
    {
        return (int) round(((float) $amount) * 100);
    }

    private function fromCents($cents)
    {
        return $cents / 100;
    }

    private function percentCents($amountCents, $percent)
    {
        return (int) intdiv(($amountCents * $percent) + 50, 100);
    }

    private function allocatePercentCents($amountCents, array $percents)
    {
        $shares = [];
        $remainders = [];
        $sum = 0;

        foreach ($percents as $index => $percent) {
            $raw = $amountCents * $percent;
            $share = (int) intdiv($raw, 100);
            $shares[$index] = $share;
            $remainders[$index] = $raw % 100;
            $sum += $share;
        }

        $diff = $amountCents - $sum;
        if ($diff > 0) {
            arsort($remainders);
            $keys = array_keys($remainders);
            $count = count($keys);
            for ($i = 0; $i < $diff; $i++) {
                $shares[$keys[$i % $count]]++;
            }
        }

        return $shares;
    }

    private function buildReportData(Carbon $start, Carbon $end, $multiplier)
    {
        $savingsByType = DB::table('savings_transactions')
            ->select('type', DB::raw('sum(amount) as total'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('type')
            ->pluck('total', 'type');

        $savingsTotal = DB::table('savings_transactions')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $salesTotal = DB::table('sales')
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_amount');

        $loanRequested = DB::table('loans')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $loanApproved = DB::table('loans')
            ->where('status', 'approved_chairman')
            ->whereNotNull('transfered_at')
            ->whereBetween('transfered_at', [$start, $end])
            ->sum('amount');

        $loanRejected = DB::table('loans')
            ->where('status', 'rejected')
            ->whereBetween('rejected_at', [$start, $end])
            ->sum('amount');

        $loanCounts = DB::table('loans')
            ->select('status', DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('status')
            ->pluck('total', 'status');

        $cashIn = $savingsTotal + $salesTotal;
        $cashOut = $loanApproved;
        $cashNet = $cashIn - $cashOut;

        $feeByMonth = DB::table('loan_installment_payments')
            ->select(DB::raw("date_format(paid_at, '%Y-%m') as ym"), DB::raw('sum(amount_fee) as total'))
            ->where('status', 'approved')
            ->whereBetween('paid_at', [$start->toDateString(), $end->toDateString()])
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $monthlyServiceIncome = [];
        $serviceIncomeCents = 0;
        $serviceMonths = [];
        $cursor = $start->copy()->startOfMonth();
        $endMonth = $end->copy()->startOfMonth();
        while ($cursor <= $endMonth) {
            $key = $cursor->format('Y-m');
            $serviceMonths[] = [
                'key' => $key,
                'label' => $cursor->translatedFormat('F'),
                'label_full' => $cursor->translatedFormat('F Y'),
            ];
            $amountCents = $this->toCents($feeByMonth[$key] ?? 0);
            $monthlyServiceIncome[] = [
                'label' => $cursor->translatedFormat('F Y'),
                'amount' => $this->fromCents($amountCents),
            ];
            $serviceIncomeCents += $amountCents;
            $cursor->addMonth();
        }
        $serviceIncome = $this->fromCents($serviceIncomeCents);

        $feeRows = DB::table('loan_installment_payments as lip')
            ->join('loans', 'lip.loan_id', '=', 'loans.id')
            ->select('loans.user_id', DB::raw("date_format(lip.paid_at, '%Y-%m') as ym"), DB::raw('sum(lip.amount_fee) as total'))
            ->where('lip.status', 'approved')
            ->whereBetween('lip.paid_at', [$start->toDateString(), $end->toDateString()])
            ->groupBy('loans.user_id', 'ym')
            ->get();

        $feeMap = [];
        foreach ($feeRows as $row) {
            $feeMap[$row->user_id][$row->ym] = $this->toCents($row->total);
        }

        $members = DB::table('users')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $memberServiceRows = [];
        foreach ($members as $member) {
            $rowMonths = [];
            $totalCents = 0;
            foreach ($serviceMonths as $month) {
                $amountCents = (int) ($feeMap[$member->id][$month['key']] ?? 0);
                $rowMonths[] = $this->fromCents($amountCents);
                $totalCents += $amountCents;
            }
            $memberServiceRows[] = [
                'name' => $member->name,
                'months' => $rowMonths,
                'total' => $this->fromCents($totalCents),
                'total_cents' => $totalCents,
            ];
        }

        $tradeRows = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->leftJoin('products as p', 'si.product_id', '=', 'p.id')
            ->select(
                's.buyer_name',
                DB::raw("date_format(s.created_at, '%Y-%m') as ym"),
                DB::raw('sum((si.price - coalesce(p.modal,0)) * si.qty) as profit')
            )
            ->whereNotNull('s.buyer_name')
            ->whereBetween('s.created_at', [$start, $end])
            ->groupBy('s.buyer_name', 'ym')
            ->get();

        $tradeMap = [];
        foreach ($tradeRows as $row) {
            $tradeMap[$row->buyer_name][$row->ym] = $this->toCents($row->profit);
        }

        $memberTradeRows = [];
        $tradeTotalsByName = [];
        foreach ($members as $member) {
            $rowMonths = [];
            $totalCents = 0;
            foreach ($serviceMonths as $month) {
                $amountCents = (int) ($tradeMap[$member->name][$month['key']] ?? 0);
                $rowMonths[] = $this->fromCents($amountCents);
                $totalCents += $amountCents;
            }
            $memberTradeRows[] = [
                'name' => $member->name,
                'months' => $rowMonths,
                'total' => $this->fromCents($totalCents),
                'total_cents' => $totalCents,
            ];
            $tradeTotalsByName[$member->name] = $totalCents;
        }

        $monthlyTradeTotals = [];
        $tradeTotalCents = 0;
        foreach ($serviceMonths as $index => $month) {
            $monthTotalCents = 0;
            foreach ($memberTradeRows as $row) {
                $monthTotalCents += $this->toCents($row['months'][$index] ?? 0);
            }
            $monthlyTradeTotals[] = [
                'label' => $month['label'],
                'amount' => $this->fromCents($monthTotalCents),
            ];
            $tradeTotalCents += $monthTotalCents;
        }
        $tradeTotal = $this->fromCents($tradeTotalCents);

        $shuMemberRows = [];
        $shuMemberTotals = [
            'hu' => 0,
            'toko' => 0,
            'sp_pinjam' => 0,
            'operational' => 0,
            'shu_pinjaman' => 0,
            'shu_partisipasi' => 0,
            'dana_pengurus' => 0,
            'dana_cadangan' => 0,
            'dana_sosial' => 0,
            'dana_pendidikan' => 0,
        ];

        foreach ($memberServiceRows as $row) {
            $spPinjamCents = (int) ($row['total_cents'] ?? 0);
            $tokoCents = (int) ($tradeTotalsByName[$row['name']] ?? 0);
            $huCents = $spPinjamCents + $tokoCents;
            $operationalCents = $this->percentCents($huCents, 25);
            $baseCents = $huCents - $operationalCents;
            $parts = $this->allocatePercentCents($baseCents, [30, 25, 15, 15, 10, 5]);
            $shuPinjamanCents = $parts[0] ?? 0;
            $shuPartisipasiCents = $parts[1] ?? 0;
            $danaPengurusCents = $parts[2] ?? 0;
            $danaCadanganCents = $parts[3] ?? 0;
            $danaSosialCents = $parts[4] ?? 0;
            $danaPendidikanCents = $parts[5] ?? 0;

            $shuMemberTotals['hu'] += $huCents;
            $shuMemberTotals['toko'] += $tokoCents;
            $shuMemberTotals['sp_pinjam'] += $spPinjamCents;
            $shuMemberTotals['operational'] += $operationalCents;
            $shuMemberTotals['shu_pinjaman'] += $shuPinjamanCents;
            $shuMemberTotals['shu_partisipasi'] += $shuPartisipasiCents;
            $shuMemberTotals['dana_pengurus'] += $danaPengurusCents;
            $shuMemberTotals['dana_cadangan'] += $danaCadanganCents;
            $shuMemberTotals['dana_sosial'] += $danaSosialCents;
            $shuMemberTotals['dana_pendidikan'] += $danaPendidikanCents;

            $shuMemberRows[] = [
                'name' => $row['name'],
                'toko' => $this->fromCents($tokoCents),
                'sp_pinjam' => $this->fromCents($spPinjamCents),
                'jumlah' => $this->fromCents($huCents),
                'hu' => $this->fromCents($huCents),
                'operational' => $this->fromCents($operationalCents),
                'shu_pinjaman' => $this->fromCents($shuPinjamanCents),
                'shu_partisipasi' => $this->fromCents($shuPartisipasiCents),
                'dana_pengurus' => $this->fromCents($danaPengurusCents),
                'dana_cadangan' => $this->fromCents($danaCadanganCents),
                'dana_sosial' => $this->fromCents($danaSosialCents),
                'dana_pendidikan' => $this->fromCents($danaPendidikanCents),
            ];
        }

        $shuTotalsBaseCents = $shuMemberTotals['hu'] - $shuMemberTotals['operational'];
        $shuTotalsParts = $this->allocatePercentCents($shuTotalsBaseCents, [30, 25, 15, 15, 10, 5]);

        $operationalCostCents = $this->percentCents($serviceIncomeCents, 25);
        $shuBaseCents = $serviceIncomeCents - $operationalCostCents;
        $operationalCost = $this->fromCents($operationalCostCents);
        $shuBase = $this->fromCents($shuBaseCents);
        $shuBreakdownParts = $this->allocatePercentCents($shuBaseCents, [30, 25, 15, 15, 10, 5]);
        $danaPengurus = $this->fromCents($shuBreakdownParts[2] ?? 0);
        $danaCadangan = $this->fromCents($shuBreakdownParts[3] ?? 0);
        $danaSosial = $this->fromCents($shuBreakdownParts[4] ?? 0);
        $danaPendidikan = $this->fromCents($shuBreakdownParts[5] ?? 0);
        $shuBreakdown = [
            ['name' => 'Biaya Operasional (25%)', 'amount' => $operationalCost],
            ['name' => 'SHU Pinjaman (30%)', 'amount' => $this->fromCents($shuBreakdownParts[0] ?? 0)],
            ['name' => 'SHU Partisipasi Usaha (25%)', 'amount' => $this->fromCents($shuBreakdownParts[1] ?? 0)],
            ['name' => 'Dana Pengurus (15%)', 'amount' => $danaPengurus],
            ['name' => 'Dana Cadangan (15%)', 'amount' => $danaCadangan],
            ['name' => 'Dana Sosial (10%)', 'amount' => $danaSosial],
            ['name' => 'Dana Pendidikan (5%)', 'amount' => $danaPendidikan],
        ];

        $rate = config('koperasi.service_fee_rate');

        $otherIncomeTotal = DB::table('cash_entries')
            ->where('direction', 'in')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'approved');
            })
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($query) {
                $query->whereNull('category')
                    ->orWhereNotIn('category', ['potongan', 'simpanan', 'pelunasan', 'saldo_awal']);
            })
            ->sum('amount');

        $expenseItems = DB::table('cash_entries')
            ->join('users', 'cash_entries.created_by', '=', 'users.id')
            ->select('cash_entries.description', DB::raw('sum(cash_entries.amount) as total'))
            ->where('cash_entries.direction', 'out')
            ->where(function ($query) {
                $query->whereNull('cash_entries.status')
                    ->orWhere('cash_entries.status', 'approved');
            })
            ->whereBetween('cash_entries.entry_date', [$start->toDateString(), $end->toDateString()])
            ->where('users.role', 'bendahara')
            ->groupBy('cash_entries.description')
            ->orderBy('cash_entries.description')
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->description,
                    'amount' => (float) $row->total,
                ];
            })
            ->toArray();

        $otherIncomeItems = [
            ['name' => 'Pendapatan Lain-lain', 'amount' => (float) $otherIncomeTotal],
        ];

        $expenseTotal = $this->sumItems($expenseItems);
        $totalIncome = $serviceIncome + $tradeTotal + $otherIncomeTotal;
        $shu = $totalIncome - $expenseTotal;

        $investingCash = 0;
        $operatingCash = $cashIn - $expenseTotal;
        $financingCash = -$loanApproved;
        $netCashFlow = $operatingCash + $investingCash + $financingCash;

        $savingsBalance = DB::table('savings_transactions')
            ->where('created_at', '<=', $end)
            ->sum('amount');

        $inventoryValue = DB::table('products')
            ->select(DB::raw('sum(stock * modal) as total'))
            ->value('total') ?? 0;

        $approvedLoansTotal = DB::table('loans')
            ->where('status', 'approved_chairman')
            ->whereNotNull('transfered_at')
            ->where('transfered_at', '<=', $end)
            ->sum('amount');

        $paidPrincipal = DB::table('loan_installment_payments')
            ->join('loans', 'loan_installment_payments.loan_id', '=', 'loans.id')
            ->where('loans.status', 'approved_chairman')
            ->whereNotNull('loans.transfered_at')
            ->where('loan_installment_payments.status', 'approved')
            ->where('loan_installment_payments.paid_at', '<=', $end->toDateString())
            ->sum('loan_installment_payments.amount_principal');

        $loanReceivable = max(0, (float) $approvedLoansTotal - (float) $paidPrincipal);

        $loanReceipts = DB::table('loan_installment_payments')
            ->where('status', 'approved')
            ->where('installment_no', '>', 0)
            ->where('paid_at', '<=', $end->toDateString())
            ->where(function ($query) {
                $query->whereNull('note')
                    ->orWhereNotIn('note', ['Potong Gaji', 'Pelunasan']);
            })
            ->select(DB::raw('sum(amount_principal + amount_fee) as total'))
            ->value('total');

        $cashInApproved = DB::table('cash_entries')
            ->where('direction', 'in')
            ->whereIn('category', ['potongan', 'simpanan', 'pelunasan', 'saldo_awal'])
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'approved');
            })
            ->where('entry_date', '<=', $end->toDateString())
            ->sum('amount');

        $cashOutApproved = DB::table('cash_entries')
            ->where('direction', 'out')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'approved');
            })
            ->where('entry_date', '<=', $end->toDateString())
            ->sum('amount');

        $cashBalance = (float) ($loanReceipts ?? 0)
            + (float) $cashInApproved
            - (float) $cashOutApproved;

        $tradeCash = DB::table('sales')
            ->where('created_at', '<=', $end)
            ->sum('total_amount');

        $investmentItems = DB::table('cash_entries')
            ->select('description', DB::raw('sum(amount) as total'))
            ->where('direction', 'out')
            ->where('category', 'investasi')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'approved');
            })
            ->where('entry_date', '<=', $end->toDateString())
            ->groupBy('description')
            ->orderBy('description')
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->description ?: 'Tanpa Keterangan',
                    'amount' => (float) $row->total,
                ];
            })
            ->toArray();

        $inventoryItems = DB::table('cash_entries')
            ->select('description', DB::raw('sum(amount) as total'))
            ->where('direction', 'out')
            ->where('category', 'inventaris')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'approved');
            })
            ->where('entry_date', '<=', $end->toDateString())
            ->groupBy('description')
            ->orderBy('description')
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->description ?: 'Tanpa Keterangan',
                    'amount' => (float) $row->total,
                ];
            })
            ->toArray();

        $investmentTotal = $this->sumItems($investmentItems);
        $inventoryTotal = $this->sumItems($inventoryItems);
        $currentAssetsTotal = (float) $cashBalance
            + (float) $loanReceivable
            + (float) $tradeCash
            + (float) $inventoryValue;
        $totalAssets = $currentAssetsTotal + (float) $investmentTotal + (float) $inventoryTotal;

        $unpaidRat = DB::table('cash_entries')
            ->where('direction', 'out')
            ->where('category', 'rat')
            ->where('status', 'pending')
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $pasivaLancarItems = [
            ['name' => 'Simpanan Sukarela', 'amount' => (float) ($savingsByType['sukarela'] ?? 0)],
            ['name' => 'Biaya RAT Belum Dibayar', 'amount' => (float) $unpaidRat],
            ['name' => 'Dana Pengurus', 'amount' => (float) $danaPengurus],
            ['name' => 'Dana Pendidikan', 'amount' => (float) $danaPendidikan],
            ['name' => 'Dana Sosial', 'amount' => (float) $danaSosial],
        ];
        $pasivaLancarTotal = $this->sumItems($pasivaLancarItems);

        $modalSendiriItems = [
            ['name' => 'Simpanan Pokok', 'amount' => (float) ($savingsByType['pokok'] ?? 0)],
            ['name' => 'Simpanan Wajib', 'amount' => (float) ($savingsByType['wajib'] ?? 0)],
            ['name' => 'HU Dagang', 'amount' => (float) $tradeTotal],
            ['name' => 'Dana Cadangan', 'amount' => (float) $danaCadangan],
            ['name' => 'Laba Bersih', 'amount' => (float) $shu],
        ];
        $modalSendiriTotal = $this->sumItems($modalSendiriItems);
        $pasivaTotal = $pasivaLancarTotal + $modalSendiriTotal;
        $totalLiabilities = $savingsBalance;
        $equityTarget = max(0, $totalAssets - $totalLiabilities);
        $equityOpening = max(0, $equityTarget - $shu);
        $equityItems = [
            ['name' => 'Modal Awal', 'amount' => $equityOpening],
            ['name' => 'SHU Berjalan', 'amount' => $shu],
        ];

        $savingsJournal = DB::table('savings_transactions')
            ->select('created_at as date', DB::raw("'Simpanan' as source"), 'amount', DB::raw("'in' as direction"), 'type as description')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $salesJournal = DB::table('sales')
            ->select('created_at as date', DB::raw("'Penjualan' as source"), 'total_amount as amount', DB::raw("'in' as direction"), 'buyer_name as description')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $loanJournal = DB::table('loans')
            ->select('transfered_at as date', DB::raw("'Pencairan Pinjaman' as source"), 'amount', DB::raw("'out' as direction"), 'applicant_name as description')
            ->where('status', 'approved_chairman')
            ->whereNotNull('transfered_at')
            ->whereBetween('transfered_at', [$start, $end])
            ->get();

        $journal = collect($savingsJournal)
            ->merge($salesJournal)
            ->merge($loanJournal)
            ->sortByDesc('date')
            ->values()
            ->take(40);

        return [
            'savingsByType' => $savingsByType,
            'savingsTotal' => $savingsTotal,
            'salesTotal' => $salesTotal,
            'loanRequested' => $loanRequested,
            'loanApproved' => $loanApproved,
            'loanRejected' => $loanRejected,
            'loanCounts' => $loanCounts,
            'cashIn' => $cashIn,
            'cashOut' => $cashOut,
            'cashNet' => $cashNet,
            'serviceIncome' => $serviceIncome,
            'monthlyServiceIncome' => $monthlyServiceIncome,
            'serviceMonths' => $serviceMonths,
            'memberServiceRows' => $memberServiceRows,
            'memberTradeRows' => $memberTradeRows,
            'monthlyTradeTotals' => $monthlyTradeTotals,
            'tradeTotal' => $tradeTotal,
            'shuMemberRows' => $shuMemberRows,
            'shuMemberTotals' => [
                'hu' => $this->fromCents($shuMemberTotals['hu']),
                'toko' => $this->fromCents($shuMemberTotals['toko']),
                'sp_pinjam' => $this->fromCents($shuMemberTotals['sp_pinjam']),
                'operational' => $this->fromCents($shuMemberTotals['operational']),
                'shu_pinjaman' => $this->fromCents($shuTotalsParts[0] ?? 0),
                'shu_partisipasi' => $this->fromCents($shuTotalsParts[1] ?? 0),
                'dana_pengurus' => $this->fromCents($shuTotalsParts[2] ?? 0),
                'dana_cadangan' => $this->fromCents($shuTotalsParts[3] ?? 0),
                'dana_sosial' => $this->fromCents($shuTotalsParts[4] ?? 0),
                'dana_pendidikan' => $this->fromCents($shuTotalsParts[5] ?? 0),
            ],
            'shuBreakdown' => $shuBreakdown,
            'operationalCost' => $operationalCost,
            'shuBase' => $shuBase,
            'otherIncomeItems' => $otherIncomeItems,
            'expenseItems' => $expenseItems,
            'otherIncomeTotal' => $otherIncomeTotal,
            'expenseTotal' => $expenseTotal,
            'totalIncome' => $totalIncome,
            'shu' => $shu,
            'labaBersih' => $shu,
            'labaBersihTerbilang' => $this->rupiahTerbilang($shu),
            'operatingCash' => $operatingCash,
            'investingCash' => $investingCash,
            'financingCash' => $financingCash,
            'netCashFlow' => $netCashFlow,
            'savingsBalance' => $savingsBalance,
            'inventoryValue' => $inventoryValue,
            'loanReceivable' => $loanReceivable,
            'cashBalance' => $cashBalance,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'equityItems' => $equityItems,
            'tradeCash' => $tradeCash,
            'currentAssetsTotal' => $currentAssetsTotal,
            'investmentItems' => $investmentItems,
            'investmentTotal' => $investmentTotal,
            'inventoryItems' => $inventoryItems,
            'inventoryTotal' => $inventoryTotal,
            'danaPengurus' => $danaPengurus,
            'danaCadangan' => $danaCadangan,
            'danaSosial' => $danaSosial,
            'danaPendidikan' => $danaPendidikan,
            'unpaidRat' => $unpaidRat,
            'pasivaLancarItems' => $pasivaLancarItems,
            'pasivaLancarTotal' => $pasivaLancarTotal,
            'modalSendiriItems' => $modalSendiriItems,
            'modalSendiriTotal' => $modalSendiriTotal,
            'pasivaTotal' => $pasivaTotal,
            'journal' => $journal,
        ];
    }

    private function reportTypes()
    {
        return [
            'shu' => 'SHU',
            'laba-rugi' => 'Laba Rugi',
            'neraca' => 'Neraca',
        ];
    }

    private function rupiahTerbilang($amount)
    {
        $number = (int) floor((float) $amount);
        $decimal = (int) round(((float) $amount - $number) * 100);
        $result = trim($this->terbilang($number)) . ' rupiah';

        if ($decimal > 0) {
            $result .= ' ' . trim($this->terbilang($decimal)) . ' sen';
        }

        return ucfirst(trim($result));
    }

    private function terbilang($number)
    {
        $number = abs((int) $number);
        $words = [
            'nol',
            'satu',
            'dua',
            'tiga',
            'empat',
            'lima',
            'enam',
            'tujuh',
            'delapan',
            'sembilan',
            'sepuluh',
            'sebelas',
        ];

        if ($number < 12) {
            return $words[$number];
        }

        if ($number < 20) {
            return $this->terbilang($number - 10) . ' belas';
        }

        if ($number < 100) {
            $tens = (int) floor($number / 10);
            $rest = $number % 10;
            if ($rest === 0) {
                return trim($this->terbilang($tens) . ' puluh');
            }
            return trim($this->terbilang($tens) . ' puluh ' . $this->terbilang($rest));
        }

        if ($number < 200) {
            return trim('seratus ' . $this->terbilang($number - 100));
        }

        if ($number < 1000) {
            $hundreds = (int) floor($number / 100);
            $rest = $number % 100;
            if ($rest === 0) {
                return trim($this->terbilang($hundreds) . ' ratus');
            }
            return trim($this->terbilang($hundreds) . ' ratus ' . $this->terbilang($rest));
        }

        if ($number < 2000) {
            return trim('seribu ' . $this->terbilang($number - 1000));
        }

        if ($number < 1000000) {
            $thousands = (int) floor($number / 1000);
            $rest = $number % 1000;
            if ($rest === 0) {
                return trim($this->terbilang($thousands) . ' ribu');
            }
            return trim($this->terbilang($thousands) . ' ribu ' . $this->terbilang($rest));
        }

        if ($number < 1000000000) {
            $millions = (int) floor($number / 1000000);
            $rest = $number % 1000000;
            if ($rest === 0) {
                return trim($this->terbilang($millions) . ' juta');
            }
            return trim($this->terbilang($millions) . ' juta ' . $this->terbilang($rest));
        }

        if ($number < 1000000000000) {
            $billions = (int) floor($number / 1000000000);
            $rest = $number % 1000000000;
            if ($rest === 0) {
                return trim($this->terbilang($billions) . ' milyar');
            }
            return trim($this->terbilang($billions) . ' milyar ' . $this->terbilang($rest));
        }

        $trillions = (int) floor($number / 1000000000000);
        $rest = $number % 1000000000000;
        if ($rest === 0) {
            return trim($this->terbilang($trillions) . ' triliun');
        }
        return trim($this->terbilang($trillions) . ' triliun ' . $this->terbilang($rest));
    }
}
