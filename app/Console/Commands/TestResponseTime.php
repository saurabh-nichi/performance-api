<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Traits\Miscellaneous;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Octane\Facades\Octane;

class TestResponseTime extends Command
{
    use Miscellaneous;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-response-time {maxSample} {memoryLimit?} {--useConcurrency}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for testing performance.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        print('Initiating ... ');
        app()->setLocale('en');
        set_time_limit(24 * 3600); // 24 hours
        $availableRam = $this->getAvailableRAM();
        if ($this->argument('memoryLimit')) {
            if (!is_numeric($this->argument('memoryLimit')) || $this->argument('memoryLimit') > 99 || $this->argument('memoryLimit') < 1) {
                print('Invalid memory limit set.' . PHP_EOL);
                print('memoryLimit: MUST BE INTEGER BETWEEN (1, 99) -> IT IS THE PERCENTAGE OF FREE RAM AVAILABLE IN SYSTEM CURRENTLY (' . number_format($availableRam) . ' MB). Re-running ... ' . PHP_EOL);
                list($correctMemoryLimit) = $this->readInputFromCli(1, ['Enter correct value: '], function ($val) {
                    return is_numeric($val) && $val > 1 && $val < 99;
                }, null, true);
                Artisan::call("app:test-response-time {$this->argument('maxSample')} {$correctMemoryLimit}");
            }
            $memoryLimit = (int)($this->argument('memoryLimit') * $availableRam / 100);
            list($confirm) = $this->readInputFromCli(1, ['Set memory limit to: ' . $this->argument('memoryLimit') . '% of free RAM (' . number_format($memoryLimit) . ' MB) ? (Y/N): ']);
            if (!(strtolower($confirm) == 'y' || strtolower($confirm) == 'yes')) {
                print('Operation aborted.' . PHP_EOL);
                return 0;
            }
        } else {
            $memoryLimit = (int)50 * $availableRam / 100;
        }
        ini_set('memory_limit', "{$memoryLimit}M");
        print('Done. Memory limit: ' . number_format(str_replace('M', '', ini_get('memory_limit'))) . ' MB. Max execution time: ' . number_format(ini_get('max_execution_time')) . ' seconds.' . PHP_EOL);
        print('Getting pre-requesite values ... ');
        $runStart = [
            'time' => now(),
            'memory' => memory_get_usage(true)
        ];
        $diff = [];
        $totalUserCount = DB::table('users')->count();
        $idMin = DB::table('users')->select('id')->orderBy('id')->limit(1)->first()->id;
        $idMax = DB::table('users')->select('id')->orderByDesc('id')->limit(1)->first()->id;
        $primes = $this->findPrimesBetween($idMin, $idMax);
        print('Done.' . PHP_EOL);
        print('----------------------------------------------------------------------------------------------' . PHP_EOL);
        for ($i = 0; $i < $this->argument('maxSample'); $i++) {
            print('Running ....   Sample: ' . number_format($i + 1) . ' / ' . number_format($this->argument('maxSample')) . ' ... ');
            $start = [
                'time' => time(),
                'memory' => memory_get_usage(true)
            ];
            if ($this->option('useConcurrency')) {
                [$users_withPrimeIds, $users_withVowelsInEmail] = Octane::concurrently([
                    fn () => User::all()->filter(function ($user) use ($primes) {
                        return in_array($user->id, $primes);
                    }),
                    fn () => User::all()->filter(function ($user) {
                        return Str::contains(Str::before($user->email, '@'), ['a', 'e', 'i', 'o', 'u']);
                    })
                ]);
            } else {
                $users_withPrimeIds = User::all()->filter(function ($user) use ($primes) {
                    return in_array($user->id, $primes);
                });
                $users_withVowelsInEmail = User::all()->filter(function ($user) {
                    return Str::contains(Str::before($user->email, '@'), ['a', 'e', 'i', 'o', 'u']);
                });
            }
            $end = [
                'time' => time(),
                'memory' => memory_get_usage(true)
            ];
            $counts = [
                'prime_ids' => $users_withPrimeIds->count(),
                'vowels_in_email' => $users_withVowelsInEmail->count()
            ];
            $users_withPrimeIds = $users_withVowelsInEmail = null;
            print('Complete.' . PHP_EOL);
            $took = [
                'time' => $end['time'] - $start['time'], // in seconds
                'memory' => $end['memory'] - $start['memory'] // in bytes
            ];
            $diff[$i] = $took;
            print('Users with prime ids: ' . number_format($counts['prime_ids']) . '. Users with vowels in email: ' . number_format($counts['vowels_in_email']) . '. Total users: ' . number_format($totalUserCount) . PHP_EOL);
            print(PHP_EOL . 'Memory used: ' . number_format($took['memory'] / (1024 * 1024), 7) . ' MB' . PHP_EOL);
            //--------------------------------------------|-memory used in bytes-----KB-----MB----
            print('Time taken: ' . number_format($took['time']) . ' seconds' . PHP_EOL);
            print('----------------------------------------------------------------------------------------------' . PHP_EOL);
        }
        $diff['memory'] = array_column($diff, 'memory');
        $diff['time'] = array_column($diff, 'time');
        $avg = [
            'memory' => $this->calculateAverage($diff['memory']),
            'time' => $this->calculateAverage($diff['time'])
        ];
        print('Average memory used: ' . number_format($avg['memory'] / (1024 * 1024)) . ' MB' . PHP_EOL);
        //-------------------------------------------|-memory used in bytes---KB-----MB----
        print('Average time taken: ' . number_format($avg['time'], 2) . ' seconds' . PHP_EOL);
        print('----------------------------------------------------------------------------------------------' . PHP_EOL);
        $runTook = [
            'time' => str_replace([' before', ' after'], '', now()->diffForHumans($runStart['time'])),
            'memory' => memory_get_usage(true) - $runStart['memory'] / (1024 * 1024)
            //----------------------------|-memory used in bytes--------------------KB-----MB----
        ];
        print('Total memory used: ' . number_format($runTook['memory'] / (1024 * 1024), 7) . ' MB');
        //-----------------------------------------|-memory used in bytes-------KB-----MB----
        print('Total time taken: ' . $runTook['time'] . PHP_EOL);
        print('----------------------------------------------------------------------------------------------' . PHP_EOL);
        return 1;
    }
}
