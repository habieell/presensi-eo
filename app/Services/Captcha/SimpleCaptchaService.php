<?php

namespace App\Services\Captcha;

use App\Models\CaptchaChallenge;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * SimpleCaptchaService - lightweight built-in captcha
 *
 * Flow:
 *  1) Client GET /api/captcha   -> dapat { id, image (data:image/svg) }
 *  2) Client kirim form login dgn captcha_id + captcha_answer
 *  3) Backend validasi via verify($id, $answer)
 *
 * Tidak butuh imagick/gd, kita render SVG sederhana.
 */
class SimpleCaptchaService
{
    public function generate(?string $ip = null): array
    {
        $code = $this->randomCode();
        $challenge = CaptchaChallenge::create([
            'code'       => config('captcha.sensitive', true) ? $code : strtoupper($code),
            'ip_address' => $ip,
            'used'       => false,
            'expires_at' => Carbon::now()->addSeconds(config('captcha.ttl', 300)),
            'created_at' => now(),
        ]);

        return [
            'id'         => $challenge->id,
            'image'      => $this->renderSvg($code),
            'expires_in' => config('captcha.ttl', 300),
        ];
    }

    public function verify(?string $id, ?string $answer, ?string $ip = null): bool
    {
        if (!config('captcha.enabled', true)) return true;
        if (!$id || !$answer) return false;

        $challenge = CaptchaChallenge::find($id);
        if (!$challenge || $challenge->used || $challenge->isExpired()) {
            return false;
        }

        $stored = config('captcha.sensitive', true) ? $challenge->code : strtoupper($challenge->code);
        $given  = config('captcha.sensitive', true) ? $answer : strtoupper($answer);

        $ok = hash_equals($stored, $given);

        // mark as used regardless to prevent replay
        $challenge->update(['used' => true]);
        return $ok;
    }

    public function purgeExpired(): int
    {
        return CaptchaChallenge::where('expires_at', '<', now()->subHour())->delete();
    }

    private function randomCode(): string
    {
        $chars = config('captcha.characters', 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789');
        $len = (int) config('captcha.length', 5);
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $out;
    }

    /**
     * Render SVG captcha berisi text + noise lines
     */
    private function renderSvg(string $code): string
    {
        $w = config('captcha.width', 160);
        $h = config('captcha.height', 60);
        $colors = ['#7c3aed', '#0ea5e9', '#10b981', '#f97316', '#ef4444', '#ec4899'];

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '">';

        // gradient background
        $svg .= '<defs><linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">'
              . '<stop offset="0%" stop-color="#1e1b4b"/>'
              . '<stop offset="100%" stop-color="#312e81"/>'
              . '</linearGradient></defs>';
        $svg .= '<rect width="' . $w . '" height="' . $h . '" fill="url(#bg)"/>';

        // noise lines
        for ($i = 0; $i < 6; $i++) {
            $x1 = random_int(0, $w);
            $y1 = random_int(0, $h);
            $x2 = random_int(0, $w);
            $y2 = random_int(0, $h);
            $c = $colors[array_rand($colors)];
            $svg .= "<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke=\"$c\" stroke-width=\"1.5\" opacity=\"0.4\"/>";
        }

        // dots
        for ($i = 0; $i < 30; $i++) {
            $x = random_int(0, $w);
            $y = random_int(0, $h);
            $r = random_int(1, 2);
            $c = $colors[array_rand($colors)];
            $svg .= "<circle cx=\"$x\" cy=\"$y\" r=\"$r\" fill=\"$c\" opacity=\"0.5\"/>";
        }

        // text - distorted
        $chars = str_split($code);
        $totalChars = count($chars);
        $charSpacing = ($w - 30) / $totalChars;
        $startX = 20;
        foreach ($chars as $i => $ch) {
            $x = $startX + ($i * $charSpacing);
            $y = ($h / 2) + 12 + random_int(-6, 6);
            $rotate = random_int(-20, 20);
            $color = $colors[array_rand($colors)];
            $svg .= "<text x=\"$x\" y=\"$y\" font-family=\"Verdana, monospace\" font-size=\"32\" font-weight=\"bold\" fill=\"$color\" transform=\"rotate($rotate $x " . ($y - 10) . ")\">$ch</text>";
        }

        $svg .= '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
