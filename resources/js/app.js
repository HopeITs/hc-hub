import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;

import select2 from 'select2';
select2(); // Mounts the plugin to jQuery
// 

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




window.Swal = Swal
window.Chart = Chart;

Livewire.hook('commit', ({component, commit, respond, succeed, fail}) => {
    succeed(({snapshot, effect}) => {
        queueMicrotask(() => {
            initFlowbite();
        })
    })
})

document.addEventListener('livewire:navigated', () => {
    initFlowbite();
})

