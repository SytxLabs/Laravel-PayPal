<?php

namespace SytxLabs\PayPal\Services;

use GuzzleHttp\Psr7\MimeType;

class VerifyDocuments
{
    private static array $dispute_evidence_types = [
        'application/pdf',
        'image/gif',
        'image/jpeg',
        'image/png',
    ];

    private static int $dispute_evidence_file_size = 10;

    private static int $dispute_evidences_size = 50;

    /**
     * Get Mime type from filename.
     */
    public static function getMimeType(string $file): string
    {
        return MimeType::fromFilename($file);
    }

    /**
     * Check if the evidence file being submitted mime type is valid.
     */
    public static function isValidEvidenceFile(array $files): bool
    {
        $validFile = true;
        $validSize = true;
        $total_size = 0;

        $basic = (1024 * 1024);
        $file_size = $basic * self::$dispute_evidence_file_size;
        $overall_size = $basic * self::$dispute_evidences_size;

        foreach ($files as $file) {
            $mime_type = self::getMimeType($file);

            if (!in_array($mime_type, self::$dispute_evidence_types, true)) {
                $validFile = false;
                break;
            }

            $size = filesize($file);

            if ($size > $file_size) {
                $validSize = false;
                break;
            }

            $total_size += $size;
        }

        return !((($validFile === false) || ($validSize === false)) || ($total_size > $overall_size));
    }
}
