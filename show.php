<?php  
	$username ="root";
	$password ="";
	$hostname = "localhost";
	$database_name = "taxi";
	$con = mysqli_connect($hostname , $username, $password);
	$selected = mysqli_select_db($con, $database_name);
	$result = mysqli_query($con, "SELECT id, name FROM profile GROUP BY id");
	$json_response = array();
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		$json_response[] = $row;
	}
	echo json_encode(array('person' => $json_response));  
?>

