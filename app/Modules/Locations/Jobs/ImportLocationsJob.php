<?php

namespace App\Modules\Locations\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ImportLocationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?string $path = null,
        public ?string $only = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('single')->info('Starting background location dataset import job', [
            'path' => $this->path,
            'only' => $this->only,
        ]);

        $params = [];
        if ($this->path) {
            $params['--path'] = $this->path;
        }
        if ($this->only) {
            $params['--only'] = $this->only;
        }

        $exitCode = Artisan::call('locations:import', $params);

        Log::channel('single')->info('Background location dataset import job completed', [
            'exit_code' => $exitCode,
            'output' => Artisan::output(),
        ]);
    }
}

