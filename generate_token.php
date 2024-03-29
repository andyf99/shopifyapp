<?php

// Get our helper functions
require_once("inc/functions.php");
require_once("connect_to_mysql.php");

// Set variables for our request
$api_key = "6daf26c02ab47a8dd91b0964549daa80";
$shared_secret = "922ef9df203829578800ca1799e251ee";
$params = $_GET; // Retrieve all request parameters
$hmac = $_GET['hmac']; // Retrieve HMAC request parameter

$params = array_diff_key($params, array('hmac' => '')); // Remove hmac from params
ksort($params); // Sort params lexographically

$computed_hmac = hash_hmac('sha256', http_build_query($params), $shared_secret);

// Use hmac data to check that the response is from Shopify or not
if (hash_equals($hmac, $computed_hmac)) {

	// Set variables for our request
	$query = array(
		"client_id" => $api_key, // Your API key
		"client_secret" => $shared_secret, // Your app credentials (secret key)
		"code" => $params['code'] // Grab the access key from the URL
	);

	// Generate access token URL
	$access_token_url = "https://" . $params['shop'] . "/admin/oauth/access_token";



	// Configure curl client and execute request
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_URL, $access_token_url);
	curl_setopt($ch, CURLOPT_POST, count($query));
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
	$result = curl_exec($ch);
	curl_close($ch);

	// Store the access token
	$result = json_decode($result, true);
	$access_token = $result['access_token'];

	// Show the access token (don't do this in production!)
	//echo $access_token;


$sql = "INSERT INTO tokens (store_url, access_token, install_date)
		VALUES ('".$params['shop']."', '".$access_token."', NOW())";

if (mysqli_query($conn, $sql)) {
	header('Location: https://'.$params['shop'].'/admin/apps');
	die();
} else {
	echo "Error inserting new record: " . mysqli_error($conn);
}

	
	
	
	
	
	

} else {
	// Someone is trying to be shady!
	die('This request is NOT from Shopify!');
}