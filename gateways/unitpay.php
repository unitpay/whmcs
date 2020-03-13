<?php
function unitpay_config() {
    $configarray = array(
     "FriendlyName" => array("Type" => "System", "Value"=>"UnitPay"),
     "DOMAIN" => array("FriendlyName" => "Domain name", "Type" => "text", "Size" => "60", "Description" => "Your working domain" ),
     "URL" => array("FriendlyName" => "Payment Form URL", "Type" => "text", "Size" => "60", "Description" => "(Example: https://domain_name/pay/<b>262fde297f8e4e3d31e272d74aa39401</b>)" ),
     "SecretKey" => array("FriendlyName" => "Secret Key", "Type" => "text", "Size" => "60", ),
     "hideHint" => array("FriendlyName" => "Hide Hint", "Type" => "yesno","Description" => ""),
     "hideBackUrl" => array("FriendlyName" => "Hide Shop Url", "Type" => "yesno","Description" => ""),
     "hideOrderCost" => array("FriendlyName" => "Hide Order Cost", "Type" => "yesno","Description" => ""),
     "hideLogo" => array("FriendlyName" => "Hide UnitPay Logo", "Type" => "yesno","Description" => "")
    );
    return $configarray;
}
function unitpay_link($params) {
	global $_LANG;
	$code = '<form method="post" action="https://'.$params['DOMAIN'].'/pay/'.$params['URL'].'">
		<input type="hidden" name="account" value="'.$params['invoiceid'].'" />
		<input type="hidden" name="sum" value="'.$params['amount'].'" />
		<input type="hidden" name="desc"  value="'.$params["description"].'" />
		<input type="hidden" name="hideHint"  value="'.(($params["hideHint"]=="on")?"true":"false").'" />
		<input type="hidden" name="hideBackUrl"  value="'.(($params["hideBackUrl"]=="on")?"true":"false").'" />
		<input type="hidden" name="hideOrderCost"  value="'.(($params["hideOrderCost"]=="on")?"true":"false").'" />
		<input type="hidden" name="hideLogo"  value="'.(($params["hideLogo"]=="on")?"true":"false").'" />
		<input type="submit" value="'.$_LANG["invoicespaynow"].'" />
		</form>';
	return $code;
}


