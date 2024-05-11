<?php

namespace TiMacDonald\Pulse\Cards;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\Livewire\Card;
use Laravel\Pulse\Livewire\Concerns\HasPeriod;
use Laravel\Pulse\Livewire\Concerns\RemembersQueries;
use Livewire\Attributes\Lazy;
use TiMacDonald\Pulse\Recorders\ValidationErrors as ValidationErrorsRecorder;

/**
 * @internal
 */
#[Lazy]
class ValidationErrors extends Card
{
    use HasPeriod, RemembersQueries;

    /**
     * Render the component.
     */
    public function render(): Renderable
    {
        [$validationErrors, $time, $runAt] = $this->remember(
            fn () => Pulse::aggregate(
                'validation_error',
                ['count'],
                $this->periodAsInterval(),
            )->map(function ($row) {
                [$method, $uri, $action, $bag, $name, $message] = json_decode($row->key, flags: JSON_THROW_ON_ERROR) + [5 => null];

                return (object) [
                    'bag' => $bag,
                    'uri' => $uri,
                    'name' => $name,
                    'action' => $action,
                    'method' => $method,
                    'message' => $message,
                    'count' => $row->count,
                ];
            }),
        );

        return View::make('timacdonald::validation-errors', [
            'time' => $time,
            'runAt' => $runAt,
            'validationErrors' => $validationErrors,
            'config' => [
                'enabled' => true,
                'sample_rate' => 1,
                'ignore' => [],
                ...Config::get('pulse.recorders.'.ValidationErrorsRecorder::class, []),
            ],
        ]);
    }
}
