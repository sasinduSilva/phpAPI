<?php
date_default_timezone_set('Asia/Colombo');
$time = time();

//HTTP_GB_API_TOKEN values (gb-api-token)
$api_token_global = "dskghdfkhgjdf54546456546";
$api_token_device = "dskghdfkhgjdf57610382621";
$device_access = false;
$waterChangeInterval = 7; //7 days

//logs
$api_log = "api.log";
$error_log = "error.log";

//tables 
$gb_users_table = "gb_users";
$gb_devices_table = "gb_devices";
$gb_device_stats_table = "gb_device_stats";

//other variables
$status = "active";
$device_status = "active";
$user_token_lifetime = 30*24*60*60; //30 days

//status codes
$ResponseCodeDesc = array(
    "0001" => "User Registered Successfully",
    "0002" => "Email already registered",
    "0003" => "Mobile already registered",
    "0004" => "User logged in successfully­",
    "0005" => "Incorrect email or password",
    "0006" => "Device registered successfully",
    "0007" => "Device already registered",
    "0008" => "Device data sent successfully",
    "0009" => "Device data updated successfully",
    "0010" => "No device data updated",
    "0011" => "Password changed successfully",
    "0012" => "Incorrect current password",
    "0013" => "Invalid operation",
    "0014" => "Autherization failed",
    "0015" => "System error occurred",
    "0016" => "Session expired. Please relogin",
    "0017" => "User data sent successfully",
    "0018" => "Device not found",
    "0019" => "Password emailed to user successfully",
    "0020" => "Error is emailing password. Please contact ezbloombox administrator",
    "0021" => "Grow calendar data sent successfully",
    "0022" => "No grow calendar data found",
    );

?>