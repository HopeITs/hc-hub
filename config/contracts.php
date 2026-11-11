<?php

return [

    /*
    | Contract type ids (contract_types.id) mapped to the category the HR
    | rules depend on: trial contracts, service agreements and annual ones.
    */
    'map_by_id' => [
        1 => 'annual',
        3 => 'trial',
        5 => 'service',
    ],
];
