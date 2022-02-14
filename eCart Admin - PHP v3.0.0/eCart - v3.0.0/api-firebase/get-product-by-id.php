<?php
header('Access-Control-Allow-Origin: *');
include_once('../includes/variables.php');
include_once('../includes/crud.php');
include_once('verify-token.php');
$db = new Database();
$db->connect();
include_once('../includes/custom-functions.php');
$fn = new custom_functions;

$config = $fn->get_configurations();
$time_slot_config = $fn->time_slot_config();
$time_zone = $fn->set_timezone($config);
if (!$time_zone) {
    $response['error'] = true;
    $response['message'] = "Time Zone is not set.";
    print_r(json_encode($response));
    return false;
    exit();
}

/* 
1.get-product-by-id.php
	accesskey:90336
	product_id:230 OR slug:onion-1
	user_id:369     // {optional}
*/
if (!verify_token()) {
    return false;
}

if (isset($_POST['accesskey'])) {
    if ((!isset($_POST['product_id'])) && (!isset($_POST['slug']))) {
        $response['error'] = true;
        $response['message'] = "Please pass product id or slug ";
        print_r(json_encode($response));
        return false;
    }

    $product_id = (isset($_POST['product_id']) && is_numeric($_POST['product_id'])) ? $db->escapeString($fn->xss_clean($_POST['product_id'])) : "";
    $slug = (isset($_POST['slug']) && !empty($_POST['slug'])) ? $db->escapeString($fn->xss_clean($_POST['slug'])) : "";
    $user_id = (isset($_POST['user_id']) && is_numeric($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";

    $product = $fn->get_products($user_id, $product_id, $slug);

    print_r(json_encode($product));
    return false;
} else {
    $response['error'] = true;
    $response['message'] = "accesskey is required.";
    print_r(json_encode($response));
}
