'name' => 'org.routamc.positioning',
'icon' => 'stock-icons/16x16/geoclue.png',
'purecode' => true,
'privileges' => array(),
'class_mapping' => array
(
    'org_routamc_positioning_country' => 'org_routamc_positioning_country_dba',
    'org_routamc_positioning_city' => 'org_routamc_positioning_city_dba',
    'org_routamc_positioning_aerodrome' => 'org_routamc_positioning_aerodrome_dba',
    'org_routamc_positioning_location' => 'org_routamc_positioning_location_dba',
    'org_routamc_positioning_log' => 'org_routamc_positioning_log_dba',
),
'customdata' => array
(
    'midcom.services.cron' => array
    (
        array
        (
            'handler' => 'org_routamc_positioning_cron_fetchicbm',
            'recurrence' => MIDCOM_CRON_HOUR,
        ),
        array
        (
            'handler' => 'org_routamc_positioning_cron_fetchgeorss',
            'recurrence' => MIDCOM_CRON_HOUR,
        ),
    ),
),
