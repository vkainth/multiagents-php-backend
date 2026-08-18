@extends('frontend.layouts.default_mobilefirst')

@push('after-scripts')
<script>
(function () {
    function registerLivewire419Hook() {
        if (typeof Livewire === 'undefined') { return; }
        Livewire.hook('request', ({ fail }) => {
            fail(({ status, preventDefault }) => {
                if (status === 419) {
                    preventDefault();
                    window.location.reload();
                }
            });
        });
    }
    document.addEventListener('livewire:init', registerLivewire419Hook);
    registerLivewire419Hook();
})();
</script>
@endpush
