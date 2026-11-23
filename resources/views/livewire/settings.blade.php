<div>
    <div class="mb-6">
        <flux:heading size="xl">g8scope</flux:heading>
        <flux:subheading>Where this app ships its errors. Settings here override the .env defaults.</flux:subheading>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <flux:switch wire:model="enabled" label="Enabled" description="Master kill switch for error capture." />

            <flux:input wire:model="key" label="Ingest key" type="password" viewable
                placeholder="from the g8scope dashboard" description="Identifies your project. The project is resolved server-side from this key." />

            <flux:input wire:model="endpoint" label="Ingest endpoint" placeholder="https://ingest.bliptrace.com/v1/ingest" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="environment" label="Environment" placeholder="production" />
                <flux:input wire:model="release" label="Release" placeholder="my-app@1.0.0" />
            </div>

            <div class="flex items-center gap-3">
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:card>
</div>
