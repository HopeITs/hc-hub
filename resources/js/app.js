import '@yaireo/tagify/dist/tagify.css'
import '../css/app.css';

    import {
        Chart,DoughnutController, BarController, LineController, BarElement, LineElement,ArcElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend, Title
            } from 'chart.js';
    Chart.register(DoughnutController,BarController, LineController, BarElement, LineElement,ArcElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend, Title );

    import Swal from 'sweetalert2'

    import 'flowbite';

    import './password-toggle.js'
    import './password-strength.js'
    import './contract-type-employees-chart.js';
    import './job-levels-employees.js';
    import './employees-by-pay-type.js'


import Tagify from '@yaireo/tagify'


    window.Swal = Swal
    window.Chart = Chart;

document.querySelectorAll('.tagify').forEach((el) => {
    const whitelist = el.dataset.whitelist ? el.dataset.whitelist.split(',') : []
    const enforceWhitelist = el.dataset.enforcewhitelist !== "0"

    const tagify = new Tagify(el, {
        whitelist,
        enforceWhitelist,
        dropdown: {

            enabled: 0,

        }

    })

    const hidden = document.getElementById(el.id + '_hidden')

    // preload existing values from Livewire into Tagify
    if (hidden.value) {
        try {
            const values = JSON.parse(hidden.value)
            tagify.addTags(values)
        } catch (e) {
            // if backend sent CSV instead of JSON
            tagify.addTags(hidden.value.split(','))
        }
    }

    function sync() {
        const values = tagify.value.map(t => t.value)
        hidden.value = JSON.stringify(values)
        hidden.dispatchEvent(new Event('input', { bubbles: true }))
    }

    tagify.on('add', sync)
    tagify.on('remove', sync)
    tagify.on('edit', sync)
})






