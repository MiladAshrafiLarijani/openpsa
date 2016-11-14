'name' => 'midgard.admin.user',
'description' => 'User management tool',
'icon' => 'midgard.admin.user/user-manager.png',
'purecode' => true,
'version' => '9.1.0-dev',
'privileges' => array
(
    'access' => MIDCOM_PRIVILEGE_DENY,
),
'class_mapping' => array(),
'package.xml' => array
(
    'license' => 'LGPL',
    'maintainers' => array
    (
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
            'role' => 'lead',
        ),
        'adrenalin' => array
        (
            'name' => 'Arttu Manninen',
            'email' => 'arttu@kaktus.cc',
            'role' => 'lead',
        ),
    ),
    'dependencies' => array
    (
        'midcom.admin.help' => array(),
        'midcom.helper.datamanager2' => array(),
        'midcom' => array(),
    ),
),
'state' => 'stable',
'customdata' => array
(
    'asgard_plugin' => array
    (
        'class' => 'midgard_admin_user_plugin',
        'name' => 'User Management',
        'config' => '',
    ),
),