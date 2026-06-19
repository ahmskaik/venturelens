<?php

namespace App\Http\Controllers;

use App\Services\ImpactEvidenceArchiveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class EvidenceSnapshotController extends Controller
{
    public function show(string $filename, ImpactEvidenceArchiveService $archives): BinaryFileResponse|JsonResponse
    {
        if (! $archives->isValidFilename($filename)) {
            abort(404);
        }

        $localPath = $archives->resolveLocalPath($filename);
        if ($localPath !== null) {
            return response()->file($localPath, [
                'Content-Type' => 'application/json',
            ]);
        }

        $gcsPath = 'evidence/'.$filename;
        if (config('filesystems.disks.gcs.bucket') && Storage::disk('gcs')->exists($gcsPath)) {
            $contents = Storage::disk('gcs')->get($gcsPath);

            return response()->json(json_decode($contents, true), 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        abort(404);
    }
}
