<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

Class ADMIN extends PluginActions
{
	private array $_Config = [];
	private array $_Lang = [];

	function __construct()
	{
		$this->_Lang = include MODULE_PATH . "/plugins/prcode/lang.php";
	}

	public function main( array $GET = [] )
	{
        $this->checkInstall();

		# Сохранить настройки
		#
		if( isset( $_POST['save'] ) )
		{
			$this->Dashboard->CheckHash();

            $_POST['save_con']['version'] = parse_ini_file( MODULE_PATH . '/plugins/' . $this->Dashboard->controller . '/info.ini' )['version'];

			$this->Dashboard->SaveConfig('plugin.prcode', $_POST['save_con']);

			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['save_settings'] );
		}

		$_Config = $this->Dashboard->LoadConfig( "prcode" );

		# Удалить отмеченные
		#
		if( isset( $_POST['bnt_remove_select'] ) )
		{
			$this->Dashboard->CheckHash();

			$MassList = $_POST['massact_list'];

			foreach( $MassList as $id )
			{
				$id = intval( $id );

				if( ! $id ) continue;

				$this->Dashboard->LQuery->db->query( "DELETE FROM " . USERPREFIX . "_billing_prcodes WHERE prcode_id='$id'" );
			}

			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->_Lang['ap_remove_ok'] );
		}

		# Создать промо коды
		#
		if( isset( $_POST['btnGenerate'] ) )
		{
			$this->Dashboard->CheckHash();

			$_Answer = '';

			$_Theme = $this->Dashboard->LQuery->db->safesql( $_POST['get_theme'] );
			$_Sum = $this->Dashboard->API->Convert( $_POST['get_sum'] );
			$_Declension = $this->Dashboard->API->Declension( $_POST['get_sum'] );

			for( $n = 1; $n <= intval( $_POST['get_num'] ); $n ++ )
			{
				$_prCode = $_Theme;

				while( true )
				{
					$pos = strripos($_prCode, '0');

					if( ! $pos )
					{
						break;
					}

					$_prCode = substr_replace($_prCode, $this->generate(), $pos, 1);
				}

				$_prCode = substr_replace($_prCode, $this->generate(), 0, 1);

				$this->Dashboard->LQuery->db->query( "INSERT INTO " . USERPREFIX . "_billing_prcodes
														(prcode_tag, prcode_sum) values
														('" . $_prCode . "', '" . $_Sum . "')" );


				$_Answer .= '<tr><td>' . $_prCode . '</td><td>' . $_Sum . ' ' . $_Declension . '</td></tr>';
			}

			$this->Dashboard->ThemeMsg( $this->_Lang['ap_gen_ok'], '<table class="table table-normal table-hover">' . $_Answer . '</table>', '?mod=billing&c=prcode' );
		}

		# Список
		#
		$this->Dashboard->ThemeAddTR( array(
			'<td width="1%">#</td>',
			'<td>' . $this->_Lang['ap_code'] . '</td>',
			'<td>' . $this->_Lang['ap_sum'] . '</td>',
			'<td>' . $this->_Lang['ap_active'] . '</td>',
			'<td>' . $this->_Lang['ap_time_active'] . '</td>',
			'<td width="2%"><center><input type="checkbox" value="" name="massact_list[]" onclick="checkAll(this)" /></center></td>'
		));

		$PerPage = $this->Dashboard->config['paging'];

		$StartFrom = $GET['page'];

		$this->Dashboard->LQuery->parsPage( $StartFrom, $PerPage );

		$ResultCount = $this->Dashboard->LQuery->db->super_query( "SELECT COUNT(*) as count
																	FROM " . USERPREFIX . "_billing_prcodes
																	ORDER BY prcode_id DESC" );

		$NumData = $ResultCount['count'];

		$this->Dashboard->LQuery->db->query( "SELECT * FROM " . USERPREFIX . "_billing_prcodes
												ORDER BY prcode_active_date, prcode_id DESC
												LIMIT {$StartFrom}, {$PerPage}" );

		while ( $Value = $this->Dashboard->LQuery->db->get_row() )
		{
			$this->Dashboard->ThemeAddTR( array(
				$Value['prcode_id'],
				$Value['prcode_active_user'] ? '<span style="text-decoration:line-through">' . $Value['prcode_tag'] . '</span>' : $Value['prcode_tag'],
				$Value['prcode_sum'] . ' ' . $this->Dashboard->API->Declension( $Value['prcode_sum'] ),
				$Value['prcode_active_user'] ? $this->Dashboard->ThemeInfoUser( $Value['prcode_active_user'] ) : '',
				$Value['prcode_active_user'] ? $this->Dashboard->ThemeChangeTime( $Value['prcode_active_date'] ) : '',
				"<center><input name=\"massact_list[]\" value=\"".$Value['prcode_id']."\" type=\"checkbox\"></center>"
			) );
		}

		$TabFirst = $this->Dashboard->ThemeParserTable();

		if( $NumData)
		{
			$TabFirst .= $this->Dashboard->ThemePadded( '
				<div class="pull-left" style="margin:7px; vertical-align: middle">
					<ul class="pagination pagination-sm">' .
						$this->Dashboard->API->Pagination(
							$NumData,
							$GET['page'],
							$PHP_SELF . "?mod=billing&c=prcode&p=page/{p}",
							"<li><a href=\"{page_num_link}\">{page_num}</a></li>",
							"<li class=\"active\"><span>{page_num}</span></li>",
							$PerPage ) . '
						</ul>
					</ul>
				</div>

				<span style="float: right"><input class="btn" style="vertical-align: middle" name="bnt_remove_select" type="submit" value="' . $this->_Lang['ap_remove_selected'] . '"></span>
				
				<input type="hidden" name="user_hash" value="' . $this->Dashboard->hash . '" />',
				'box-footer', 'right' );
		}
		else
		{
			$TabFirst .= $this->Dashboard->ThemePadded( $this->Dashboard->lang['history_no'], '' );
		}

		$tabs[] = array(
			'id' => 'list',
			'title' => $this->_Lang['ap_codes'],
			'content' => $TabFirst
		);

		# Форма создания кодов
		#
		$this->Dashboard->ThemeAddStr(
			$this->_Lang['ap_num'],
			$this->_Lang['ap_num_desc'],
			"<input name=\"get_num\" class=\"form-control\" type=\"text\" style=\"width: 100%\" value=\"10\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->_Lang['ap_getsum'],
			$this->_Lang['ap_getsum_desc'],
			"<input name=\"get_sum\" class=\"form-control\" type=\"text\" style=\"width: 30%\" value=\"10.00\"> " . $this->Dashboard->API->Declension( 10 )
		);

		$this->Dashboard->ThemeAddStr(
			$this->_Lang['ap_theme'],
			$this->_Lang['ap_theme_desc'],
			"<input name=\"get_theme\" class=\"form-control\" type=\"text\" style=\"width: 100%\" value=\"0000-0000-0000-0000\">"
		);

		$tabs[] = array(
			'id' => 'create',
			'title' => $this->_Lang['ap_create'],
			'content' => $this->Dashboard->ThemeParserStr() . $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("btnGenerate", $this->_Lang['ap_create_btn'], "green") )
		);

		# Настройка плагина
		#
		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_status'],
			$this->Dashboard->lang['refund_status_desc'],
			$this->Dashboard->MakeCheckBox("save_con[status]", $_Config['status'])
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['paysys_name'],
			$this->Dashboard->lang['refund_name_desc'],
			"<input name=\"save_con[name]\" size=\"50\" class=\"form-control\" type=\"text\" value=\"" . $_Config['name'] ."\">"
		);

		$tabs[] = array(
			'id' => 'settings',
			'title' => 'Настройки',
			'content' => $this->Dashboard->ThemeParserStr() . $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green") )
		);

		$this->Dashboard->ThemeEchoHeader( $this->_Lang['title'] );

		$Content = $this->Dashboard->PanelPlugin('plugins/prcode', 'https://dle-billing.ru/doc/plugins/prcode/' );
		$Content .= $this->Dashboard->PanelTabs( $tabs );
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}

	private function generate()
	{
		$chars = 'ABDEFGHKNQRSTYZ23456789';

		return substr($chars, rand(1, strlen($chars)) - 1, 1);
	}

    /**
     * Процесс установки
     * @return void
     */
    public function install()
    {
        $this->Dashboard->CheckHash();

        @unlink(ROOT_DIR . '/engine/data/billing/plugin.prcode.php');

        $tableSchema = [];

        $tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_billing_prcodes";
        $tableSchema[] = "CREATE TABLE IF NOT EXISTS `" . PREFIX . "_billing_prcodes` (
							  `prcode_id` int(11) NOT NULL AUTO_INCREMENT,
							  `prcode_tag` varchar(128) NOT NULL,
							  `prcode_sum` varchar(21) NOT NULL,
							  `prcode_active_user` varchar(21) NOT NULL,
							  `prcode_active_date` int(11) NOT NULL,
							  PRIMARY KEY (`prcode_id`)
							) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

        foreach( $tableSchema as $table )
        {
            $this->Dashboard->LQuery->db->query($table);
        }

        $default = [
            'status' => '0',
            'version' => parse_ini_file( MODULE_PATH . '/plugins/prcode/info.ini' )['version']
        ];

        $this->Dashboard->SaveConfig('plugin.prcode', $default);

        $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['plugin_install'], '?mod=billing&c=' . $this->Dashboard->controller );
    }

    public function uninstall()
    {
        $this->Dashboard->CheckHash();

        @unlink(ROOT_DIR . '/engine/data/billing/plugin.prcode.php');

        $this->Dashboard->LQuery->db->query( "DROP TABLE IF EXISTS " . PREFIX . "_billing_prcodes" );

        $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['plugin_uninstall'], '?mod=billing' );
    }
}