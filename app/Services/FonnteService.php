<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    protected $token;
    protected $baseUrl;

    public function __construct()
    {
        $this->token = config('services.fonnte.token');
        $this->baseUrl = config('services.fonnte.base_url', 'https://api.fonnte.com/send');
    }

    public function isEnabled(): bool
    {
        return !empty($this->token);
    }

    public function notifyUser(?int $userId, string $message): bool
    {
        if (!$userId) {
            return false;
        }

        $user = DB::table('users')->select('name', 'phone')->where('id', $userId)->first();
        $phone = $user->phone ?? null;
        if (!$phone) {
            return false;
        }

        $personalMessage = $this->personalizeMessage($message, $user->name ?? null);

        return $this->send($phone, $personalMessage);
    }

    public function notifyRole(string $role, string $message): bool
    {
        $recipients = DB::table('users')
            ->select('name', 'phone')
            ->where('role', $role)
            ->whereNotNull('phone')
            ->get();

        $sent = false;
        foreach ($recipients as $recipient) {
            $personalMessage = $this->personalizeMessage($message, $recipient->name ?? null);
            $sent = $this->send($recipient->phone, $personalMessage) || $sent;
        }

        return $sent;
    }

    public function send(string $target, string $message): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $normalized = $this->normalizePhone($target);
        if (!$normalized) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->withHeaders([
                    'Authorization' => $this->token,
                ])
                ->post($this->baseUrl, [
                    'target' => $normalized,
                    'message' => $message,
                ]);

            if (!$response->successful()) {
                Log::warning('Fonnte send failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Fonnte send exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    protected function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $digits = preg_replace('/\\D+/', '', $phone);
        if ($digits === '') {
            return null;
        }

        if (strpos($digits, '0') === 0) {
            $digits = '62' . substr($digits, 1);
        } elseif (strpos($digits, '62') !== 0 && strpos($digits, '8') === 0) {
            $digits = '62' . $digits;
        }

        return $digits;
    }

    protected function personalizeMessage(string $message, ?string $name): string
    {
        if (!$name) {
            return $message;
        }

        $pattern = '/^Yth\\. Bapak\\/Ibu,?$/m';
        $replacement = 'Yth. Bapak/Ibu ' . $name . ',';

        return preg_replace($pattern, $replacement, $message, 1) ?? $message;
    }

    public function formatMessage(array $points, string $closingEmoji = '', ?string $recipientName = null): string
    {
        $recipientLine = $recipientName
            ? 'Yth. Bapak/Ibu ' . $recipientName . ','
            : 'Yth. Bapak/Ibu,';

        $lines = [
            '*[KOPERASI NOTIF]*',
            '',
            'Assalamualaikum wr wb.',
            '',
            $recipientLine,
            '',
            'Dengan hormat,',
            'Berikut pemberitahuan resmi dari Koperasi:',
            '',
            'Rincian:',
            '',
        ];

        $hasFollowUpPoint = false;
        foreach ($points as $point) {
            $normalizedPoint = trim((string) $point);
            $normalizedPoint = preg_replace('/^[^\\p{L}\\p{N}]+/u', '', $normalizedPoint) ?? $normalizedPoint;
            if ($normalizedPoint === '') {
                continue;
            }
            if (stripos($normalizedPoint, 'tindak lanjut') !== false) {
                $hasFollowUpPoint = true;
            }
            $lines[] = $this->pointIcon($normalizedPoint) . ' ' . $normalizedPoint;
        }

        if ($hasFollowUpPoint) {
            $lines[] = '';
            $lines[] = 'Mohon tindak lanjut sesuai kewenangan dan ketentuan yang berlaku.';
        }
        $lines[] = 'Demikian pemberitahuan ini disampaikan.';
        $lines[] = '';
        $lines[] = 'Sekian dan terima kasih.';

        return implode("\n", $lines);
    }

    private function pointIcon(string $point): string
    {
        $text = mb_strtolower($point, 'UTF-8');

        if (strpos($text, 'tindak lanjut') !== false || strpos($text, 'tautan') !== false || strpos($text, 'link') !== false) {
            return '🔗';
        }
        if (strpos($text, 'nominal') !== false || strpos($text, 'jumlah') !== false || strpos($text, 'total') !== false) {
            return '💰';
        }
        if (strpos($text, 'tanggal') !== false || strpos($text, 'periode') !== false) {
            return '📅';
        }
        if (strpos($text, 'nama') !== false) {
            return '👤';
        }
        if (strpos($text, 'no. anggota') !== false || strpos($text, 'nomor anggota') !== false) {
            return '🆔';
        }
        if (strpos($text, 'detail') !== false || strpos($text, 'dokumen') !== false) {
            return '📄';
        }
        if (strpos($text, 'angsuran') !== false || strpos($text, 'tenor') !== false) {
            return '🔢';
        }
        if (strpos($text, 'status') !== false) {
            return '📌';
        }

        return '✅';
    }
}




