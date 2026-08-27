import jQuery from 'jquery';

const $ = jQuery;

const id = (value) => value === null || value === undefined || value === ''
    ? ''
    : String(value);

document.addEventListener('alpine:init', () => {
    window.Alpine.data('contractLocationPicker', (config) => ({
        loading: false,
        advancing: false,
        loadingText: config.loadingText,
        placeholder: config.placeholder,
        requestSequence: 0,
        selected: {
            city: id(config.cityId),
            employee: id(config.employeeId),
            program: id(config.programId),
            center: id(config.centerId),
            position: id(config.positionId),
            contractType: id(config.contractTypeId),
        },
        options: {
            employees: [],
            programs: [],
            centers: [],
            positions: [],
        },

        initialize() {
            this.$nextTick(() => {
                ['city', 'employee', 'program', 'center', 'position', 'contractType']
                    .forEach((name) => this.initializeSelect(name));

                this.setSelectValue('city', this.selected.city);
                this.setSelectValue('contractType', this.selected.contractType);

                if (this.selected.city) {
                    this.loadCityOptions(true);
                } else {
                    this.refreshDependentSelects();
                }
            });
        },

        destroy() {
            ['city', 'employee', 'program', 'center', 'position', 'contractType']
                .forEach((name) => {
                    const element = this.$refs[name];

                    if (! element) {
                        return;
                    }

                    const select = $(element);
                    select.off('.contract-location');

                    if (select.hasClass('select2-hidden-accessible')) {
                        select.select2('destroy');
                    }
                });
        },

        initializeSelect(name) {
            const element = this.$refs[name];

            if (! element) {
                return;
            }

            const select = $(element);

            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }

            select.select2({
                width: '100%',
                dir: document.documentElement.dir || 'rtl',
            });

            select
                .off('change.contract-location')
                .on('change.contract-location', () => {
                    this.selectionChanged(name, id(select.val()));
                });

            select.next('.select2').find('.select2-selection__rendered').removeAttr('title');
        },

        selectionChanged(name, value) {
            this.selected[name] = value;

            if (name === 'city') {
                this.clearSelections(['employee', 'program', 'center', 'position']);
                this.loadCityOptions(false);

                return;
            }

            if (name === 'program') {
                this.clearSelections(['center', 'position']);
                this.refreshDependentSelects();

                return;
            }

            if (name === 'center') {
                this.clearSelections(['position']);
                this.refreshPositionSelect();
            }
        },

        async loadCityOptions(preserveSelections) {
            const cityId = Number(this.selected.city);
            const request = ++this.requestSequence;

            if (! Number.isInteger(cityId) || cityId < 1) {
                this.options = { employees: [], programs: [], centers: [], positions: [] };
                this.loading = false;
                this.refreshDependentSelects();

                return;
            }

            this.loading = true;
            this.setDisabled('employee', true);
            this.setDisabled('program', true);
            this.setDisabled('center', true);
            this.setDisabled('position', true);

            try {
                const options = await this.$wire.locationOptions(cityId);

                if (request !== this.requestSequence) {
                    return;
                }

                this.options = {
                    employees: options.employees ?? [],
                    programs: options.programs ?? [],
                    centers: options.centers ?? [],
                    positions: options.positions ?? [],
                };

                if (! preserveSelections) {
                    this.clearSelections(['employee', 'program', 'center', 'position']);
                }

                this.dropUnavailableSelections();
            } finally {
                if (request === this.requestSequence) {
                    this.loading = false;
                    this.refreshDependentSelects();
                }
            }
        },

        dropUnavailableSelections() {
            if (! this.hasOption(this.options.employees, this.selected.employee)) {
                this.selected.employee = '';
            }

            if (! this.hasOption(this.options.programs, this.selected.program)) {
                this.selected.program = '';
                this.selected.center = '';
                this.selected.position = '';

                return;
            }

            if (! this.hasOption(this.availableCenters(), this.selected.center)) {
                this.selected.center = '';
            }

            if (! this.hasOption(this.availablePositions(), this.selected.position)) {
                this.selected.position = '';
            }
        },

        refreshDependentSelects() {
            this.replaceOptions('employee', this.options.employees, this.selected.employee);
            this.replaceOptions('program', this.options.programs, this.selected.program);
            this.replaceOptions('center', this.availableCenters(), this.selected.center);
            this.refreshPositionSelect();

            this.setDisabled('employee', this.loading || ! this.selected.city);
            this.setDisabled('program', this.loading || ! this.selected.city);
            this.setDisabled('center', this.loading || ! this.selected.program);
        },

        refreshPositionSelect() {
            const positions = this.availablePositions();
            this.replaceOptions('position', positions, this.selected.position);
            this.setDisabled('position', this.loading || ! this.selected.program || positions.length === 0);
        },

        availableCenters() {
            if (! this.selected.program) {
                return [];
            }

            return this.options.centers.filter((center) =>
                (center.programIds ?? []).map(id).includes(this.selected.program),
            );
        },

        availablePositions() {
            if (! this.selected.program) {
                return [];
            }

            return this.options.positions.filter((position) => {
                if (id(position.programId) !== this.selected.program) {
                    return false;
                }

                return this.selected.center
                    ? id(position.centerId) === this.selected.center
                    : id(position.centerId) === '';
            });
        },

        hasOption(options, selectedValue) {
            return selectedValue === '' || options.some((option) => id(option.value) === selectedValue);
        },

        clearSelections(names) {
            names.forEach((name) => {
                this.selected[name] = '';
                this.setSelectValue(name, '');
            });
        },

        replaceOptions(name, options, selectedValue) {
            const element = this.$refs[name];

            if (! element) {
                return;
            }

            const select = $(element);
            select.empty().append(new Option(this.placeholder, ''));

            options.forEach((option) => {
                select.append(new Option(option.label, id(option.value)));
            });

            this.setSelectValue(name, selectedValue);
        },

        setSelectValue(name, value) {
            const element = this.$refs[name];

            if (element) {
                $(element).val(id(value)).trigger('change.select2');
            }
        },

        setDisabled(name, disabled) {
            const element = this.$refs[name];

            if (element) {
                $(element).prop('disabled', disabled);
            }
        },

        async advanceIdentity() {
            if (this.loading || this.advancing) {
                return;
            }

            this.advancing = true;

            try {
                await this.$wire.advanceIdentity({
                    cityId: this.selected.city,
                    employeeId: this.selected.employee,
                    programId: this.selected.program,
                    centerId: this.selected.center,
                    positionId: this.selected.position,
                    contractTypeId: this.selected.contractType,
                });
            } finally {
                this.advancing = false;
            }
        },
    }));
});
