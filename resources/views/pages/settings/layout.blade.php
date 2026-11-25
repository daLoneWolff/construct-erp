<div class="w-full max-w-3xl">
    <div class="space-y-1">
        <flux:heading size="xl" level="1">{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>
    </div>

    <div class="mt-5 w-full max-w-lg">
        {{ $slot }}
    </div>
</div>
