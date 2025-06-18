<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\CheckOlxPrices; // Ensure this use statement is present for your custom command

/**
 * Class Kernel
 *
 * The console kernel handles the registration and execution of Artisan commands.
 * This class primarily serves to register custom commands, allowing them to be
 * callable via `php artisan your-command-name`.
 *
 * For scheduling tasks (e.g., running commands automatically every minute),
 * Laravel 10/11+ applications typically use the `->withSchedule()` method
 * in `bootstrap/app.php` instead of the `schedule()` method within this Kernel.
 */
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * These commands are made available to the Artisan console.
     * If your commands are not automatically discovered (e.g., by `load(__DIR__.'/Commands')`),
     * you would explicitly list them here.
     *
     * @var array<int, class-string>
     */
    protected $commands = [
        CheckOlxPrices::class, // Your custom OLX price checking command
    ];

    /**
     * Register the commands for the application.
     *
     * This method is responsible for loading console commands.
     * By default, it loads all commands from the 'Commands' directory.
     * It also loads console routes defined in 'routes/console.php'.
     *
     * @return void
     */
    protected function commands(): void
    {
        // Automatically load commands from the 'app/Console/Commands' directory
        $this->load(__DIR__.'/Commands');

        // Load console-specific routes (e.g., for simple closures as commands)
        require base_path('routes/console.php');
    }
}
