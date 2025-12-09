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
    import './donut-chart.js'
    import './scatter-chart.js'

import Tagify from '@yaireo/tagify'


    window.Swal = Swal
    window.Chart = Chart;

if (typeof window.preloaderScriptLoaded === 'undefined') {
    window.preloaderScriptLoaded = true;

    const overlay = document.getElementById('page-gif-overlay');
    const MIN_TIME = 3200;
    const start = performance.now();
    let isHiding = false;

    function hidePreloader() {
        if (!overlay || isHiding) {
            return;
        }
        isHiding = true;

        overlay.style.transition = "opacity 0.6s ease";
        overlay.style.opacity = "0";

        setTimeout(() => {
            overlay.style.display = "none";
        }, 600); // Matched to the transition duration
    }

    function scheduleHide() {
        const elapsed = performance.now() - start;
        const remaining = MIN_TIME - elapsed;
        setTimeout(hidePreloader, Math.max(0, remaining));
    }

    // For Livewire apps, 'livewire:load' is the most reliable event
    // to signal that the page is ready.
    document.addEventListener("livewire:load", scheduleHide);

    // Fallback to ensure the loader always hides, even if 'livewire:load' fails to fire.
    setTimeout(hidePreloader, MIN_TIME + 1500);
}
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

    if (hidden.value) {
        try {
            const values = JSON.parse(hidden.value)
            tagify.addTags(values)
        } catch (e) {
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






