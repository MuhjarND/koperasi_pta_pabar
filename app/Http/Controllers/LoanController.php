<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\FonnteService;

class LoanController extends Controller
{
    public function memberIndex(Request $request)
    {
        $userId = $request->session()->get('auth.id');

        $loans = DB::table('loans')
            ->select('id', 'amount', 'term_months', 'status', 'created_at', 'pdf_path', 'transfer_evidence_path')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        $loanSummary = [
            'total' => $loans->count(),
            'approved' => $loans->where('status', 'approved_chairman')->count(),
            'pending' => $loans->whereIn('status', ['submitted', 'reviewed', 'approved_treasurer'])->count(),
            'rejected' => $loans->where('status', 'rejected')->count(),
            'total_amount' => (float) $loans->sum('amount'),
            'approved_amount' => (float) $loans->where('status', 'approved_chairman')->sum('amount'),
        ];

        return view('loans.member.index', [
            'loans' => $loans,
            'statusLabels' => config('koperasi.status_labels'),
            'loanSummary' => $loanSummary,
        ]);
    }

    public function memberDocument($id, Request $request)
    {
        $userId = $request->session()->get('auth.id');
        $loan = DB::table('loans')
            ->select('id', 'amount', 'term_months', 'status', 'created_at', 'pdf_path', 'transfer_evidence_path')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$loan) {
            return redirect()
                ->route('anggota.loans.index')
                ->withErrors(['error' => 'Dokumen pinjaman tidak ditemukan.']);
        }
        if ($loan->status !== 'approved_chairman' || empty($loan->transfer_evidence_path)) {
            return redirect()
                ->route('anggota.loans.index')
                ->withErrors(['error' => 'Dokumen pencairan belum tersedia.']);
        }

        return view('loans.member.document', [
            'loan' => $loan,
            'statusLabels' => config('koperasi.status_labels'),
        ]);
    }

    public function memberCreate(Request $request)
    {
        $userId = $request->session()->get('auth.id');
        $member = DB::table('users')
            ->select('name', 'member_no', 'nip', 'unit_kerja', 'phone')
            ->where('id', $userId)
            ->first();

        $profileComplete = $member
            && !empty($member->member_no)
            && !empty($member->name)
            && !empty($member->nip)
            && !empty($member->unit_kerja)
            && !empty($member->phone);

        return view('loans.member.create', [
            'memberProfile' => $member,
            'profileComplete' => $profileComplete,
        ]);
    }

    public function memberStore(Request $request)
    {
        $payload = $request->validate([
            'amount' => 'required|numeric|min:1000',
            'term_months' => 'required|integer|min:1|max:60',
            'purpose' => 'required|string|max:500',
            'selfie_data' => 'required|string',
        ]);

        $member = DB::table('users')
            ->select('name', 'member_no', 'nip', 'unit_kerja', 'phone')
            ->where('id', $request->session()->get('auth.id'))
            ->first();

        if (!$member || empty($member->name)) {
            return back()
                ->withErrors(['profile' => 'Profil pengguna tidak ditemukan. Hubungi admin untuk memperbarui data akun.'])
                ->withInput();
        }

        $applicantProfile = $this->normalizeApplicantProfile(
            $request->session()->get('auth.id'),
            $member
        );

        $selfiePath = null;
        $selfieData = $payload['selfie_data'] ?? '';
        if (!preg_match('/^data:image\\/(png|jpeg);base64,/', $selfieData, $matches)) {
            return back()
                ->withErrors(['selfie_data' => 'Selfie tidak valid.'])
                ->withInput();
        }

        $decoded = base64_decode(substr($selfieData, strlen($matches[0])), true);
        if ($decoded === false) {
            return back()
                ->withErrors(['selfie_data' => 'Selfie tidak valid.'])
                ->withInput();
        }

        $maxBytes = 2 * 1024 * 1024;
        if (strlen($decoded) > $maxBytes) {
            return back()
                ->withErrors(['selfie_data' => 'Ukuran selfie terlalu besar.'])
                ->withInput();
        }

        $folder = 'uploads/selfies';
        $publicFolder = public_path($folder);

        if (!is_dir($publicFolder)) {
            mkdir($publicFolder, 0755, true);
        }

        $extension = $matches[1] === 'jpeg' ? 'jpg' : 'png';
        $filename = uniqid('selfie_', true) . '.' . $extension;
        file_put_contents($publicFolder . DIRECTORY_SEPARATOR . $filename, $decoded);
        $selfiePath = $folder . '/' . $filename;

        $loanId = DB::table('loans')->insertGetId([
            'user_id' => $request->session()->get('auth.id'),
            'member_no' => $applicantProfile['member_no'],
            'applicant_name' => $applicantProfile['name'],
            'nip' => $applicantProfile['nip'],
            'unit_kerja' => $applicantProfile['unit_kerja'],
            'phone' => $applicantProfile['phone'],
            'amount' => $payload['amount'],
            'term_months' => $payload['term_months'],
            'purpose' => $payload['purpose'],
            'selfie_path' => $selfiePath,
            'status' => 'submitted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->generateLoanPdf($loanId);

        $notifier = new FonnteService();
        $amountLabel = number_format((float) $payload['amount'], 2, ',', '.');
        $memberLabel = ($member->name ?? 'Anggota') . ($member->member_no ? ' (' . $member->member_no . ')' : '');
        $sekretarisLink = route('sekretaris.loans.show', $loanId);
        $notifier->notifyRole(
            'sekretaris',
            $notifier->formatMessage([
                "📌 Pengajuan pinjaman baru.",
                "Nama: {$member->name}",
                "No. Anggota: " . ($member->member_no ?? '-'),
                "Nominal: Rp {$amountLabel}",
                "Tujuan: {$payload['purpose']}",
                "Tindak lanjut: {$sekretarisLink}",
            ], '🤝')
        );

        return redirect()
            ->route('anggota.loans.index')
            ->with('success', 'Pengajuan pinjaman berhasil dikirim.');
    }

    private function generateLoanPdf($loanId)
    {
        if (!$loanId) {
            return;
        }

        $loanRow = DB::table('loans')
            ->leftJoin('users as sekretaris', 'loans.sekretaris_id', '=', 'sekretaris.id')
            ->leftJoin('users as bendahara', 'loans.bendahara_id', '=', 'bendahara.id')
            ->leftJoin('users as ketua', 'loans.ketua_id', '=', 'ketua.id')
            ->select(
                'loans.*',
                'sekretaris.name as sekretaris_name',
                'bendahara.name as bendahara_name',
                'ketua.name as ketua_name'
            )
            ->where('loans.id', $loanId)
            ->first();

        if (!$loanRow) {
            return;
        }

        $token = $loanRow->pdf_token ?? null;
        if (!$token) {
            $token = Str::random(48);
            DB::table('loans')->where('id', $loanId)->update([
                'pdf_token' => $token,
                'updated_at' => now(),
            ]);
        }

        $kopPath = public_path('kop_koperasi.png');
        $kopImage = null;
        if (is_file($kopPath)) {
            $kopImage = 'data:image/png;base64,' . base64_encode(file_get_contents($kopPath));
        }

        $serviceRate = (float) config('koperasi.service_fee_rate', 0);
        $amount = (float) $loanRow->amount;
        $termMonths = max((int) $loanRow->term_months, 1);
        $feePerMonth = $amount * $serviceRate;
        $principalPerMonth = $amount / $termMonths;
        $installmentTotal = $principalPerMonth + $feePerMonth;

        $date = now()->locale('id')->translatedFormat('d F Y');
        $city = 'Manokwari';

        $basePayload = [
            'loan_id' => $loanRow->id,
            'token' => $token,
        ];
        $qrPemohon = $this->buildQr($basePayload + ['role' => 'pemohon'], $token, 'pemohon');
        $qrSekretaris = $loanRow->sekretaris_name ? $this->buildQr($basePayload + ['role' => 'sekretaris'], $token, 'sekretaris') : null;
        $qrBendahara = $loanRow->bendahara_name ? $this->buildQr($basePayload + ['role' => 'bendahara'], $token, 'bendahara') : null;
        $qrKetua = $loanRow->ketua_name ? $this->buildQr($basePayload + ['role' => 'ketua'], $token, 'ketua') : null;

        $data = [
            'kopImage' => $kopImage,
            'memberNo' => $loanRow->member_no ?? '-',
            'memberName' => $loanRow->applicant_name ?? '-',
            'nip' => $loanRow->nip ?? '-',
            'unitKerja' => $loanRow->unit_kerja ?? '-',
            'phone' => $loanRow->phone ?? '-',
            'amount' => $amount,
            'termMonths' => $termMonths,
            'purpose' => $loanRow->purpose ?? '-',
            'serviceRate' => $serviceRate * 100,
            'feePerMonth' => $feePerMonth,
            'installmentTotal' => $installmentTotal,
            'date' => $date,
            'city' => $city,
            'qrPemohon' => $qrPemohon,
            'qrSekretaris' => $qrSekretaris,
            'qrBendahara' => $qrBendahara,
            'qrKetua' => $qrKetua,
            'bendaharaName' => $loanRow->bendahara_name ?? null,
            'sekretarisName' => $loanRow->sekretaris_name ?? null,
            'ketuaName' => $loanRow->ketua_name ?? null,
        ];

        try {
            $pdf = PDF::loadView('loans.pdf', $data)->setPaper('a4', 'portrait');
            $folder = 'uploads/loan-forms';
            $publicFolder = public_path($folder);
            if (!is_dir($publicFolder)) {
                mkdir($publicFolder, 0755, true);
            }
            $fileName = 'loan_' . $loanId . '.pdf';
            $pdfPath = $folder . '/' . $fileName;
            $pdf->save(public_path($pdfPath));

            DB::table('loans')
                ->where('id', $loanId)
                ->update([
                    'pdf_path' => $pdfPath,
                    'updated_at' => now(),
                ]);
        } catch (\Exception $e) {
            // Jika PDF gagal dibuat, pengajuan tetap tersimpan.
        }
    }

    private function buildQr(array $payload, string $token, string $role)
    {
        try {
            $payload['ts'] = now()->timestamp;
            $encodedPayload = base64_encode(json_encode($payload));
            $verifyUrl = route('qr.verify') . '?payload=' . urlencode($encodedPayload);

            $folder = 'qr';
            $publicFolder = public_path($folder);
            if (!is_dir($publicFolder)) {
                mkdir($publicFolder, 0755, true);
            }

            $fileName = 'qr_' . $token . '_' . $role . '.png';
            $filePath = $publicFolder . DIRECTORY_SEPARATOR . $fileName;
            $fileUrl = $filePath;

            if (is_file($filePath)) {
                return 'data:image/png;base64,' . base64_encode(file_get_contents($filePath));
            }

            $qrBinary = $this->downloadQrImage($verifyUrl);
            if (!$qrBinary) {
                return null;
            }

            file_put_contents($filePath, $qrBinary);

            $logoPath = public_path('logo_koperasi.png');
            if (is_file($logoPath) && function_exists('imagecreatefromstring')) {
                $qrImage = imagecreatefromstring($qrBinary);
                $logoImage = imagecreatefromstring(file_get_contents($logoPath));
                if ($qrImage && $logoImage) {
                    $qrWidth = imagesx($qrImage);
                    $qrHeight = imagesy($qrImage);
                    $logoSize = (int) ($qrWidth * 0.22);
                    $logoX = (int) (($qrWidth - $logoSize) / 2);
                    $logoY = (int) (($qrHeight - $logoSize) / 2);
                    imagealphablending($qrImage, true);
                    imagesavealpha($qrImage, true);
                    imagecopyresampled(
                        $qrImage,
                        $logoImage,
                        $logoX,
                        $logoY,
                        0,
                        0,
                        $logoSize,
                        $logoSize,
                        imagesx($logoImage),
                        imagesy($logoImage)
                    );
                    imagepng($qrImage, $filePath);
                    imagedestroy($qrImage);
                    imagedestroy($logoImage);
                }
            }

            return 'data:image/png;base64,' . base64_encode(file_get_contents($filePath));
        } catch (\Exception $e) {
            return null;
        }
    }

    private function downloadQrImage(string $url)
    {
        $google = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($url);
        $fallback = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($url);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 6,
            ],
        ]);

        $data = @file_get_contents($google, false, $context);
        if ($data === false || strlen($data) < 100) {
            $data = @file_get_contents($fallback, false, $context);
        }

        if (($data === false || strlen($data) < 100) && function_exists('curl_init')) {
            $data = $this->curlFetch($google);
            if ($data === false || strlen($data) < 100) {
                $data = $this->curlFetch($fallback);
            }
        }

        return ($data && strlen($data) > 100) ? $data : null;
    }

    private function curlFetch(string $url)
    {
        $ch = curl_init($url);
        if (!$ch) {
            return false;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public function verifyForm(Request $request, $token = null)
    {
        $payloadToken = null;
        $payloadRole = null;
        $payloadTimestamp = null;
        $payload = $request->query('payload');
        if ($payload) {
            $decoded = base64_decode($payload, true);
            if ($decoded) {
                $json = json_decode($decoded, true);
                if (is_array($json)) {
                    $payloadToken = $json['token'] ?? null;
                    $payloadRole = $json['role'] ?? null;
                    $payloadTimestamp = $json['ts'] ?? null;
                }
            }
        }

        $lookupToken = $payloadToken ?: $token;

        $loan = DB::table('loans')
            ->join('users', 'loans.user_id', '=', 'users.id')
            ->select(
                'loans.id',
                'loans.member_no',
                'loans.applicant_name',
                'loans.created_at',
                'users.name as user_name'
            )
            ->where('loans.pdf_token', $lookupToken)
            ->first();

        $roleLabel = null;
        $role = $payloadRole ?: $request->query('role');
        $roleMap = [
            'sekretaris' => 'Sekretaris',
            'bendahara' => 'Bendahara',
            'ketua' => 'Ketua',
            'pemohon' => 'Pemohon',
        ];
        if ($role && isset($roleMap[$role])) {
            $roleLabel = $roleMap[$role];
        }

        $signedAt = null;
        if ($payloadTimestamp) {
            try {
                $signedAt = Carbon::createFromTimestamp((int) $payloadTimestamp)
                    ->locale('id')
                    ->translatedFormat('d F Y, H:i');
            } catch (\Exception $e) {
                $signedAt = null;
            }
        }

        return view('loans.verify', [
            'loan' => $loan,
            'roleLabel' => $roleLabel,
            'signedAt' => $signedAt,
        ]);
    }

    public function sekretarisIndex()
    {
        $baseQuery = $this->approvalBaseQuery();
        $loans = (clone $baseQuery)
            ->where('loans.status', 'submitted')
            ->orderByDesc('loans.created_at')
            ->get();
        $historyLoans = (clone $baseQuery)
            ->orderByDesc('loans.updated_at')
            ->limit(10)
            ->get();

        return view('loans.sekretaris.index', [
            'loans' => $loans,
            'historyLoans' => $historyLoans,
        ]);
    }

    public function sekretarisShow($id)
    {
        $loan = $this->loanDetail($id);

        if (!$loan) {
            return redirect()->route('sekretaris.loans.index');
        }

        return view('loans.sekretaris.show', [
            'loan' => $loan,
            'statusLabels' => config('koperasi.status_labels'),
        ]);
    }

    public function sekretarisReview(Request $request, $id)
    {
        $decision = $request->validate([
            'decision' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:500',
        ]);

        $loan = DB::table('loans')->where('id', $id)->first();

        if (!$loan || $loan->status !== 'submitted') {
            return redirect()->route('sekretaris.loans.index');
        }

        $updates = [
            'sekretaris_id' => $request->session()->get('auth.id'),
            'sekretaris_note' => $decision['note'],
            'updated_at' => now(),
        ];

        if ($decision['decision'] === 'approve') {
            $updates['status'] = 'approved_treasurer';
            $updates['reviewed_at'] = now();
        } else {
            $updates['status'] = 'rejected';
            $updates['rejected_at'] = now();
        }

        DB::table('loans')->where('id', $id)->update($updates);

        $amountLabel = number_format((float) $loan->amount, 2, ',', '.');
        $member = DB::table('users')->select('name', 'member_no')->where('id', $loan->user_id)->first();
        if ($decision['decision'] === 'approve') {
            $this->generateLoanPdf($id);
            $memberLabel = ($member->name ?? 'Anggota') . ($member->member_no ? ' (' . $member->member_no . ')' : '');
            $ketuaLink = route('ketua.loans.show', $id);
            $notifier = new FonnteService();
            $notifier->notifyRole(
                'ketua',
                $notifier->formatMessage([
                    "✅ Pengajuan pinjaman telah direview sekretaris.",
                    "Nama: " . ($member->name ?? 'Anggota'),
                    "No. Anggota: " . ($member->member_no ?? '-'),
                    "Nominal: Rp {$amountLabel}",
                    "Tujuan: " . ($loan->purpose ?? '-'),
                    "Tindak lanjut: {$ketuaLink}",
                ], '🤝')
            );
        } else {
            $memberLink = route('anggota.loans.index');
            $notifier = new FonnteService();
            $notifier->notifyUser(
                $loan->user_id,
                $notifier->formatMessage([
                    "❌ Pengajuan pinjaman Anda ditolak oleh sekretaris.",
                    "Nominal: Rp {$amountLabel}",
                    "Tujuan: " . ($loan->purpose ?? '-'),
                    "Detail: {$memberLink}",
                ], '🙏', $member->name ?? null)
            );
        }

        return redirect()
            ->route('sekretaris.loans.index')
            ->with('success', 'Keputusan berhasil disimpan.');
    }

    public function bendaharaIndex()
    {
        $baseQuery = $this->approvalBaseQuery();
        $loans = (clone $baseQuery)
            ->where('loans.status', 'reviewed')
            ->orderByDesc('loans.created_at')
            ->get();
        $historyLoans = (clone $baseQuery)
            ->orderByDesc('loans.updated_at')
            ->limit(10)
            ->get();

        return view('loans.bendahara.index', [
            'loans' => $loans,
            'historyLoans' => $historyLoans,
        ]);
    }

    public function bendaharaDisbursementIndex()
    {
        $loans = DB::table('loans')
            ->join('users', 'loans.user_id', '=', 'users.id')
            ->select(
                'loans.id',
                'users.name',
                'users.member_no',
                'loans.amount',
                'loans.term_months',
                'loans.chairman_approved_at',
                'loans.pdf_path'
            )
            ->where('loans.status', 'approved_chairman')
            ->whereNull('loans.transfered_at')
            ->whereNull('loans.transfer_evidence_path')
            ->orderByDesc('loans.chairman_approved_at')
            ->get();

        return view('loans.bendahara.disbursement', [
            'loans' => $loans,
        ]);
    }

    public function bendaharaMonitoring(Request $request)
    {
        $statusOptions = [
            'all' => 'Semua Status',
            'submitted' => 'Menunggu Sekretaris',
            'approved_treasurer' => 'Menunggu Ketua',
            'reviewed' => 'Menunggu Bendahara',
            'pending_disbursement' => 'Menunggu Pencairan',
            'disbursed' => 'Dicairkan',
            'rejected' => 'Ditolak',
        ];

        $selectedStatus = $request->query('status', 'all');
        if (!array_key_exists($selectedStatus, $statusOptions)) {
            $selectedStatus = 'all';
        }

        $availableYears = DB::table('loans')
            ->selectRaw('YEAR(created_at) as year')
            ->whereNotNull('created_at')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->values();

        $selectedYear = $request->query('year');
        $selectedYear = is_numeric($selectedYear) ? (int) $selectedYear : null;

        $baseQuery = DB::table('loans')
            ->join('users', 'loans.user_id', '=', 'users.id')
            ->leftJoin('users as sekretaris', 'loans.sekretaris_id', '=', 'sekretaris.id')
            ->leftJoin('users as bendahara', 'loans.bendahara_id', '=', 'bendahara.id')
            ->leftJoin('users as ketua', 'loans.ketua_id', '=', 'ketua.id')
            ->leftJoin('users as pencair', 'loans.transfered_by', '=', 'pencair.id')
            ->select(
                'loans.id',
                'loans.amount',
                'loans.term_months',
                'loans.purpose',
                'loans.status',
                'loans.created_at',
                'loans.reviewed_at',
                'loans.chairman_approved_at',
                'loans.treasurer_approved_at',
                'loans.transfered_at',
                'loans.rejected_at',
                'loans.pdf_path',
                'loans.transfer_evidence_path',
                'users.name',
                'users.member_no',
                'sekretaris.name as sekretaris_name',
                'bendahara.name as bendahara_name',
                'ketua.name as ketua_name',
                'pencair.name as transfered_by_name'
            );

        if ($selectedYear) {
            $baseQuery->whereYear('loans.created_at', $selectedYear);
        }

        $this->applyLoanMonitoringStatusFilter($baseQuery, $selectedStatus);

        $statusLabels = config('koperasi.status_labels');
        $loans = $baseQuery
            ->orderByDesc('loans.created_at')
            ->get()
            ->map(function ($loan) use ($statusLabels) {
                $loan->process_steps = $this->buildLoanMonitoringSteps($loan);
                $loan->current_label = $this->loanMonitoringStatusLabel($loan, $statusLabels);
                $loan->current_badge_class = $this->loanMonitoringBadgeClass($loan);
                $loan->progress_percent = $this->loanMonitoringProgressPercent($loan->process_steps);

                return $loan;
            });

        $summaryQuery = DB::table('loans');
        if ($selectedYear) {
            $summaryQuery->whereYear('created_at', $selectedYear);
        }

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'submitted' => (clone $summaryQuery)->where('status', 'submitted')->count(),
            'chairman_pending' => (clone $summaryQuery)->where('status', 'approved_treasurer')->count(),
            'treasurer_pending' => (clone $summaryQuery)->where('status', 'reviewed')->count(),
            'pending_disbursement' => (clone $summaryQuery)
                ->where('status', 'approved_chairman')
                ->whereNull('transfered_at')
                ->whereNull('transfer_evidence_path')
                ->count(),
            'disbursed' => (clone $summaryQuery)
                ->where('status', 'approved_chairman')
                ->where(function ($query) {
                    $query->whereNotNull('transfered_at')
                        ->orWhereNotNull('transfer_evidence_path');
                })
                ->count(),
            'rejected' => (clone $summaryQuery)->where('status', 'rejected')->count(),
            'total_amount' => (float) (clone $summaryQuery)->sum('amount'),
            'disbursed_amount' => (float) (clone $summaryQuery)
                ->where('status', 'approved_chairman')
                ->where(function ($query) {
                    $query->whereNotNull('transfered_at')
                        ->orWhereNotNull('transfer_evidence_path');
                })
                ->sum('amount'),
        ];

        return view('loans.bendahara.monitoring', [
            'loans' => $loans,
            'summary' => $summary,
            'statusOptions' => $statusOptions,
            'availableYears' => $availableYears,
            'filters' => [
                'status' => $selectedStatus,
                'year' => $selectedYear,
            ],
        ]);
    }

    public function bendaharaDisbursementStore(Request $request, $id)
    {
        $payload = $request->validate([
            'transfer_evidence' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $loan = DB::table('loans')->where('id', $id)->first();
        if (!$loan || $loan->status !== 'approved_chairman') {
            return redirect()->route('bendahara.loans.disbursement');
        }

        if (!empty($loan->transfer_evidence_path)) {
            return redirect()
                ->route('bendahara.loans.disbursement')
                ->withErrors(['error' => 'Bukti transfer sudah diunggah.']);
        }

        $folder = 'uploads/loan-transfers';
        $publicFolder = public_path($folder);
        if (!is_dir($publicFolder)) {
            mkdir($publicFolder, 0755, true);
        }

        $file = $request->file('transfer_evidence');
        $filename = 'transfer_' . $loan->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $file->move($publicFolder, $filename);
        $transferPath = $folder . '/' . $filename;

        DB::table('loans')
            ->where('id', $loan->id)
            ->update([
                'transfer_evidence_path' => $transferPath,
                'transfered_at' => now(),
                'transfered_by' => $request->session()->get('auth.id'),
                'updated_at' => now(),
            ]);

        $memberName = DB::table('users')
            ->where('id', $loan->user_id)
            ->value('name');

        DB::table('cash_entries')->insert([
            'entry_date' => now()->toDateString(),
            'direction' => 'out',
            'description' => 'Peminjaman (' . ($memberName ?? 'Anggota') . ')',
            'amount' => $loan->amount,
            'category' => 'peminjaman',
            'evidence_path' => $transferPath,
            'status' => 'approved',
            'created_by' => $request->session()->get('auth.id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $detailLink = route('anggota.loans.document', $loan->id);
        $notifier = new FonnteService();
        $notifier->notifyUser(
            $loan->user_id,
            $notifier->formatMessage([
                "✅ Pengajuan pinjaman Anda telah dicairkan.",
                "Nominal: Rp " . number_format((float) $loan->amount, 2, ',', '.'),
                "Tujuan: " . ($loan->purpose ?? '-'),
                "Detail dokumen: {$detailLink}",
            ], '🎉', $memberName ?? null)
        );

        return redirect()
            ->route('bendahara.loans.disbursement')
            ->with('success', 'Bukti transfer berhasil diunggah.');
    }

    public function bendaharaMembers(Request $request)
    {
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

        $selectedMonth = $request->query('month');
        $selectedYear = $request->query('year');
        $selectedSettlement = $request->query('settlement');
        $selectedApproval = $request->query('approval');

        $loanQuery = DB::table('loans')
            ->join('users', 'loans.user_id', '=', 'users.id')
            ->select(
                'loans.id',
                'loans.user_id',
                'loans.amount',
                'loans.term_months',
                'loans.status',
                'loans.created_at',
                'users.name',
                'users.member_no'
            )
            ->orderBy('users.name')
            ->orderByDesc('loans.created_at');

        if ($selectedYear) {
            $loanQuery->whereYear('loans.created_at', $selectedYear);
        }

        if ($selectedMonth) {
            $loanQuery->whereMonth('loans.created_at', $selectedMonth);
        }

        $loans = $loanQuery->get();

        $serviceRate = (float) config('koperasi.service_fee_rate', 0);
        $members = [];
        $loanIds = $loans->pluck('id')->all();
        $paymentMap = [];

        if ($loanIds) {
            $paymentRows = DB::table('loan_installment_payments')
                ->whereIn('loan_id', $loanIds)
                ->where('status', 'approved')
                ->where('installment_no', '>', 0)
                ->get();

            foreach ($paymentRows as $payment) {
                $paymentMap[$payment->loan_id][$payment->installment_no] = $payment;
            }
        }

        foreach ($loans as $loan) {
            if (!isset($members[$loan->user_id])) {
                $members[$loan->user_id] = [
                    'id' => $loan->user_id,
                    'name' => $loan->name,
                    'member_no' => $loan->member_no,
                    'loans' => [],
                    'latest_loan_id' => null,
                    'latest_status' => null,
                    'latest_created_at' => null,
                    'settlement_label' => 'Belum Lunas',
                    'all_settled' => true,
                    'settled_loans' => 0,
                    'unsettled_loans' => 0,
                    'settled_installments' => 0,
                    'unsettled_installments' => 0,
                    'total_installments' => 0,
                ];
            }

            $termMonths = max((int) $loan->term_months, 1);
            $principalPerMonth = $loan->amount / $termMonths;
            $feePerMonth = $loan->amount * $serviceRate;

            $startDate = Carbon::parse($loan->created_at)->startOfMonth();
            $installments = [];
            $paidCount = 0;
            for ($i = 0; $i < $termMonths; $i++) {
                $dueDate = $startDate->copy()->addMonthsNoOverflow($i);
                $monthLabel = $monthNames[(int) $dueDate->format('n')] ?? $dueDate->format('F');
                $payment = $paymentMap[$loan->id][$i + 1] ?? null;
                $paidLabel = $payment ? 'Lunas' : 'Belum Lunas';
                if ($payment) {
                    $paidCount++;
                }

                $installments[] = [
                    'month' => $monthLabel,
                    'date' => $dueDate->format('d/m/Y'),
                    'principal' => $payment ? $payment->amount_principal : $principalPerMonth,
                    'fee' => $payment ? $payment->amount_fee : $feePerMonth,
                    'status' => $paidLabel,
                ];
            }

            $isSettled = $paidCount === $termMonths && $termMonths > 0;
            $settlementLabel = $isSettled ? 'Lunas' : 'Belum Lunas';

            $members[$loan->user_id]['loans'][] = [
                'id' => $loan->id,
                'amount' => $loan->amount,
                'term_months' => $termMonths,
                'status' => $loan->status,
                'created_at' => $loan->created_at,
                'installments' => $installments,
                'principal_total' => $principalPerMonth * $termMonths,
                'fee_total' => $feePerMonth * $termMonths,
                'settlement_label' => $settlementLabel,
            ];

            $members[$loan->user_id]['all_settled'] = $members[$loan->user_id]['all_settled'] && $isSettled;
            if ($isSettled) {
                $members[$loan->user_id]['settled_loans']++;
            } else {
                $members[$loan->user_id]['unsettled_loans']++;
            }
            $members[$loan->user_id]['settled_installments'] += $paidCount;
            $members[$loan->user_id]['unsettled_installments'] += max($termMonths - $paidCount, 0);
            $members[$loan->user_id]['total_installments'] += $termMonths;

            if (
                !$members[$loan->user_id]['latest_created_at']
                || $loan->created_at > $members[$loan->user_id]['latest_created_at']
            ) {
                $members[$loan->user_id]['latest_created_at'] = $loan->created_at;
                $members[$loan->user_id]['latest_status'] = $loan->status;
                $members[$loan->user_id]['latest_loan_id'] = $loan->id;
            }
        }

        foreach ($members as $id => $member) {
            $members[$id]['settlement_label'] = $member['all_settled'] && count($member['loans']) > 0
                ? 'Lunas'
                : 'Belum Lunas';
        }

        $members = array_values($members);

        if ($selectedSettlement) {
            $target = $selectedSettlement === 'lunas' ? 'Lunas' : 'Belum Lunas';
            $members = array_values(array_filter($members, function ($member) use ($target) {
                return ($member['settlement_label'] ?? '') === $target;
            }));
        }

        if ($selectedApproval) {
            $members = array_values(array_filter($members, function ($member) use ($selectedApproval) {
                return ($member['latest_status'] ?? '') === $selectedApproval;
            }));
        }

        $availableYears = DB::table('loans')
            ->selectRaw('distinct year(created_at) as year')
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->values()
            ->all();

        if (!$availableYears) {
            $availableYears = [now()->year];
        }

        return view('loans.bendahara.members', [
            'members' => $members,
            'statusLabels' => config('koperasi.status_labels'),
            'statusBadges' => config('koperasi.status_badges'),
            'monthNames' => $monthNames,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'selectedSettlement' => $selectedSettlement,
            'selectedApproval' => $selectedApproval,
            'availableYears' => $availableYears,
        ]);
    }

    public function bendaharaShow($id)
    {
        $loan = $this->loanDetail($id);

        if (!$loan) {
            return redirect()->route('bendahara.loans.index');
        }

        return view('loans.bendahara.show', [
            'loan' => $loan,
            'statusLabels' => config('koperasi.status_labels'),
        ]);
    }

    public function bendaharaApprove(Request $request, $id)
    {
        $decision = $request->validate([
            'decision' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:500',
        ]);

        $loan = DB::table('loans')->where('id', $id)->first();

        if (!$loan || $loan->status !== 'reviewed') {
            return redirect()->route('bendahara.loans.index');
        }

        $updates = [
            'bendahara_id' => $request->session()->get('auth.id'),
            'bendahara_note' => $decision['note'],
            'updated_at' => now(),
        ];

        if ($decision['decision'] === 'approve') {
            $updates['status'] = 'approved_chairman';
            $updates['treasurer_approved_at'] = now();
        } else {
            $updates['status'] = 'rejected';
            $updates['rejected_at'] = now();
        }

        DB::table('loans')->where('id', $id)->update($updates);

        $amountLabel = number_format((float) $loan->amount, 2, ',', '.');
        $member = DB::table('users')->select('name', 'member_no')->where('id', $loan->user_id)->first();
        if ($decision['decision'] === 'approve') {
            $this->generateLoanPdf($id);
            $memberLabel = ($member->name ?? 'Anggota') . ($member->member_no ? ' (' . $member->member_no . ')' : '');
            $disbursementLink = route('bendahara.loans.disbursement');
            $notifier = new FonnteService();
            $notifier->notifyRole(
                'bendahara',
                $notifier->formatMessage([
                    "✅ Pengajuan pinjaman telah disetujui bendahara.",
                    "Nama: " . ($member->name ?? 'Anggota'),
                    "No. Anggota: " . ($member->member_no ?? '-'),
                    "Nominal: Rp {$amountLabel}",
                    "Tujuan: " . ($loan->purpose ?? '-'),
                    "Tindak lanjut pencairan: {$disbursementLink}",
                ], '🤝')
            );
        } else {
            $memberLink = route('anggota.loans.index');
            $notifier = new FonnteService();
            $notifier->notifyUser(
                $loan->user_id,
                $notifier->formatMessage([
                    "❌ Pengajuan pinjaman Anda ditolak oleh bendahara.",
                    "Nominal: Rp {$amountLabel}",
                    "Tujuan: " . ($loan->purpose ?? '-'),
                    "Detail: {$memberLink}",
                ], '🙏', $member->name ?? null)
            );
        }

        return redirect()
            ->route('bendahara.loans.index')
            ->with('success', 'Keputusan berhasil disimpan.');
    }

    public function ketuaIndex()
    {
        $baseQuery = $this->approvalBaseQuery();
        $loans = (clone $baseQuery)
            ->where('loans.status', 'approved_treasurer')
            ->orderByDesc('loans.created_at')
            ->get();
        $historyLoans = (clone $baseQuery)
            ->orderByDesc('loans.updated_at')
            ->limit(10)
            ->get();

        return view('loans.ketua.index', [
            'loans' => $loans,
            'historyLoans' => $historyLoans,
        ]);
    }

    public function ketuaShow($id)
    {
        $loan = $this->loanDetail($id);

        if (!$loan) {
            return redirect()->route('ketua.loans.index');
        }

        return view('loans.ketua.show', [
            'loan' => $loan,
            'statusLabels' => config('koperasi.status_labels'),
        ]);
    }

    public function ketuaApprove(Request $request, $id)
    {
        $decision = $request->validate([
            'decision' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:500',
        ]);

        $loan = DB::table('loans')->where('id', $id)->first();

        if (!$loan || $loan->status !== 'approved_treasurer') {
            return redirect()->route('ketua.loans.index');
        }

        $updates = [
            'ketua_id' => $request->session()->get('auth.id'),
            'ketua_note' => $decision['note'],
            'updated_at' => now(),
        ];

        if ($decision['decision'] === 'approve') {
            $updates['status'] = 'reviewed';
            $updates['chairman_approved_at'] = now();
        } else {
            $updates['status'] = 'rejected';
            $updates['rejected_at'] = now();
        }

        DB::table('loans')->where('id', $id)->update($updates);
        $member = DB::table('users')->select('name', 'member_no')->where('id', $loan->user_id)->first();

        if ($decision['decision'] === 'approve') {
            $this->generateLoanPdf($id);
        }

        if ($decision['decision'] === 'approve') {
            $notifier = new FonnteService();
            $bendaharaLink = route('bendahara.loans.show', $id);
            $notifier->notifyRole(
                'bendahara',
                $notifier->formatMessage([
                    "✅ Pengajuan pinjaman disetujui ketua dan menunggu persetujuan bendahara.",
                    "Nama: " . ($member->name ?? 'Anggota'),
                    "No. Anggota: " . ($member->member_no ?? '-'),
                    "Nominal: Rp " . number_format((float) $loan->amount, 2, ',', '.'),
                    "Tujuan: " . ($loan->purpose ?? '-'),
                    "Tindak lanjut: {$bendaharaLink}",
                ], '🤝')
            );
        } else {
            $memberLink = route('anggota.loans.index');
            $notifier = new FonnteService();
            $notifier->notifyUser(
                $loan->user_id,
                $notifier->formatMessage([
                    "❌ Pengajuan pinjaman Anda ditolak oleh ketua.",
                    "Nominal: Rp " . number_format((float) $loan->amount, 2, ',', '.'),
                    "Tujuan: " . ($loan->purpose ?? '-'),
                    "Detail: {$memberLink}",
                ], '🙏', $member->name ?? null)
            );
        }

        return redirect()
            ->route('ketua.loans.index')
            ->with('success', 'Keputusan berhasil disimpan.');
    }

    private function loanDetail($id)
    {
        return DB::table('loans')
            ->join('users as anggota', 'loans.user_id', '=', 'anggota.id')
            ->leftJoin('users as sekretaris', 'loans.sekretaris_id', '=', 'sekretaris.id')
            ->leftJoin('users as bendahara', 'loans.bendahara_id', '=', 'bendahara.id')
            ->leftJoin('users as ketua', 'loans.ketua_id', '=', 'ketua.id')
            ->select(
                'loans.*',
                'anggota.name as anggota_name',
                'sekretaris.name as sekretaris_name',
                'bendahara.name as bendahara_name',
                'ketua.name as ketua_name'
            )
            ->where('loans.id', $id)
            ->first();
    }

    private function approvalBaseQuery()
    {
        return DB::table('loans')
            ->join('users', 'loans.user_id', '=', 'users.id')
            ->leftJoin('users as sekretaris', 'loans.sekretaris_id', '=', 'sekretaris.id')
            ->leftJoin('users as bendahara', 'loans.bendahara_id', '=', 'bendahara.id')
            ->leftJoin('users as ketua', 'loans.ketua_id', '=', 'ketua.id')
            ->select(
                'loans.id',
                'users.name',
                'loans.amount',
                'loans.term_months',
                'loans.created_at',
                'loans.status',
                'loans.reviewed_at',
                'loans.treasurer_approved_at',
                'loans.chairman_approved_at',
                'loans.rejected_at',
                'sekretaris.name as sekretaris_name',
                'bendahara.name as bendahara_name',
                'ketua.name as ketua_name'
            );
    }

    private function applyLoanMonitoringStatusFilter($query, string $status): void
    {
        if ($status === 'pending_disbursement') {
            $query->where('loans.status', 'approved_chairman')
                ->whereNull('loans.transfered_at')
                ->whereNull('loans.transfer_evidence_path');

            return;
        }

        if ($status === 'disbursed') {
            $query->where('loans.status', 'approved_chairman')
                ->where(function ($innerQuery) {
                    $innerQuery->whereNotNull('loans.transfered_at')
                        ->orWhereNotNull('loans.transfer_evidence_path');
                });

            return;
        }

        if ($status !== 'all') {
            $query->where('loans.status', $status);
        }
    }

    private function buildLoanMonitoringSteps($loan): array
    {
        $rejectedStage = null;
        if ($loan->status === 'rejected') {
            if (empty($loan->reviewed_at)) {
                $rejectedStage = 'secretary';
            } elseif (empty($loan->chairman_approved_at)) {
                $rejectedStage = 'chairman';
            } elseif (empty($loan->treasurer_approved_at)) {
                $rejectedStage = 'treasurer';
            } else {
                $rejectedStage = 'disbursement';
            }
        }

        return [
            [
                'key' => 'submitted',
                'label' => 'Pengajuan',
                'state' => 'done',
                'time' => $loan->created_at,
                'actor' => $loan->name,
            ],
            [
                'key' => 'secretary',
                'label' => 'Review Sekretaris',
                'state' => $this->loanMonitoringStepState($loan, 'secretary', $rejectedStage),
                'time' => $loan->reviewed_at ?: $loan->rejected_at,
                'actor' => $loan->sekretaris_name,
            ],
            [
                'key' => 'chairman',
                'label' => 'Persetujuan Ketua',
                'state' => $this->loanMonitoringStepState($loan, 'chairman', $rejectedStage),
                'time' => $loan->chairman_approved_at ?: ($rejectedStage === 'chairman' ? $loan->rejected_at : null),
                'actor' => $loan->ketua_name,
            ],
            [
                'key' => 'treasurer',
                'label' => 'Persetujuan Bendahara',
                'state' => $this->loanMonitoringStepState($loan, 'treasurer', $rejectedStage),
                'time' => $loan->treasurer_approved_at ?: ($rejectedStage === 'treasurer' ? $loan->rejected_at : null),
                'actor' => $loan->bendahara_name,
            ],
            [
                'key' => 'disbursement',
                'label' => 'Pencairan',
                'state' => $this->loanMonitoringStepState($loan, 'disbursement', $rejectedStage),
                'time' => $loan->transfered_at ?: ($rejectedStage === 'disbursement' ? $loan->rejected_at : null),
                'actor' => $loan->transfered_by_name,
            ],
        ];
    }

    private function loanMonitoringStepState($loan, string $stage, ?string $rejectedStage): string
    {
        if ($rejectedStage === $stage) {
            return 'failed';
        }

        if ($stage === 'secretary') {
            if (!empty($loan->reviewed_at)) {
                return 'done';
            }

            return $loan->status === 'submitted' ? 'active' : 'waiting';
        }

        if ($stage === 'chairman') {
            if (!empty($loan->chairman_approved_at)) {
                return 'done';
            }

            return $loan->status === 'approved_treasurer' ? 'active' : 'waiting';
        }

        if ($stage === 'treasurer') {
            if (!empty($loan->treasurer_approved_at)) {
                return 'done';
            }

            return $loan->status === 'reviewed' ? 'active' : 'waiting';
        }

        if ($stage === 'disbursement') {
            if (!empty($loan->transfered_at) || !empty($loan->transfer_evidence_path)) {
                return 'done';
            }

            return $loan->status === 'approved_chairman' ? 'active' : 'waiting';
        }

        return 'waiting';
    }

    private function loanMonitoringStatusLabel($loan, array $statusLabels): string
    {
        if ($loan->status === 'approved_chairman') {
            if (!empty($loan->transfered_at) || !empty($loan->transfer_evidence_path)) {
                return 'Dicairkan';
            }

            return 'Menunggu Pencairan';
        }

        return $statusLabels[$loan->status] ?? ucfirst((string) $loan->status);
    }

    private function loanMonitoringBadgeClass($loan): string
    {
        if ($loan->status === 'rejected') {
            return 'status-pill--danger';
        }

        if ($loan->status === 'approved_chairman'
            && (!empty($loan->transfered_at) || !empty($loan->transfer_evidence_path))) {
            return 'status-pill--success';
        }

        return 'status-pill--warning';
    }

    private function loanMonitoringProgressPercent(array $steps): int
    {
        if (empty($steps)) {
            return 0;
        }

        $finished = collect($steps)
            ->filter(fn ($step) => in_array($step['state'], ['done', 'failed']))
            ->count();

        return (int) round(($finished / count($steps)) * 100);
    }

    private function normalizeApplicantProfile(int $userId, $member): array
    {
        $memberNo = trim((string) ($member->member_no ?? ''));
        if ($memberNo === '') {
            $memberNo = 'U-' . str_pad((string) $userId, 3, '0', STR_PAD_LEFT);
        }

        return [
            'member_no' => $memberNo,
            'name' => trim((string) ($member->name ?? '-')),
            'nip' => trim((string) ($member->nip ?? '')) !== '' ? trim((string) $member->nip) : '-',
            'unit_kerja' => trim((string) ($member->unit_kerja ?? '')) !== '' ? trim((string) $member->unit_kerja) : '-',
            'phone' => trim((string) ($member->phone ?? '')) !== '' ? trim((string) $member->phone) : '-',
        ];
    }
}
