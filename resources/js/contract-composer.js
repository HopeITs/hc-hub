/**
 * Contract "Composer" — the single-page screen behind both creating a contract
 * and editing one.
 *
 * Alpine owns the whole form while it is being filled in. Only three things
 * cross the wire: one renderless request per city change, one debounced
 * request for the leave-entitlement figure, and one request on submit.
 *
 * The two screens differ only in what Blade hands down: an edit seeds every
 * field from the contract it opened on, and appends a `termination` section
 * that a contract which does not exist yet cannot have.
 */

const ALL_CENTERS = '__all__';
const CITY_LEVEL = '';

const str = (value) => (value === null || value === undefined ? '' : String(value));

const clampIndex = (index, length) => (length === 0 ? -1 : Math.min(Math.max(index, 0), length - 1));

document.addEventListener('alpine:init', () => {
    /* ------------------------------------------------------------------ *
     * Combobox
     * ------------------------------------------------------------------ */
    window.Alpine.data('hcCombobox', (config) => ({
        open: false,
        query: '',
        active: 0,

        readOptions: config.readOptions,
        readValue: config.readValue,
        writeValue: config.writeValue,
        isDisabled: config.isDisabled,
        readError: config.readError ?? (() => false),
        clearable: config.clearable ?? false,
        placeholder: config.placeholder ?? '',

        get allOptions() {
            return this.readOptions() ?? [];
        },

        get value() {
            return str(this.readValue());
        },

        get hasError() {
            return Boolean(this.readError());
        },

        get filtered() {
            const needle = this.query.trim().toLocaleLowerCase();

            if (needle === '') {
                return this.allOptions;
            }

            return this.allOptions.filter((option) =>
                str(option.label).toLocaleLowerCase().includes(needle),
            );
        },

        get selectedLabel() {
            const match = this.allOptions.find((option) => str(option.value) === this.value);

            return match ? match.label : '';
        },

        get canClear() {
            return this.clearable && !this.open && this.value !== '';
        },

        isSelected(option) {
            return str(option.value) === this.value;
        },

        isFull(option) {
            return option.maxEmp !== undefined
                && option.maxEmp !== null
                && Number(option.existEmp) >= Number(option.maxEmp);
        },

        /** At most six segments, so a 40-seat position does not draw 40 bars. */
        seatBar(option) {
            const max = Number(option.maxEmp) || 0;
            const taken = Number(option.existEmp) || 0;

            if (max < 1) {
                return [];
            }

            const segments = Math.min(max, 6);
            const filled = Math.round((taken / max) * segments);

            return Array.from({ length: segments }, (_, i) => ({ i, taken: i < filled }));
        },

        initials(label) {
            return str(label)
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 2)
                .map((word) => word.charAt(0))
                .join('')
                .toLocaleUpperCase();
        },

        firstSelectable() {
            return this.filtered.findIndex((option) => !this.isFull(option));
        },

        lastSelectable() {
            for (let i = this.filtered.length - 1; i >= 0; i -= 1) {
                if (!this.isFull(this.filtered[i])) {
                    return i;
                }
            }

            return -1;
        },

        openList() {
            if (this.isDisabled() || this.open) {
                return;
            }

            this.open = true;
            this.query = '';

            const selectedIndex = this.filtered.findIndex((option) => this.isSelected(option));
            this.active = selectedIndex >= 0 ? selectedIndex : this.firstSelectable();
        },

        toggle() {
            this.open ? this.close() : (this.$refs.input.focus(), this.openList());
        },

        close() {
            this.open = false;
            this.query = '';
        },

        /** Arrow keys skip over full positions rather than landing on them. */
        move(direction) {
            if (!this.open) {
                this.openList();

                return;
            }

            const options = this.filtered;

            if (options.length === 0) {
                return;
            }

            let next = clampIndex(this.active, options.length);

            for (let step = 0; step < options.length; step += 1) {
                next = (next + direction + options.length) % options.length;

                if (!this.isFull(options[next])) {
                    this.active = next;
                    this.scrollActiveIntoView();

                    return;
                }
            }
        },

        scrollActiveIntoView() {
            this.$nextTick(() => {
                this.$el.querySelector(`[aria-selected], #${CSS.escape(this.$refs.input.id)}-option-${this.active}`)
                    ?.scrollIntoView({ block: 'nearest' });
            });
        },

        choose(index) {
            const option = this.filtered[index];

            if (!option || this.isFull(option)) {
                return;
            }

            this.writeValue(str(option.value));
            this.close();
        },

        commitAndClose() {
            if (this.open && this.query.trim() !== '' && this.active >= 0) {
                this.choose(this.active);

                return;
            }

            this.close();
        },

        clear() {
            this.writeValue('');
            this.query = '';
        },
    }));

    /* ------------------------------------------------------------------ *
     * Composer
     * ------------------------------------------------------------------ */
    window.Alpine.data('contractComposer', (config) => ({
        // ---- configuration handed down from Blade
        typeDefaults: config.typeDefaults,
        labels: config.labels,
        locale: config.locale,
        cities: config.cities ?? [],
        terminationReasons: config.terminationReasons ?? [],
        // Create renders four sections; edit adds `termination` on the end.
        sections: config.sections ?? ['assignment', 'contract', 'schedule', 'pay'],
        // Only create insists on work days: contracts imported before the field
        // existed have none, and editing one must not be blocked on that.
        requireWorkDays: config.requireWorkDays ?? true,

        // ---- assignment
        // config.seed is present when this page opened on an existing contract
        // — a renewal or an edit — and fills every field below from it instead
        // of opening blank. Everything it sets stays user-editable afterwards.
        selected: {
            city: str(config.cityId),
            employee: str(config.seed?.employeeId),
            program: str(config.seed?.programId),
            center: config.seed?.centerId != null ? str(config.seed.centerId) : ALL_CENTERS,
            position: str(config.seed?.positionId),
            contractType: str(config.seed?.contractTypeId),
        },
        options: { employees: [], programs: [], centers: [], positions: [] },

        // Set when the selected employee's most recent contract is a draft
        // (type = Draft, created from the employee-create screen's
        // position-reservation step) — submit() then upgrades that exact row
        // instead of inserting a new one. See checkForDraftContract(). Only
        // the create screen's component exposes the method this checks —
        // an edit is always about a contract that already exists.
        allowDraftUpgrade: config.allowDraftUpgrade ?? true,
        draftContractId: null,

        // ---- contract
        startDate: config.seed?.startDate ?? '',
        endDate: config.seed?.endDate ?? '',
        type: str(config.seed?.type ?? '1'),
        place: str(config.seed?.place ?? '1'),

        // ---- schedule
        workHoursFrom: config.seed?.workHoursFrom ?? '09:00',
        workHoursTo: config.seed?.workHoursTo ?? '17:00',
        weeklyHours: config.seed ? str(config.seed.weeklyHours) : '40',
        // Free text in both languages — "Monday to Friday", not a row of seven
        // toggles. It is what the contract PDF prints, verbatim.
        workDaysEn: config.seed?.workDaysEn ?? '',
        workDaysAr: config.seed?.workDaysAr ?? '',
        showNotes: Boolean(config.seed?.attendingNotesEn || config.seed?.attendingNotesAr),
        attendingNotesEn: config.seed?.attendingNotesEn ?? '',
        attendingNotesAr: config.seed?.attendingNotesAr ?? '',

        // ---- pay
        salary: config.seed?.salary ?? '',
        currency: str(config.seed?.currency ?? '1'),
        salaryPer: str(config.seed?.salaryPer ?? '1'),

        // ---- termination (edit only; create does not render the section)
        terminatedAt: config.seed?.terminatedAt ?? '',
        terminationReasonId: str(config.seed?.terminationReasonId ?? ''),
        reasonOfTermination: config.seed?.reasonOfTermination ?? '',

        // ---- ui
        // An edit opens with every section collapsed and summarised: there is
        // nothing to fill in there, only something to change.
        section: config.initialSection ?? 'assignment',
        loading: false,
        submitting: false,
        leaveDays: null,
        serverErrors: {},
        requestSequence: 0,
        leaveTimer: null,
        // Set while the composer rewrites its own selections, so the cascade
        // watchers do not mistake a programmatic tidy-up for a user choice.
        syncing: false,

        // =============================================================
        // Lifecycle
        // =============================================================

        initialize() {
            this.$watch('selected.city', () => this.syncing || this.loadCityOptions(true));
            this.$watch('selected.program', () => this.syncing || this.onProgramChange());
            this.$watch('selected.center', () => this.syncing || this.onCenterChange());
            this.$watch('selected.employee', () => this.syncing || this.checkForDraftContract());
            this.$watch('leaveInputs', () => this.scheduleLeavePreview());

            if (this.selected.city) {
                this.loadCityOptions(false);
            }

            // A seeded screen — an edit, or a renewal — arrives with every
            // entitlement input already complete, so the watcher above has
            // nothing to react to. Ask once, up front.
            this.scheduleLeavePreview();

            window.addEventListener('composer-invalid', (event) => {
                const sections = event.detail?.sections ?? [];

                if (sections.length > 0) {
                    this.section = sections[0];
                }

                this.submitting = false;
            });
        },

        // =============================================================
        // Section state machine
        // =============================================================

        get assignmentValid() {
            return Boolean(this.selected.city && this.selected.employee
                && this.selected.program && this.selected.position);
        },

        get contractValid() {
            return Boolean(this.selected.contractType && this.type && this.place
                && this.startDate && this.endDate
                && this.endDate >= this.startDate && !this.endDateTooFar);
        },

        get scheduleValid() {
            if (this.requireWorkDays && (this.workDaysEn.trim() === '' || this.workDaysAr.trim() === '')) {
                return false;
            }

            return Number(this.weeklyHours) >= 1;
        },

        get payValid() {
            return this.salary !== '' && Number(this.salary) >= 0 && Number.isFinite(Number(this.salary));
        },

        isValid(name) {
            return {
                assignment: this.assignmentValid,
                contract: this.contractValid,
                schedule: this.scheduleValid,
                pay: this.payValid,
                // Optional by nature: a contract is complete whether or not it
                // ended early, so this section never locks the ones after it.
                termination: true,
            }[name] ?? false;
        },

        /** The sections that must be filled in before the form can be sent. */
        get requiredSections() {
            return this.sections.filter((name) => name !== 'termination');
        },

        isLastSection(name) {
            return this.sections[this.sections.length - 1] === name;
        },

        /** The first earlier section that is not yet satisfied, or null. */
        blockedBy(name) {
            const index = this.sections.indexOf(name);

            for (let i = 0; i < index; i += 1) {
                if (!this.isValid(this.sections[i])) {
                    return this.sections[i];
                }
            }

            return null;
        },

        isUnlocked(name) {
            return this.blockedBy(name) === null;
        },

        stateOf(name) {
            if (this.section === name) {
                return 'open';
            }

            if (!this.isUnlocked(name)) {
                return 'locked';
            }

            return this.isValid(name) ? 'done' : 'idle';
        },

        openSection(name) {
            if (!this.isUnlocked(name)) {
                return;
            }

            this.section = this.section === name ? '' : name;
        },

        nextSection() {
            const next = this.sections[this.sections.indexOf(this.section) + 1];

            if (next && this.isUnlocked(next)) {
                this.section = next;
            }
        },

        get completedCount() {
            return this.requiredSections.filter((name) => this.isValid(name)).length;
        },

        get canSubmit() {
            return this.completedCount === this.requiredSections.length
                && !this.submitting && !this.loading;
        },

        // =============================================================
        // Assignment cascade
        // =============================================================

        async loadCityOptions(clearDownstream = true) {
            const cityId = Number(this.selected.city);
            const request = (this.requestSequence += 1);

            if (clearDownstream) {
                await this.withSync(() => this.resetDownstream());
            }

            if (!Number.isInteger(cityId) || cityId < 1) {
                this.options = { employees: [], programs: [], centers: [], positions: [] };

                return;
            }

            this.loading = true;

            try {
                const payload = await this.$wire.locationOptions(cityId);

                if (request !== this.requestSequence) {
                    return;
                }

                this.options = {
                    employees: payload.employees ?? [],
                    programs: payload.programs ?? [],
                    centers: payload.centers ?? [],
                    positions: payload.positions ?? [],
                };

                await this.withSync(() => this.dropUnavailableSelections());
            } finally {
                if (request === this.requestSequence) {
                    this.loading = false;
                }
            }
        },

        async withSync(mutate) {
            this.syncing = true;

            try {
                mutate();
            } finally {
                await this.$nextTick();
                this.syncing = false;
            }
        },

        resetDownstream() {
            this.selected.employee = '';
            this.selected.program = '';
            this.selected.center = ALL_CENTERS;
            this.selected.position = '';
        },

        dropUnavailableSelections() {
            const has = (list, value) => value === '' || list.some((o) => str(o.value) === value);

            if (!has(this.options.employees, this.selected.employee)) {
                this.selected.employee = '';
            }

            if (!has(this.options.programs, this.selected.program)) {
                this.selected.program = '';
                this.selected.center = ALL_CENTERS;
                this.selected.position = '';

                return;
            }

            if (!has(this.centerOptions, this.selected.center)) {
                this.selected.center = ALL_CENTERS;
            }

            if (!has(this.positionOptions, this.selected.position)) {
                this.selected.position = '';
            }
        },

        onCityChange() {
            this.loadCityOptions(true);
        },

        /**
         * Auto-fills program/center/position from the selected employee's
         * draft contract, if their most recently created contract is one.
         * Fields set here stay user-editable afterwards, same as a renewal's
         * seed — this only saves re-entering what the employee-create screen
         * already captured, it does not lock anything in.
         */
        async checkForDraftContract() {
            const employeeId = Number(this.selected.employee);

            if (!this.allowDraftUpgrade || !Number.isInteger(employeeId) || employeeId < 1) {
                this.draftContractId = null;

                return;
            }

            const draft = await this.$wire.draftContractFor(employeeId);

            if (!draft) {
                this.draftContractId = null;

                return;
            }

            this.draftContractId = draft.draftContractId;

            await this.withSync(() => {
                this.selected.program = str(draft.programId ?? '');
                this.selected.center = draft.centerId != null ? str(draft.centerId) : CITY_LEVEL;
                this.selected.position = str(draft.positionId ?? '');
            });
        },

        onProgramChange() {
            // Study Zone positions are city-level by data, not by a center
            // choice someone forgot to make — there is nothing to pick, so
            // land on "city-level" directly instead of "all centers".
            this.selected.center = this.isStudyZoneProgram ? CITY_LEVEL : ALL_CENTERS;
            this.selected.position = '';
        },

        /** Whether the selected program is Study Zone (slug 'SZ') — the one program whose positions never sit under a center. */
        get isStudyZoneProgram() {
            const program = this.options.programs.find((p) => str(p.value) === this.selected.program);

            return program?.slug === 'SZ';
        },

        onCenterChange() {
            this.selected.position = '';
        },

        get programPositions() {
            if (!this.selected.program) {
                return [];
            }

            return this.options.positions
                .filter((position) => str(position.programId) === this.selected.program);
        },

        /**
         * "All centers" is the default, and it shows every position in the
         * program. The old screen filtered to centre-less positions whenever
         * the centre box was blank, which hid most positions behind a control
         * that was not even marked as required.
         */
        get centerOptions() {
            if (!this.selected.program) {
                return [];
            }

            const inProgram = this.options.centers.filter((center) =>
                (center.programIds ?? []).map(str).includes(this.selected.program));

            const options = [{ value: ALL_CENTERS, label: this.labels.allCenters }];

            if (this.programPositions.some((position) => str(position.centerId) === '')) {
                options.push({ value: CITY_LEVEL, label: this.labels.cityLevel });
            }

            return options.concat(inProgram);
        },

        get positionOptions() {
            const centerName = (id) => this.options.centers
                .find((center) => str(center.value) === str(id))?.label ?? '';

            return this.programPositions
                .filter((position) => {
                    if (this.selected.center === ALL_CENTERS) {
                        return true;
                    }

                    return str(position.centerId) === this.selected.center;
                })
                .map((position) => ({
                    ...position,
                    centerLabel: str(position.centerId) === ''
                        ? this.labels.cityLevel
                        : centerName(position.centerId),
                }));
        },

        get selectedEmployee() {
            return this.options.employees
                .find((employee) => str(employee.value) === this.selected.employee) ?? null;
        },

        get selectedPosition() {
            return this.options.positions
                .find((position) => str(position.value) === this.selected.position) ?? null;
        },

        get selectedProgramLabel() {
            return this.options.programs
                .find((program) => str(program.value) === this.selected.program)?.label ?? '';
        },

        get selectedCenterLabel() {
            const position = this.selectedPosition;

            if (!position) {
                return '';
            }

            if (str(position.centerId) === '') {
                return this.labels.cityLevel;
            }

            return this.options.centers
                .find((center) => str(center.value) === str(position.centerId))?.label ?? '';
        },

        get capacity() {
            const position = this.selectedPosition;

            if (!position || !position.maxEmp) {
                return null;
            }

            const max = Number(position.maxEmp);
            const taken = Number(position.existEmp);

            return { taken, max, left: Math.max(0, max - taken), full: taken >= max };
        },

        // =============================================================
        // Contract
        // =============================================================

        get selectedType() {
            return this.typeDefaults[this.selected.contractType] ?? null;
        },

        chooseType(id) {
            this.selected.contractType = str(id);

            const defaults = this.typeDefaults[str(id)];

            if (defaults) {
                this.startDate = defaults.start;
                this.endDate = defaults.end;
                this.flashDates();

                // Full/Part time only make sense for a volunteer or trial
                // contract; Freelance only for a service one — switching
                // category must not leave a now-invalid type selected.
                if (defaults.category === 'service') {
                    this.type = '3'; // Freelance
                } else if (this.type === '3') {
                    this.type = '1'; // Full Time
                }
            }
        },

        flashDates() {
            this.datesJustSet = true;
            setTimeout(() => { this.datesJustSet = false; }, 700);
        },
        datesJustSet: false,

        /** Mirrors validateTerms(): end_date must be within a year of the start. */
        get maxEndDate() {
            if (!this.startDate) {
                return '';
            }

            const date = new Date(`${this.startDate}T00:00:00`);
            date.setFullYear(date.getFullYear() + 1);

            return date.toISOString().slice(0, 10);
        },

        get endDateTooFar() {
            return Boolean(this.startDate && this.endDate && this.maxEndDate
                && this.endDate > this.maxEndDate);
        },

        /**
         * Months to one decimal place, mirroring Contract::getDurationAttribute()
         * — the same figure the leave calculation runs on.
         *
         * Reporting "3 months · 30 days" for 1 Sep → 31 Dec was accurate but read
         * as a shorter contract than it is, and disagreed with the number the
         * server would store for the very same dates.
         */
        get durationLabel() {
            if (!this.startDate || !this.endDate || this.endDate < this.startDate) {
                return '';
            }

            const start = new Date(`${this.startDate}T00:00:00`);
            const end = new Date(`${this.endDate}T00:00:00`);

            let months = (end.getFullYear() - start.getFullYear()) * 12
                + (end.getMonth() - start.getMonth());

            const anchor = new Date(start);
            anchor.setMonth(anchor.getMonth() + months);

            if (anchor > end) {
                months -= 1;
                anchor.setMonth(anchor.getMonth() - 1);
            }

            if (months < 0) {
                return '';
            }

            // How far into the following month the end date falls.
            const nextAnchor = new Date(anchor);
            nextAnchor.setMonth(nextAnchor.getMonth() + 1);

            const total = Math.round((months + (end - anchor) / (nextAnchor - anchor)) * 10) / 10;

            if (total < 1) {
                const days = Math.max(0, Math.round((end - start) / 86400000));

                return `${days} ${days === 1 ? this.labels.day : this.labels.days}`;
            }

            return `${total} ${total === 1 ? this.labels.month : this.labels.months}`;
        },

        // =============================================================
        // Termination — rendered by the edit screen only.
        // =============================================================

        get isTerminated() {
            return Boolean(this.terminatedAt || this.terminationReasonId);
        },

        get terminationReasonLabel() {
            return this.terminationReasons
                .find((reason) => str(reason.value) === this.terminationReasonId)?.label ?? '';
        },

        clearTermination() {
            this.terminatedAt = '';
            this.terminationReasonId = '';
            this.reasonOfTermination = '';
        },

        // =============================================================
        // Pay
        // =============================================================

        get currencyLabel() {
            return { 0: this.labels.unknownCurrency, 1: 'SYP', 2: 'USD', 3: 'EUR' }[this.currency] ?? '';
        },

        get salaryPerLabel() {
            return {
                1: this.labels.perMonth,
                2: this.labels.perHour,
                3: this.labels.perSession,
            }[this.salaryPer] ?? '';
        },

        formatNumber(value) {
            const number = Number(value);

            if (!Number.isFinite(number)) {
                return '';
            }

            return new Intl.NumberFormat(this.locale === 'ar' ? 'ar-EG' : 'en-US').format(number);
        },

        get salaryEcho() {
            if (this.salary === '' || !Number.isFinite(Number(this.salary))) {
                return '';
            }

            const base = `${this.formatNumber(this.salary)} ${this.currencyLabel} ${this.salaryPerLabel}`;

            if (this.salaryPer !== '1') {
                return base;
            }

            const yearly = this.formatNumber(Number(this.salary) * 12);

            return `${base} · ${yearly} ${this.currencyLabel} ${this.labels.perYear}`;
        },

        // =============================================================
        // Display
        // =============================================================

        /** Substitutes Laravel's :placeholders, so no copy is assembled in PHP. */
        fill(template, values) {
            return Object.entries(values).reduce(
                (text, [key, value]) => text.replaceAll(`:${key}`, value),
                str(template),
            );
        },

        formatDate(value) {
            if (!value) {
                return '';
            }

            return new Intl.DateTimeFormat(this.locale === 'ar' ? 'ar-EG' : 'en-GB', {
                day: 'numeric', month: 'short', year: 'numeric',
            }).format(new Date(`${value}T00:00:00`));
        },

        get cityName() {
            return this.cities.find((city) => str(city.value) === this.selected.city)?.label ?? '';
        },

        lockedHintFor(name) {
            const blocker = this.blockedBy(name);

            return blocker
                ? this.fill(this.labels.lockedBecause, { section: this.labels.sections[blocker] })
                : '';
        },

        get progressLabel() {
            return this.fill(this.labels.progress, { done: this.completedCount, total: 4 });
        },

        get duplicateContractNotice() {
            const endsAt = this.selectedEmployee?.activeContractEndsAt;

            return endsAt ? this.fill(this.labels.duplicate, { date: this.formatDate(endsAt) }) : '';
        },

        /** Non-empty once checkForDraftContract() finds this employee already has a reserved position. */
        get draftContractNotice() {
            return this.draftContractId ? this.labels.draftFound : '';
        },

        get capacityBar() {
            const capacity = this.capacity;

            if (!capacity) {
                return [];
            }

            const segments = Math.min(capacity.max, 6);
            const filled = Math.round((capacity.taken / capacity.max) * segments);

            return Array.from({ length: segments }, (_, i) => ({ i, taken: i < filled }));
        },

        get capacityLabel() {
            const capacity = this.capacity;

            if (!capacity) {
                return '';
            }

            return capacity.left === 0
                ? this.fill(this.labels.noSeatsLeft, { taken: capacity.taken, max: capacity.max })
                : this.fill(this.labels.seatsLeft, {
                    taken: capacity.taken, max: capacity.max, left: capacity.left,
                });
        },

        // ---- collapsed section summaries -------------------------------

        get assignmentSummary() {
            return [this.selectedEmployee?.label, this.selectedPosition?.label, this.cityName]
                .filter(Boolean).join(' · ');
        },

        get contractSummary() {
            const period = this.startDate && this.endDate
                ? `${this.formatDate(this.startDate)} → ${this.formatDate(this.endDate)}`
                : '';

            return [this.selectedType?.name, period, this.durationLabel].filter(Boolean).join(' · ');
        },

        get scheduleSummary() {
            const days = this.locale === 'ar' ? this.workDaysAr : this.workDaysEn;

            return [days, `${this.workHoursFrom}–${this.workHoursTo}`, `${this.weeklyHours} h`]
                .filter(Boolean).join(' · ');
        },

        get terminationSummary() {
            if (!this.isTerminated) {
                return this.labels.notTerminated;
            }

            return [this.formatDate(this.terminatedAt), this.terminationReasonLabel]
                .filter(Boolean).join(' · ');
        },

        get paySummary() {
            return this.salaryEcho.split(' · ')[0];
        },

        // ---- contract card ---------------------------------------------

        get cardSubtitle() {
            return [this.cityName, this.selectedProgramLabel].filter(Boolean).join(' · ')
                || this.labels.empty;
        },

        get cardPosition() {
            return this.selectedPosition?.label ?? this.labels.empty;
        },

        get cardCenter() {
            return this.selectedCenterLabel || this.labels.empty;
        },

        get cardCapacity() {
            return this.capacity ? `${this.capacity.taken} / ${this.capacity.max}` : this.labels.empty;
        },

        get cardType() {
            return this.selectedType?.name ?? this.labels.empty;
        },

        get cardStart() {
            return this.formatDate(this.startDate) || this.labels.empty;
        },

        get cardEnd() {
            return this.formatDate(this.endDate) || this.labels.empty;
        },

        get cardPeriod() {
            return this.durationLabel || this.labels.empty;
        },

        get cardWorkDays() {
            return (this.locale === 'ar' ? this.workDaysAr : this.workDaysEn) || this.labels.empty;
        },

        get cardHours() {
            return Number(this.weeklyHours) >= 1 ? `${this.weeklyHours} h` : this.labels.empty;
        },

        get cardSalary() {
            return this.salary === ''
                ? this.labels.empty
                : `${this.formatNumber(this.salary)} ${this.currencyLabel} ${this.salaryPerLabel}`;
        },

        get cardLeave() {
            return this.leaveDays === null
                ? this.labels.empty
                : `${this.formatNumber(this.leaveDays)} ${this.labels.daysUnit}`;
        },

        // =============================================================
        // Leave preview
        // =============================================================

        get leaveInputs() {
            return [
                this.selected.employee,
                this.selected.contractType,
                this.startDate,
                this.endDate,
                this.weeklyHours,
            ].join('|');
        },

        scheduleLeavePreview() {
            clearTimeout(this.leaveTimer);

            if (!this.selected.employee || !this.startDate || !this.endDate || Number(this.weeklyHours) < 1) {
                this.leaveDays = null;

                return;
            }

            this.leaveTimer = setTimeout(async () => {
                this.leaveDays = await this.$wire.leavePreview({
                    employeeId: this.selected.employee,
                    contractTypeId: this.selected.contractType,
                    startDate: this.startDate,
                    endDate: this.endDate,
                    weeklyHours: this.weeklyHours,
                });
            }, 500);
        },

        // =============================================================
        // Submit
        // =============================================================

        async submit() {
            if (!this.canSubmit) {
                return;
            }

            this.submitting = true;

            try {
                await this.$wire.submit({
                    cityId: this.selected.city,
                    employeeId: this.selected.employee,
                    programId: this.selected.program,
                    centerId: this.selected.center === ALL_CENTERS ? '' : this.selected.center,
                    positionId: this.selected.position,
                    draftContractId: this.draftContractId,
                    contractTypeId: this.selected.contractType,
                    type: this.type,
                    place: this.place,
                    startDate: this.startDate,
                    endDate: this.endDate,
                    workHoursFrom: this.workHoursFrom,
                    workHoursTo: this.workHoursTo,
                    weeklyHours: this.weeklyHours,
                    workDaysEn: this.workDaysEn,
                    workDaysAr: this.workDaysAr,
                    attendingNotesEn: this.attendingNotesEn,
                    attendingNotesAr: this.attendingNotesAr,
                    salary: this.salary,
                    currency: this.currency,
                    salaryPer: this.salaryPer,
                    // Ignored by ContractCreate, which always writes a contract
                    // that is active and un-terminated.
                    terminatedAt: this.terminatedAt,
                    terminationReasonId: this.terminationReasonId,
                    reasonOfTermination: this.reasonOfTermination,
                });
            } finally {
                this.submitting = false;
            }
        },
    }));
});
