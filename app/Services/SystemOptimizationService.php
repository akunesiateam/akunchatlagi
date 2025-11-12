<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * System Optimization Service
 *
 * Handles system-wide optimization settings including cache, session, and queue drivers.
 * Includes Redis availability detection and .env file management.
 *
 * @author WhatsApp SaaS Team
 *
 * @since 2.0.0
 */
class SystemOptimizationService
{
    /**
     * Available cache drivers
     */
    public const CACHE_DRIVERS = [
        'file' => 'File System',
        'database' => 'Database',
        'redis' => 'Redis (Requires Redis Server)',
    ];

    /**
     * Available queue drivers
     */
    public const QUEUE_DRIVERS = [
        'sync' => 'Synchronous (No Queue)',
        'database' => 'Database Queue',
        'redis' => 'Redis Queue (Requires Redis Server)',
    ];

    /**
     * Check if Redis is available and properly configured
     *
     * @return array Redis status information
     */
    public function checkRedisAvailability(): array
    {
        $status = [
            'available' => false,
            'connected' => false,
            'error' => null,
            'details' => [],
        ];

        try {
            // Check if Redis PHP extension is loaded
            if (! extension_loaded('redis') && ! extension_loaded('phpredis')) {
                throw new Exception('Redis PHP extension is not installed. Please install php-redis extension.');
            }

            $status['details']['php_extension'] = true;

            // Check Redis configuration in .env
            $redisHost = env('REDIS_HOST', '127.0.0.1');
            $redisPort = env('REDIS_PORT', 6379);
            $redisPassword = env('REDIS_PASSWORD', null);

            if (empty($redisHost)) {
                throw new Exception('REDIS_HOST is not configured in .env file.');
            }

            $status['details']['config_present'] = true;

            // Test Redis connection
            $redis = Redis::connection();
            $pingResult = $redis->ping();

            // Handle different ping response formats (phpredis vs predis)
            $isPingSuccessful = false;
            if ($pingResult === true || $pingResult === 'PONG') {
                $isPingSuccessful = true;
            } elseif (is_object($pingResult) && method_exists($pingResult, '__toString')) {
                $isPingSuccessful = (string) $pingResult === 'PONG';
            } elseif (is_object($pingResult) && isset($pingResult->payload)) {
                $isPingSuccessful = $pingResult->payload === 'PONG';
            }

            if (! $isPingSuccessful) {
                throw new Exception('Redis server is not responding to ping. Response: '.json_encode($pingResult));
            }

            $status['details']['ping_successful'] = true;

            // Test basic Redis operations
            $testKey = 'system_optimization_test_'.time();
            $testValue = 'test_value_'.Str::random(10);

            Redis::set($testKey, $testValue, 'EX', 60); // Set with 60s expiry
            $retrievedValue = Redis::get($testKey);

            if ($retrievedValue !== $testValue) {
                throw new Exception('Redis read/write test failed.');
            }

            Redis::del($testKey); // Cleanup
            $status['details']['read_write_test'] = true;

            // Test Redis cache driver
            Cache::store('redis')->put('system_test_cache', 'cache_test_value', 60);
            $cacheValue = Cache::store('redis')->get('system_test_cache');

            if ($cacheValue !== 'cache_test_value') {
                throw new Exception('Redis cache driver test failed.');
            }

            Cache::store('redis')->forget('system_test_cache');
            $status['details']['cache_driver_test'] = true;

            // Get Redis info
            $info = Redis::info();

            // Parse Redis info sections (for Predis client)
            if (is_array($info) && isset($info['Server'])) {
                $status['details']['redis_version'] = $info['Server']['redis_version'] ?? 'Unknown';
            } elseif (is_array($info) && isset($info['redis_version'])) {
                // Direct format (for phpredis)
                $status['details']['redis_version'] = $info['redis_version'];
            } else {
                $status['details']['redis_version'] = 'Unknown';
            }

            if (is_array($info) && isset($info['Memory'])) {
                $status['details']['used_memory'] = $info['Memory']['used_memory_human'] ?? 'Unknown';
            } elseif (is_array($info) && isset($info['used_memory_human'])) {
                $status['details']['used_memory'] = $info['used_memory_human'];
            } else {
                $status['details']['used_memory'] = 'Unknown';
            }

            if (is_array($info) && isset($info['Clients'])) {
                $status['details']['connected_clients'] = $info['Clients']['connected_clients'] ?? 'Unknown';
            } elseif (is_array($info) && isset($info['connected_clients'])) {
                $status['details']['connected_clients'] = $info['connected_clients'];
            } else {
                $status['details']['connected_clients'] = 'Unknown';
            }

            $status['available'] = true;
            $status['connected'] = true;

        } catch (Exception $e) {
            $status['error'] = $e->getMessage();
            $status['available'] = false;
            $status['connected'] = false;
        }

        return $status;
    }

    /**
     * Get current system optimization settings
     *
     * @return array Current settings
     */
    public function getCurrentSettings(): array
    {
        return [
            'cache_driver' => env('CACHE_STORE', 'file'),
            'queue_driver' => env('QUEUE_CONNECTION', 'database'),
        ];
    }

    /**
     * Validate optimization settings
     *
     * @param  array  $settings  Settings to validate
     * @return array Validation result
     */
    public function validateSettings(array $settings): array
    {
        $errors = [];
        $warnings = [];

        // Validate cache driver
        if (isset($settings['cache_driver'])) {
            if (! array_key_exists($settings['cache_driver'], self::CACHE_DRIVERS)) {
                $errors['cache_driver'] = 'Invalid cache driver selected.';
            } elseif ($settings['cache_driver'] === 'redis') {
                $redisStatus = $this->checkRedisAvailability();
                if (! $redisStatus['available']) {
                    $errors['cache_driver'] = 'Redis is not available: '.$redisStatus['error'];
                }
            }
        }

        // Validate queue driver
        if (isset($settings['queue_driver'])) {
            if (! array_key_exists($settings['queue_driver'], self::QUEUE_DRIVERS)) {
                $errors['queue_driver'] = 'Invalid queue driver selected.';
            } elseif ($settings['queue_driver'] === 'redis') {
                $redisStatus = $this->checkRedisAvailability();
                if (! $redisStatus['available']) {
                    $errors['queue_driver'] = 'Redis is not available: '.$redisStatus['error'];
                }
            }
        }

        // Performance warnings
        if (isset($settings['cache_driver']) && $settings['cache_driver'] === 'database') {
            $warnings[] = 'Database cache may be slower than Redis for high-traffic applications.';
        }

        if (isset($settings['queue_driver']) && $settings['queue_driver'] === 'sync') {
            $warnings[] = 'Synchronous queue will block application responses for heavy operations.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Update .env file with new optimization settings
     *
     * @param  array  $settings  Settings to update
     * @return bool Success status
     */
    public function updateEnvironmentSettings(array $settings): bool
    {
        try {
            $envPath = base_path('.env');

            if (! File::exists($envPath)) {
                throw new Exception('.env file not found.');
            }

            $envContent = File::get($envPath);

            // Prepare env updates
            $updates = [];

            if (isset($settings['cache_driver'])) {
                $updates['CACHE_STORE'] = $settings['cache_driver'];
            }

            if (isset($settings['queue_driver'])) {
                $updates['QUEUE_CONNECTION'] = $settings['queue_driver'];
            }

            // Update env content
            foreach ($updates as $key => $value) {
                $pattern = "/^{$key}=.*$/m";
                $replacement = "{$key}={$value}";

                if (preg_match($pattern, $envContent)) {
                    $envContent = preg_replace($pattern, $replacement, $envContent);
                } else {
                    // Add new key if not exists
                    $envContent .= "\n{$replacement}";
                }
            }

            // Write updated content
            File::put($envPath, $envContent);

            // Clear config cache to reload new values
            try {
                Artisan::call('config:clear');
            } catch (Exception $e) {
                // Continue even if config:clear fails
            }

            return true;

        } catch (Exception $e) {
            throw new Exception('Failed to update .env file: '.$e->getMessage());
        }
    }

    /**
     * Check if Laravel Horizon should be available
     *
     * @return bool Whether Horizon should be shown
     */
    public function shouldShowHorizon(): bool
    {
        return env('QUEUE_CONNECTION') === 'redis' &&
               $this->checkRedisAvailability()['available'] &&
               class_exists('Laravel\Horizon\Horizon');
    }

    /**
     * Get system performance recommendations
     *
     * @return array Performance recommendations
     */
    public function getPerformanceRecommendations(): array
    {
        $recommendations = [];
        $redisStatus = $this->checkRedisAvailability();

        if ($redisStatus['available']) {
            $recommendations[] = [
                'type' => 'success',
                'title' => 'Redis Available',
                'message' => 'Consider using Redis for cache, session, and queue for better performance.',
                'action' => 'Use Redis drivers for optimal performance in production.',
            ];
        } else {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Redis Not Available',
                'message' => $redisStatus['error'],
                'action' => 'Install Redis server and PHP Redis extension for better performance.',
            ];
        }

        // Check current configuration
        $currentSettings = $this->getCurrentSettings();

        if ($currentSettings['cache_driver'] === 'file') {
            $recommendations[] = [
                'type' => 'info',
                'title' => 'File Cache in Use',
                'message' => 'File cache is suitable for small applications but may be slower for high traffic.',
                'action' => $redisStatus['available'] ? 'Consider switching to Redis cache.' : 'Consider database cache for better performance.',
            ];
        }

        if ($currentSettings['queue_driver'] === 'sync') {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Synchronous Queue',
                'message' => 'Sync queue processes jobs immediately, which may slow down responses.',
                'action' => 'Use database or Redis queue for better user experience.',
            ];
        }

        return $recommendations;
    }

    /**
     * Test database connectivity
     *
     * @return bool Database connection status
     */
    public function testDatabaseConnection(): bool
    {
        try {
            DB::select('SELECT 1');

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check queue worker status
     *
     * @return array Worker status information
     */
    public function checkQueueWorkerStatus(): array
    {
        $status = [
            'active' => false,
            'workers_count' => 0,
            'processes' => [],
            'supervisor_running' => false,
            'horizon_running' => false,
            'error' => null,
        ];

        try {
            // Check if supervisor is running
            $supervisorStatus = $this->checkSupervisorStatus();
            $status['supervisor_running'] = $supervisorStatus['running'];

            // For Redis queue, check Horizon
            if (env('QUEUE_CONNECTION') === 'redis' && class_exists('Laravel\Horizon\Horizon')) {
                $horizonStatus = $this->checkHorizonStatus();
                $status['horizon_running'] = $horizonStatus['running'];
                $status['active'] = $horizonStatus['running'];
                $status['workers_count'] = $horizonStatus['workers_count'];
                $status['processes'] = $horizonStatus['processes'];
            } else {
                // For database queue, check for queue:work processes
                $queueStatus = $this->checkQueueWorkProcesses();
                $status['active'] = $queueStatus['running'];
                $status['workers_count'] = $queueStatus['workers_count'];
                $status['processes'] = $queueStatus['processes'];
            }

        } catch (Exception $e) {
            $status['error'] = $e->getMessage();
        }

        return $status;
    }

    /**
     * Check if supervisor is running
     *
     * @return array Supervisor status
     */
    protected function checkSupervisorStatus(): array
    {
        $status = ['running' => false, 'processes' => []];

        try {
            // Use Process component for safer command execution
            $process = new Process(['which', 'supervisorctl']);
            $process->run();

            if (! $process->isSuccessful()) {
                return $status;
            }

            // Check supervisor status using Process
            $process = new Process(['supervisorctl', 'status']);
            $process->run();

            if ($process->isSuccessful()) {
                $status['running'] = true;

                // Validate and sanitize each line of output
                $validProcesses = [];
                $lines = explode("\n", trim($process->getOutput()));

                foreach ($lines as $line) {
                    $line = trim(preg_replace('/[\x00-\x1F\x7F]|\x1B\[[0-9;]*[a-zA-Z]/', '', $line));

                    // Validate line format (program_name STATUS [pid])
                    if (preg_match('/^[a-zA-Z0-9_\-:]+\s+(RUNNING|STOPPED|STARTING|BACKOFF|STOPPING|EXITED|FATAL|UNKNOWN)(\s+pid\s+\d+)?/', $line)) {
                        if (strlen($line) <= 255) {
                            $validProcesses[] = $line;
                        }
                    }
                }

                $status['processes'] = $validProcesses;
            }

        } catch (Exception $e) {
            // Log the error but don't expose details in the response
            Log::error('Error checking supervisor status: '.$e->getMessage());
        }

        return $status;
    }

    /**
     * Check Horizon status
     *
     * @return array Horizon status
     */
    protected function checkHorizonStatus(): array
    {
        $status = [
            'running' => false,
            'workers_count' => 0,
            'processes' => [],
            'debug' => [],
        ];

        try {
            // Method 1: Check via artisan horizon:status command
            $process = new Process(['php', base_path('artisan'), 'horizon:status']);
            $process->setTimeout(10);
            $process->run();

            $horizonOutput = trim($process->getOutput());
            $status['debug']['horizon_status_output'] = $horizonOutput;
            $status['debug']['horizon_status_exit_code'] = $process->getExitCode();

            // Horizon is running if exit code is 0 AND output contains "running"
            if ($process->getExitCode() === 0 || str_contains(strtolower($horizonOutput), 'horizon is running')) {
                $status['running'] = true;
                $status['debug']['method'] = 'artisan_command';

                // Try to get worker count from processes
                try {
                    $processCheck = new Process(['pgrep', '-f', 'artisan horizon']);
                    $processCheck->run();
                    if ($processCheck->isSuccessful()) {
                        $processes = array_filter(explode("\n", trim($processCheck->getOutput())));
                        $status['workers_count'] = max(1, count($processes)); // At least 1 if horizon is running
                        $status['processes'] = $processes;
                    }
                } catch (Exception $e) {
                    $status['workers_count'] = 1; // Default to 1 if we can't count processes
                }
            } else {
                // Method 2: Fallback - check for horizon processes directly
                $processCheck = new Process(['pgrep', '-f', 'artisan horizon']);
                $processCheck->run();
                if ($processCheck->isSuccessful()) {
                    $processes = array_filter(explode("\n", trim($processCheck->getOutput())));
                    if (count($processes) > 0) {
                        $status['running'] = true;
                        $status['workers_count'] = count($processes);
                        $status['processes'] = $processes;
                        $status['debug']['method'] = 'process_check';
                    }
                }
            }

            // Method 3: Alternative - Check via Redis if available
            if (! $status['running'] && $this->checkRedisAvailability()['available']) {
                try {
                    // Check for Horizon master processes in Redis
                    $masters = Redis::get('horizon:masters');
                    if ($masters) {
                        $mastersData = json_decode($masters, true);
                        if (is_array($mastersData) && ! empty($mastersData)) {
                            $status['running'] = true;
                            $status['workers_count'] = count($mastersData);
                            $status['debug']['method'] = 'redis_masters';
                        }
                    }

                    // Also check supervisors
                    if (! $status['running']) {
                        $supervisors = Redis::smembers('horizon:supervisors');
                        if (! empty($supervisors)) {
                            $status['running'] = true;
                            $status['workers_count'] = count($supervisors);
                            $status['debug']['method'] = 'redis_supervisors';
                        }
                    }
                } catch (Exception $redisE) {
                    $status['debug']['redis_error'] = $redisE->getMessage();
                }
            }

        } catch (Exception $e) {
            $status['debug']['error'] = $e->getMessage();

            // Final fallback - direct process check
            try {
                $processCheck = new Process(['pgrep', '-f', 'artisan horizon']);
                $processCheck->run();
                if ($processCheck->isSuccessful()) {
                    $processes = array_filter(explode("\n", trim($processCheck->getOutput())));
                    $status['running'] = count($processes) > 0;
                    $status['workers_count'] = count($processes);
                    $status['processes'] = $processes;
                    $status['debug']['method'] = 'fallback_process_check';
                }
            } catch (Exception $e2) {
                $status['debug']['fallback_error'] = $e2->getMessage();
            }
        }

        return $status;
    }

    /**
     * Check queue:work processes
     *
     * @return array Queue work status
     */
    protected function checkQueueWorkProcesses(): array
    {
        $status = [
            'running' => false,
            'workers_count' => 0,
            'processes' => [],
        ];

        try {
            // Use Process component to safely get process list
            $process = new Process(['ps', 'aux']);
            $process->run();

            if (! $process->isSuccessful()) {
                return $status;
            }

            $lines = explode("\n", trim($process->getOutput()));
            $validProcesses = [];

            foreach ($lines as $line) {
                // Remove control characters and ANSI escape sequences
                $line = trim(preg_replace('/[\x00-\x1F\x7F]|\x1B\[[0-9;]*[a-zA-Z]/', '', $line));

                // Skip if line is too long or doesn't match ps aux format
                if (strlen($line) > 255 || ! preg_match('/^[\w-]+\s+\d+\s+\d+\.*\d*\s+\d+\.*\d*/', $line)) {
                    continue;
                }

                // Only keep lines containing queue:work but not grep
                if (strpos($line, 'queue:work') !== false && strpos($line, 'grep') === false) {
                    $validProcesses[] = $line;
                }
            }

            $status['processes'] = $validProcesses;
            $status['workers_count'] = count($validProcesses);
            $status['running'] = $status['workers_count'] > 0;

        } catch (Exception $e) {
            Log::error('Error checking queue workers: '.$e->getMessage());
        }

        return $status;
    }

    /**
     * Get supervisor installation and configuration guide
     *
     * @return array Installation guide steps
     */
    public function getSupervisorInstallationGuide(): array
    {
        $projectPath = base_path();
        $user = 'www-data'; // Default web server user

        return [
            'title' => 'Laravel Queue Worker Setup Guide',
            'description' => 'Follow these steps to set up queue workers for your application',
            'steps' => [
                [
                    'title' => '1. Install Supervisor',
                    'description' => 'Install supervisor package on your server',
                    'commands' => [
                        '# Ubuntu/Debian',
                        'sudo apt update',
                        'sudo apt install supervisor',
                        '',
                        '# CentOS/RHEL',
                        'sudo yum install supervisor',
                        // Or for newer versions:',
                        'sudo dnf install supervisor',
                    ],
                    'type' => 'command',
                ],
                [
                    'title' => '2. Create Supervisor Configuration',
                    'description' => 'Create a configuration file for your queue workers',
                    'commands' => [
                        'sudo nano /etc/supervisor/conf.d/whatsmark-horizon.conf',
                    ],
                    'config' => $this->generateSupervisorConfig($projectPath, $user),
                    'type' => 'config',
                ],
                [
                    'title' => '3. Update Supervisor',
                    'description' => 'Reload supervisor configuration and start workers',
                    'commands' => [
                        'sudo supervisorctl reread',
                        'sudo supervisorctl update',
                        'sudo supervisorctl start whatsmark-horizon:*',
                    ],
                    'type' => 'command',
                ],
                [
                    'title' => '4. Verify Workers',
                    'description' => 'Check if workers are running properly',
                    'commands' => [
                        'sudo supervisorctl status',
                        'php artisan queue:monitor',
                    ],
                    'type' => 'command',
                ],
                [
                    'title' => '5. Enable Auto-start (Optional)',
                    'description' => 'Enable supervisor to start automatically on boot',
                    'commands' => [
                        'sudo systemctl enable supervisor',
                        'sudo systemctl start supervisor',
                    ],
                    'type' => 'command',
                ],
            ],
            'troubleshooting' => [
                [
                    'issue' => 'Workers not starting',
                    'solutions' => [
                        'Check file permissions: sudo chown -R $user:$user '.$projectPath,
                        'Verify PHP path: which php',
                        'Check logs: sudo tail -f /var/log/supervisor/supervisord.log',
                    ],
                ],
                [
                    'issue' => 'Permission denied errors',
                    'solutions' => [
                        'Update user in supervisor config to your web server user',
                        'Common users: www-data, nginx, apache, ubuntu',
                        'Check with: ps aux | grep nginx (or apache)',
                    ],
                ],
                [
                    'issue' => 'Redis connection issues',
                    'solutions' => [
                        'Verify Redis is running: redis-cli ping',
                        'Check Redis configuration in .env file',
                        'Test Redis connection from application',
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate supervisor configuration content
     *
     * @param  string  $projectPath  Project root path
     * @param  string  $user  Web server user
     * @return string Configuration content
     *
     * @throws \InvalidArgumentException If path or user is invalid
     */
    protected function generateSupervisorConfig(string $projectPath, string $user): string
    {
        // Validate and normalize project path
        $realPath = realpath($projectPath);
        if (! $realPath || ! is_dir($realPath)) {
            throw new \InvalidArgumentException('Invalid project path provided');
        }
        $projectPath = rtrim($realPath, '/\\');

        // Validate user
        if (! preg_match('/^[a-z_][a-z0-9_-]{0,31}$/i', $user)) {
            throw new \InvalidArgumentException('Invalid user name provided');
        }

        // Sanitize paths for config
        $storagePath = $projectPath.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'logs';
        $artisanPath = $projectPath.DIRECTORY_SEPARATOR.'artisan';

        if (! is_dir($storagePath) || ! is_file($artisanPath)) {
            throw new \InvalidArgumentException('Invalid project structure - missing required paths');
        }

        $queueConnection = env('QUEUE_CONNECTION', 'database');
        $command = $queueConnection === 'redis' ? 'horizon' : 'queue:work --sleep=3 --tries=3';

        // Build config with escaped values
        return sprintf(
            '[program:whatsmark-horizon]
process_name=%%(program_name)s_%%(process_num)02d
command=php "%s" %s
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=%s
numprocs=1
redirect_stderr=true
stdout_logfile="%s/supervisor.log"
stdout_logfile_maxbytes=100MB
stdout_logfile_backups=3
stderr_logfile="%s/supervisor-error.log"
stderr_logfile_maxbytes=100MB
stderr_logfile_backups=3
stopwaitsecs=3600
environment=LARAVEL_ENV="production"',
            addslashes($artisanPath),
            $command,
            addslashes($user),
            addslashes($storagePath),
            addslashes($storagePath)
        );
    }

    /**
     * Get common supervisor management commands
     *
     * @return array Management commands
     */
    public function getSupervisorCommands(): array
    {
        return [
            'start' => 'sudo supervisorctl start whatsmark-horizon:*',
            'stop' => 'sudo supervisorctl stop whatsmark-horizon:*',
            'restart' => 'sudo supervisorctl restart whatsmark-horizon:*',
            'status' => 'sudo supervisorctl status',
            'reload' => 'sudo supervisorctl reread && sudo supervisorctl update',
            'logs' => 'sudo supervisorctl tail -f whatsmark-horizon',
        ];
    }
}
