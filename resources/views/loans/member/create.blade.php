@extends('layouts.app')

@section('title', 'Ajukan Pinjaman')
@section('subtitle', 'Lengkapi selfie dan detail pinjaman.')

@section('content')
    @php
        $feeRate = config('koperasi.service_fee_rate');
    @endphp

    <div class="grid-two">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'file'])</div>
                    <h3>Data Pinjaman</h3>
                </div>
            </div>
            @if($errors->has('profile'))
                <div class="alert danger">{{ $errors->first('profile') }}</div>
            @endif
            <form method="post" action="{{ route('anggota.loans.store') }}" class="form-grid" enctype="multipart/form-data">
                @csrf
                <h3>Detail Pinjaman</h3>
                <div class="form-control">
                    <label>Nominal Pinjaman</label>
                    <input id="loan-amount" type="number" name="amount" min="1000" step="500" value="{{ old('amount') }}" required>
                </div>
                <div class="form-control">
                    <label>Jangka Waktu (bulan)</label>
                    <input id="loan-term" type="number" name="term_months" min="1" max="60" value="{{ old('term_months') }}" required>
                </div>
                <div class="form-control">
                    <label>Tujuan Pinjaman</label>
                    <textarea name="purpose" placeholder="Contoh: Modal usaha / Kebutuhan keluarga" required>{{ old('purpose') }}</textarea>
                </div>
                <div class="form-control">
                    <label>Selfie untuk Validasi</label>
                    <div class="selfie-wrap">
                        <video id="selfie-video" class="selfie-video" autoplay playsinline muted></video>
                        <button class="btn btn-ghost" type="button" id="selfie-capture">Ambil Foto</button>
                        <input type="hidden" name="selfie_data" id="selfie-data" required>
                    </div>
                    <div class="image-preview" id="selfie-preview">Belum ada foto</div>
                    <small class="muted" id="selfie-status">Klik "Ambil Foto" untuk mengaktifkan kamera.</small>
                </div>
                <button class="btn btn-primary" type="submit" @if(!$profileComplete) disabled @endif>Kirim Pengajuan</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'chart'])</div>
                    <h3>Ringkasan Perhitungan</h3>
                </div>
            </div>
            <p class="muted">Jasa per bulan mengikuti rate {{ $feeRate * 100 }}% dari nominal.</p>
            <table class="table">
                <tr>
                    <td>Nominal Pinjaman</td>
                    <td id="loan-amount-summary">Rp 0</td>
                </tr>
                <tr>
                    <td>Jangka Waktu</td>
                    <td id="loan-term-summary">0 bulan</td>
                </tr>
                <tr>
                    <td>Jasa per Bulan</td>
                    <td id="loan-fee">Rp 0</td>
                </tr>
                <tr>
                    <td>Angsuran Pokok per Bulan</td>
                    <td id="loan-installment">Rp 0</td>
                </tr>
                <tr>
                    <td>Total Angsuran per Bulan</td>
                    <td id="loan-installment-total">Rp 0</td>
                </tr>
            </table>
        </div>
    </div>

    <script>
        (function () {
            var rate = {{ $feeRate }};
            var amountInput = document.getElementById('loan-amount');
            var termInput = document.getElementById('loan-term');
            var feeOutput = document.getElementById('loan-fee');
            var amountSummary = document.getElementById('loan-amount-summary');
            var termSummary = document.getElementById('loan-term-summary');
            var installmentOutput = document.getElementById('loan-installment');
            var installmentTotalOutput = document.getElementById('loan-installment-total');
            var selfieVideo = document.getElementById('selfie-video');
            var selfieCapture = document.getElementById('selfie-capture');
            var selfiePreview = document.getElementById('selfie-preview');
            var selfieData = document.getElementById('selfie-data');
            var selfieStatus = document.getElementById('selfie-status');
            var selfieStream = null;

            function formatRupiah(value) {
                return 'Rp ' + value.toLocaleString('id-ID');
            }

            function parseNumberInput(value) {
                var digits = (value || '').toString().replace(/[^\d]/g, '');
                return digits ? parseInt(digits, 10) : 0;
            }

            function refresh() {
                var amount = parseNumberInput(amountInput.value || 0);
                var term = parseNumberInput(termInput.value || 0);
                var fee = amount * rate;
                var principalInstallment = term > 0 ? (amount / term) : 0;
                var totalInstallment = principalInstallment + fee;

                feeOutput.textContent = formatRupiah(Math.round(fee));
                amountSummary.textContent = formatRupiah(Math.round(amount));
                termSummary.textContent = term + ' bulan';
                installmentOutput.textContent = formatRupiah(Math.round(principalInstallment));
                if (installmentTotalOutput) {
                    installmentTotalOutput.textContent = formatRupiah(Math.round(totalInstallment));
                }
            }

            amountInput.addEventListener('input', refresh);
            termInput.addEventListener('input', refresh);
            refresh();

            function setupCamera() {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    selfieStatus.textContent = 'Perangkat tidak mendukung kamera.';
                    return;
                }

                selfieStatus.textContent = 'Mengaktifkan kamera...';
                navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
                    .then(function (stream) {
                        selfieStream = stream;
                        selfieVideo.srcObject = stream;
                        selfieStatus.textContent = 'Kamera aktif. Klik lagi untuk ambil foto.';
                    })
                    .catch(function () {
                        selfieStatus.textContent = 'Tidak bisa mengakses kamera.';
                    });
            }

            function captureSelfie() {
                if (!selfieVideo.videoWidth) {
                    selfieStatus.textContent = 'Kamera belum siap.';
                    return;
                }

                var canvas = document.createElement('canvas');
                canvas.width = selfieVideo.videoWidth;
                canvas.height = selfieVideo.videoHeight;
                var ctx = canvas.getContext('2d');
                ctx.drawImage(selfieVideo, 2, 0, canvas.width, canvas.height);
                var dataUrl = canvas.toDataURL('image/jpeg', 0.9);
                selfiePreview.textContent = '';
                selfiePreview.style.backgroundImage = 'url(' + dataUrl + ')';
                selfieData.value = dataUrl;
                selfieStatus.textContent = 'Selfie berhasil diambil.';
            }

            if (selfieCapture) {
                selfieCapture.addEventListener('click', function () {
                    if (!selfieStream) {
                        setupCamera();
                        return;
                    }

                    captureSelfie();
                });
            }
        })();
    </script>
@endsection

