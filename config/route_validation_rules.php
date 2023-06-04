<?php

$allowedMethods = 'GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS';

return [
    'ignore_routes' => [],
    'user' => [
        'POST' => [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6'
        ]
    ],
    'user/login' => [
        'POST' => [
            'email' => 'required|string|email|exists:users',
            'password' => 'required|string|min:6',
            'remember' => 'nullable|boolean'
        ]
    ],
    'user/verify-email/send' => [
        'POST' => []
    ],
    'user/verify-email/confirm' => [
        'POST' => [
            'token' => 'required|string|max:255'
        ]
    ],
    'user/logout' => [
        'POST' => []
    ],
    'user/reset-password' => [
        'POST' => [
            'email' => 'required|email|exists:users',
            'token' => 'nullable|string|size:8',
            'new_password' => 'string|min:6|required_with:token'
        ]
    ],
    'read-request-logs' => [
        'POST' => [
            'fields' => 'required|array',
            'fields.*' => 'required|string|validate_log_column',
            'route' => 'nullable|array',
            'route.*' => 'required|string|max:255',
            'ignore_route' => 'nullable|array',
            'ignore_route.*' => 'required|string|max:255',
            'method' => 'nullable|array',
            'method.*' => 'required|string|in:' . $allowedMethods,
            'status_code' => 'nullable|array',
            'status_code.*' => 'required|integer',
            'search' => 'nullable|array',
            'search.*' => 'required|array',
            'search.*.column' => 'required|string|validate_log_column',
            'search.*.operator' => 'required|string|in:=,!=,<>,>,>=,<,<=,like,not like,in',
            'search.*.value' => 'required',
            'search.*.successful' => 'nullable|in:yes,no,ignore',
            'sort' => 'nullable|array',
            'sort.*' => 'required|array',
            'sort.*.column' => 'required|string|validate_log_column',
            'sort.*.order' => 'required|in:asc,desc',
            'paginate' => 'nullable|array|size:2',
            'paginate.page' => 'integer|min:1|required_with:paginate',
            'paginate.per_page' => 'integer|min:1|required_with:paginate',
        ]
    ]
];
