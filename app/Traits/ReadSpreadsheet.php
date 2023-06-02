<?php

namespace App\Traits;

use PhpOffice\PhpSpreadsheet\IOFactory;

trait ReadSpreadsheet
{
    /**
     * Read an excel sheet
     * @param string $file - file location
     * @param array $sheetNames - array of sheets to be read from file
     * @param boolean $formatData - return formatted data, assumes first row contains headers in English
     * @param array $headers
     * @return array
     */
    public function readExcelSheet(string $file, array $sheetNames, bool $formatData = true, array $headers = [])
    {
        ini_set('memory_limit', '512M');
        $dataArray = [];
        printf("Loading excel table data sheet...");
        $excelFile = IOFactory::load(realpath(getcwd() . '/' . $file));
        foreach ($sheetNames as $sheet) {
            $data = $excelFile->getSheetByName($sheet);
            if ($data) {
                $dataArray[$sheet] = [];
                $data = $data->getRowIterator();
            } else {
                printf(" Sheet: {$sheet} not found." . PHP_EOL);
                continue;
            }
            printf(" Done." . PHP_EOL);
            $rows = [];
            foreach ($data as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $cells = [];
                foreach ($cellIterator as $cell) {
                    $cell = $cell->getFormattedValue();
                    if (is_object($cell)) {
                        $cells[] = $cell->getPlainText();
                    } else {
                        $cells[] = $cell;
                    }
                }
                $rows[] = $cells;
            }
            if ($formatData) {
                printf("Formatting data...");
                if (!empty($headers)) {
                    $headers = array_intersect(reset($rows), $headers);
                    $headerIndices = [];
                    foreach ($headers as $header) {
                        $headerIndices[$header] = array_search($header, reset($rows));
                    }
                    array_shift($rows);
                    $record = [];
                    foreach ($rows as $row) {
                        $columnsWithValues = 0;
                        foreach ($headerIndices as $header => $index) {
                            $record[$header] = $row[$index];
                            if (!empty($row[$index])) {
                                $columnsWithValues++;
                            }
                        }
                        if ($columnsWithValues) {
                            $dataArray[$sheet][] = $record;
                        }
                    }
                }
                printf(' Done.' . PHP_EOL);
            } else {
                $dataArray[$sheet][] = $rows;
            }
        }
        return $dataArray;
    }

    /**
     * Read a csv spreadsheet
     * @param string $file - file location
     * @param array $headers
     * @param boolean $treatFirstRowAsHeader - if true, returns data as key value pair arrays with keys as first row of file
     * @param string $encoding
     * @param string $delimiter
     * @return array
     */
    public function readCsvSheet(
        string $file,
        array $headers = [],
        bool $treatFirstRowAsHeader = false,
        string $encoding = 'UTF-8',
        string $delimiter = ','
    ) {
        $reader = IOFactory::createReader('Csv');
        $reader->setInputEncoding($encoding);
        $reader->setDelimiter($delimiter);
        $spreadsheet = $reader->load($file)->getActiveSheet();
        $data = [];
        foreach ($spreadsheet->getRowIterator() as $row) {
            $cells = $row->getCellIterator();
            $rowValues = [];
            foreach ($cells as $cell) {
                $value = $cell->getValue();
                array_push($rowValues, $value);
            }
            array_push($data, $rowValues);
        }
        if (!empty($data) && (!empty($headers) || $treatFirstRowAsHeader)) {
            $headerRow = reset($data);
            $headers = $treatFirstRowAsHeader ? $headerRow : array_intersect($headerRow, $headers);
            if (!empty($headers)) {
                $headerIndices = [];
                foreach ($headers as $header) {
                    $headerIndices[$header] = array_search($header, $headerRow);
                }
                $csvRowsWithoutHeaders = array_slice($data, 1);
                $data = [];
                foreach ($csvRowsWithoutHeaders as $rowValues) {
                    $row = [];
                    foreach ($headerIndices as $header => $headerIndex) {
                        $row[$header] = $rowValues[$headerIndex];
                    }
                    array_push($data, $row);
                }
            }
        }
        return $data;
    }

    /**
     * Check if a string has only english characters
     * @param string $str
     * @return bool
     */
    function isEnglish(string &$str)
    {
        return strlen($str) == mb_strlen($str);
    }

    /**
     * Check column names for english characters
     * @param array $columns
     * @return bool
     */
    function allStringsAreEnglish(array &$columnNames)
    {
        foreach ($columnNames as $columnName) {
            if (!$this->isEnglish($columnName)) {
                return false;
            }
        }
        return true;
    }
}
