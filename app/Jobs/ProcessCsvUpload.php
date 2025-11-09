<?php

namespace App\Jobs;

use App\Models\Upload;
use App\Models\Products;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;

class ProcessCsvUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uploadId;

    public function __construct($uploadId)
    {
        $this->uploadId = $uploadId;
    }

    public function middleware()
    {
        // Prevent duplicate job for the same upload ID from running concurrently
        return [new WithoutOverlapping($this->uploadId)];
    }

    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $upload = Upload::find($this->uploadId);
        if (!$upload) return;

        $upload->update(['status' => 'processing', 'message' => null]);

        try {
            $fullPath = storage_path('app/private/' . $upload->file_path);
            if (!file_exists($fullPath)) {
                throw new \Exception('File not found.');
            }

            // Open CSV file
            if (($handle = fopen($fullPath, 'r')) === false) {
                throw new \Exception('Unable to open file.');
            }

            // Read header row
            $header = fgetcsv($handle);
            if ($header === false) {
                throw new \Exception('Empty CSV file.');
            }

            // Normalize header names: clean, trim, uppercase
            $header = array_map(function ($h) {
                $h = $this->cleanString($h);
                return trim(strtoupper($h));
            }, $header);

            // Expected columns (minimum required)
            $expected = [
                "UNIQUE_KEY",
                "PRODUCT_TITLE",
                "PRODUCT_DESCRIPTION",
                "STYLE#",
                "AVAILABLE_SIZES",
                "BRAND_LOGO_IMAGE",
                "THUMBNAIL_IMAGE",
                "COLOR_SWATCH_IMAGE",
                "PRODUCT_IMAGE",
                "SPEC_SHEET",
                "PRICE_TEXT",
                "SUGGESTED_PRICE",
                "CATEGORY_NAME",
                "SUBCATEGORY_NAME",
                "COLOR_NAME",
                "COLOR_SQUARE_IMAGE",
                "COLOR_PRODUCT_IMAGE",
                "COLOR_PRODUCT_IMAGE_THUMBNAIL",
                "SIZE",
                "QTY",
                "PIECE_WEIGHT",
                "PIECE_PRICE",
                "DOZENS_PRICE",
                "CASE_PRICE",
                "PRICE_GROUP",
                "CASE_SIZE",
                "INVENTORY_KEY",
                "SIZE_INDEX",
                "SANMAR_MAINFRAME_COLOR",
                "MILL",
                "PRODUCT_STATUS",
                "COMPANION_STYLES",
                "MSRP",
                "MAP_PRICING",
                "FRONT_MODEL_IMAGE_URL",
                "BACK_MODEL_IMAGE",
                "FRONT_FLAT_IMAGE",
                "BACK_FLAT_IMAGE",
                "PRODUCT_MEASUREMENTS",
                "PMS_COLOR",
                "GTIN",
            ];

            // Check missing columns
            $missing = array_diff($expected, $header);
            if (!empty($missing)) {
                throw new \Exception('Missing columns: ' . implode(',', $missing));
            }

            // Map header name to index
            $indexMap = [];
            foreach ($header as $i => $col) $indexMap[$col] = $i;

            $rowCount = 0;
            $batch = [];

            while (($row = fgetcsv($handle)) !== false) {
                $rowCount++;

                // Skip empty rows
                if (count($row) === 1 && trim($row[0]) === '') continue;

                // Helper closure
                $get = function ($colName) use ($row, $indexMap) {
                    if (!isset($indexMap[$colName])) return null;
                    $val = $row[$indexMap[$colName]] ?? null;
                    return $val !== null ? $this->cleanString($val) : null;
                };

                $uniqueKey = $this->cleanString($get('UNIQUE_KEY'));
                if (!$uniqueKey) continue;

                $batch[] = [
                    'unique_key' => $uniqueKey,
                    'product_title' => $get('PRODUCT_TITLE'),
                    'product_description' => $get('PRODUCT_DESCRIPTION'),
                    'style' => $get('STYLE#'),
                    'available_sizes' => $get('AVAILABLE_SIZES'),
                    'brand_logo_image' => $get('BRAND_LOGO_IMAGE'),
                    'thumbnail_image' => $get('THUMBNAIL_IMAGE'),
                    'color_swatch_image' => $get('COLOR_SWATCH_IMAGE'),
                    'product_image' => $get('PRODUCT_IMAGE'),
                    'spec_sheet' => $get('SPEC_SHEET'),
                    'price_text' => $get('PRICE_TEXT'),
                    'suggested_price' => $get('SUGGESTED_PRICE'),
                    'category_name' => $get('CATEGORY_NAME'),
                    'subcategory_name' => $get('SUBCATEGORY_NAME'),
                    'color_name' => $get('COLOR_NAME'),
                    'color_square_image' => $get('COLOR_SQUARE_IMAGE'),
                    'color_product_image' => $get('COLOR_PRODUCT_IMAGE'),
                    'color_product_image_thumbnail' => $get('COLOR_PRODUCT_IMAGE_THUMBNAIL'),
                    'size' => $get('SIZE'),
                    'qty' => $get('QTY'),
                    'piece_weight' => $get('PIECE_WEIGHT'),
                    'piece_price' => $get('PIECE_PRICE'),
                    'dozens_price' => $get('DOZENS_PRICE'),
                    'case_price' => $get('CASE_PRICE'),
                    'price_group' => $get('PRICE_GROUP'),
                    'case_size' => $get('CASE_SIZE'),
                    'inventory_key' => $get('INVENTORY_KEY'),
                    'size_index' => $get('SIZE_INDEX'),
                    'sanmar_mainframe_color' => $get('SANMAR_MAINFRAME_COLOR'),
                    'mill' => $get('MILL'),
                    'product_status' => $get('PRODUCT_STATUS'),
                    'companion_styles' => $get('COMPANION_STYLES'),
                    'msrp' => $get('MSRP'),
                    'map_pricing' => $get('MAP_PRICING'),
                    'front_model_image_url' => $get('FRONT_MODEL_IMAGE_URL'),
                    'back_model_image_url' => $get('BACK_MODEL_IMAGE'),
                    'front_flat_image' => $get('FRONT_FLAT_IMAGE'),
                    'back_flat_image' => $get('BACK_FLAT_IMAGE'),
                    'product_measurements' => $get('PRODUCT_MEASUREMENTS'),
                    'pms_color' => $get('PMS_COLOR'),
                    'gtin' => $get('GTIN'),
                ];

                // Save every 500 rows
                if (count($batch) >= 500) {
                    $this->saveBatch($batch);
                    $batch = [];
                }
            }

            // Save remaining batch
            if (!empty($batch)) {
                $this->saveBatch($batch);
            }

            fclose($handle);

            $upload->update([
                'status' => 'completed',
                'message' => "Processed {$rowCount} rows"
            ]);
        } catch (\Exception $e) {
            $upload->update([
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Save batch with transaction and upsert
     */
    protected function saveBatch(array $batch)
    {
        if (empty($batch)) return;

        DB::transaction(function () use ($batch) {
            Products::upsert($batch, ['unique_key'], array_keys($batch[0]));
        });
    }

    /**
     * Remove BOM and invalid UTF-8 characters
     */
    protected function cleanString($val)
    {
        if ($val === null) return null;

        // Remove BOM
        $val = preg_replace('/^\x{FEFF}/u', '', $val);
        // Remove invalid UTF-8
        $val = @iconv('UTF-8', 'UTF-8//IGNORE', $val);
        // Strip control chars except newline & tab
        $val = preg_replace('/[^\P{C}\n\t]+/u', '', $val);

        return trim($val);
    }

    /**
     * Normalize price format (optional helper)
     */
    protected function normalizePrice($val)
    {
        if ($val === null) return null;
        $clean = preg_replace('/[^\d\.\-]/', '', $val);
        if ($clean === '') return null;
        return number_format((float)$clean, 2, '.', '');
    }
}
