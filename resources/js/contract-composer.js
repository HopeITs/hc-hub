/**
 * Contract create — "Composer".
 *
 * Alpine owns the whole form while it is being filled in. Only three things
 * cross the wire: one renderless request per city change, one debounced
 * request for the leave-entitlement figure, and one request on submit.
 *
 * Deliberately independent of contract-location-picker.js / jQuery / Select2,
 * which stay in place for ContractEdit.
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
        dayNames: config.dayNames,
        listSeparator: config.listSeparator,
        typeDefaults: config.typeDefaults,
        labels: config.labels,
        draftKey: config.draftKey,
        locale: config.locale,
        cities: config.cities ?? [],

        // ---- assignment
        selected: {
            city: str(config.cityId),
            employee: '',
            program: '',
            center: ALL_CENTERS,
            position: '',
            contractType: '',
        },
        options: { employees: [], programs: [], centers: [], positions: [] },

        // ---- contract
        startDate: '',
        endDate: '',

        // ---- schedule
        days: [false, false, false, false, false, false, false],
        workHoursFrom: '09:00',
        workHoursTo: '17:00',
        weeklyHoursOverride: null,
        manualDays: false,
        workDaysEnManual: '',
        workDaysArManual: '',
        showNotes: false,
        attendingNotesEn: '',
        attendingNotesAr: '',

        // ---- pay
        salary: '',
        currency: '1',
        salaryPer: '1',

        // ---- ui
        section: 'assignment',
        loading: false,
        submitting: false,
        leaveDays: null,
        draftAvailable: false,
        serverErrors: {},
        requestSequence: 0,
        leaveTimer: null,
        draftTimer: null,
        // Set while the composer rewrites its own selections, so the cascade
        // watchers do not mistake a programmatic tidy-up for a user choice.
        syncing: false,

        // =============================================================
        // Lifecycle
        // =============================================================

        initialize() {
            this.draftAvailable = this.readDraft() !== null;

            this.$watch('selected.city', () => this.syncing || this.loadCityOptions(true));
            this.$watch('selected.program', () => this.syncing || this.onProgramChange());
            this.$watch('selected.center', () => this.syncing || this.onCenterChange());
            this.$watch('leaveInputs', () => this.scheduleLeavePreview());
            this.$watch('persistable', () => this.scheduleDraftSave());

            if (this.selected.city) {
                this.loadCityOptions(false);
            }

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
            return Boolean(this.selected.contractType && this.startDate && this.endDate
                && this.endDate >= this.startDate && !this.endDateTooFar);
        },

        get scheduleValid() {
            return this.workDaysEn !== '' && this.workDaysAr !== '' && this.weeklyHours >= 1;
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
            }[name] ?? false;
        },

        /** The first earlier section that is not yet satisfied, or null. */
        blockedBy(name) {
            const order = ['assignment', 'contract', 'schedule', 'pay'];
            const index = order.indexOf(name);

            for (let i = 0; i < index; i += 1) {
                if (!this.isValid(order[i])) {
                    return order[i];
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
            const order = ['assignment', 'contract', 'schedule', 'pay'];
            const next = order[order.indexOf(this.section) + 1];

            if (next && this.isUnlocked(next)) {
                this.section = next;
            }
        },

        get completedCount() {
            return ['assignment', 'contract', 'schedule', 'pay']
                .filter((name) => this.isValid(name)).length;
        },

        get canSubmit() {
            return this.completedCount === 4 && !this.submitting && !this.loading;
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

        onProgramChange() {
            this.selected.center = ALL_CENTERS;
            this.selected.position = '';
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

        get durationLabel() {
            if (!this.startDate || !this.endDate || this.endDate < this.startDate) {
                return '';
            }

            const start = new Date(`${this.startDate}T00:00:00`);
            const end = new Date(`${this.endDate}T00:00:00`);

            let months = (end.getFullYear() - start.getFullYear()) * 12
                + (end.getMonth() - start.getMonth());

            if (end.getDate() < start.getDate()) {
                months -= 1;
            }

            months = Math.max(0, months);

            const anchor = new Date(start);
            anchor.setMonth(anchor.getMonth() + months);

            const days = Math.max(0, Math.round((end - anchor) / 86400000));

            const parts = [];

            if (months > 0) {
                parts.push(`${months} ${months === 1 ? this.labels.month : this.labels.months}`);
            }

            if (days > 0 || parts.length === 0) {
                parts.push(`${days} ${days === 1 ? this.labels.day : this.labels.days}`);
            }

            return parts.join(' · ');
        },

        // =============================================================
        // Schedule
        // =============================================================

        toggleDay(index) {
            this.days[index] = !this.days[index];
        },

        get selectedDayCount() {
            return this.days.filter(Boolean).length;
        },

        get workDaysEn() {
            if (this.manualDays) {
                return this.workDaysEnManual.trim();
            }

            return this.days
                .map((on, index) => (on ? this.dayNames.en[index] : null))
                .filter(Boolean)
                .join(', ');
        },

        get workDaysAr() {
            if (this.manualDays) {
                return this.workDaysArManual.trim();
            }

            return this.days
                .map((on, index) => (on ? this.dayNames.ar[index] : null))
                .filter(Boolean)
                .join(this.listSeparator);
        },

        /** Hours between the two times, rolling over midnight when needed. */
        get hoursPerDay() {
            const toMinutes = (value) => {
                const [h, m] = str(value).split(':').map(Number);

                return Number.isFinite(h) && Number.isFinite(m) ? h * 60 + m : null;
            };

            const from = toMinutes(this.workHoursFrom);
            const to = toMinutes(this.workHoursTo);

            if (from === null || to === null) {
                return 0;
            }

            const span = to > from ? to - from : to + 1440 - from;

            return Math.round((span / 60) * 100) / 100;
        },

        get computedWeeklyHours() {
            return Math.round(this.selectedDayCount * this.hoursPerDay);
        },

        get weeklyHours() {
            return this.weeklyHoursOverride === null
                ? this.computedWeeklyHours
                : Number(this.weeklyHoursOverride) || 0;
        },

        get weeklyHoursIsAuto() {
            return this.weeklyHoursOverride === null;
        },

        startOverride() {
            this.weeklyHoursOverride = String(this.computedWeeklyHours);
            this.$nextTick(() => this.$refs.weeklyHours?.focus());
        },

        clearOverride() {
            this.weeklyHoursOverride = null;
        },

        enableManualDays() {
            this.workDaysEnManual = this.workDaysEn;
            this.workDaysArManual = this.workDaysAr;
            this.manualDays = true;
        },

        disableManualDays() {
            this.manualDays = false;
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

        get hoursDerivation() {
            return this.fill(this.labels.derivation, {
                days: this.selectedDayCount,
                hours: this.hoursPerDay,
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

        get cardPeriod() {
            return this.durationLabel || this.labels.empty;
        },

        get cardHours() {
            return this.weeklyHours >= 1 ? `${this.weeklyHours} h` : this.labels.empty;
        },

        get cardSalary() {
            return this.salary === ''
                ? this.labels.empty
                : `${this.formatNumber(this.salary)} ${this.currencyLabel}`;
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

            if (!this.selected.employee || !this.startDate || !this.endDate || this.weeklyHours < 1) {
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
        // Draft
        // =============================================================

        get persistable() {
            return JSON.stringify(this.snapshot());
        },

        snapshot() {
            return {
                selected: this.selected,
                startDate: this.startDate,
                endDate: this.endDate,
                days: this.days,
                workHoursFrom: this.workHoursFrom,
                workHoursTo: this.workHoursTo,
                weeklyHoursOverride: this.weeklyHoursOverride,
                manualDays: this.manualDays,
                workDaysEnManual: this.workDaysEnManual,
                workDaysArManual: this.workDaysArManual,
                attendingNotesEn: this.attendingNotesEn,
                attendingNotesAr: this.attendingNotesAr,
                salary: this.salary,
                currency: this.currency,
                salaryPer: this.salaryPer,
            };
        },

        scheduleDraftSave() {
            clearTimeout(this.draftTimer);

            this.draftTimer = setTimeout(() => {
                if (!this.selected.employee && !this.selected.position && this.salary === '') {
                    return;
                }

                try {
                    sessionStorage.setItem(this.draftKey, this.persistable);
                } catch {
                    // Storage unavailable (private mode, quota) — drafting is a
                    // convenience, never a requirement.
                }
            }, 400);
        },

        readDraft() {
            try {
                const raw = sessionStorage.getItem(this.draftKey);

                return raw ? JSON.parse(raw) : null;
            } catch {
                return null;
            }
        },

        clearDraft() {
            this.draftAvailable = false;

            try {
                sessionStorage.removeItem(this.draftKey);
            } catch {
                // ignored — see scheduleDraftSave()
            }
        },

        async restoreDraft() {
            const draft = this.readDraft();

            this.draftAvailable = false;

            if (!draft) {
                return;
            }

            Object.assign(this, {
                ...draft,
                selected: { ...this.selected, ...draft.selected },
            });

            if (this.selected.city) {
                await this.loadCityOptions(false);
            }
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
                    contractTypeId: this.selected.contractType,
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
                });

                this.clearDraft();
            } finally {
                this.submitting = false;
            }
        },
    }));
});
