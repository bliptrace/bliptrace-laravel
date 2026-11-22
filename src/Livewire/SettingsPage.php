<?php

declare(strict_types=1);

namespace G8Scope\Laravel\Livewire;

use Flux\Flux;
use G8Scope\Laravel\Settings\G8ScopeSettings;
use G8Scope\Laravel\Support\Requirements;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

/** UI to configure the SDK. Only routed when Requirements::uiAvailable(). */
#[Title('g8scope')]
class SettingsPage extends Component
{
    public bool $enabled = true;

    public string $key = '';

    public string $endpoint = '';

    public string $environment = '';

    public string $release = '';

    /** Ability that guards the page (config bliptrace.ui.gate). */
    public static function ability(): string
    {
        return (string) config('bliptrace.ui.gate', 'manage-g8scope');
    }

    public function mount(): void
    {
        abort_unless(Requirements::uiAvailable(), 404);
        abort_unless(Gate::allows(self::ability()), 403);

        $s = app(G8ScopeSettings::class);
        $this->enabled = $s->enabled;
        $this->key = (string) $s->key;
        $this->endpoint = $s->endpoint;
        $this->environment = $s->environment;
        $this->release = (string) $s->release;
    }

    public function save(): void
    {
        // Livewire updates hit /livewire/update, not this route — re-check.
        abort_unless(Gate::allows(self::ability()), 403);

        $this->validate([
            'endpoint' => 'required|url',
            'environment' => 'required|string|max:255',
            'key' => 'nullable|string|max:255',
            'release' => 'nullable|string|max:255',
        ]);

        $s = app(G8ScopeSettings::class);
        $s->enabled = $this->enabled;
        $s->key = $this->key !== '' ? $this->key : null;
        $s->endpoint = $this->endpoint;
        $s->environment = $this->environment;
        $s->release = $this->release !== '' ? $this->release : null;
        $s->save();

        Flux::toast('g8scope settings saved.');
    }

    public function render()
    {
        $layout = config('livewire.component_layout', config('livewire.layout', 'components.layouts.app'));

        return view('g8scope::livewire.settings')->layout($layout);
    }
}
