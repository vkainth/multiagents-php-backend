{{--
  Platform ads injection slot.
  This partial is included in layout.blade.php before main content.
  Ads are only shown on non-agent pages (AgentContext::current() === null)
  or when the agent has opted in via agent_settings.
  Currently a no-op placeholder — ad content injected in a future task.
--}}
@if(\App\Helpers\AgentContext::current() === null)
{{-- Main site: no platform ads here --}}
@else
{{-- Agent site: reserved for future platform ad injection (Task #565) --}}
@endif
