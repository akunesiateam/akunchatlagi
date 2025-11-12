EditCorbital Module Manager - Installation and Usage Guide
This comprehensive guide will take you through installation, configuration, testing, and advanced usage of the Corbital Module Manager for Laravel.
Table of Contents

Installation
Manual Testing
Basic Usage
Working with Modules
Web Interface
Advanced Usage
Troubleshooting

Installation

1. Require the Package
bashcomposer require corbital/module-manager
2. Publish Configuration
bashphp artisan vendor:publish --provider="Corbital\ModuleManager\Providers\ModuleManagerServiceProvider" --tag="config"
This will create a config/modules.php file.
3. Run Migrations
bashphp artisan migrate
This creates the module_settings table for storing module-specific settings.
4. Set Up Module Directory
By default, modules are stored in the app/Modules directory. Make sure this directory exists:
bashmkdir -p app/Modules

Manual Testing
Let's walk through a complete test of the package functionality:

1. Create a Test Module
Create your first module:
bashphp artisan module:make TestModule
You should see the message: Module [TestModule] created successfully.
Check the structure:
bashls -la app/Modules/TestModule
You should see the following structure:
Config/
Console/
Database/
Http/
Models/
Providers/
resources/
Routes/
Tests/
TestModule.php
composer.json
module.json
README.md
2. Check Module List
bashphp artisan module:list
You should see your module listed as inactive.
3. Activate Module
bashphp artisan module:activate TestModule
You should see: Module [TestModule] activated successfully.
4. Test Deactivation
bashphp artisan module:deactivate TestModule
You should see: Module [TestModule] deactivated successfully.
5. Create Core Module
bashphp artisan module:make CoreModule --type=core
6. Try Core Module Operations
Activate it:
bashphp artisan module:activate CoreModule
Try to deactivate it (should fail as core modules can't be deactivated):
bashphp artisan module:deactivate CoreModule
You should see: Module [CoreModule] is a core module and cannot be deactivated.
7. Testing Module Dependencies
Create modules with dependencies:
bash# Create parent module first
php artisan module:make ParentModule
php artisan module:activate ParentModule

# Create dependent module

php artisan module:make ChildModule
Edit app/Modules/ChildModule/module.json and add dependency:
json"require": {
    "ParentModule": ">=1.0.0"
}
Activate child module:
bashphp artisan module:activate ChildModule
Try deactivating parent (should fail as child depends on it):
bashphp artisan module:deactivate ParentModule
8. Test Web Interface
Add the routes to your routes/web.php:
php// Ensure this is behind auth middleware in production
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/modules', [\Corbital\ModuleManager\Http\Controllers\ModuleController::class, 'index'])->name('modules.index');
    Route::get('/modules/upload', [\Corbital\ModuleManager\Http\Controllers\ModuleController::class, 'showUploadForm'])->name('modules.upload');
    Route::post('/modules/upload', [\Corbital\ModuleManager\Http\Controllers\ModuleController::class, 'upload'])->name('modules.upload.process');
    Route::get('/modules/{name}', [\Corbital\ModuleManager\Http\Controllers\ModuleController::class, 'show'])->name('modules.show');
    Route::post('/modules/{name}/activate', [\Corbital\ModuleManager\Http\Controllers\ModuleController::class, 'activate'])->name('modules.activate');
    Route::post('/modules/{name}/deactivate', [\Corbital\ModuleManager\Http\Controllers\ModuleController::class, 'deactivate'])->name('modules.deactivate');
    Route::delete('/modules/{name}', [\Corbital\ModuleManager\Http\Controllers\ModuleController::class, 'remove'])->name('modules.remove');
});
Visit /modules in your browser to access the web interface.

Basic Usage
Creating a Module
The basic command to create a new module:
bashphp artisan module:make ModuleName
Options:

--type=core: Create as a core module (cannot be deactivated or removed)
--type=addon: Create as an addon module (default)
--force: Overwrite if module already exists

Managing Modules via Artisan
bash# List all modules
php artisan module:list

# Activate a module

php artisan module:activate ModuleName

# Deactivate a module

php artisan module:deactivate ModuleName

# Remove a module

php artisan module:remove ModuleName

Working with Modules
Module Structure
Each module should follow this structure:
ModuleName/
├── module.json             # Module manifest file
├── Config/                 # Module configuration
├── Console/                # Console commands
├── Database/               # Migrations, seeders, and factories
├── Http/                   # Controllers, middleware, requests
├── Models/                 # Eloquent models
├── Providers/              # Service providers
├── resources/              # Views, language files, and assets
├── Routes/                 # Route definitions
└── Tests/                  # Module tests
Module Manifest
The module.json file contains metadata about your module:
json{
    "name": "MyModule",
    "alias": "my-module",
    "namespace": "App\\Modules\\MyModule\\",
    "provider": "App\\Modules\\MyModule\\Providers\\MyModuleServiceProvider",
    "author": "Your Name",
    "url": "<https://example.com>",
    "version": "1.0.0",
    "description": "Description of what the module does",
    "require": {
        "CoreModule": ">=1.0.0",
        "AnotherModule": "^2.0.0"
    },
    "conflicts": [
        "IncompatibleModule >=2.0.0"
    ],
    "type": "addon"
}
Module Service Provider
Each module has a service provider that bootstraps its components:
php<?php

namespace App\Modules\MyModule\Providers;

use Illuminate\Support\ServiceProvider;

class MyModuleServiceProvider extends ServiceProvider
{
    protected $moduleName = 'MyModule';

    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
        $this->registerRoutes();
    }

    public function register()
    {
        // Register module bindings
    }

    // Other methods...
}
Module Class
Each module should extend the base Module class:
php<?php

namespace App\Modules\MyModule;

use Corbital\ModuleManager\Support\Module;
use Corbital\ModuleManager\Facades\ModuleEvents;

class MyModule extends Module
{
    public function registerHooks()
    {
        // Register event hooks
        ModuleEvents::listen('content.rendered', function ($content) {
            return $content . ' [Modified by MyModule]';
        });
    }

    public function activate()
    {
        // Code to run during activation
    }

    public function deactivate()
    {
        // Code to run during deactivation
    }

    public function activated()
    {
        // Code to run after activation
    }

    public function deactivated()
    {
        // Code to run after deactivation
    }
}
Module Routes
Each module can define its own routes in Routes/web.php and Routes/api.php:
php<?php

use Illuminate\Support\Facades\Route;
use App\Modules\MyModule\Http\Controllers\MyModuleController;

Route::middleware('web')->group(function () {
    Route::prefix('my-module')->group(function () {
        Route::get('/', [MyModuleController::class, 'index'])->name('my-module.index');
    });
});
Module Views
Views should be placed in resources/views/ directory.
Usage in code:
php// Using helper
return module_view('MyModule', 'index');

// Or directly with namespace
return view('MyModule::index');
Module Migrations
Migrations should be placed in Database/Migrations/ directory.
They will be automatically loaded by the service provider.

### Automatic Migrations and Seeders

When you activate a module, the system can automatically run its migrations and seeders. This functionality is controlled by configuration settings:

```php
// In config/modules.php
'auto_migrations' => [
    'enabled' => true, // Whether to automatically run migrations on module activation
    'seed'    => true, // Whether to automatically run seeders after migrations
],
```

#### Via Command Line

When activating a module via the command line, you can control this behavior with options:

```bash
# Activate with automatic migrations and seeders (default behavior)
php artisan module:activate ModuleName

# Activate without running migrations
php artisan module:activate ModuleName --skip-migrations

# Activate with migrations but without seeders
php artisan module:activate ModuleName --skip-seeders
```

#### Directory Structure for Migrations & Seeders

The system will look for migrations in these directories:

- `Modules/ModuleName/Database/Migrations`
- `Modules/ModuleName/src/Database/Migrations`
- `Modules/ModuleName/database/migrations`

For seeders, it will look for these classes:

- `Modules\ModuleName\Database\Seeders\ModuleNameDatabaseSeeder`
- `Modules\ModuleName\Database\Seeders\DatabaseSeeder`
- `Modules\ModuleName\src\Database\Seeders\ModuleNameDatabaseSeeder`
- `Modules\ModuleName\src\Database\Seeders\DatabaseSeeder`
- `Modules\ModuleName\database\seeders\ModuleNameDatabaseSeeder`
- `Modules\ModuleName\database\seeders\DatabaseSeeder`
- `App\Modules\ModuleName\Database\Seeders\ModuleNameDatabaseSeeder`
- `App\Modules\ModuleName\Database\Seeders\DatabaseSeeder`

Web Interface
Accessing the Web Interface
Visit /modules to access the web interface (make sure you've added the routes as shown in Manual Testing).
Features

Module List

View all installed modules
Filter by type (core/addon) and status (active/inactive)
Search modules
Sort by different properties

Module Details

View detailed information about a module
See dependencies and conflicts
Activate/deactivate modules (if applicable)
Remove modules (if applicable)

Module Upload

Upload new modules as zip files
System will extract and install them

Upload Format
When uploading a module, it should be a zip file with the following structure:
ModuleName/
├── module.json             # Module manifest file
├── ... (other module files)
Or with the module contents directly in the root of the zip:
module.json                # Module manifest file
... (other module files)

Advanced Usage
Dependency Management
Version Constraints
You can use various version constraints in your module.json:
json"require": {
    "ExactVersion": "1.2.3",         // Exactly version 1.2.3
    "GreaterThan": ">1.2.3",         // Greater than 1.2.3
    "GreaterThanEqual": ">=1.2.3",   // Greater than or equal to 1.2.3
    "LessThan": "<1.2.3",            // Less than 1.2.3
    "LessThanEqual": "<=1.2.3",      // Less than or equal to 1.2.3
    "Range": ">=1.2.3 <2.0.0",       // Between 1.2.3 and 2.0.0 (not inclusive)
    "Caret": "^1.2.3",               // >=1.2.3 <2.0.0 (compatible with 1.x)
    "Tilde": "~1.2.3",               // >=1.2.3 <1.3.0 (patch updates only)
    "Wildcard": "1.2.*"              // >=1.2.0 <1.3.0 (any patch version)
}
Defining Conflicts
json"conflicts": [
    "IncompatibleModule >=2.0.0",    // Conflicts with version 2.0.0 or higher
    "AnotherModule"                  // Conflicts with any version
]
Events System
Registering Hooks
In your module class:
phppublic function registerHooks()
{
    // Simple event listener
    ModuleEvents::listen('content.rendered', function ($content) {
        return $content . ' [Modified by MyModule]';
    });

    // Event listener with priority (higher numbers run first)
    ModuleEvents::listen('content.rendered', function ($content) {
        return '[MyModule prefix] ' . $content;
    }, 20);
}
Firing Events
phpuse Corbital\ModuleManager\Facades\ModuleEvents;

// Fire an event with a single parameter
$modifiedContent = ModuleEvents::fire('content.rendered', $content);

// Fire an event with multiple parameters (returned as array)
list($modifiedContent, $metadata) = ModuleEvents::fire('content.rendered', [$content, $metadata]);

// Alternative methods
$modifiedContent = ModuleEvents::filter('content.rendered', $content);  // For filter-style events
ModuleEvents::action('module.initialized');  // For action-style events (no return value needed)
Module Settings
You can store module-specific settings in the database:
php// In your module service provider or controller
use Illuminate\Support\Facades\DB;

// Save a setting
DB::table('module_settings')->updateOrInsert(
    ['module_name' => 'MyModule', 'key' => 'setting_key'],
    ['value' => 'setting_value']
);

// Retrieve a setting
$setting = DB::table('module_settings')
    ->where('module_name', 'MyModule')
    ->where('key', 'setting_key')
    ->value('value');
Helper Functions
The package provides these helper functions:
php// Get the path to a module directory
module_path('ModuleName');
module_path('ModuleName', 'Config/config.php');

// Get module configuration value
module_config('ModuleName', 'key', 'default');

// Render module view
module_view('ModuleName', 'view-name', ['data' => 'value']);

// Check if module exists
module_exists('ModuleName');

// Check if module is enabled/disabled
module_enabled('ModuleName');
module_disabled('ModuleName');

Troubleshooting
Common Issues
Module Not Found After Upload

Ensure the module.json file is in the root of your ZIP
Make sure the module name in module.json matches the directory structure

Cannot Activate Module

Check for missing dependencies
Look for version constraint issues
Verify there are no conflicting modules active

Module Functionality Not Working

Verify the module is properly activated
Check the Laravel log for errors
Ensure all required hooks are registered

404 Errors When Accessing Module Routes

Make sure the route is defined correctly in the module's routes file
Check that the module is active
Ensure the controller exists at the specified namespace
Verify middleware is configured correctly

Logging
Check Laravel logs for module-related errors:
bashtail -f storage/logs/laravel.log
Look for entries with these prefixes:

[Module Manager]
[MyModule] (where MyModule is your module name)

Debugging Tips

Check Module Status:
bashphp artisan module:list

Verify Configuration:
bashphp artisan config:show modules

Clear Cache:
bashphp artisan config:clear
php artisan cache:clear

Check for Syntax Errors:
bashphp -l app/Modules/MyModule/MyModule.php

Debug Events:
Add logging to your event handlers:
phpModuleEvents::listen('content.rendered', function ($content) {
    \Log::info('Event fired with content: ' . substr($content, 0, 50));
    return $content;
});

Enable Module Cache Manually:
Edit config/modules.php:
php'cache' => [
    'enabled' => true,
    'key' => 'corbital-modules',
    'lifetime' => 60, // minutes
],

Getting Help
If you encounter issues not covered in this troubleshooting section:

Check the GitHub repository issues section
Create a new issue with detailed information about your problem
Provide steps to reproduce the issue
Include your Laravel and PHP version information

This detailed guide should help you get started with the Corbital Module Manager and provide solutions to common issues. For more advanced use cases, refer to the source code documentation.
