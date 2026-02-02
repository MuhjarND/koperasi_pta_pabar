<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Formulir Pinjaman</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111;
            margin: 24px 32px;
        }
        .header {
            text-align: center;
            margin-bottom: 14px;
        }
        .header img {
            width: 100%;
            max-height: 120px;
            object-fit: contain;
        }
        .title {
            font-weight: 700;
            text-align: center;
            margin: 10px 0 16px;
            text-transform: uppercase;
        }
        .member-no {
            text-align: right;
            margin-top: 6px;
            margin-bottom: 6px;
            font-size: 12px;
        }
        .member-no-label {
            margin-right: 8px;
            font-weight: 600;
        }
        .member-box {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 1px solid #111;
            text-align: center;
            line-height: 18px;
            font-size: 11px;
            margin-left: 2px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .info-table td {
            padding: 4px 0;
            vertical-align: top;
        }
        .info-table td.label {
            width: 140px;
        }
        .section {
            margin-top: 10px;
        }
        .bullet {
            margin: 0;
            padding-left: 16px;
        }
        .bullet li {
            margin-bottom: 4px;
        }
        .signature {
            width: 100%;
            margin-top: 28px;
            table-layout: fixed;
        }
        .signature td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            padding-top: 12px;
        }
        .signature-role {
            display: block;
            margin-bottom: 4px;
        }
        .signature-date {
            display: block;
            height: 16px;
        }
        .signature-qr-wrap {
            height: 92px;
            margin: 6px 0 4px;
        }
        .signature-qr {
            width: 90px;
            height: 90px;
            display: block;
            margin: 0 auto;
        }
        .signature-name {
            font-weight: 600;
        }
        .approval-title {
            text-align: center;
            font-weight: 700;
            margin: 18px 0 10px;
            text-transform: uppercase;
        }
        .muted {
            color: #555;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(!empty($kopImage))
            <img src="{{ $kopImage }}" alt="Kop Koperasi">
        @endif
    </div>

    <div class="title">Formulir Permohonan Pinjaman</div>

    @php
        $memberChars = str_split(str_pad(preg_replace('/[^0-9A-Za-z]/', '', $memberNo), 6, ' ', STR_PAD_RIGHT));
    @endphp
    <div class="member-no">
        <span class="member-no-label">Nomor Anggota:</span>
        @foreach($memberChars as $char)
            <span class="member-box">{{ $char }}</span>
        @endforeach
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Nama Lengkap</td>
            <td>: {{ $memberName }}</td>
        </tr>
        <tr>
            <td class="label">NIP</td>
            <td>: {{ $nip }}</td>
        </tr>
        <tr>
            <td class="label">Unit Kerja</td>
            <td>: {{ $unitKerja }}</td>
        </tr>
        <tr>
            <td class="label">No. HP</td>
            <td>: {{ $phone }}</td>
        </tr>
    </table>

    <p>Dengan ini mengajukan permohonan kredit/pinjaman kepada Koperasi sebagai berikut:</p>
    <ul class="bullet">
        <li>Nominal Permohonan : Rp {{ number_format($amount, 2, ',', '.') }}</li>
        <li>Jangka Waktu : {{ $termMonths }} bulan</li>
        <li>Untuk Keperluan : {{ $purpose }}</li>
        <li>Pemberian jasa perbulan ({{ $serviceRate }}% x pinjaman) : Rp {{ number_format($feePerMonth, 2, ',', '.') }}</li>
    </ul>

    <table class="signature">
        <tr>
            <td>
                <span class="signature-date">&nbsp;</span>
                <span class="signature-role">Bendahara,</span>
                <div class="signature-qr-wrap">
                    @if(!empty($qrBendahara))
                        <img class="signature-qr" src="{{ $qrBendahara }}" alt="QR TTD Bendahara">
                    @endif
                </div>
                <div class="signature-name">({{ $bendaharaName ?? 'Bendahara' }})</div>
            </td>
            <td>
                {{ $city }}, {{ $date }}
                <br>
                <span class="signature-role">Pemohon,</span>
                <div class="signature-qr-wrap">
                    @if(!empty($qrPemohon))
                        <img class="signature-qr" src="{{ $qrPemohon }}" alt="QR TTD Pemohon">
                    @endif
                </div>
                <div class="signature-name">({{ $memberName }})</div>
            </td>
        </tr>
    </table>

    <div class="approval-title">Lembar Persetujuan</div>
    <p>Menyetujui permohonan pinjaman sebagaimana tersebut di atas:</p>
    <ul class="bullet">
        <li>Nominal Pinjaman : Rp {{ number_format($amount, 2, ',', '.') }}</li>
        <li>Jangka Waktu : {{ $termMonths }} bulan</li>
        <li>Angsuran per Bulan : Rp {{ number_format($installmentTotal, 2, ',', '.') }}</li>
    </ul>

    <table class="signature">
        <tr>
            <td></td>
            <td>
                {{ $city }}, {{ $date }}
                <br>
                <span class="signature-role">Ketua,</span>
                <div class="signature-qr-wrap">
                    @if(!empty($qrKetua))
                        <img class="signature-qr" src="{{ $qrKetua }}" alt="QR TTD Ketua">
                    @endif
                </div>
                <div class="signature-name">({{ $ketuaName ?? 'Ketua' }})</div>
            </td>
        </tr>
    </table>
</body>
</html>

