<?php

namespace App\Traits;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Illuminate\Support\Str;

trait GenerateSpreadsheet
{
    /**
     * Generate a csv data sheet
     * @param array $headers
     * @param array $values
     * @param string $outputFile
     * @return boolean
     */
    public function generateCsv(array $headers, array $values, string $outputFile)
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->fromArray($headers);
        $worksheet->fromArray($values, null, 'A2');
        $writer = new Csv($spreadsheet);
        $writer->setDelimiter(',');
        $writer->setEnclosure('"');
        $writer->setLineEnding("\r\n");
        $writer->setSheetIndex(0);
        if (!Str::endsWith($outputFile, '.csv')) {
            $outputFile .= '.csv';
        }
        $writer->save($outputFile);
        return file_exists($outputFile);
    }
}
