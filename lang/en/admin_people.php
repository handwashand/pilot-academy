<?php

/*
 * The People screens: users (form, list, their tabs, the progress export) and
 * partner companies. Keys mirror in every language (see
 * StudentSiteTranslationTest).
 */

return [
    'users' => [
        'password' => 'Password',
        'password_help' => 'Leave blank to keep the current password when editing.',
        'company_help' => 'Partner company this user belongs to (leave empty for admins).',
        'role' => 'Role',
        'role_help' => 'Admins run the platform. Creators manage the training for their own products only. Learners take courses.',
        'products' => 'Products / modules',
        'products_help' => 'The products this creator owns the training for. They cannot see any other product\'s courses.',
        'product_filter' => 'Product / module',
        'all_users' => 'All users',
        'admins' => 'Admins',
        'creators' => 'Creators',
        'learners' => 'Learners',
        'permissions' => 'Extra permissions',
        'permissions_help' => 'Granted per account. These are not included in the Admin or Creator roles by default.',
        'manage_languages' => 'Manage languages',
        'manage_translations' => 'Manage translations',
        'lessons_done' => 'Lessons done',
        'last_login' => 'Last login',
        'export' => 'Export learner progress',
        'access_link' => 'Access link',
        'access_link_heading' => 'Personal access link',
        'copy_link' => 'Copy link',
        'copied' => 'Copied!',
        'access_link_help' => 'Send this link to the user. Opening it signs them in without a password, and their progress is saved to this account.',
        'csv' => [
            'name' => 'Name',
            'email' => 'Email',
            'partner' => 'Partner',
            'lessons_completed' => 'Lessons completed',
            'certificates' => 'Certificates',
            'last_activity' => 'Last activity',
            'last_login' => 'Last login',
            'joined' => 'Joined',
        ],
    ],

    'tabs' => [
        'started' => 'Started',
        'completed' => 'Completed',
        'action' => 'Action',
        'details' => 'Details',
    ],

    'companies' => [
        'name' => 'Company name',
        'region' => 'Region',
        'region_help' => 'e.g. EMEA, LATAM, CIS',
        'industry' => 'Industry',
        'industry_help' => 'e.g. Logistics, Construction',
        'members' => 'Members',
        'certified' => 'Certified',
        'certified_tip' => 'Students with at least one valid certificate, out of total members.',
    ],
];
