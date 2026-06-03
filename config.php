<?php
define('LAMIX_BASE', 'http://51.210.208.26/ints');
define('AGENT_USERNAME', 'AHS_SHOVON');
define('AGENT_PASSWORD', '509124866');define('COOKIE_FILE',    sys_get_temp_dir() . '/AHSsms_agent_' . md5(AGENT_USERNAME) . '.txt');
define('SESSION_LIFE',   7200); // 2 hours

$COUNTRY_PAYOUTS = [
    'afghanistan'=>'0.00845','algeria'=>'0.0117','angola'=>'0.00975',
    'argentina'=>'0.00845','armenia'=>'0.00845','karabakh'=>'0.00845',
    'azerbaijan'=>'0.0065','belarus'=>'0.0065','benin'=>'0.00845',
    'bhutan'=>'0.0065','bolivia'=>'0.0065','bulgaria'=>'0.0065',
    'burkina'=>'0.0065','cambodia'=>'0.01495','cameroon'=>'0.00845',
    'comoros'=>'0.01495','ecuador'=>'0.0065','egypt'=>'0.0065',
    'ethiopia'=>'0.00845','gabon'=>'0.00845','georgia'=>'0.0078',
    'germany'=>'0.00845','guinea'=>'0.00845','indonesia'=>'0.0078',
    'iraq'=>'0.00845','ivory'=>'0.0065','jordan'=>'0.00845',
    'kazakhstan'=>'0.00845','kenya'=>'0.00845','kosovo'=>'0.0065',
    'kuwait'=>'0.0065','kyrgyzstan'=>'0.00845','lesotho'=>'0.00845',
    'libya'=>'0.00845','madagascar'=>'0.0078','malaysia'=>'0.01235',
    'mauritania'=>'0.00845','moldova'=>'0.0078','mongolia'=>'0.0065',
    'morocco'=>'0.00845','mozambique'=>'0.0065','myanmar'=>'0.01105',
    'nepal'=>'0.0065','niger'=>'0.00845','nigeria'=>'0.00845',
    'oman'=>'0.0078','pakistan'=>'0.00845','palestine'=>'0.01365',
    'russia many'=>'0.0065','russia'=>'0.00845','saudi'=>'0.0065',
    'senegal'=>'0.00845','slovenia (k)'=>'0.0065','slovenia'=>'0.0078',
    'sri lanka'=>'0.01495','srilanka'=>'0.01495','sri'=>'0.01495','lanka'=>'0.01495',
    'sudan sudatel'=>'0.0078','sudan'=>'0.0078','syria'=>'0.00845',
    'tajikistan'=>'0.0065','tanzania'=>'0.01625','tunisia'=>'0.00975',
    'turkey'=>'0.0078','turkmenistan'=>'0.00845','uganda'=>'0.0078',
    'ukraine'=>'0.0078','united arab'=>'0.0065','uae'=>'0.0065',
    'uzbekistan'=>'0.00845','vietnam mobifone'=>'0.00845','vietnam'=>'0.00845',
    'yemen'=>'0.00845','zambia'=>'0.00845','zimbabwe'=>'0.00845',
    'celcom'=>'0.01235','maxis'=>'0.01235','digi'=>'0.01235',
    'airtel'=>'0.00845','jio'=>'0.00845','mobifone'=>'0.00845',
];
define('DEFAULT_PAYOUT', '0.0065');

function getPayoutForRange($name) {
    global $COUNTRY_PAYOUTS;
    $lower = strtolower($name);
    $keys  = array_keys($COUNTRY_PAYOUTS);
    usort($keys, fn($a,$b) => strlen($b)-strlen($a));
    foreach ($keys as $k) {
        if (strpos($lower, $k) !== false) return $COUNTRY_PAYOUTS[$k];
    }
    return DEFAULT_PAYOUT;
}
