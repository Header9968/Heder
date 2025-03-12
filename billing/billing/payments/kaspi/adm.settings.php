<?php	if( ! defined( 'BILLING_MODULE' ) ) die( "Hacking attempt!" );


Class Payment
{
	var $doc = 'kaspi.kz';

	function Settings( $config )
	{
		$Form = array();

		$Form[] = array(
			"Кошелек продавца:",
			"Кошелек продавца, на который покупатель должен совершить платеж. Формат – буква и 12 цифр. ",
			"<input name=\"save_con[wm]\" class=\"edit bk\" type=\"text\" value=\"" . $config['wm'] ."\" style=\"width: 100%\">"
		);

	

		return $Form;
	}

	function Form( $id, $config, $invoice, $currency, $desc )
	{
		return '
			<form method="post" id="paysys_form" accept-charset="windows-1251" action="/kaspi.html">
				<input name="lmi_payment_desc" value="'.$desc.'" type="hidden">
				<input name="lmi_payment_no" value="'.$id.'" type="hidden">
				<input name="lmi_payment_amount" value="'.$invoice['invoice_pay'].'" type="hidden">
				<input name="lmi_sim_mode" value="0" type="hidden">
				<input name="lmi_payee_purse" value="'.$config['wm'].'" type="hidden">
				<input type="submit" class="btn" value="Оплатить">
			</form>';

	
}

	function check_id( $data )
	{
		return $data["LMI_PAYMENT_NO"];
	}

	function check_payer_requisites( $data )
	{
		return 'WMID' . $data["LMI_PAYER_WM"];
	}

	function check_ok( $data )
	{
		return 'YES';
	}

	function check_out( $data, $config, $invoice )
	{
		if( ! $data['LMI_PAYMENT_AMOUNT'] or $data['LMI_PAYMENT_AMOUNT'] != $invoice['invoice_pay'] )
		{
			return "Error: PAYMENT_AMOUNT";
		}

		if( ! $data['LMI_PAYEE_PURSE'] or $data['LMI_PAYEE_PURSE'] != $config['wm'] )
		{
			return "Error: LMI_PAYEE_PURSE";
		}

		IF( $data['LMI_PREREQUEST'] == 1 )
		{
			return "YES";
		}

		$sign = strtoupper( hash("sha256", $data['LMI_PAYEE_PURSE'].$data['LMI_PAYMENT_AMOUNT'].$data['LMI_PAYMENT_NO'].$data['LMI_MODE'].$data['LMI_SYS_INVS_NO'].$data['LMI_SYS_TRANS_NO'].$data['LMI_SYS_TRANS_DATE'].$config['key'].$data['LMI_PAYER_PURSE'].$data['LMI_PAYER_WM'] ) );

		if( $data['LMI_HASH'] == $sign )
		{
			return 200;
		}

		return "Error: bad sign";
	}
}

$Paysys = new Payment;
	
?>
