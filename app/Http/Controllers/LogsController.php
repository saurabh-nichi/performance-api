<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RequestLog;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LogsController extends Controller
{
    private $logTable;

    public function __construct()
    {
        if (empty(env('LOG_API_ACCESS_KEY'))) {
            throw new HttpException(
                500,
                'Logs read api access key not set. Please run: php artisan generate:log_api_access_key'
            );
        }
        if (!request()->hasHeader('Log-Api-Access-Key') || !Hash::check(request()->header('Log-Api-Access-Key'), env('LOG_API_ACCESS_KEY'))) {
            throw new HttpException(
                403,
                'Logs read api access denied. Invalid access key.'
            );
        }
        $this->logTable = new RequestLog();
    }

    /**
     * Read request resource consumption log
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function readRequestLogs(Request $request)
    {
        return $request->all();
    }

    /**
     * Clear cache for backend devs
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCache()
    {
        Artisan::call('cache:clear');
        return response()->json([
            'result' => true
        ]);
    }

    /**
     * Read or download laravel log file
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function readOrDownloadLogFile(Request $request)
    {
        $logDirectory = storage_path('logs');
        $files = array_filter(scandir($logDirectory), function ($fileName) {
            return !in_array($fileName, ['.', '..', '.gitignore']);
        });
        $request->validate([
            'download' => 'nullable|boolean|required_with:file',
            'file' => 'nullable|string|in:' . implode(',', $files)
        ]);
        if (!$request->has('file') || empty($request->get('file'))) {
            return response()->json([
                'present_log_files' => array_values($files)
            ]);
        }
        $logFile = $logDirectory . '/' . $request->file;
        if ($request->download) {
            return response()->download(
                $logFile,
                $request->file . '_' . env('APP_ENV') . 'Environment_' . now()->format('Ymd_Hi_T') . '.log'
            );
        } else {
            return response()->json([
                'laravel_log_data' => file_get_contents($logFile)
            ]);
        }
    }
}
