<?php
session_start();
include_once('../includes/custom-functions.php');
include_once('../includes/crud.php');
include_once('../includes/variables.php');
header('Access-Control-Allow-Origin: *');

$fn = new custom_functions;								
$db = new Database();
$db->connect();
$response = array();

$config = $fn->get_configurations();
$time_slot_config = $fn->time_slot_config();
$time_zone = $fn->set_timezone($config);

// set time for session timeout
$currentTime = time() + 25200;
$expired = 3600;

// if session not set go to login page
if (!isset($_SESSION['user'])) {
    return false;
}

// if current time is more than session timeout back to login page
if ($currentTime > $_SESSION['timeout']) {
    session_destroy();
    return false;
}

unset($_SESSION['timeout']);
$_SESSION['timeout'] = $currentTime + $expired;

/* Load Complaint messages here */
//         get_complaint_comments:1
//         complaint_id:1                    
//         id:10                   // {optional}
//         user_id:1782            // {optional}
//         offset:0                // {optional}
//         limit:10                // {optional}
//         sort:id                 // {optional}
//         order:DESC / ASC        // {optional}
//         search:search_value     // {optional}
if (isset($_POST['get_complaint_comments']) && !empty($_POST['get_complaint_comments'])) {
    $complaint_id = $db->escapeString($fn->xss_clean($_POST['complaint_id']));

    $where = "";
    if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
        $user_id = $db->escapeString($fn->xss_clean($_POST['user_id']));
        $where .= " AND user_id = $user_id";
    }
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = $db->escapeString($fn->xss_clean($_POST['id']));
        $where .= " AND cc.id = $id";
    }
    $offset = (isset($_POST['offset']) && !empty($fn->xss_clean($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;
    $limit = (isset($_POST['limit']) && !empty($fn->xss_clean($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;

    $sort = (isset($_POST['sort']) && !empty($fn->xss_clean($_POST['sort']))) ? $db->escapeString($fn->xss_clean($_POST['sort'])) : 'id';
    $order = (isset($_POST['order']) && !empty($fn->xss_clean($_POST['order']))) ? $db->escapeString($fn->xss_clean($_POST['order'])) : 'DESC';

    if (isset($_POST['search'])) {
        $search = $db->escapeString($fn->xss_clean($_POST['search']));
        $where .= " AND (c.`title` like '%" . $search . "%' OR cc.`message` like '%" . $search . "%' )";
    }

    if (isset($_POST['complaint_id']) && !empty($_POST['complaint_id'])) {
        $sql = "SELECT count(cc.id) as total FROM `complaint_comments` cc join complaints c on c.id=cc.complaint_id join users u on u.id=c.user_id Where cc.complaint_id = $complaint_id" . $where;
        $db->sql($sql);
        $res = $db->getResult();
        $total = $res[0]['total'];

        $sql = "select cc.*,case when cc.type='admin' then a.username else u.name end as username from complaint_comments cc left join admin a on a.id = cc.type_id
        left join users u on u.id = cc.type_id Where cc.complaint_id = $complaint_id " . $where . " ORDER BY `$sort` $order LIMIT $offset,$limit";
        
        $db->sql($sql);
        $res = $db->getResult();
        if (!empty($res)) {
            foreach ($res as $row) {
                $row['username'] = (!empty($row['username'])) ? $row['username'] : "";
                $tempRow['id'] = $row['id'];
                $tempRow['type'] = $row['type'];
                $tempRow['type_id'] = $row['type_id'];
                $tempRow['username'] = $row['username'];
                $tempRow['complaint_id'] = $row['complaint_id'];
                $tempRow['message'] = $row['message'];
                $tempRow['created'] = $row['created'];
                $rows[] = $tempRow;
            }
            $response['error'] = false;
            $response['message'] = 'Complaints Retrived Successfully!';
            $response['total'] = $total;
            $response['data'] = $rows;
        } else {
            $response['error'] = true;
            $response['message'] = 'Data not Found!';
            $response['total'] = $total;
            $response['data'] = [];
        }
        print_r(json_encode($response));
    }
}

// send message
if (isset($_POST['send']) == 1) {
    // print_r($_POST['message']);
    // return false;
    if (isset($_POST['message']) && $_POST['message'] != "") {
        $type = $_POST['type'];
        $type_id = $_POST['type_id'];
        $complaint_id = $_POST['complaint_id'];
        $message = $_POST['message'];
    }
    $sql = "INSERT INTO complaint_comments (complaint_id,message,type,type_id) VALUES ($complaint_id, '$message','$type','$type_id')";
    $result = $db->sql($sql);
    $row1 = $db->getResult();
    echo json_encode($row1);
//     return false;
}