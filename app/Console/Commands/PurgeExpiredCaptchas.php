<?php

namespace App\Console\Commands;

use App\Services\Captcha\SimpleCaptchaService;
use Illuminate\Console\Command;

class PurgeExpiredCaptchas extends Command
{
    protected $signature = 'captcha:purge';
    protected $description = 'Hapus captcha challenges yang sudah kadaluarsa';

    public function handle(SimpleCaptchaService $captcha): int
    {
        $deleted = $captcha->purgeExpired();
        $this->info("✓ Hapus {$deleted} captcha kadaluarsa");
        return self::SUCCESS;
    }
}
