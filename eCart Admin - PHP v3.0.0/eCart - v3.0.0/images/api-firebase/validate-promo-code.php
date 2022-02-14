<?php session_start();
header('Access-Control-Allow-Origin: *');
include '../includes/crud.php';
include '../includes/variables.php';
include_once('verify-token.php');
$db = new Database();
$db->connect();
include_once('../includes/custom-functions.php');
$fn = new custom_functions;
$config = $fn->get_configurations();
$time_zone = $fn->set_timezone($config);
if(!$time_zone){
    $response['error'] = true;
    $response['message'] = "Time Zone is not set.";
    print_r(json_encode($response));
    return false;
    exit();
}

$response = array();
$accesskey = isset($_POST['accesskey']) && $_POST['accesskey'] != '' ? $db->escapeString($fn->xss_clean($_POST['accesskey'])) : '';
if (empty($accesskey)) {
    $response['error'] = true;
    $response['message'] = "accesskey required";
    print_r(json_encode($response));
    return false;
}
if ($access_key != $accesskey) {
    $response['error'] = true;
    $response['message'] = "invalid accesskey";
    print_r(json_encode($response));
    return false;
}
if (!verify_token()) {
    return false;
}

if (isset($_POST['validate_promo_code']) && $_POST['validate_promo_code'] == 1) {

    if ((isset($_POST['user_id']) && $_POST['user_id'] != '') && (isset($_POST['promo_code']) && $_POST['promo_code'] != '') && (isset($_POST['total']) && $_POST['total'] != '')) {
        $user_id = $db->escapeString($fn->xss_clean($_POST['user_id']));
        $promo_code = $db->escapeString($fn->xss_clean($_POST['promo_code']));
        $total = $db->escapeString($fn->xss_clean($_POST['total']));
        $response = $fn->validate_promo_code($user_id, $promo_code, $total);
        print_r(json_encode($response));
        return false;
    } else {
        $response['error'] = true;
        $response['message'] = "Please enter user id,promo code and total.";
        echo json_encode($response);
        return false;
    }
}
