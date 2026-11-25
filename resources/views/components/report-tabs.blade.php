@props(['active' => ''])

@php
    $tabs = [
        'project-summary' => ['label' => __('Project summary'), 'route' => 'reports.project-summary'],
        'reconciliation' => ['label' => __('Reconciliation'), 'route' => 'reports.reconciliation'],
        'po-status' => ['label' => __('PO status'), 'route' => 'reports.po-status'],
        'attendance-summary' => ['label' => __('Attendance summary'), 'route' => 'reports.attendance-summary'],
        'milestone-progress' => ['label' => __('Milestone progress'), 'route' => 'reports.milestone-progress'],
    ];
@endphp

<div class="flex flex-wrap gap-2" aria-label="{{ __('Reports') }}">
    @foreach ($tabs as $slug => $tab)
        <flux:button
            size="sm"
            :variant="$slug === $active ? 'primary' : 'ghost'"
            :href="route($tab['route'])"
            wire:navigate
        >
            {{ $tab['label'] }}
        </flux:button>
    @endforeach
</div>
