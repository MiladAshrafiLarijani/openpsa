// Handle / MidCOM help system welcome screen
'welcome' => array
(
    'handler' => array('midcom_admin_help_help', 'welcome'),
),
// Handle /<component> component documentation ToC
'component' => array
(
    'handler' => array('midcom_admin_help_help', 'component'),
    'variable_args' => 1,
),
// Handle /<component>/<help id> display help page from a component
'help' => array
(
    'handler' => array('midcom_admin_help_help', 'help'),
    'variable_args' => 2,
),
