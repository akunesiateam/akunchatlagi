<?php

namespace App\Console\Commands;

use App\Services\SystemOptimizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupHorizonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:setup-supervisor
                          {--user=www-data : The web server user}
                          {--path= : Custom project path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate supervisor configuration for Laravel Horizon';

    protected SystemOptimizationService $optimizationService;

    public function __construct()
    {
        parent::__construct();
        $this->optimizationService = new SystemOptimizationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if Redis is available
        $redisStatus = $this->optimizationService->checkRedisAvailability();

        if (! $redisStatus['available']) {
            $this->error('Redis is not available: '.$redisStatus['error']);
            $this->info('Please install and configure Redis before setting up Horizon.');

            return 1;
        }

        // Check if queue driver is Redis
        $queueDriver = env('QUEUE_CONNECTION', 'database');
        if ($queueDriver !== 'redis') {
            $this->warn("Current queue driver is '{$queueDriver}', not 'redis'.");
            $this->info('You can change this in Advanced Optimization Settings or update QUEUE_CONNECTION in .env');
        }

        $webUser = $this->option('user');
        $projectPath = $this->option('path') ?: base_path();

        $this->info('Generating Supervisor configuration for Laravel Horizon...');

        // Read template
        $templatePath = base_path('horizon-supervisor.conf');
        if (! File::exists($templatePath)) {
            $this->error('Supervisor template not found at: '.$templatePath);

            return 1;
        }

        $template = File::get($templatePath);

        // Replace placeholders
        $config = str_replace([
            '{{PROJECT_PATH}}',
            '{{WEB_USER}}',
        ], [
            $projectPath,
            $webUser,
        ], $template);

        // Generate output filename
        $outputFile = $projectPath.'/whatsmark-horizon-supervisor.conf';
        File::put($outputFile, $config);

        $this->info('✅ Supervisor configuration generated successfully!');
        $this->line('');
        $this->info('📄 Configuration file: '.$outputFile);
        $this->line('');

        $this->info('📋 Next steps:');
        $this->line('1. Copy the configuration file to supervisor directory:');
        $this->line('   sudo cp '.$outputFile.' /etc/supervisor/conf.d/whatsmark-horizon.conf');
        $this->line('');
        $this->line('2. Update supervisor:');
        $this->line('   sudo supervisorctl reread');
        $this->line('   sudo supervisorctl update');
        $this->line('');
        $this->line('3. Start Horizon worker:');
        $this->line('   sudo supervisorctl start whatsmark-horizon:*');
        $this->line('');
        $this->line('4. Check status:');
        $this->line('   sudo supervisorctl status whatsmark-horizon:*');
        $this->line('');

        if ($queueDriver === 'redis') {
            $this->info('✅ Redis queue is configured. Horizon will work properly.');
        } else {
            $this->warn('⚠️  Current queue driver is not Redis. Update QUEUE_CONNECTION=redis in .env for Horizon to work.');
        }

        $this->line('');
        $this->info('🌐 Access Horizon dashboard at: '.url('/horizon'));

        return 0;
    }
}
