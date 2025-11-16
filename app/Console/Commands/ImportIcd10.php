<?php

namespace App\Console\Commands;

use App\Models\ImportIcd10 as ModelsImportIcd10;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportIcd10 extends Command
{
    protected $signature = 'icd10:import-csv';
    protected $description = 'Import ICD-10 CSV to DB with parent_id';

    public function handle()
    {
        $file = __DIR__ . '/icd10_2019.csv';

        if (!file_exists($file)) {
            $this->error("File tidak ditemukan: $file");
            return;
        }

        // DB::transaction(function () use ($file) {
            if (($handle = fopen($file, 'r')) !== false) {
                $header = fgetcsv($handle); // skip header
                $chapterMap = []; // simpan chapter code => model
                $blockMap = [];   // simpan block code => model

                while (($data = fgetcsv($handle)) !== false) {
                    $url       = $data[0];
                    $chapter   = $data[1];
                    $domain    = $data[2];
                    $subCode   = $data[3]; // misal A00
                    $definition= $data[4] ?? null;

                    // ====== Chapter ======
                    if (!isset($chapterMap[$chapter])) {
                        $chapterModel = ModelsImportIcd10::updateOrCreate(
                            ['code' => $chapter],
                            [
                                'title' => $chapter,
                                'chapter' => $chapter,
                                'definition' => null,
                                'url' => null,
                                'parent_id' => null
                            ]
                        );
                        $chapterMap[$chapter] = $chapterModel;
                    } else {
                        $chapterModel = $chapterMap[$chapter];
                    }

                    // ====== Block / Domain ======
                    if (!isset($blockMap[$domain])) {
                        $blockModel = ModelsImportIcd10::updateOrCreate(
                            ['code' => $domain],
                            [
                                'title' => $domain,
                                'chapter' => $chapter,
                                'definition' => null,
                                'url' => null,
                                'parent_id' => $chapterModel->id
                            ]
                        );
                        $blockMap[$domain] = $blockModel;
                    } else {
                        $blockModel = $blockMap[$domain];
                    }

                    // ====== Category / Sub-code ======
                    ModelsImportIcd10::updateOrCreate(
                        ['code' => $subCode],
                        [
                            'title' => $subCode,
                            'chapter' => $chapter,
                            'domain' => $domain,
                            'definition' => $definition,
                            'url' => $url,
                            'parent_id' => $blockModel->id
                        ]
                    );
                }

                fclose($handle);
            }
        // });

        $this->info("Import ICD-10 CSV selesai dengan parent_id!");
    }
}
