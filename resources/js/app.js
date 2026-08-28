import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;

import select2 from 'select2';
select2();

import '../css/app.css';
import {initFlowbite} from 'flowbite';

import {
    Chart,
    DoughnutController,
    BarController,
    LineController,
    BarElement,
    LineElement,
    ArcElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
    Title
} from 'chart.js';

Chart.register(DoughnutController, BarController, LineController, BarElement, LineElement, ArcElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend, Title);

import Swal from 'sweetalert2'

import 'flowbite';

import './password-toggle.js'
import './password-strength.js'
import './contract-type-employees-chart.js';
import './job-levels-employees.js';
import './donut-chart.js'
import './scatter-chart.js'
import { initClockTimepickers } from './clock-timepicker.js';

window.Swal = Swal
window.Chart = Chart;

document.addEventListener('DOMContentLoaded', () => initClockTimepickers());

Livewire.hook('commit', ({component, commit, respond, succeed, fail}) => {
    succeed(({snapshot, effect}) => {
        queueMicrotask(() => {
            initFlowbite();
            initClockTimepickers();
        })
    })
})

document.addEventListener('livewire:navigated', () => {
    initFlowbite();
    initClockTimepickers();
})

// When a Select2 dropdown opens, flag its results list with `.results--scroll`
// only if it actually scrolls, so the hover highlight can be trimmed on the
// scrollbar side (see `.results--scroll` rule in app.css).
$(document).on('select2:open', function () {
    requestAnimationFrame(function () {
        document.querySelectorAll('.select2-results__options').forEach(function (ul) {
            ul.classList.toggle('results--scroll', ul.scrollHeight > ul.clientHeight + 1);
        });
    });
});

