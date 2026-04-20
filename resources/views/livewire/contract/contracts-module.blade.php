@php use App\Enums\SalaryPer; @endphp
<div>
    @php($locale = app()->getLocale())

    <div class="flex justify-between mb-6">
        <livewire:layout.breadcrumbs
            :title="trans_choice('general.contracts', 2)"
            :crumbs="[
                ['label' => trans_choice('general.employee-management', 2)],
                ['label' => trans_choice('general.contracts', 2)],
            ]"
            :showButton="true"
        />
        <div>
            <x-primary-button :href="route('contracts.create')">
                @lang('general.add-new')
            </x-primary-button>
        </div>
    </div>

    <livewire:contract.contracts-table />
</div>
@push('scripts')
<script type="module">
    document.addEventListener('livewire:initialized', () => {
        let isProcessing = false;

        function applySpecificSelect2() {
            if (isProcessing) return;

            const targetStrings = ['employee_name'];

            document.querySelectorAll('select').forEach(select => {
                // Get the model name (e.g., "search.employee_name_en")
                const wireModel = select.getAttribute('wire:model') || select.getAttribute('wire:model.live');
                
                if (wireModel && targetStrings.some(str => wireModel.includes(str))) {
                    let $select = $(select);
                    
                    if (!$select.hasClass("select2-hidden-accessible")) {
                        $select.select2({
                            width: '100%',
                            placeholder: "{{ __('general.select') }}",
                            allowClear: true
                        });

                        $select.on('select2:select select2:unselect', function (e) {
                            isProcessing = true;
                            
                            // 1. Get the value from Select2
                            const data = $select.val();
                            
                            // 2. Directly tell the Livewire component to update the property
                            // This bypasses the need for native event listeners
                            let component = Livewire.find(select.closest('[wire\\:id]').getAttribute('wire:id'));
                            component.set(wireModel, data);

                            setTimeout(() => { isProcessing = false; }, 500);
                        });
                    }
                }
            });
        }

        applySpecificSelect2();

        Livewire.hook('commit', ({ succeed }) => {
            succeed(() => {
                setTimeout(() => { applySpecificSelect2(); }, 50);
            });
        });
    });
</script>
@endpush