        @php
            $totalLines = $this->totals->map(fn (array $line): array => [
                'currency' => $line['currency'],
                'caption' => trans_choice('transfers.count_tranches', $line['tranches'], ['count' => $line['tranches']]),
                'figures' => array_values(array_filter([
                    ['key' => 'expected', 'label' => __('transfers.expected'), 'value' => $line['expected']],
                    ['key' => 'sent', 'label' => __('transfers.sent'), 'value' => $line['sent']],
                    ['key' => 'received', 'label' => __('finance_tables.received_net'), 'value' => $line['landed'], 'tone' => 'good'],
                    /*
                     * One figure, not two. Fees and shortfall were printed side
                     * by side at the same size, so a reader added them: 13,202
                     * of fees and 13,202 "lost" reads as 26,404 gone, when the
                     * fees ARE the shortfall. The note names the parts instead,
                     * and they sum to the figure exactly.
                     */
                    $line['shrink'] > 0 ? ['key' => 'shortfall', 'label' => __('transfers.shortfall'), 'value' => $line['shrink'], 'prefix' => '−', 'tone' => $line['exchangeGap'] > 0 ? 'critical' : null, 'note' => __('transfers.shortfall_parts', ['fees' => number_format($line['fees'], 2), 'gap' => number_format($line['exchangeGap'], 2)])] : null,
                    $line['overage'] > 0 ? ['key' => 'overage', 'label' => __('transfers.arrived_over'), 'value' => $line['overage'], 'prefix' => '+', 'tone' => 'good'] : null,
                    ['key' => 'outstanding', 'label' => __('finance_tables.outstanding'), 'value' => $line['inFlight']],
                ])),
                'notes' => array_values(array_filter([
                    $line['convertedCount'] > 0 ? trans_choice('transfers.count_tranches_landed_in_another_currency', $line['convertedCount'], ['count' => $line['convertedCount'], 'amount' => number_format($line['convertedAmount'], 2).' '.$line['currency']]) : null,
                    $line['unrecordedCount'] > 0 ? trans_choice('transfers.count_tranches_missing_net', $line['unrecordedCount'], ['count' => $line['unrecordedCount'], 'amount' => number_format($line['unrecordedAmount'], 2).' '.$line['currency']]) : null,
                    $line['feesElsewhereCount'] > 0 ? trans_choice('finance_tables.count_fees_in_another_currency', $line['feesElsewhereCount'], ['count' => $line['feesElsewhereCount']]) : null,
                ])),
            ])->all();


            $isGrouped = $this->group !== '';
            $previousGroup = null;
        @endphp

        <x-funding.totals :lines="$totalLines" />

        {{-- Five columns fit in sm; xl (92rem) was the width thirteen needed, and it
             forced a sideways scroll that put the edit button past the edge. --}}
        <x-funding.register size="sm" :caption="__('finance_tables.transfers_caption')">
            <x-slot:head>
                <x-funding.register.th sort="donor" :active="$sortKey" :dir="$sortDirection">{{ __('finance_tables.donor_agreement') }}</x-funding.register.th>
                <x-funding.tranche-head compact sortable :active="$sortKey" :dir="$sortDirection" />
            </x-slot:head>

            @foreach ($this->rows as $transfer)
                @php
                    $rowAgreement = $transfer->donorProposal;
                    $groupKey = $isGrouped ? $this->groupKeyOf($transfer) : null;
                @endphp

                @if ($isGrouped && $groupKey !== $previousGroup)
                    <x-funding.tranche-group
                        :label="$this->groupLabelOf($transfer)"
                        :group="$groupKey"
                        :lines="$this->groupTotals->get($groupKey, [])"
                        :lead="1"
                        compact
                    />
                    @php
                        $previousGroup = $groupKey;
                    @endphp
                @endif

                <x-funding.tranche-row :transfer="$transfer" compact>
                    <x-slot:lead>
                        <td data-label="{{ __('finance_tables.donor_agreement') }}" data-wide>
                            @can('donors.view')
                                <a href="{{ route('donors.show', $rowAgreement->donor) }}" wire:navigate class="font-semibold underline-offset-2 hover:underline">{{ $rowAgreement->donor->name }}</a>
                            @else
                                <span class="font-semibold">{{ $rowAgreement->donor->name }}</span>
                            @endcan
                            <span class="sub">
                                @can('proposals.view')
                                    <a href="{{ route('proposals.show', $rowAgreement->proposal) }}" wire:navigate class="underline-offset-2 hover:underline">{{ $rowAgreement->proposal->name }}</a>
                                @else
                                    {{ $rowAgreement->proposal->name }}
                                @endcan
                                ·
                                @can('funding-agreements.view')
                                    <a href="{{ route('funding-agreements.show', $rowAgreement) }}" wire:navigate class="underline-offset-2 hover:underline" data-agreement-link>{{ filled($rowAgreement->external_project_number) ? $rowAgreement->external_project_number : __('proposals.view_agreement') }}</a>
                                @else
                                    {{ $rowAgreement->external_project_number ?? __('common.no_external_project_number') }}
                                @endcan
                            </span>
                        </td>
                    </x-slot:lead>

                    <x-slot:actions>
                        <td>
                            @can('transfers.update')
                                {{-- Deleting a tranche happens inside its edit form. --}}
                                <span class="flex items-center justify-end">
                                    <x-ui.button tone="ghost" size="sm" icon="pencil-square" icon:variant="outline" class="size-8!" wire:click="openEditModal({{ $transfer->id }})" :aria-label="__('transfers.edit_transfer')" data-test="edit-transfer-{{ $transfer->id }}" />
                                </span>
                            @endcan
                        </td>
                    </x-slot:actions>
                </x-funding.tranche-row>
            @endforeach
        </x-funding.register>

        <x-funding.pager :paginator="$this->rows" :per-page="(string) $this->registerPerPage()" :sizes="$this->registerPageSizes()" scroll-to="#transfer-ladder-heading" />
