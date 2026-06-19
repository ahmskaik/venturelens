<?php

namespace App\Services;

use App\Models\ApplicationFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Smalot\PdfParser\Parser;

class DocumentExtractor
{
    private const MAX_EXTRACT_CHARS = 30_000;

    public function __construct(
        private readonly Parser $pdfParser,
    ) {}

    public function extractFromFile(ApplicationFile $file): string
    {
        $disk = Storage::disk(config('venturelens.uploads_disk', 'local'));
        $path = $file->storage_path;

        if (! $disk->exists($path)) {
            throw new RuntimeException("File not found: {$path}");
        }

        $contents = $disk->get($path);
        $text = '';

        if ($file->mime_type === 'application/pdf' || str_ends_with(strtolower($file->original_filename), '.pdf')) {
            $pdf = $this->pdfParser->parseContent($contents);
            $text = $pdf->getText();
        } else {
            $text = is_string($contents) ? $contents : '';
        }

        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        if (mb_strlen($text) > self::MAX_EXTRACT_CHARS) {
            $text = mb_substr($text, 0, self::MAX_EXTRACT_CHARS);
        }

        $file->update(['extracted_text' => $text]);

        return $text;
    }

    public function summarizeApplicationDocuments(int $applicationId): string
    {
        $files = ApplicationFile::where('application_id', $applicationId)->get();
        $parts = [];

        foreach ($files as $file) {
            if ($file->extracted_text) {
                $parts[] = sprintf(
                    "[%s: %s]\n%s",
                    $file->type,
                    $file->original_filename,
                    $file->extracted_text
                );
                continue;
            }

            try {
                $parts[] = sprintf(
                    "[%s: %s]\n%s",
                    $file->type,
                    $file->original_filename,
                    $this->extractFromFile($file)
                );
            } catch (RuntimeException) {
                $parts[] = sprintf("[%s: %s]\n[Extraction failed]", $file->type, $file->original_filename);
            }
        }

        $application = \App\Models\Application::query()->find($applicationId);
        $gohortoText = $application?->form_data['integration']['gohorto_document_text'] ?? null;
        if (is_string($gohortoText) && trim($gohortoText) !== '') {
            $parts[] = "[gohorto_import]\n".trim($gohortoText);
        }

        return implode("\n\n", $parts);
    }
}
