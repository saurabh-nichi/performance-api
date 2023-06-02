# performance-api

<p><pre>Laravel Framework <font color="#26A269">10.13.1</font></pre></p>

A <a href="https://laravel.com/docs/10.x" target="_blank">laravel</a> api optimised for performance, works in tandem with <a href="https://laravel.com/docs/10.x/octane#main-content" target="_blank">laravel/octane</a> and <a href="https://laravel.com/docs/10.x/queues#main-content" target="_blank">queues</a>.

<h3>Minimum Requirements:</h3>
OS: LINUX<br>
PHP Version: 8.2<br>
RAM: 4 GB<br>
Php's swoole library must be installed.<br>

<h3>Installation:</h3>
<ol>
<li>Verify <strong><a href="https://nodejs.org/" target="_blank">Node</a></strong> is installed.</li>
<li>
Verify PHP package <strong><a href="https://openswoole.com/" target="_blank">Swoole</a></strong> is installed in system.<br>
(Code: <code>sudo apt install php8.2-swoole</code>)
</li>
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
(<strong>--watch</strong> parameter is optional & watches for changes in laravel app installtion directory, however it consumes more memory.)
</li>
<li>
In another terminal instance, run: <code>php artisan queue:work</code> and keep it running.<br>
(Everytime there is some change in queue related files, this command has to be restarted.)<br>
(Alternatively, <code>php artisan queue:listen</code> can be used to monitor file changes, however it consumes more memory.)
</li>
</ol>

<h3>Usage Instructions:</h3>
<p>
To translate, use function <code>translate()</code>, works in similar way as <code>trans()</code> laravel function.<br>
The function is autoloaded through <code>composer.json</code> and can be found in file: <code>app/Helpers/custom-functions.php</code>.
</p>

<h4>Feel free to suggest changes. 😌</h4>