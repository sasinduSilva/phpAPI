<?php
// date_default_timezone_set('UTC');
date_default_timezone_set('Asia/Colombo');
$time = time();

$db = mysqli_connect($db_host, $db_username ,$db_password, $db_name);
mysqli_set_charset($db,"utf8");

/*

 	// echo $timestamp_for_currentday ."=>". json_encode($user_counts_data["daily"][$timestamp_for_currentday]) ."=>". json_encode($user_counts_data["total"]) ."\n";
    $userdata_enter_sql = "INSERT INTO $user_stats_daily_table (date, user_counts_daily, user_counts_total) VALUES ('$timestamp_for_currentday','" . json_encode($user_counts_data["daily"][$timestamp_for_currentday]) ."', '" .json_encode($user_counts_data["total"])."')";
    // echo $userdata_enter_sql ."\n";
    mysqli_query($db,$userdata_enter_sql); 
    echo mysqli_error($db);    
    // exit();

	// echo $timestamp_for_currentday ."=>". json_encode($user_counts_data["daily"][$timestamp_for_currentday]) ."=>". json_encode($user_counts_data["total"]) ."\n";
    $userdata_enter_sql = "INSERT INTO $user_stats_daily_table (date, user_counts_daily, user_counts_total) VALUES ('$timestamp_for_currentday','" . json_encode($user_counts_data["daily"][$timestamp_for_currentday]) ."', '" .json_encode($user_counts_data["total"])."')";
    // echo $userdata_enter_sql ."\n";
    mysqli_query($db,$userdata_enter_sql); 
    echo mysqli_error($db);    
    // exit();

	$access_token_query = mysqli_query($db,"SELECT * FROM $ideabiz_tokens_table WHERE id = 1");  
	if($access_token_query_array = mysqli_fetch_assoc($access_token_query))
	{

	while($access_token_query_array = mysqli_fetch_assoc($access_token_query))
	{

	$query_string = "UPDATE $wasi_999_package_subscriptions_table SET lastRentalCorrelator = '$clientCorrelator' WHERE  msisdn = '$msisdn' AND package_id = '$package_id'";
	mysqli_query($db, $query_string);

*/

function setResponseCode($RespCode, $additional_note = null)
{
	global $response_object;
	global $ResponseCodeDesc;
	global $msisdn;
	global $mode;
	global $display_msisdn;
	global $package_id;
	global $service_name;		

	$response_object["ResponseCode"] = $RespCode;
	$response_object["ResponseDesc"] = str_replace(array("<mobile>","<package_id>","<service>"),array($display_msisdn,$package_id,$service_name), $ResponseCodeDesc[$response_object["ResponseCode"]]);
	
	if(isset($additional_note))
	{
		$response_object["ResponseDesc"] .= "(".$additional_note.")";
	}
}

function get_token($input)
{
	return base64_encode((string) rand(1000000,999999) * $input);
}

function validate_user_token($token)
{
	global $db, $gb_users_table;
	$user_token_query = mysqli_query($db,"SELECT * FROM $gb_users_table WHERE user_token = '$token'");  
	if($user_token_query_array = mysqli_fetch_assoc($user_token_query))
	{
		return $user_token_query_array["id"];
	}else{
		return false;
	}
}

function sendResponse($response_object)
{	
	global $api_log;
	
	writetolog("Response << ".json_encode($response_object), $api_log);
	echo json_encode($response_object);
	exit;
}

function getWaterChangeGuide($wc_date)
{
	global $waterChangeInterval;
	$daysTowc  = $waterChangeInterval - round((time() - $wc_date)/86400);

	if($daysTowc >= 0)
	{
		return "In " . $daysTowc ." Days";
	}else{
		return "Delayed by " . $daysTowc . " Days";
	}
}

function emailPassword($useremail,$password)
{		
	global $api_log;
	$sendgrid_apikey = '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';

	// require '/var/www/growbro.lk/api.growbro.lk/html/includes/emailer/vendor/autoload.php';
	require_once '/var/www/growbro.lk/api.growbro.lk/html/includes/emailer/sendgrid-php.php';

	$email = new \SendGrid\Mail\Mail();
	$email->setFrom("admin@ezbloombox.com", "EzbloomBox Admin");
	$email->setSubject("Ezbloombox Password Reminder");
	$email->addTo("$useremail", "Ezbloombox User");
	$email->addContent("text/plain", "These are your login details email: '$useremail' <br> password: '$password'");
	$email->addContent(
		"text/html", "These are your login details email: '$useremail' <br> password: '$password'"
	);
	// $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
	$sendgrid = new \SendGrid($sendgrid_apikey);
	try {
		$response = $sendgrid->send($email);
		
		writetolog("Email Response code << ". $response->statusCode() . "\n", $api_log);
		writetolog("Email Response headers<< ". json_encode($response->headers()), $api_log);
		writetolog("Email Response body << ". $response->body(), $api_log);

		return true;

	} catch (Exception $e) {
		// echo 'Caught exception: '. $e->getMessage() ."\n";
		writetolog("Email Error << ". 'Caught exception: '. $e->getMessage() ."\n", $api_log);

		return false;
	}
}

function writetolog($content, $logfile)
{
	$logfile = "logs/$logfile";
	$fp = fopen($logfile, 'a');
	fwrite($fp, date("g:i A dmy")."|".$content."\n");
	fclose($fp);
}
?>