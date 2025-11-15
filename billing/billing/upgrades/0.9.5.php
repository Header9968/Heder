<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2025
 */

require_once MODULE_PATH . '/helpers/install.functions.php';

$_version = '0.9.5';

$tableSchema = [
    "CREATE TABLE IF NOT EXISTS `" . USERPREFIX . "_billing_whatsapp_codes` (
        `code_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `phone` varchar(20) NOT NULL,
        `code_hash` varchar(128) NOT NULL,
        `attempts` smallint(5) unsigned NOT NULL DEFAULT '0',
        `resend_count` smallint(5) unsigned NOT NULL DEFAULT '0',
        `daily_sent` smallint(5) unsigned NOT NULL DEFAULT '0',
        `daily_reset` int(11) NOT NULL DEFAULT '0',
        `last_sent` int(11) NOT NULL DEFAULT '0',
        `expires_at` int(11) NOT NULL DEFAULT '0',
        `blocked_until` int(11) NOT NULL DEFAULT '0',
        `verified` tinyint(1) NOT NULL DEFAULT '0',
        `verified_at` int(11) NOT NULL DEFAULT '0',
        `session_token` varchar(64) NOT NULL DEFAULT '',
        `ip` varchar(45) NOT NULL DEFAULT '',
        `channel` varchar(16) NOT NULL DEFAULT 'whatsapp',
        `payload` text,
        `created_at` int(11) NOT NULL DEFAULT '0',
        `updated_at` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`code_id`),
        UNIQUE KEY `phone` (`phone`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . COLLATE . " AUTO_INCREMENT=1;",
    "CREATE TABLE IF NOT EXISTS `" . USERPREFIX . "_billing_user_profiles` (
        `profile_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `user_id` int(10) unsigned NOT NULL,
        `phone` varchar(20) NOT NULL,
        `school` varchar(191) NOT NULL,
        `display_name` varchar(191) NOT NULL,
        `meta` text,
        `created_at` int(11) NOT NULL DEFAULT '0',
        `updated_at` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`profile_id`),
        UNIQUE KEY `phone` (`phone`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . COLLATE . " AUTO_INCREMENT=1;",
];

$configDefaults = [
    'invoice_time' => "0",
    'register_group' => "4",
    'register_login_field' => "phone",
    'register_login_url' => "/index.php?do=login",
    'register_success_redirect' => "",
    'whatsapp_enabled' => "0",
    'whatsapp_phone_id' => "",
    'whatsapp_token' => "",
    'whatsapp_template' => "",
    'whatsapp_language' => "ru",
    'whatsapp_require_code' => "1",
    'whatsapp_code_ttl' => "5",
    'whatsapp_resend_timeout' => "60",
    'whatsapp_daily_limit' => "5",
    'whatsapp_attempt_limit' => "5",
    'whatsapp_block_minutes' => "30",
    'whatsapp_stub_mode' => "0",
    'whatsapp_debug_phone' => "",
];

if( isset( $_POST['next'] ) or isset($_GET['install']) )
{
    if( $_GET['install'] !== 'ignore' )
    {
        if( file_exists(ROOT_DIR . '/templates/' . $this->Dashboard->dle['skin'] . '/billing/' ) )
        {
            if( $_GET['install'] === 'rewrite' )
            {
                if( rename(ROOT_DIR . '/templates/' . $this->Dashboard->dle['skin'] . '/billing/', ROOT_DIR . '/templates/' . $this->Dashboard->dle['skin'] . '/billing_old_' . time() . '/') )
                {
                    if( ! copy_folder(ENGINE_DIR . '/modules/billing/install/_template_/', ROOT_DIR . '/templates/' . $this->Dashboard->dle['skin'] ) )
                    {
                        msg( "error", $this->Dashboard->lang['install_bad'], "<div style=\"text-align: left\">" . sprintf($this->Dashboard->lang['install_error_templates_error2'], '/templates/' . $this->Dashboard->dle['skin'] ) . "</div>", array( "?mod=billing&install=ignore" => $this->Dashboard->lang['main_re']) );
                    }
                }
                else
                {
                    msg( "error", $this->Dashboard->lang['install_bad'], "<div style=\"text-align: left\">" . sprintf($this->Dashboard->lang['install_error_templates_error'], '/templates/' . $this->Dashboard->dle['skin'] . '/billing/') . "</div>", array( "?mod=billing&install=rewrite" => $this->Dashboard->lang['main_re']) );
                }
            }
            else
            {
                msg( "warning", $this->Dashboard->lang['install_bad'], "<div style=\"text-align: left\">" . sprintf($this->Dashboard->lang['install_error_templates'], '/templates/' . $this->Dashboard->dle['skin'] . '/billing/') . "</div>", array( "?mod=billing&install=rewrite" => $this->Dashboard->lang['main_next']) );
            }
        }
        else
        {
            if( ! copy_folder(ENGINE_DIR . '/modules/billing/install/_template_/', ROOT_DIR . '/templates/' . $this->Dashboard->dle['skin'] ) )
            {
                msg( "error", $this->Dashboard->lang['install_bad'], "<div style=\"text-align: left\">" . sprintf($this->Dashboard->lang['install_error_templates_error2'], '/templates/' . $this->Dashboard->dle['skin'] ) . "</div>", array( "?mod=billing&install=ignore" => $this->Dashboard->lang['main_re']) );
            }
        }
    }

    if( ! $_GET['install'] )
    {
        foreach($tableSchema as $sqlquery)
        {
            $this->Dashboard->LQuery->db->query($sqlquery);
        }
    }

    $newConfig = $this->Dashboard->config;

    foreach ($configDefaults as $key => $value)
    {
        if( !isset($newConfig[$key]) )
        {
            $newConfig[$key] = $value;
        }
    }

    $newConfig['version'] = $_version;

    $this->Dashboard->SaveConfig("config", $newConfig );
    $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['upgrade_ok'] . $_version, '?mod=billing' );
}

$this->Dashboard->ThemeEchoHeader();

$Content = $this->Dashboard->ThemeHeadStart( $this->Dashboard->lang['upgrade_title'] . $_version );

$Content .= "<div class='quote' style='margin: 10px'><b>" . $this->Dashboard->lang['upgrade_wsql'] . "</b>
    <pre>" . implode("\n", $tableSchema) . "</pre>
</div>";

$Content .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("next", $this->Dashboard->lang['main_next'], "blue") );

$Content .= $this->Dashboard->ThemeHeadClose();
$Content .= $this->Dashboard->ThemeEchoFoother();

echo $Content;
