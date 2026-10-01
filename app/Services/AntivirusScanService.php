<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AntivirusScanService
{
    /**
     * Scan a file binary or stream for known viruses/malware using ClamAV or fallback heuristic inspection.
     * 
     * @return array{is_clean: bool, threat: string|null}
     */
    public function scanBuffer(string $buffer, string $filename = 'upload.tmp'): array
    {
        // 1. Basic Heuristic Check: Check for executable headers and PHP script tags in uploaded documents
        $threat = $this->runHeuristicCheck($buffer);
        if ($threat) {
            Log::warning("Antivirus heuristic detection triggered for [{$filename}]: {$threat}");
            return ['is_clean' => false, 'threat' => $threat];
        }

        // 2. ClamAV Daemon Socket / CLI Scan if configured
        $clamSocket = config('app.clamav_socket');
        if ($clamSocket && function_exists('fsockopen')) {
            try {
                $scanResult = $this->scanWithClamDaemon($clamSocket, $buffer);
                if (!$scanResult['is_clean']) {
                    return $scanResult;
                }
            } catch (\Throwable $e) {
                Log::warning('ClamAV scan skipped or failed: ' . $e->getMessage());
            }
        }

        return ['is_clean' => true, 'threat' => null];
    }

    /**
     * Scan buffer for disguised executable signatures, embedded script tags, and PHP shells.
     */
    private function runHeuristicCheck(string $buffer): ?string
    {
        // DOS / PE executable header (MZ)
        if (str_starts_with($buffer, "MZ")) {
            return 'Disguised Windows PE/DOS Executable';
        }

        // Linux ELF header (\x7fELF)
        if (str_starts_with($buffer, "\x7FELF")) {
            return 'Disguised Linux ELF Executable';
        }

        // Embedded PHP code tags inside image/document
        if (preg_match('/<\?php|<\?=\s*|<script\b/i', $buffer)) {
            return 'Embedded Script Execution Payload';
        }

        return null;
    }

    /**
     * Communicate with clamd via INSTREAM protocol.
     */
    private function scanWithClamDaemon(string $socketPath, string $buffer): array
    {
        $socket = @fsockopen($socketPath, 3310, $errno, $errstr, 2);
        if (!$socket) {
            return ['is_clean' => true, 'threat' => null];
        }

        fwrite($socket, "zINSTREAM\0");
        $len = strlen($buffer);
        fwrite($socket, pack('N', $len));
        fwrite($socket, $buffer);
        fwrite($socket, pack('N', 0));

        $response = trim(fgets($socket));
        fclose($socket);

        if (str_contains($response, 'FOUND')) {
            return ['is_clean' => false, 'threat' => $response];
        }

        return ['is_clean' => true, 'threat' => null];
    }
}
