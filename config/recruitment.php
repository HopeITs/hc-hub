<?php

return [
    /*
    | Roles that legitimately span the complete organization. HR-SYR is the
    | existing headquarters/HR-director role; hr-director is retained as an
    | explicit alias for installations that use the plan's role name.
    */
    'organization_roles' => [
        'super-admin',
        'general-manager',
        'HR-SYR',
        'hr-director',
    ],

    /*
    | Only these roles may open a recruitment request. Regional HR roles are
    | handled dynamically below when their HR-* suffix resolves to a city.
    */
    'request_creator_roles' => [
        'super-admin',
        'general-manager',
        'HR-SYR',
        'hr-director',
        'program-manager',
        'project-manager',
    ],

    /*
    | Structural scope is derived only for these roles. A direct permission
    | grant to an arbitrary role never silently expands access to the user's
    | whole city/program/department.
    */
    'structural_role_scopes' => [
        'program-manager' => ['program', 'project'],
        'project-manager' => ['project', 'program'],
        'manager' => ['program', 'project', 'department', 'position'],
        'finance' => ['program', 'project', 'department', 'city'],
    ],
];
