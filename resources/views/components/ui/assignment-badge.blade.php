@props(['project'])

{{-- "Rejected" beside the owner of a project whose owner rejected being put in
     charge, as an x-ui.chip (plan task 2.4). Needs the project's
     ownerAssignment loaded (the directory does). --}}
@if ($project->isOwnerRejected())
    <x-ui.chip tone="critical" icon="x-circle" :attributes="$attributes->merge(['data-test' => 'owner-rejected-badge'])">{{ \Modules\Tracking\Enums\AssignmentStatus::Rejected->label() }}</x-ui.chip>
@endif
