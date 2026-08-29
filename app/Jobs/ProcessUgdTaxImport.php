<?php
// app/Jobs/ProcessUgdTaxImport.php

namespace App\Jobs;

use App\Imports\UgdTaxImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessUgdTaxImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $corporationId;
    public $timeout = 3600;
    public $tries = 3;

    public function __construct($filePath, $corporationId)
    {
        $this->filePath = $filePath;
        $this->corporationId = $corporationId;
    }

    public function handle()
    {
        try {
            Log::info("Starting UGD Tax import for corporation: {$this->corporationId}");
            
            ini_set('memory_limit', '2048M');
            set_time_limit(0);

            $import = new UgdTaxImport($this->corporationId);
            $fullPath = storage_path('app/' . $this->filePath);
            
            if (!file_exists($fullPath)) {
                throw new \Exception("File not found: {$fullPath}");
            }

            Excel::import($import, $fullPath);

            $stats = $import->getStats();
            Cache::put("ugd_tax_import_final_stats_{$this->corporationId}", $stats, 86400);
            Cache::put("ugd_tax_import_status_{$this->corporationId}", 'completed', 86400);
            
            Log::info("UGD Tax import completed for corporation: {$this->corporationId}");

            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

        } catch (\Exception $e) {
            Log::error("UGD Tax import failed: " . $e->getMessage());
            
            Cache::put("ugd_tax_import_error_{$this->corporationId}", [
                'message' => $e->getMessage(),
                'time' => now()->toDateTimeString()
            ], 86400);
            Cache::put("ugd_tax_import_status_{$this->corporationId}", 'failed', 86400);
            
            throw $e;
        }
    }
}