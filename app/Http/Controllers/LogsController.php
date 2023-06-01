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
        $logFields = 'log_id,request,method,body,headers,peak_memory_use,time_taken,status,request_completed_on,request_path,result,response_code';
        $allowedRequestMethods = 'get,post,put,patch,delete,copy';
        $allowedSqlSearchOperators = '=,!=,like,>,<,<>,>=,=>,<=,=<';
        $deviceHeader = 'user-agent';
        $mobileDevicesRegex = '/Android|webOS|iPhone|iPad|iPod|iOS|BlackBerry|IEMobile|Opera Mini/i';
        $keysOrder = [
            'log_id', 'request', 'response_code', 'result', 'request_path', 'method', 'body', 'headers', 'status', 'peak_memory_use', 'time_taken',
            'request_completed_on', 'created_at'
        ];
        $jsonFields = ['body', 'headers', 'status'];
        $browserList = ['Mozilla', 'Chrome', 'Safari', 'Opera'];
        $payload = $request->validate([
            'request' => 'nullable|string',
            'request_match_operator' => 'nullable|string|in:=,!=,<>,>,<,=>,>=,<=,=<,like,not like',
            'exclude_request' => 'nullable|string',
            'method' => 'nullable|string|in:' . $allowedRequestMethods . ',' . strtoupper($allowedRequestMethods),
            'fields' => 'nullable|array|min:1',
            'fields.*' => 'required|string|in:' . $logFields,
            'sort' => 'nullable|array|size:2',
            'sort.0' => 'string|in:' . $logFields,
            'sort.1' => 'string|in:asc,desc',
            'status' => 'nullable|string|in:success,failure',
            'status_code' => 'nullable|integer',
            'exclude_v1' => 'nullable|boolean',
            'request_source' => 'nullable|string|in:web,mobile,android,ios',
            'paginate' => 'required|array',
            'paginate.per_page' => 'required|integer|min:1|max:1000',
            'paginate.page' => 'required|integer|min:1',
            'body_search' => 'nullable|array',
            'body_search.*.key' => 'required|string',
            'body_search.*.value' => 'required|string',
            'log_column_search' => 'nullable|array',
            'log_column_search.*.key' => 'required|string|in:' . $logFields,
            'log_column_search.*.value' => 'required|string',
            'log_column_search.*.operator' => 'required|string|in:' . $allowedSqlSearchOperators,
            'sendPaginationData' => 'nullable|boolean'
        ]);
        if (isset($payload['fields'])) {
            $this->logTable->select(array_unique(array_merge(
                ['id as log_id', 'peak_memory_use', 'time_taken', 'headers'],
                $payload['fields']
            )));
            $jsonFields = array_intersect($payload['fields'], $jsonFields);
        }
        if (isset($payload['status'])) {
            $this->logTable->where('result', $payload['status']);
        }
        if (isset($payload['status_code'])) {
            $this->logTable->where('response_code', $payload['status_code']);
        }
        if (array_key_exists('exclude_v1', $payload) && $payload['exclude_v1']) {
            $this->logTable->where('request', 'not like', 'api/v1/%');
        }
        if (isset($payload['request'])) {
            $operator = '=';
            if (isset($payload['request_match_operator'])) {
                $operator = $payload['request_match_operator'];
            }
            $this->logTable->where('request', $operator, $payload['request']);
        }
        if (isset($payload['exclude_request'])) {
            $excludePaths = array_map('trim', explode(',', $payload['exclude_request']));
            foreach ($excludePaths as $path) {
                $this->logTable->where('request', 'not like', $path);
            }
        }
        if (isset($payload['method'])) {
            $this->logTable->where('method', $payload['method']);
        }
        if (isset($payload['request_source'])) {
            // TODO: add query to filter mobile & web requests based on payload
        }
        if (isset($payload['body_search'])) {
            foreach ($payload['body_search'] as $bodySearchParams) {
                $this->logTable->where('body', 'like', '%"' . $bodySearchParams['key'] . '":"' . $bodySearchParams['value'] . '"%');
            }
        }
        if (isset($payload['log_column_search'])) {
            foreach ($request->get('log_column_search') as $logColumnSearchParams) {
                if ($logColumnSearchParams['key'] == 'log_id') {
                    $logColumnSearchParams['key'] = 'id';
                }
                $this->logTable->where($logColumnSearchParams['key'], $logColumnSearchParams['operator'], $logColumnSearchParams['value']);
            }
        }
        if (isset($payload['sort'])) {
            list($column, $sortOrder) = $payload['sort'];
            $this->logTable->orderBy($column, $sortOrder);
        };
        $totalLogCount = $this->logTable->count();
        $logData = $this->logTable->offset(($request->paginate['page'] - 1) * $request->paginate['per_page'])
            ->limit($request->paginate['per_page']);
        $queryString = vsprintf(str_replace('?', "'%s'", $logData->toSql()), $logData->getBindings());
        $logData = $logData->get()
            ->map(function ($logEntry) use ($keysOrder, $jsonFields, $payload, $deviceHeader, $mobileDevicesRegex, $browserList) {
                foreach ($jsonFields as $field) {
                    $logEntry->$field = isset($logEntry->$field) ? json_decode($logEntry->$field) : null;
                }
                $logEntry->peak_memory_use .= ' MB';
                $logEntry->time_taken .= ' second(s)';
                $logEntry->headers = is_string($logEntry->headers) ? json_decode($logEntry->headers) : $logEntry->headers;
                if (preg_match($mobileDevicesRegex, $source = reset($logEntry->headers->$deviceHeader))) {
                    $logEntry->request_device = 'Mobile Device';
                    $logEntry->request_source = Str::contains($source, $browserList) ? 'Web' : $source;
                } else {
                    $logEntry->request_device = 'Computer';
                    $logEntry->request_source = 'Web';
                }
                $logEntry->user_agent_value = $source;
                $logEntry = in_array('headers', $payload['fields']) ? (array)$logEntry : Arr::except((array)$logEntry, 'headers');
                uksort($logEntry, function ($col1, $col2) use ($keysOrder) {
                    return array_search($col1, $keysOrder) - array_search($col2, $keysOrder);
                });
                return $logEntry;
            });
        $totalPages = ceil($totalLogCount / $request->paginate['per_page']);
        $response = [
            'status' => 'Displaying ' . number_format($logData->count()) . ' of ' . number_format($totalLogCount) . ' result(s). Page: ' . number_format($request->paginate['page']) . ' of ' . number_format($totalPages) . '. Memory limit: ' . ini_get('memory_limit'),
            'logs' => $logData,
            'search_query' => $queryString
        ];
        if ($request->sendPaginationData) {
            $response['pagination'] = [
                'total' => $totalLogCount,
                'current_page' => (int)$request->paginate['page'],
                'total_pages' => $totalPages
            ];
        }
        return response()->json($response);
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
