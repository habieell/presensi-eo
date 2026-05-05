<?php

namespace App\Services\FaceRecognition;

use App\Models\FaceRegistration;
use App\Models\User;
use InvalidArgumentException;

/**
 * FaceMatchService - cocokkan face descriptor dari client (face-api.js)
 * dengan descriptor yg tersimpan di DB.
 *
 * face-api.js menghasilkan Float32Array(128). Di backend kita bandingkan
 * pakai Euclidean distance. Threshold default 0.55:
 *  - distance < 0.55 = MATCH (orang yg sama, recommended)
 *  - distance > 0.6  = bukan orang yg sama
 *
 * Skor confidence yg dipakai = 1 - normalized_distance
 */
class FaceMatchService
{
    private float $threshold;

    public function __construct()
    {
        $this->threshold = (float) config('face.match_threshold', 0.55);
    }

    /**
     * Validasi format descriptor dari client
     */
    public function validateDescriptor(array $descriptor): array
    {
        $expected = (int) config('face.descriptor_length', 128);
        if (count($descriptor) !== $expected) {
            throw new InvalidArgumentException("Descriptor harus berisi $expected angka, dapat " . count($descriptor));
        }

        return array_map(fn($v) => (float) $v, $descriptor);
    }

    /**
     * Hitung Euclidean distance antara dua descriptor (lebih kecil = lebih mirip)
     */
    public function distance(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return 99.0;
        }
        $sum = 0.0;
        $n = count($a);
        for ($i = 0; $i < $n; $i++) {
            $d = $a[$i] - $b[$i];
            $sum += $d * $d;
        }
        return sqrt($sum);
    }

    /**
     * Cocokkan descriptor input dengan descriptor user
     *
     * @return array{matched:bool, distance:float, score:float}
     */
    public function matchUser(User $user, array $descriptor): array
    {
        $registration = FaceRegistration::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$registration) {
            return ['matched' => false, 'distance' => 99.0, 'score' => 0.0];
        }

        $stored = $registration->descriptor;
        if (!is_array($stored) || empty($stored)) {
            return ['matched' => false, 'distance' => 99.0, 'score' => 0.0];
        }

        $descriptor = $this->validateDescriptor($descriptor);
        $stored = $this->validateDescriptor($stored);

        $distance = $this->distance($descriptor, $stored);
        $matched  = $distance < $this->threshold;

        // Score: 1.0 = perfect match, 0 = totally different
        // Dimensi distance untuk face-api.js biasanya 0-1.5
        $score = max(0.0, min(1.0, 1.0 - ($distance / 1.0)));

        return [
            'matched'  => $matched,
            'distance' => round($distance, 4),
            'score'    => round($score, 4),
        ];
    }

    /**
     * Identifikasi user dari descriptor (cari di semua registrasi)
     * Berguna untuk "siapa yang ada di kamera ini?"
     */
    public function identify(array $descriptor): ?array
    {
        $descriptor = $this->validateDescriptor($descriptor);

        $best = null;
        $bestDistance = 99.0;

        FaceRegistration::with('user:id,name,email,role')
            ->where('is_active', true)
            ->chunk(50, function ($items) use ($descriptor, &$best, &$bestDistance) {
                foreach ($items as $item) {
                    $stored = $item->descriptor;
                    if (!is_array($stored)) continue;
                    $d = $this->distance($descriptor, $stored);
                    if ($d < $bestDistance) {
                        $bestDistance = $d;
                        $best = $item;
                    }
                }
            });

        if (!$best || $bestDistance >= $this->threshold) {
            return null;
        }

        return [
            'user'     => $best->user,
            'distance' => round($bestDistance, 4),
            'score'    => round(max(0.0, 1.0 - $bestDistance), 4),
        ];
    }
}
