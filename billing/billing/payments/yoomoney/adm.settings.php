<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023, mr_Evgen
 */

Class Payment
{
	public string $doc = 'https://dle-billing.ru/doc/payments/yoomoney';

	function Settings( $config )
	{
		$Form = array();

		$Form[] = array(
			"Номер кошелька:",
			"Номер кошелька в системе ЮMoney",
			"<input name=\"save_con[yanumber]\" class=\"form-control\" type=\"text\" value=\"" . $config['yanumber'] ."\" style=\"width: 100%\">"
		);

		$Form[] = array(
			"Секретное слово:",
			"<a href='https://yoomoney.ru/transfer/myservices/http-notification' target='_blank'>Секретное слово</a> позволит вам проверять подлинность уведомлений.",
			"<input name=\"save_con[key]\" class=\"form-control\" type=\"password\" value=\"" . $config['key'] ."\" style=\"width: 100%\">"
		);

		return $Form;
	}

	function Form( $id, $payment_config, $invoice, $currency, $desc )
	{
		global $config;

		return '<form method="POST" id="paysys_form" action="https://yoomoney.ru/quickpay/confirm.xml">
				 <input type="hidden" name="receiver" value="'.$payment_config['yanumber'].'">
				 <input type="hidden" name="formcomment" value="'.$desc.'">
				 <input type="hidden" name="short-dest" value="'.$desc.'">
				 <input type="hidden" name="label" value="'.$id.'">
				 <input type="hidden" name="quickpay-form" value="donate">
				 <input type="hidden" name="targets" value="транзакция '.$id.'">
				 <input type="hidden" name="sum" value="'.$invoice['invoice_pay'].'" data-type="number" >
				 <input type="hidden" name="comment" value="'.$desc.'" >
				 <input type="hidden" name="need-fio" value="false">
				 <input type="hidden" name="need-email" value="false" >
				 <input type="hidden" name="need-phone" value="false">
				 <input type="hidden" name="need-address" value="false">
				 <input type="hidden" name="successURL" value="' . $config['http_home_url'] . 'billing.html/pay/ok/">
				 <input type="hidden" name="paymentType" id="paymentType" value="PC">

				 <script type="text/javascript">
				 var ydSelect = "yd_1";

					function yd_select( sel )
					{
						if( sel == ydSelect ) return;

						ydSelect = sel;

						if( sel == "yd_1" )
						{
							$("#yd_1").css("text-decoration", "underline");
							$("#yd_2").css("text-decoration", "none");
							$("#paymentType").val("PC");
						}
						else
						{
							$("#yd_1").css("text-decoration", "none");
							$("#yd_2").css("text-decoration", "underline");
							$("#paymentType").val("AC");
						}
					}
				 </script>

				 <p>Оплатить с <span id="yd_1" style="cursor: pointer; color: black; text-decoration: underline" onClick="yd_select( \'yd_1\' )">личного кошелька</span> или <span id="yd_2" style="cursor: pointer; color: black" onClick="yd_select( \'yd_2\' )">банковской картой</span></p>
				 <br />
				 <input type="submit" name="submit-button" class="btn" value="Оплатить">
				</form>';
	}

	function check_payer_requisites( $data )
	{
		return $data['sender'];
	}

	function check_id( $data )
	{
		return $data['label'];
	}

	function check_ok( $data )
	{
		return "HTTP 202 OK";
	}

	function check_out( $data, $config, $invoice )
	{
		$hash = sha1($data['notification_type'].'&'.$data['operation_id'].'&'.$data['amount'].'&'.$data['currency'].'&'.$data['datetime'].'&'.$data['sender'].'&'.$data['codepro'].'&'.$config['key'].'&'.$data['label']);

		if( $data['withdraw_amount'] != $invoice['invoice_pay'] )
		{
			return "Error sum";
		}

		if($hash !== $data['sha1_hash'])
		{
			return "Error hash";
		}

		return 200;
	}
}

$Paysys = new Payment;
?>
