<?php

/*
Local: H:\htdocs\mobile\etisalat\999.lk\wasi.999.lk\chargingServer
Remote: /var/www/charging.999.lk/html 	(AWS_999.lk_T3.small_52.77.159.59_EIP)
*/

/*
Running:
========

php get_user_counts.php all     <- for all stats up to date. truncate user_stats_daily before running
php get_user_counts.php daily   <- for daily stats. Run via cron
*/

/*
POST /api/data HTTP/1.1
Host: 3.1.94.73:8082
Content-Type: application/json
Content-Length: 124

{"DeviceID":"6052e44860ab3d1d88673fb7",
"Wtemp": 23.5,
"Atemp": 25.6,
"Rhumidity": 35.8,
"WaterLevel":1,
"Status": 0
}


PUT /api/relays/6052e44860ab3d1d88673fb7 HTTP/1.1
Host: 3.1.94.73:8082
Content-Type: application/json
Content-Length: 9

{"WC": 0}
*/


// Turn off all error reporting
//error_reporting(0);

$inputvalues = json_decode(file_get_contents(('php://input'), true),true);

include("includes/dbparameters.php");
include("includes/commonVariables.php");
include("includes/commonFunctions.php");

// $queryType = $argv[1]; //all or daily

// var_dump($_SERVER);
// exit;

writetolog("Request >> HTTP_GB_API_TOKEN: " . $_SERVER["HTTP_GB_API_TOKEN"] . " |  HTTP_GB_USER_TOKEN: " . $_SERVER["HTTP_GB_USER_TOKEN"] . " | " . json_encode($inputvalues), $api_log);

//writetolog("Header >> ". json_encode($_SERVER) , $api_log);

//read headers
foreach (getallheaders() as $name => $value) {
    //echo "$name: $value\n";
   // writetolog("Header >> ". $name . ":" . $value , $api_log);
}

//echo "123456";
//exit;

if($_SERVER["HTTP_GB_API_TOKEN"] == $api_token_device)
{	//Authorization Check
	$device_access = true;
}else{
	if($_SERVER["HTTP_GB_API_TOKEN"] <> $api_token_global)
	{	//Authorization Check
		setResponseCode("0014");//"0014" => "Autherization failed",
		sendResponse($response_object);
		exit;
	}
	$device_access = false;
}

$response_object["operation"] = $inputvalues["operation"];

switch($inputvalues["operation"])
{
	case "signup":
	{			
		$email = $inputvalues["email"];
		$password = $inputvalues["password"];
		$mobile = $inputvalues["mobile"];
		$user_type = $inputvalues["user_type"];

		$user_query = mysqli_query($db,"SELECT * FROM $gb_users_table WHERE email_id = '$email' OR mobile_number = '$mobile'");  
		if($user_query_array = mysqli_fetch_assoc($user_query))
		{
			
			if($user_query_array["email_id"] == "$email")
			{
				setResponseCode("0002"); //"0002" => "Email already registered",
				break;
			}

			if($user_query_array["mobile_number"] == "$mobile")
			{
				setResponseCode("0003"); //"0003" => "Mobile already registered",
				break;
			}
			
		}else{

			$user_token = get_token($time);
			
			$userdata_enter_sql = "INSERT INTO $gb_users_table (status ,user_type, email_id, mobile_number, password, user_token, user_token_lifetime, lastlogin) VALUES ('$status ','$user_type', '$email', '$mobile', '$password', '$user_token', $user_token_lifetime,  $time )";
			// echo $userdata_enter_sql ."\n";
			if(!mysqli_query($db,$userdata_enter_sql))
			{
				echo mysqli_error($db);   
				writetolog(mysqli_error($db), $error_log);

				setResponseCode("0015"); //"0015" => "System error occurred",
				break;				
			}else{

				$user_query = mysqli_query($db,"SELECT * FROM $gb_users_table WHERE email_id = '$email'");  
				if($user_query_array = mysqli_fetch_assoc($user_query))
				{					
					$response_object["user_token"] = $user_query_array["user_token"];
					$response_object["userData"]["email"] = array(
						"email_id" => $user_query_array["email_id"],
						"email_status" => $user_query_array["email_status"]
					);
					$response_object["userData"]["mobile"] = array(
						"mobile_number" => $user_query_array["mobile_number"],
						"mobile_status" => $user_query_array["mobile_status"]
					);

					setResponseCode("0001"); //"0001" => "User Registered Successfully",
					break;

				}
					setResponseCode("0015"); //"0015" => "System error occurred",
					break;		
			}			
		}

		break;
	}

	case "login":
	{			
		$email = $inputvalues["email"];
		$password = $inputvalues["password"];
		
		$user_query = mysqli_query($db,"SELECT * FROM $gb_users_table WHERE email_id = '$email' AND  password = '$password'");  
		if($user_query_array = mysqli_fetch_assoc($user_query))
		{
			$user_token = get_token($time);
			$query_string = "UPDATE $gb_users_table SET user_token = '$user_token', user_token_lifetime = '$user_token_lifetime', lastlogin = $time WHERE email_id = '$email'";
		
			if(!mysqli_query($db, $query_string))
			{
				echo mysqli_error($db);   
				writetolog(mysqli_error($db), $error_log);

				setResponseCode("0015"); //"0015" => "System error occurred",
				break;				
			}else{

				$user_query = mysqli_query($db,"SELECT * FROM $gb_users_table WHERE email_id = '$email'");  
				if($user_query_array = mysqli_fetch_assoc($user_query))
				{					
					$response_object["user_token"] = $user_query_array["user_token"];
					$response_object["userData"]["email"] = array(
						"email_id" => $user_query_array["email_id"],
						"email_status" => $user_query_array["email_status"]
					);
					$response_object["userData"]["mobile"] = array(
						"mobile_number" => $user_query_array["mobile_number"],
						"mobile_status" => $user_query_array["mobile_status"]
					);

					setResponseCode("0004"); //"0004" => "User logged in successfully­",
					break;	
				}
					setResponseCode("0015"); //"0015" => "System error occurred",
					break;					
			}
			
		}else{
			setResponseCode("0005"); //"0005" => "Incorrect email or password",
			break;
		}
	}

	case "register_device":
	{			
		$device_id = $inputvalues["device_id"];
		$device_name = $inputvalues["device_name"];
		$token = $_SERVER["HTTP_GB_USER_TOKEN"];
		$user_id = validate_user_token($token);
		if(!$user_id)
		{
			setResponseCode("0016"); //"0016" => "Session expired. Please relogin",
			break;	
		}else{
			
			$device_query = mysqli_query($db,"SELECT * FROM $gb_devices_table WHERE device_id = '$device_id'");  
			if($device_query_array = mysqli_fetch_assoc($device_query))
			{
				setResponseCode("0007"); //"0007" => "Device already registered",
				break;	
			}else{

				$device_enter_sql = "INSERT INTO $gb_devices_table (user_id, device_status ,device_id, device_name, last_updated) VALUES ($user_id, '$device_status ','$device_id','$device_name', $time )";
				// echo $userdata_enter_sql ."\n";
				if(!mysqli_query($db,$device_enter_sql))
				{
					echo mysqli_error($db);   
					writetolog(mysqli_error($db), $error_log);

					setResponseCode("0015"); //"0015" => "System error occurred",
					break;				
				}else{

					$device_query = mysqli_query($db,"SELECT * FROM $gb_devices_table WHERE device_id = '$device_id'");  
					if($device_query_array = mysqli_fetch_assoc($device_query))
					{					
						$response_object["device_id"] = $device_query_array["device_id"];	
						setResponseCode("0006"); //"0006" => "Device registered successfully",
						break;
					}							
				}		

				setResponseCode("0015"); //"0015" => "System error occurred",
				break;				
			}	
		}

		break;
	}

	case "get_user_data":
		{			
			// $device_id = $inputvalues["device_id"];
			$token = $_SERVER["HTTP_GB_USER_TOKEN"];
			$user_id = validate_user_token($token);
	
			if(!$user_id and !$device_access)
			{
				setResponseCode("0016"); //"0016" => "Session expired. Please relogin",
				break;	
			}else{		
				
				$user_query = mysqli_query($db,"SELECT * FROM $gb_users_table WHERE id = $user_id");  
				if($user_query_array = mysqli_fetch_assoc($user_query))
				{
					$response_object["user"]["status"] = $user_query_array["status"];
					$response_object["user"]["user_type"] = $user_query_array["user_type"];
					$response_object["user"]["email_id"] = $user_query_array["email_id"];
					$response_object["user"]["email_status"] = $user_query_array["email_status"];
					$response_object["user"]["mobile_number"] = $user_query_array["mobile_number"];
					$response_object["user"]["mobile_status"] = $user_query_array["mobile_status"];
					$response_object["user"]["lastlogin"] = $user_query_array["lastlogin"];
				}

				$device_query = mysqli_query($db,"SELECT * FROM $gb_devices_table WHERE user_id = $user_id");  
				while($device_query_array = mysqli_fetch_assoc($device_query))
				{
					$device_id = $device_query_array["device_id"];

					$response_object["devices"][$device_id]["device_name"] = $device_query_array["device_name"];
                    $response_object["devices"][$device_id]["device_id"] = $device_query_array["device_id"];
					$response_object["devices"][$device_id]["device_status"] = $device_query_array["device_status"];
					$response_object["devices"][$device_id]["device_data"] = json_decode($device_query_array["device_data"], true);
					$response_object["devices"][$device_id]["device_data"]["wc_guide"] = getWaterChangeGuide($response_object["devices"][$device_id]["device_data"]["wc_date"] );
					$response_object["devices"][$device_id]["last_updated"] = $device_query_array["last_updated"];															
				}
				
				setResponseCode("0017"); //"0017" => "User data sent successfully",
				break;	
			}
			setResponseCode("0015"); //"0015" => "System error occurred",
			break;	
		}

	case "get_device_data":
	{			
		$device_id = $inputvalues["device_id"];
		$token = $_SERVER["HTTP_GB_USER_TOKEN"];
		$user_id = validate_user_token($token);

		if(!$user_id and !$device_access)
		{
			setResponseCode("0016"); //"0016" => "Session expired. Please relogin",
			break;	
		}else{		
			
			if($device_access)
			{
				$deviceQueryString = "SELECT * FROM $gb_devices_table WHERE device_id = '$device_id'";
			}else{
				$deviceQueryString = "SELECT * FROM $gb_devices_table WHERE user_id = $user_id AND device_id = '$device_id'";
			}
			
			writetolog($deviceQueryString, $api_log);
			$device_query = mysqli_query($db,$deviceQueryString);  
			
			if($device_query_array = mysqli_fetch_assoc($device_query))
			{
				$response_object["device_id"] = $device_query_array["device_id"];
				$response_object["device_data"] = json_decode($device_query_array["device_data"], true);
				$response_object["device_data"]["wc_guide"] = getWaterChangeGuide($response_object["device_data"]["wc_date"]);
				setResponseCode("0008"); //"0008" => "Device data sent successfully",
				break;				
			}else{
				setResponseCode("0018"); //"0018" => "No device found",
				break;	
			}
		}
		setResponseCode("0015"); //"0015" => "System error occurred",
		break;	
	}

	case "post_device_data":
	{			
		$device_id = $inputvalues["device_id"];
		$device_data_new = $inputvalues["device_data"];
		$token = $_SERVER["HTTP_GB_USER_TOKEN"];
		$user_id = validate_user_token($token);

		if(!$user_id and !$device_access)
		{
			setResponseCode("0016"); //"0016" => "Session expired. Please relogin",
			break;	
		}else{		
			
			$device_query = mysqli_query($db,"SELECT * FROM $gb_devices_table WHERE device_id = '$device_id'");  
			if($device_query_array = mysqli_fetch_assoc($device_query))
			{
				$device_data_current = json_decode($device_query_array["device_data"], true);
				foreach($device_data_new as $key => $value)
				{
					$device_data_current[$key] = ($device_data_new[$key] <> '') ? $device_data_new[$key] : $device_data_current[$key];
					if($key == "WC" and $value == "1")
					{
						$wc_date = time();
						$device_data_current["wc_date"] = $wc_date;
						// $device_data_current["wc_guide"] = getWaterChangeGuide($wc_date);
					}
				}

				$device_data = json_encode($device_data_current);				
				$query_string = "UPDATE $gb_devices_table SET device_data = '$device_data', last_updated = $time WHERE device_id = '$device_id'";
			
				if(!mysqli_query($db, $query_string))
				{
					echo mysqli_error($db);   
					writetolog(mysqli_error($db), $error_log);
					setResponseCode("0015"); //"0015" => "System error occurred",
					break;				
				}else{

					$device_query = mysqli_query($db,"SELECT * FROM $gb_devices_table WHERE device_id = '$device_id'");  
					if($device_query_array = mysqli_fetch_assoc($device_query))
					{
						$response_object["device_id"] = $device_query_array["device_id"];
						$response_object["device_data"] = json_decode($device_query_array["device_data"], true);
						$response_object["device_data"]["wc_guide"] = getWaterChangeGuide($response_object["device_data"]["wc_date"]);
						setResponseCode("0009"); //"0009" => "Device data updated successfully",
						break;				
					}else{
						setResponseCode("0015"); //"0015" => "System error occurred",
						break;	
					}
				}

				$response_object["device_id"] = $device_query_array["device_id"];
				$response_object["device_data"] = $device_query_array["device_data"];
				setResponseCode("0008"); //"0008" => "Device data sent successfully",
				break;				
			}else{
				setResponseCode("0018"); //"0018" => "No device found",
				break;	
			}
		}
		setResponseCode("0015"); //"0015" => "System error occurred",
		break;	
	}

	case "change_password":
	{			
		$email = $inputvalues["email"];
		$current_password = $inputvalues["current_password"];
		$new_password = $inputvalues["new_password"];
		$token = $_SERVER["HTTP_GB_USER_TOKEN"];
		$user_id = validate_user_token($token);

		if(!$user_id)
		{
			setResponseCode("0016"); //"0016" => "Session expired. Please relogin",
			break;	
		}else{		
			
			$user_query = mysqli_query($db,"SELECT * FROM $gb_users_table WHERE user_token = '$token' AND password = '$current_password'");  
			if($user_query_array = mysqli_fetch_assoc($user_query))
			{
				$query_string = "UPDATE $gb_users_table SET password = '$new_password' WHERE user_token = '$token' AND password = '$current_password'";
				if(!mysqli_query($db, $query_string))
				{
					echo mysqli_error($db);   
					writetolog(mysqli_error($db), $error_log);

					setResponseCode("0015"); //"0015" => "System error occurred",
					break;				
				}else{
					setResponseCode("0011"); //"0011" => "Password changed successfully",
					break;
				}

			}else{
				setResponseCode("0012"); //"0012" => "Incorrect current password",
				break;
			}
		}

		setResponseCode("0015"); //"0015" => "System error occurred",
		break;
	}

	case "forgot_password":
	{			
		$email = $inputvalues["email"];
							
		$user_query = mysqli_query($db,"SELECT * FROM $gb_users_table WHERE email_id = '$email'");  
		if($user_query_array = mysqli_fetch_assoc($user_query))
		{
			$password = $user_query_array["password"];
			if(emailPassword($email,$password))
			{
				setResponseCode("0019"); //"0019" => "Password emailed to user successfully",
				break;	
			}else{
				setResponseCode("0020"); //"0020" => "Error is emailing password. Please contact ezbloombox administrator",
				break;	
			}
		}else{
			setResponseCode("0018"); //"0018" => "User doesn't exist",
			break;
		}

		setResponseCode("0015"); //"0015" => "System error occurred",
		break;
	}

	case "get_grow_calendar":
	{
		$device_id = $inputvalues["device_id"];
		$from_date = strtotime($inputvalues["from_date"]);
		if(!isset($inputvalues["to_date"]) or $inputvalues["to_date"] =='' )
		{
			$to_date = $from_date + 60*60*24;
		}else{
			$to_date = strtotime($inputvalues["to_date"]) + 60*60*24;			
		}
		$token = $_SERVER["HTTP_GB_USER_TOKEN"];
		$user_id = validate_user_token($token);
		if(!$user_id)
		{
			setResponseCode("0016"); //"0016" => "Session expired. Please relogin",
			break;	
		}else{		
		
			$response_object["device_id"] = $device_id;
			$response_object["from_date"] = $inputvalues["from_date"];
			$response_object["to_date"] = $inputvalues["to_date"];
			$grow_calendar_data = array();
			
			$deviceStatsQueryString = "SELECT * FROM $gb_device_stats_table WHERE device_id = '$device_id' AND timestamp > $from_date AND timestamp < $to_date ORDER BY timestamp ASC";
			writetolog($deviceStatsQueryString, $api_log);
			$device_stats_query = mysqli_query($db,$deviceStatsQueryString);  
			
			while($device_stats_query_array = mysqli_fetch_assoc($device_stats_query))
			{
				$current_stats_timestamp = $device_stats_query_array["timestamp"];
				$current_stats_date = date('d-m-y', $current_stats_timestamp);
				$current_stats_device_data = json_decode($device_stats_query_array["device_data"],true);
				if(!isset($grow_calendar_data[$current_stats_date]))
				{
					$grow_calendar_data[$current_stats_date] = array();
					$grow_calendar_data[$current_stats_date]["Wtemp"] = array();
					$grow_calendar_data[$current_stats_date]["Atemp"] = array();
					$grow_calendar_data[$current_stats_date]["Rhumidity"] = array();
				}

				  array_push($grow_calendar_data[$current_stats_date]["Wtemp"],$current_stats_device_data["Wtemp"]);
				  array_push($grow_calendar_data[$current_stats_date]["Atemp"],$current_stats_device_data["Atemp"]);
				  array_push($grow_calendar_data[$current_stats_date]["Rhumidity"],$current_stats_device_data["Rhumidity"]);
				  $grow_calendar_data[$current_stats_date]["WaterLevel"] = $current_stats_device_data["WaterLevel"];
				  $grow_calendar_data[$current_stats_date]["Status"] = $current_stats_device_data["Status"];
						
			}
			// writetolog("grow_calendar_data =>" . json_encode($grow_calendar_data), $api_log);
			
			if(!empty($grow_calendar_data))
			{
				foreach($grow_calendar_data as $stats_date => $stats_values)
				{
					$grow_calendar_data[$stats_date]["Wtemp"] = round(array_sum($grow_calendar_data[$stats_date]["Wtemp"])/count($grow_calendar_data[$stats_date]["Wtemp"]),2);
					$grow_calendar_data[$stats_date]["Atemp"] = round(array_sum($grow_calendar_data[$stats_date]["Atemp"])/count($grow_calendar_data[$stats_date]["Atemp"]),2);
					$grow_calendar_data[$stats_date]["Rhumidity"] = round(array_sum($grow_calendar_data[$stats_date]["Rhumidity"])/count($grow_calendar_data[$stats_date]["Rhumidity"]),2);
				}
				$response_object["grow_calendar"] = $grow_calendar_data;
			}

			if(isset($response_object["grow_calendar"]))
			{
				setResponseCode("0021"); //"0021" => "Grow calendar data sent successfully",
				break;
			}else{
				setResponseCode("0022"); //"0022" => "No grow calendar data found",
				break;	
			}
		}
		setResponseCode("0015"); //"0015" => "System error occurred",
		break;	
	}

	case "get_grow_schedule":
	{
		/*
		{
			"device_name":"ABC Device",
			"R1":"OFF",
			"R2":"OFF",
			"R3":"OFF",
			"R4":"OFF",
			"R5":"OFF",
			"R6":"OFF",
			"R7":"OFF",
			"R8":"OFF",
			"R9":"OFF",
			"R10":"OFF",
			"R11":"OFF",
			"Em":null,
			"__v":null,
			"Status":1,
			"WC":1,
			"wc_date":1645675632,
			"Wtemp":"27.81",
			"Atemp":"32.10",
			"Rhumidity":"51.00",
			"WaterLevel":"0"
		}
		*/
		$device_id = $inputvalues["device_id"];

		$token = $_SERVER["HTTP_GB_USER_TOKEN"];
		$user_id = validate_user_token($token);
		if(!$user_id)
		{
			setResponseCode("0016"); //"0016" => "Session expired. Please relogin",
			break;	
		}else{		
		
			$response_object["device_id"] = $device_id;
			$grow_schedule_data = array();
			
			$deviceStatsQueryString = "SELECT * FROM $gb_device_stats_table WHERE device_id = '$device_id' ORDER BY timestamp DESC";
			writetolog($deviceStatsQueryString, $api_log);
			$device_stats_query = mysqli_query($db,$deviceStatsQueryString);  
			
			while($device_stats_query_array = mysqli_fetch_assoc($device_stats_query))
			{
				$current_stats_timestamp = $device_stats_query_array["timestamp"];
				$current_stats_date = date('d-m-y', $current_stats_timestamp);
				$current_stats_device_data = json_decode($device_stats_query_array["device_data"],true);
				$previousState = 1;
				if(!isset($grow_schedule_data[$current_stats_date]))
				{
					if($current_stats_device_data["Status"] > $previousState)
					{
						// continue;//skip loop if currentState > preious state
					}
					$grow_schedule_data[$current_stats_date] = $current_stats_device_data["Status"];//1,2,3,4,5	
					$previousState = $current_stats_device_data["Status"];
				}						
			}
			writetolog("grow_schedule_data =>" . json_encode($grow_schedule_data), $api_log);
			
			if(!empty($grow_schedule_data))
			{
				/*
				foreach($grow_calendar_data as $stats_date => $stats_values)
				{
					$grow_calendar_data[$stats_date]["Wtemp"] = round(array_sum($grow_calendar_data[$stats_date]["Wtemp"])/count($grow_calendar_data[$stats_date]["Wtemp"]),2);
					$grow_calendar_data[$stats_date]["Atemp"] = round(array_sum($grow_calendar_data[$stats_date]["Atemp"])/count($grow_calendar_data[$stats_date]["Atemp"]),2);
					$grow_calendar_data[$stats_date]["Rhumidity"] = round(array_sum($grow_calendar_data[$stats_date]["Rhumidity"])/count($grow_calendar_data[$stats_date]["Rhumidity"]),2);
				}
				*/
				$response_object["grow_schedule"] = $grow_schedule_data;
			}

			if(isset($response_object["grow_schedule"]))
			{
				setResponseCode("0023"); //"0023" => "Grow schedule data sent successfully",
				break;
			}else{
				setResponseCode("0024"); //"0024" => "No grow schedule data found",
				break;	
			}
		}
		setResponseCode("0015"); //"0015" => "System error occurred",
		break;	
	}

	default:
	{
		setResponseCode("0013"); //"0013" => "Invalid operation",
		break;
	}
}



//$data = array("a","b","c");
//header('Content-Type: application/json; charset=utf-8');
//echo json_encode($data);


sendResponse($response_object);


/*
// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    // should do a check here to match $_SERVER['HTTP_ORIGIN'] to a
    // whitelist of safe domains
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}
// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");         

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

}
*/



?>