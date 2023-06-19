<?php

namespace App\Traits;

use Closure;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

trait Miscellaneous
{
    /**
     * Flatten an array
     * @param array $array
     * @return array
     * @source: https://stackoverflow.com/a/1320156/12199939
     */
    public function flattenArray(array $array)
    {
        $return = array();
        array_walk_recursive($array, function ($a) use (&$return) {
            $return[] = $a;
        });
        return $return;
    }

    /**
     * Remove duplicates from an associative array based on a key
     * @param array $array
     * @param string $key - only depth=1 supported for now
     * @return void
     */
    public function removeDuplicatesByKey(array &$array, string $key)
    {
        $arrayIndicesToRemove = [];
        $uniqueVals = [];
        foreach ($array as $index => $value) {
            if (isset($value[$key])) {
                if (!in_array($value[$key], $uniqueVals)) {
                    array_push($uniqueVals, $value[$key]);
                } else {
                    array_push($arrayIndicesToRemove, $index);
                }
            }
        }
        unset($uniqueVals);
        if (!empty($arrayIndicesToRemove)) {
            $array = Arr::except($array, $arrayIndicesToRemove);
        }
    }

    /**
     * Generate a password prompt & read password from command line
     * @return string
     * @source: https://chat.openai.com/
     */
    public function readPasswordFromCli()
    {
        print('Enter password: ');
        shell_exec("stty -echo"); // Execute the stty command to turn off echo
        $password = trim(fgets(STDIN)); // Read the password from the terminal
        shell_exec("stty echo"); // Execute the stty command to turn on echo
        print(PHP_EOL);
        return $password;
    }

    /**
     * Read input from cli
     * @param integer $maxAllowedLines - Max input lines allowed
     * @param array $lineMsgs - Msg to display at each input line
     * @param callable $validateLine - function to validate each line, passes each line as an argument (must return boolean)
     * @param callable $terminate - function to stop reading lines when a condition is met, passes each line as an argument (must return boolean)
     * @param boolean $throwErrorIfLineValidationFails - stop execution and throw an error if validation fails for any line
     * @return array - returns input lines
     */
    public function readInputFromCli(
        int $maxAllowedLines = 1,
        array $lineMsgs = [],
        callable $validateLine = null,
        callable $terminate = null,
        bool $throwErrorIfLineValidationFails = false
    ) {
        $input = [];
        for ($i = 0; $i < $maxAllowedLines; $i++) {
            $line = readline($lineMsgs[$i] ?? null);
            if (is_callable($terminate) && $terminate($line)) {
                break;
            }
            if (is_callable($validateLine)) {
                if (!$validateLine($line)) {
                    if ($throwErrorIfLineValidationFails) {
                        throw new Exception('Invalid input error. Validation failed for line: ' . $i);
                    }
                } else {
                    array_push($input, $line);
                }
            } else {
                array_push($input, $line);
            }
        }
        return $input;
    }

    /**
     * Remove last line from cli
     * @return void
     */
    public function removeLastLine()
    {
        print("\033[1A\033[K");
    }

    /**
     * Checks if a multidimensional array has duplicate values
     * @param array $inputArray - the mutidimensional array
     * @return boolean - returns true if array has duplicate values, false otherwise
     */
    public function arrayHasDuplicateValues(array &$inputArray)
    {
        return !count(array_unique(array_map('json_encode', $inputArray))) == count($inputArray);
    }

    /**
     * Generate exists raw query string for a table from data
     * @param array $values
     * @param string $column
     * @param string $tableName
     * @param string|null $softDeleteColumn - pass null if deleted_at condition should not be added
     * @return string
     */
    public function generateExistsQuery(array $values, string $column, string $tableName, string|null $softDeleteColumn = 'deleted_at')
    {
        $values = implode(', ', array_map(function ($val) {
            return '"' . $val . '"';
        }, $values));
        $statement = "select `{$column}` from `{$tableName}` where `{$column}` in ({$values})";
        if (!is_null($softDeleteColumn)) {
            $statement .= " and `{$tableName}`.`{$softDeleteColumn}` is null";
        }
        return $statement;
    }

    /**
     * Generate raw insert query string for a table from data
     * @param array $data
     * @param string $tableName
     * @return string
     */
    public function generateRawInsertQuery(array &$data, string $tableName)
    {
        $columns = implode(', ', array_keys(reset($data)));
        $values = implode(', ', array_values(array_map(function ($row) {
            $row = array_map(function ($value) {
                return '"' . $value . '"';
            }, $row);
            return '(' . implode(', ', array_values($row)) . ')';
        }, $data)));
        return "INSERT INTO {$tableName} ({$columns}) VALUES {$values}";
    }

    /**
     * Determine number of maximum insertable rows at a time in mysql for a given array
     * @param array $dataset
     * @param integer $bufferRows - number of buffer rows for safety
     * @param string $considerExistsCheckOf - pass column name to check exist query
     * @return integer
     */
    public function findMaxAllowedInsertRows(array &$dataset, string $tableName = 'products', string $considerExistsCheckOf = null)
    {
        $maxAllowedBytes = (int)DB::select("show variables like 'max_allowed_packet'")[0]->Value;
        if ($considerExistsCheckOf) {
            if (in_array('deleted_at', Schema::getColumnListing($tableName))) {
                $softDeleteColumn = 'deleted_at';
            } else {
                $softDeleteColumn = null;
            }
            $values = array_column($dataset, $considerExistsCheckOf);
            $requiredBytes = strlen($this->generateExistsQuery(
                $values,
                $considerExistsCheckOf,
                $tableName,
                $softDeleteColumn
            ));
        } else {
            $requiredBytes = strlen($this->generateRawInsertQuery($dataset, $tableName));
        }
        $datasetLength = count($dataset);
        if ($requiredBytes <= $maxAllowedBytes) {
            return $datasetLength;
        } else {
            $requiredBytes = 0;
            for ($sliceLength = 1; $sliceLength <= $datasetLength; $sliceLength++) {
                $subset = array_slice($dataset, 0, $sliceLength);
                if ($considerExistsCheckOf) {
                    $values = array_column($subset, $considerExistsCheckOf);
                    $requiredBytes = strlen($this->generateExistsQuery(
                        $values,
                        $considerExistsCheckOf,
                        $tableName,
                        $softDeleteColumn
                    ));
                } else {
                    $requiredBytes = strlen($this->generateRawInsertQuery($subset, $tableName));
                }
                if ($requiredBytes == $maxAllowedBytes) {
                    break;
                } elseif ($requiredBytes > $maxAllowedBytes) {
                    $sliceLength--;
                }
            }
            return $sliceLength;
        }
    }

    /**
     * Append key value pair to .env file
     * @param string $key - CAUTION: The $key must not exist as commented line in .env
     * @param string $value
     * @return boolean - true on successful write, false otherwise
     */
    public function appendToEnv(string $key, string $value)
    {
        $value = trim($value);
        $envData = file_get_contents(app()->environmentFilePath());
        if (!str_contains($envData, $key . '=')) {
            $envData .= PHP_EOL . $key . '="' . $value . '"' . PHP_EOL;
            return (bool)file_put_contents(app()->environmentFilePath(), $envData);
        } else {
            return (bool)file_put_contents(app()->environmentFilePath(), str_replace(
                [$key . '=' . env($key), $key . '="' . env($key) . '"'],
                $key . '="' . $value . '"',
                $envData
            ));
        }
    }

    /**
     * Delay excution
     * @param integer seconds
     * @return void
     */
    public function delayExecution(int $seconds)
    {
        if ($seconds < 1) {
            throw new Exception('Argument $seconds must be at least 1.');
        }
        $endTime = now()->addSeconds($seconds);
        while (now()->lte($endTime)) {
            continue;
        }
    }

    /**
     * Attempt http requests - compensates for http request failures
     * @param string $url
     * @param string $method
     * @param array $payload
     * @param array $headers
     * @param integer $maxAttempts - maximum number of tries
     * @param boolean $throwErrorOnFailure
     * @param integer $attempt - attempt counter, do not pass while calling
     * @return \Illuminate\Http\Client\Response|false
     */
    public function attemptRequest(
        string $url,
        string $method,
        array $payload = null,
        array $headers = [],
        int $maxAttempts = 3,
        bool $throwErrorOnFailure = true,
        int $attempt = 1
    ) {
        $request = Http::withHeaders($headers);
        try {
            switch (strtoupper($method)) {
                case 'GET':
                    $request = $request->get($url, $payload);
                    break;
                case 'POST':
                    $request = $request->post($url, $payload ? $payload : []);
                    break;
                default:
                    throw new Exception('Unsupported request method.');
            }
            return $request;
        } catch (Exception $err) {
            if ($attempt <= $maxAttempts) {
                $attempt++;
                return $this->attemptRequest(
                    $url,
                    $method,
                    $payload,
                    $headers,
                    $maxAttempts,
                    $throwErrorOnFailure,
                    $attempt
                );
            }
            if ($throwErrorOnFailure) {
                throw $err;
            }
            return false;
        }
    }

    /**
     * Recursively hit apis (POST only, accepts json)- continuously hits api until recursion condition returns false
     * @param string $apiEndpoint
     * @param array $payload
     * @param Closure $recursionLogic - logic to calculate if recursion should continue, passes the received response as argument
     * @param Closure $dataFetchLogic - logic to determine which data to consider, passes the received response as argument, must return an array
     * @param Closure $dataMergeLogic - logic to merge the data with previous recursion data, passes the old parsed data as argument 1 & new parsed data as argument 2, must return an array
     * @param Closure $payloadUpdateLogic - logic to update payload for next recursive call, passes the received response as argument, must return a valid payload array
     * @param array $headers
     * @param array $parsedData - data to be passed/retained for next recursive call
     * @return array - returns array with unique elements
     */
    public function getDataFromApi_recursive(
        string $apiEndpoint,
        array $payload,
        Closure $recursionLogic,
        Closure $dataFetchLogic,
        Closure $dataMergeLogic,
        Closure $payloadUpdateLogic,
        array $headers = [],
        array $parsedData = []
    ) {
        print('Calling api endpoint ...' . PHP_EOL);
        if (!empty($headers)) {
            $response = Http::withHeaders($headers)->post($apiEndpoint, $payload);
        } else {
            $response = Http::post($apiEndpoint, $payload);
        }
        if (!$response->ok()) {
            $response->throw();
        }
        $response = $response->json();
        $newData = $dataFetchLogic($response);
        $parsedData = $dataMergeLogic($parsedData, $newData);
        if ($recursionLogic($response)) {
            $payload = $payloadUpdateLogic($response);
            return $this->getDataFromApi_recursive(
                $apiEndpoint,
                $payload,
                $recursionLogic,
                $dataFetchLogic,
                $dataMergeLogic,
                $payloadUpdateLogic,
                $headers,
                $parsedData
            );
        }
        return $parsedData;
    }

    /**
     * Calculate average
     * @param array
     * @return float|integer
     */
    public function calculateAverage(array $array)
    {
        $count = count($array);
        if (!$count) {
            return 0;
        }
        $sum = array_sum($array);
        return $sum / $count;
    }

    /**
     * Get available RAM in system
     * @return integer
     */
    public function getAvailableRAM()
    {
        $command = 'free -m | grep "Mem:"';
        $output = shell_exec($command);
        $availableRAM = Arr::last(explode(' ', $output));
        return $availableRAM;
    }

    /**
     * Find prime numbers in a given range. Uses: Sieve of Eratosthenes algorithm
     * @param integer $start - minimum value
     * @param integer $end - maximum value
     * @return array
     */
    public function findPrimesBetween(int $start, int $end)
    {
        // Create an array to track prime numbers
        $isPrime = array_fill($start, $end + 1, true);
        // 0 and 1 are not prime
        $isPrime[0] = $isPrime[1] = false;
        // Perform the sieve
        for ($i = 2; $i * $i <= $end; $i++) {
            if ($isPrime[$i]) {
                for ($j = $i * $i; $j <= $end; $j += $i) {
                    $isPrime[$j] = false;
                }
            }
        }
        // Collect prime numbers within the range
        $primes = [];
        for ($i = $start; $i <= $end; $i++) {
            if ($isPrime[$i]) {
                $primes[] = $i;
            }
        }
        return $primes;
    }
}
