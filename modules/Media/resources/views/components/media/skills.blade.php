{{--
    A team member's specialties (from the position title), those they do on need
    (outlined), and what else they do (interviews, print, …).
--}}
@props(['e' => null, 'title' => null])
@php($team = app(\Modules\Media\Support\MediaTeam::class))
@php($title = $e !== null ? $team->titleOf($e) : (string) $title)
<span {{ $attributes->class(['skills']) }}>
    @foreach ($team->skillsFromTitle($title) as $s)<span class="chip"><x-ic :n="config('media-requests.specialties.'.$s.'.icon')" />{{ __('media.spec.'.$s) }}</span>@endforeach
    @foreach ($team->extraSkillsFromTitle($title) as $s)<span class="chip onneed" title="{{ __('media.spec.onNeed') }}"><x-ic :n="config('media-requests.specialties.'.$s.'.icon')" />{{ __('media.spec.'.$s) }} · {{ __('media.spec.onNeedShort') }}</span>@endforeach
    @foreach ($team->extrasFromTitle($title) as $x)<span class="chip soft"><x-ic :n="config('media-requests.extras.'.$x.'.icon')" />{{ __('media.extra.'.$x) }}</span>@endforeach
</span>
