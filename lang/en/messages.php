<?php

return [
    'user_created' => 'User created successfully.',
    'email' => [
        'subjects' => [
            'verify_email' => 'Please verify your email.',
            'reset_password' => 'Token to reset password.',
            'password_reset' => 'Password Reset.'
        ]
    ],
    'verification_email_sent' => 'Verification email sent.',
    'email_verified' => 'Email verified, welcome ... ',
    'logged_out' => 'Successfully logged out.',
    'token_generated' => 'Token generated successfully, please check your email.',
    'password_reset' => 'Password reset successfully.',
    'errors' => [
        'invalid_request_payload' => 'Invalid request payload.',
        'invalid_token' => 'Invalid token!',
        'invalid_locale' => 'Provided locale not available.',
        'invalid_credentials' => 'Invalid credentials!',
        'email_already_verified' => 'Email is already verified.',
        'validation_config_absent' => 'Access denied. Validation configuration not available!',
        'value_must_be_array_when_operator_in' => 'Value must be an array when <in> or <not in> operator is in use.',
        'log_access_key_not_set' => 'Logs read api access key not set. Please run: php artisan generate:log_api_access_key',
        'invalid_log_access_key' => 'Logs read api access denied. Invalid access key.',
        'verify_email_first' => 'Please verify your account email to continue.',
        'old_new_password_same' => 'The new & old passwords must be different.'
    ]
];