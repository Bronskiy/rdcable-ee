<?php

if (!defined('SIMPLE_MAILCHIMP_VERSION')) {
    define('SIMPLE_MAILCHIMP_NAME', 'Simple MailChimp');
    define('SIMPLE_MAILCHIMP_VERSION', '1.5.3');
}

$config['name'] = SIMPLE_MAILCHIMP_NAME;
$config['version'] = SIMPLE_MAILCHIMP_VERSION;
$config['nsm_addon_updater']['versions_xml'] = 'http://jeremyworboys.com/add-ons/releases/simple-mailchimp';
