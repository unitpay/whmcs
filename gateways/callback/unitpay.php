<?php
if (file_exists("../../../init.php")) {
	include_once("../../../init.php");
} else {
	include_once("../../../dbconnect.php");
	include_once("../../../includes/functions.php");
};
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
$gatewaymodule = "unitpay";
$GATEWAY = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"]) { echo '{"error": {"code": -32000, "message": "Module Not Activated"}}'; exit;  }
$invoiceid = $_REQUEST['params']['account'];
$transid = $_REQUEST['params']['unitpayId'];
$client_amount = $_REQUEST['params']['sum'];
$your_amount = $_REQUEST['params']['profit'];
//$invoiceid = checkCbInvoiceID($invoiceid,$GATEWAY["name"]); 
$result = select_query( "tblinvoices", "id", array( "id" => $invoiceid ) );
$data = mysql_fetch_array( $result );
$id = $data["id"];
if (!$id) {
	echo '{"error": {"code": -32000, "message": "Invoice ID Not Found"}}';
	exit();
}
//checkCbTransID($transid); 
$result = select_query( "tblaccounts", "id", array( "transid" => $transid ) );
$num_rows = mysql_num_rows( $result );
if ($num_rows) {
	echo '{"result": {"message":"Payment successful"}}';
	exit();
}
function getSha256SignatureByMethodAndParams($method, array $params, $secretKey)
{
    $delimiter = '{up}';
    ksort($params);
    unset($params['sign']);
    unset($params['signature']);

    return hash('sha256', $method.$delimiter.join($delimiter, $params).$delimiter.$secretKey);
}

//
if ($_REQUEST["method"]=='check') {
	$params=$_REQUEST['params'];
	ksort($params);
	unset($params['sign']);
	$_REQUEST['params']['md5'] = md5(join(null, $params).$GATEWAY['SecretKey']);
	if ($_REQUEST['params']['sign']==$_REQUEST['params']['md5']) {
		echo '{"result": {"message":"Check successful"}}';
		exit();
    } elseif ($params['signature'] == getSha256SignatureByMethodAndParams( $_REQUEST["method"], $params, $GATEWAY['SecretKey'] ) ) {
		echo '{"result": {"message":"Check successful"}}';
		exit();
	} else {
		echo '{"error": {"code": -32000, "message": "Signature invalid"}}';
		exit();
	}
	logTransaction($GATEWAY["name"],$_REQUEST['params'],"Check");
	exit();
} elseif ($_REQUEST["method"]=='error') {
	echo '{"result": {"message":"Error logged"}}';
	logTransaction($GATEWAY["name"],$_REQUEST['params'],"Error");
	exit();
} elseif ($_REQUEST["method"]=='pay') {
	$params=$_REQUEST['params'];
	ksort($params);
	unset($params['sign']);
	$_REQUEST['params']['md5'] = md5(join(null, $params).$GATEWAY['SecretKey']);
    
    if ($params['signature'] != getSha256SignatureByMethodAndParams(
            $_REQUEST["method"], $params, $GATEWAY['SecretKey']
        )) {
        logTransaction($GATEWAY["name"],$_REQUEST['params'],"Unsuccessful");
        echo '{"error": {"code": -32000, "message": "Signature invalid"}}';
        exit();
    }

	$result = select_query( "tblinvoices", "userid,total", array( "id" => $invoiceid ) );
	$data = mysql_fetch_array( $result );
	$userid = $data['userid'];
	$total = $data['total'];
	$currency = getCurrency( $userid );
	if ( $GATEWAY['convertto'] ) { $client_amount = convertCurrency( $client_amount, $GATEWAY['convertto'], $currency['id'] ); }
	if ( $GATEWAY['convertto'] ) { $your_amount = convertCurrency( $your_amount, $GATEWAY['convertto'], $currency['id'] ); }
	if ( $total < $client_amount && $your_amount < $total ) { $amount = $total; $fee = $total - $your_amount;  }
	elseif ( $client_amount == $total ) { $amount = $total; $fee = 0;  }
	elseif ( $your_amount == $total ) { $amount = $total; $fee = 0; }
	else { $amount = $client_amount; }
	if ($amount == 0) { logTransaction($GATEWAY["name"],$POST,"Zero Payment"); echo '{"error": {"code": -32000, "message": "Zero payment"}}'; exit;  };
	addInvoicePayment($invoiceid,$transid,$amount,$fee,$gatewaymodule);
	logTransaction($GATEWAY["name"],$_REQUEST['params'],"Successful");
	echo '{"result": {"message":"Payment successful"}}';
	exit();
}
echo '{"error": {"code": -32000, "message": "Unknown query"}}';
exit(); 