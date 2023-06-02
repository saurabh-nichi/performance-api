<?php

$allowedMethods = 'GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS';

return [
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
    'read-request-logs' => [
        'POST' => [
            'paginate' => 'nullable|array|size:2',
            'paginate.page' => 'integer|min:1|required_with:paginate',
            'paginate.per_page' => 'integer|min:1|required_with:paginate',
            'route' => 'nullable|string|max:255',
            'method' => 'nullable|string|in:' . $allowedMethods,
            'successfull' => 'nullable|boolean',
            'status_code' => 'nullable|integer',
            'search' => 'nullable|array'
        ]
    ]
];
