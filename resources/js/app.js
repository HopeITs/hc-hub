import '@yaireo/tagify/dist/tagify.css'
import '../css/app.css';

import Swal from 'sweetalert2'
import 'flowbite';
import './password-toggle.js'
import './password-strength.js'
import Tagify from '@yaireo/tagify'

window.Swal = Swal

document.querySelectorAll('.tagify').forEach((input) => {
    let whitelist = []
    let enforceWhitelist = true;

    if (input.dataset.whitelist) {
        whitelist = input.dataset.whitelist.split(',') // just split by comma
        enforceWhitelist = input.dataset.enforceWhitelist ?? true;
    }


    new Tagify(input, {
        whitelist: whitelist,
        enforceWhitelist: enforceWhitelist
    })
})
