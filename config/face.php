<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Face Recognition (face-api.js) Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('FACE_RECOGNITION_ENABLED', true),

    /*
    | Match threshold (Euclidean distance):
    |   0.30 = sangat ketat (mungkin reject orang yg sama beda lighting)
    |   0.40 = ketat — RECOMMENDED untuk membedakan saudara/keluarga
    |   0.50 = standar (default face-api.js)
    |   0.60 = longgar (gampang false-positive utk saudara/kembar)
    */
    'match_threshold' => (float) env('FACE_MATCH_THRESHOLD', 0.40),

    'min_detection_confidence' => (float) env('FACE_MIN_DETECTION_CONFIDENCE', 0.7),

    'descriptor_length' => 128,

    'models_path' => '/face-models',

    'required_for' => [
        'check_in'  => true,
        'check_out' => true,
        'register'  => true,
    ],

    /*
    | Anti-spoofing: minimum quality saat capture (0..1).
    | Lebih tinggi = wajib wajah jelas & menghadap kamera.
    */
    'min_capture_quality' => (float) env('FACE_MIN_CAPTURE_QUALITY', 0.80),
];
