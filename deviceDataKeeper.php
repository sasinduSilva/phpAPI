<?php
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

$queryType = $argv[1]; //hourly_proc

if($queryType != "hourly_proc")
{
    exit;//Auto failure
}

include("includes/dbparameters.php");
include("includes/commonVariables.php");
include("includes/commonFunctions.php");

$device_query = mysqli_query($db,"SELECT DISTINCT device_id, device_data FROM $gb_devices_table WHERE 1");  
while($device_query_array = mysqli_fetch_assoc($device_query))
{
    echo $device_query_array["device_id"] . "|" . $device_query_array["device_data"] . "\n";
    $devicedata_store_sql = "INSERT INTO $gb_device_stats_table (timestamp ,device_id, device_data) VALUES ($time,'$device_query_array[device_id]','$device_query_array[device_data]')";
    mysqli_query($db,$devicedata_store_sql);
}

?>