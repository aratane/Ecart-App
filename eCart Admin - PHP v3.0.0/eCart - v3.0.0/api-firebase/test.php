<?php
header('Access-Control-Allow-Origin: *');
include_once('../includes/crud.php');
include_once('../includes/custom-functions.php');
include_once('../includes/variables.php');
$db = new Database();
$db->connect();
$db->sql("SET NAMES utf8");
$function = new custom_functions();
$settings = $function->get_settings('system_timezone', true);
$app_name = $settings['app_name'];
$support_email = $settings['support_email'];
$pickup = $settings['local-pickup'];
$config = $function->get_configurations();
$time_zone = $function->set_timezone($config);
if (!$time_zone) {
    $response['error'] = true;
    $response['message'] = "Time Zone is not set.";
    print_r(json_encode($response));
    return false;
    exit();
}

$generate_otp = $config['generate-otp'];
$response = array();
$cancel_order_from = "";

$where = '';
$user_id = 1782;
$offset = 0;
$limit = 50;
// $user_id = $db->escapeString($function->xss_clean($_POST['user_id']));
// $order_id = (isset($_POST['order_id']) && !empty($_POST['order_id']) && is_numeric($_POST['order_id'])) ? $db->escapeString($function->xss_clean($_POST['order_id'])) : "";
// $status = (isset($_POST['status']) && !empty($_POST['status'])) ? $db->escapeString($function->xss_clean($_POST['status'])) : "";
// $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($function->xss_clean($_POST['limit'])) : 10;
// $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($function->xss_clean($_POST['offset'])) : 0;
if (isset($_POST['pickup'])) {
    $where = $_POST['pickup'] == 1 ? " AND o.local_pickup = 1" : $where =  " AND o.local_pickup = 0";
}
$where .= !empty($order_id) ? " AND o.id = " . $order_id : "";
$where .= !empty($status) ? " AND active_status = '$status'" : "";
$sql = "select count(o.id) as total from orders o where user_id=" . $user_id . $where;
$db->sql($sql);
$res = $db->getResult();
$total = $res[0]['total'];
$sql = "select o.*,obt.attachment,count(obt.attachment) as total_attachment ,(select name from users u where u.id=o.user_id) as user_name from orders o LEFT JOIN order_bank_transfers obt
    ON obt.order_id=o.id where user_id=" . $user_id . $where . " GROUP BY id ORDER BY date_added DESC LIMIT $offset,$limit";
echo $sql;
$db->sql($sql);
$res = $db->getResult();
$i = 0;
$j = 0;
foreach ($res as $row) {
    if ($row['discount'] > 0) {
        $discounted_amount = $row['total'] * $row['discount'] / 100;
        $final_total = $row['total'] - $discounted_amount;
        $discount_in_rupees = $row['total'] - $final_total;
    } else {
        $discount_in_rupees = 0;
    }

    $res[$i]['discount_rupees'] = "$discount_in_rupees";
    $sql_query = "SELECT attachment FROM order_bank_transfers WHERE order_id = " . $row['id'];
    $db->sql($sql_query);
    $res_attac = $db->getResult();
    $new_array = array();
    foreach ($res_attac as $array) {
        foreach ($array as $val) {
            array_push($new_array, $val);
        }
    }
    $body = array();
    $attachment = $new_array;
    foreach ($attachment as $atta) {
        $body[] .= DOMAIN_URL . $atta;
    }
    $res[$i]['attachment'] = $body;
    $res[$i]['user_name'] = !empty($res[$i]['user_name']) ? $res[$i]['user_name'] : "";
    $res[$i]['delivery_boy_id'] = !empty($res[$i]['delivery_boy_id']) ? $res[$i]['delivery_boy_id'] : "";
    $res[$i]['otp'] = !empty($res[$i]['otp']) ? $res[$i]['otp'] : "";
    $res[$i]['order_note'] = !empty($res[$i]['order_note']) ? $res[$i]['order_note'] : "";

    $final_totals = $res[$i]['total'] + $res[$i]['delivery_charge']  - $res[$i]['discount_rupees'] - $res[$i]['promo_discount'] - $res[$i]['wallet_balance'];

    $final_total =  ceil($final_totals);
    $res[$i]['final_total'] = "$final_total";
    $res[$i]['date_added'] = date('d-m-Y h:i:sa', strtotime($res[$i]['date_added']));
    $res[$i]['status'] = json_decode($res[$i]['status']);
    if (in_array('awaiting_payment', array_column($res[$i]['status'], '0'))) {
        $temp_array = array_column($res[$i]['status'], '0');
        $index = array_search("awaiting_payment", $temp_array);
        unset($res[$i]['status'][$index]);
        $res[$i]['status'] = array_values($res[$i]['status']);
    }
    $status = $res[$i]['status'];
    $item1 = array_map('reset', $status);
    $item2 = array_map('end', $status);
    // $item1 = array_keys($status);
    // $item2 = array_values($status);
    $res[$i]['status_name'] = $item1;
    $res[$i]['status_time'] = $item2;
    $sql = "select oi.*,p.id as product_id,v.id as variant_id, pr.rate,pr.review,pr.status as review_status,p.name,p.image,p.manufacturer,p.made_in,p.return_status,p.cancelable_status,p.till_status,v.measurement,(select short_code from unit u where u.id=v.measurement_unit_id) as unit from order_items oi left join product_variant v on oi.product_variant_id=v.id left join products p on p.id=v.product_id left join product_reviews pr on p.id=pr.product_id where order_id=" . $row['id'] . " GROUP BY oi.id";
    $db->sql($sql);
    $res[$i]['items'] = $db->getResult();

    for ($j = 0; $j < count($res[$i]['items']); $j++) {
        $res[$i]['items'][$j]['status'] = (!empty($res[$i]['items'][$j]['status'])) ? json_decode($res[$i]['items'][$j]['status']) : array();

        if (in_array('awaiting_payment', array_column($res[$i]['items'][$j]['status'], '0'))) {
            $temp_array = array_column($res[$i]['items'][$j]['status'], '0');
            $index = array_search("awaiting_payment", $temp_array);
            unset($res[$i]['items'][$j]['status'][$index]);
            $res[$i]['items'][$j]['status'] = array_values($res[$i]['items'][$j]['status']);
        }

        $res[$i]['items'][$j]['image'] = DOMAIN_URL . $res[$i]['items'][$j]['image'];
        $res[$i]['items'][$j]['deliver_by'] = !empty($res[$i]['items'][$j]['deliver_by']) ? $res[$i]['items'][$j]['deliver_by'] : "";
        $res[$i]['items'][$j]['rate'] = !empty($res[$i]['items'][$j]['rate']) ? $res[$i]['items'][$j]['rate'] : "";
        $res[$i]['items'][$j]['review'] = !empty($res[$i]['items'][$j]['review']) ? $res[$i]['items'][$j]['review'] : "";
        $res[$i]['items'][$j]['manufacturer'] = !empty($res[$i]['items'][$j]['manufacturer']) ? $res[$i]['items'][$j]['manufacturer'] : "";
        $res[$i]['items'][$j]['made_in'] = !empty($res[$i]['items'][$j]['made_in']) ? $res[$i]['items'][$j]['made_in'] : "";
        $res[$i]['items'][$j]['return_status'] = !empty($res[$i]['items'][$j]['return_status']) ? $res[$i]['items'][$j]['return_status'] : "";
        $res[$i]['items'][$j]['cancelable_status'] = !empty($res[$i]['items'][$j]['cancelable_status']) ? $res[$i]['items'][$j]['cancelable_status'] : "";
        $res[$i]['items'][$j]['till_status'] = !empty($res[$i]['items'][$j]['till_status']) ? $res[$i]['items'][$j]['till_status'] : "";
        $res[$i]['items'][$j]['review_status'] = (!empty($res[$i]['items'][$j]['review_status']) && ($res[$i]['items'][$j]['review_status'] == 1)) ? $res[$i]['items'][$j]['review_status'] == TRUE : FALSE;
        $sql = "SELECT id from return_requests where product_variant_id = " . $res[$i]['items'][$j]['variant_id'] . " AND user_id = " . $user_id;
        $db->sql($sql);
        $return_request = $db->getResult();
        if (empty($return_request)) {
            $res[$i]['items'][$j]['applied_for_return'] = false;
        } else {
            $res[$i]['items'][$j]['applied_for_return'] = true;
        }
    }
    $i++;
}
$orders = $order = array();

if (!empty($res)) {
    $orders['error'] = false;
    $orders['total'] = $total;
    $orders['data'] = array_values($res);
    print_r(json_encode($orders));
} else {
    $res['error'] = true;
    $res['message'] = "No orders found!";
    print_r(json_encode($res));
}
