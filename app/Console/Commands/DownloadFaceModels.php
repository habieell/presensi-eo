<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DownloadFaceModels extends Command
{
    protected $signature = 'face:download-models';
    protected $description = 'Download model file face-api.js ke public/face-models';

    private array $files = [
        'tiny_face_detector_model-weights_manifest.json',
        'tiny_face_detector_model-shard1',
        'face_landmark_68_model-weights_manifest.json',
        'face_landmark_68_model-shard1',
        'face_recognition_model-weights_manifest.json',
        'face_recognition_model-shard1',
        'face_recognition_model-shard2',
    ];

    private string $base = 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights';

    public function handle(): int
    {
        $dir = public_path('face-models');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $this->info("Mengunduh model face-api.js ke {$dir}...");
        foreach ($this->files as $f) {
            $url = "{$this->base}/{$f}";
            $this->line("  → {$f}");
            try {
                $r = Http::timeout(60)->get($url);
                if (!$r->successful()) {
                    $this->error("  ✗ HTTP " . $r->status());
                    continue;
                }
                file_put_contents("$dir/$f", $r->body());
                $this->info("  ✓ OK (" . round(strlen($r->body()) / 1024, 1) . " KB)");
            } catch (\Throwable $e) {
                $this->error("  ✗ {$e->getMessage()}");
            }
        }
        $this->info('✓ Selesai. Pastikan semua file ada di public/face-models/');
        return self::SUCCESS;
    }
}
