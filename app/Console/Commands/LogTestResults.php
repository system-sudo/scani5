<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class LogTestResults extends Command
{
    protected $signature = 'test:log {path?}';
    protected $description = 'Run Laravel tests with optional filtering and log results';

    public function handle()
    {
        // Get the optional path argument (test file or folder)
        $path = $this->argument('path') ?? '';

        // Build the test command with forced colors
        $command = ['php', 'artisan', 'test', '--colors=always'];
        if (!empty($path)) {
            $command[] = $path;
        }

        // Initialize the Process
        $process = new Process($command);
        $process->setTimeout(null); // No timeout

        $outputBuffer = ''; // Store all output

        $process->run(function ($type, $buffer) use (&$outputBuffer) {
            echo $buffer; // Ensure live output in terminal
            $outputBuffer .= $buffer; // Store output in buffer
        });

        // Remove ANSI escape codes before logging
        $cleanOutput = preg_replace('/\e\[([;\d]*)m/', '', $outputBuffer);
        Log::channel('unittest')->info("Test Results for '{$path}':\n" . $cleanOutput);

        $this->info("\nTest results logged successfully in storage/logs/unit_tests.log");
    }
}
