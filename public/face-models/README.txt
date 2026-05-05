FACE-API.JS MODELS
==================

Folder ini akan diisi model file dari face-api.js.

LANGKAH DOWNLOAD MODEL:
1. Buka: https://github.com/justadudewhohacks/face-api.js/tree/master/weights
2. Download file-file berikut, taruh DI FOLDER INI (public/face-models/):

   tiny_face_detector_model-weights_manifest.json
   tiny_face_detector_model-shard1
   face_landmark_68_model-weights_manifest.json
   face_landmark_68_model-shard1
   face_recognition_model-weights_manifest.json
   face_recognition_model-shard1
   face_recognition_model-shard2

ATAU PAKAI SCRIPT (recommended):
   php artisan face:download-models

(command tersedia di app/Console/Commands/DownloadFaceModels.php)

Setelah download, pendaftaran wajah & verifikasi check-in/check-out akan berfungsi.
