<?php

return [
    'user_created' => 'ユーザーが正常に作成されました。',
    'email' => [
        'subjects' => [
            'verify_email' => 'メールアドレスをご確認ください。'
        ]
    ],
    'verification_email_sent' => '確認メールが送信されました。',
    'email_verified' => 'メールアドレスが確認されました。ようこそ...',
    'logged_out' => '正常にログアウトされました。',
    'errors' => [
        'invalid_request_payload' => 'リクエストのペイロードが無効です。',
        'invalid_token' => '無効なトークン！',
        'invalid_locale' => '指定されたロケールは利用できません。',
        'invalid_credentials' => '無効な資格情報！',
        'email_already_verified' => 'メールアドレスはすでに認証されています。',
        'validation_config_absent' => 'アクセス拒否。 検証構成は使用できません!',
        'value_must_be_array_when_operator_in' => '<in> または <not in> 演算子を使用する場合、値は配列である必要があります。',
        'log_access_key_not_set' => 'ログ読み取り API アクセス キーが設定されていません。 「php artisan generate:log_api_access_key」を実行してください。',
        'invalid_log_access_key' => 'ログの読み取り API アクセスが拒否されました。 アクセスキーが無効です。'
    ]
];