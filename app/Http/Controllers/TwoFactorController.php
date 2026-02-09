<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function setup(Request $request)
    {
        $auth = $request->session()->get('auth');
        if (!$auth) {
            return redirect()->route('login');
        }

        $user = DB::table('users')
            ->select('id', 'name', 'email', 'two_factor_enabled')
            ->where('id', $auth['id'])
            ->first();

        if (!$user) {
            return redirect()->route('login');
        }

        $google2fa = new Google2FA();
        $secret = null;
        $qrImage = null;
        $qrFallbackUrl = null;
        $manualKey = null;

        if (!(bool) $user->two_factor_enabled) {
            $secret = $request->session()->get('two_factor_secret');
            if (!$secret) {
                $secret = $google2fa->generateSecretKey();
                $request->session()->put('two_factor_secret', $secret);
            }

            $qrUrl = $google2fa->getQRCodeUrl('Koperasi Digital', $user->email, $secret);
            $qrImage = $this->buildQrDataUri($qrUrl, 220);
            $qrFallbackUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($qrUrl);
            $manualKey = $secret;
        }

        $recoveryCodes = $request->session()->get('two_factor_recovery_codes');

        return view('auth.2fa_setup', [
            'user' => $user,
            'enabled' => (bool) $user->two_factor_enabled,
            'qrImage' => $qrImage,
            'qrFallbackUrl' => $qrFallbackUrl,
            'manualKey' => $manualKey,
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    public function enable(Request $request)
    {
        $auth = $request->session()->get('auth');
        if (!$auth) {
            return redirect()->route('login');
        }

        $user = DB::table('users')
            ->select('id', 'two_factor_enabled')
            ->where('id', $auth['id'])
            ->first();

        if ($user && (bool) $user->two_factor_enabled) {
            return redirect()->route('authenticator.setup');
        }

        $payload = $request->validate([
            'otp' => 'required|string',
        ]);

        $secret = $request->session()->get('two_factor_secret');
        if (!$secret) {
            return redirect()
                ->route('authenticator.setup')
                ->withErrors(['otp' => 'Secret tidak ditemukan. Silakan ulangi aktivasi.']);
        }

        $google2fa = new Google2FA();
        $isValid = $google2fa->verifyKey($secret, $payload['otp'], 2);
        if (!$isValid) {
            return back()
                ->withErrors(['otp' => 'Kode autentikasi tidak valid.'])
                ->withInput();
        }

        $codes = [];
        $hashedCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $code = strtoupper(Str::random(4) . '-' . Str::random(4));
            $codes[] = $code;
            $hashedCodes[] = hash('sha256', $code);
        }

        DB::table('users')
            ->where('id', $auth['id'])
            ->update([
                'two_factor_enabled' => true,
                'two_factor_secret' => Crypt::encryptString($secret),
                'two_factor_recovery_codes' => json_encode($hashedCodes),
                'two_factor_enabled_at' => now(),
                'updated_at' => now(),
            ]);

        $request->session()->forget('two_factor_secret');
        $request->session()->flash('two_factor_recovery_codes', $codes);

        $auth['two_factor_enabled'] = true;
        $auth['two_factor_passed'] = true;
        $request->session()->put('auth', $auth);

        return redirect()
            ->route('authenticator.setup')
            ->with('success', 'Authenticator berhasil diaktifkan.');
    }

    public function verifyForm(Request $request)
    {
        $auth = $request->session()->get('auth');
        if (!$auth) {
            return redirect()->route('login');
        }

        return view('auth.2fa_verify');
    }

    public function verify(Request $request)
    {
        $auth = $request->session()->get('auth');
        if (!$auth) {
            return redirect()->route('login');
        }

        $payload = $request->validate([
            'otp' => 'nullable|string',
            'recovery_code' => 'nullable|string',
        ]);

        if (empty($payload['otp']) && empty($payload['recovery_code'])) {
            return back()->withErrors(['otp' => 'Masukkan kode autentikasi atau recovery code.']);
        }

        $user = DB::table('users')
            ->select('id', 'two_factor_secret', 'two_factor_recovery_codes')
            ->where('id', $auth['id'])
            ->first();

        if (!$user || empty($user->two_factor_secret)) {
            return redirect()->route('dashboard');
        }

        $google2fa = new Google2FA();
        $secret = Crypt::decryptString($user->two_factor_secret);

        $isValid = false;
        if (!empty($payload['otp'])) {
            $isValid = $google2fa->verifyKey($secret, $payload['otp'], 2);
        }

        if (!$isValid && !empty($payload['recovery_code'])) {
            $storedCodes = json_decode($user->two_factor_recovery_codes ?? '[]', true) ?: [];
            $inputHash = hash('sha256', strtoupper(trim($payload['recovery_code'])));
            $index = array_search($inputHash, $storedCodes, true);
            if ($index !== false) {
                $isValid = true;
                unset($storedCodes[$index]);
                DB::table('users')
                    ->where('id', $auth['id'])
                    ->update([
                        'two_factor_recovery_codes' => json_encode(array_values($storedCodes)),
                        'updated_at' => now(),
                    ]);
            }
        }

        if (!$isValid) {
            return back()->withErrors(['otp' => 'Kode autentikasi atau recovery code tidak valid.']);
        }

        $auth['two_factor_passed'] = true;
        $request->session()->put('auth', $auth);

        $intended = $request->session()->pull('two_factor_intended', route('dashboard'));

        return redirect()->to($intended);
    }

    private function buildQrDataUri($payload, $size)
    {
        $encoded = urlencode($payload);
        $primary = 'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size . '&cht=qr&chl=' . $encoded;
        $fallback = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . $encoded;

        $data = $this->downloadImage($primary);
        if (!$data) {
            $data = $this->downloadImage($fallback);
        }

        if (!$data) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode($data);
    }

    private function downloadImage($url)
    {
        $data = null;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $data = curl_exec($ch);
            curl_close($ch);
        }

        if (!$data && ini_get('allow_url_fopen')) {
            $data = @file_get_contents($url);
        }

        return $data ?: null;
    }
}
