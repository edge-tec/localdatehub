<?php

namespace App\Livewire;

use App\Support\Health\HealthCheckService;
use Livewire\Component;

class SystemStatusWidget extends Component
{
    public bool $showDetailed = false;
    public ?array $healthData = null;
    public string $lastChecked = '';
    public int $counter = 0;

    public function mount(HealthCheckService $healthService): void
    {
        $this->refreshStatus($healthService);
    }

    public function toggleDetailed(HealthCheckService $healthService): void
    {
        $this->showDetailed = !$this->showDetailed;
        if ($this->showDetailed) {
            $this->healthData = $healthService->checkReadiness();
        }
    }

    public function incrementTest(): void
    {
        $this->counter++;
    }

    public function refreshStatus(HealthCheckService $healthService): void
    {
        $this->healthData = $this->showDetailed 
            ? $healthService->checkReadiness() 
            : $healthService->checkLiveness();
            
        $this->lastChecked = now()->format('H:i:s T');
    }

    public function render()
    {
        return view('livewire.system-status-widget');
    }
}
