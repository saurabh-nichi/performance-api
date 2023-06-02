# performance-api
A laravel api optimised for performance, works in tandem with laravel/octane and queue worker.

<h3>Minimum Requirements:</h3>
OS: LINUX<br>
PHP Version: 8.2<br>
RAM: 4 GB<br>
Php's swoole library must be installed.<br>

<h3>Installation:</h3>
<ol>
<li>Verify PHP package <strong>Swoole</strong> is installed in system.</li>
<li>Run command: <code>composer install</code></li>
<li>Run command: <code>npm install</code></li>
<li>Copy <strong>.env.example</strong> and create <strong>.env</strong> file
<li>Run command: <code>php artisan key:generate</code></li>
<li>Run command: <code>php artisan generate:log_api_access_key</code></li>
<li>Run command: <code>php artisan migrate</code></li>
<li>Run command: <code>php artisan octane:install</code> & select <strong>swoole</strong> on prompt</li>
<li>Run command: <code>php artisan passport:install</code></li>
<li>
Application is now ready, to serve run: <code>php artisan octane:start --watch</code> & keep it running.<br>
(--watch parameter is optional & watches for changes in laravel app installtion directory, however it consumes more memory.)
</li>
<li>
In another terminal instance, run: <code>php artisan queue:work</code> and keep it running.<br>
(Everytime there is some change in queue related files, this command has to be restarted.)
(Alternatively, <code>php artisan queue:listen</code> can be used to monitor file changes, however it consumes more memory.)
</li>
</ol>