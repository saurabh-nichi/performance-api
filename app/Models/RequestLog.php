<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class RequestLog extends Model
{
    use HasFactory;
    const UPDATED_AT = null;
    protected $fillable = [
        'payload', 'created_at', 'headers', 'id', 'method', 'peak_memory_use', 'request_completed_on', 'request_ipv4', 'request_ipv6', 'request_start_time', 'route', 'successful',
        'status_code', 'response_body', 'time_taken'
    ];

    public static function store(array &$data)
    {
        $data = Arr::only($data, (new self)->getFillable());
        foreach ($data as $column => $value) {
            if (!(is_int($value) || is_numeric($value) || is_string($value))) {
                $data[$column] = json_encode($value);
            }
        }
        return self::create($data);
    }
}
