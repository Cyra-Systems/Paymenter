<div class="space-y-4">
    <div>
        <h3 class="text-base font-semibold text-white">Applicant</h3>
        <p class="text-sm text-gray-200">{{ $application->user?->name }} ({{ $application->user?->email }})</p>
    </div>

    <div>
        <h3 class="text-base font-semibold text-white">Message</h3>
        <p class="whitespace-pre-line text-sm text-gray-200">{{ $application->message }}</p>
    </div>

    @if ($application->desired_revenue_share)
        <div>
            <h3 class="text-base font-semibold text-white">Requested revenue share</h3>
            <p class="text-sm text-gray-200">{{ $application->desired_revenue_share }}%</p>
        </div>
    @endif

    @if ($application->admin_notes)
        <div>
            <h3 class="text-base font-semibold text-white">Admin notes</h3>
            <p class="whitespace-pre-line text-sm text-red-200">{{ $application->admin_notes }}</p>
        </div>
    @endif
</div>
