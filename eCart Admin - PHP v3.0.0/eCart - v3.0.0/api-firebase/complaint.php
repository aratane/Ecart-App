<?php
header('Access-Control-Allow-Origin: *');
include_once('../includes/variables.php');
include_once('../includes/crud.php');
include_once('verify-token.php');
$db = new Database();
$db->connect();
include_once('../includes/functions.php');
include_once('../includes/custom-functions.php');
$fn = new custom_functions;
$function = new functions;
$config = $fn->get_configurations();
$time_slot_config = $fn->time_slot_config();
$time_zone = $fn->set_timezone($config);
if (!$time_zone) {
    $response['error'] = true;
    $response['message'] = "Time Zone is not set.";
    print_r(json_encode($response));
    return false;
}

/* 
-------------------------------------------
APIs for eCart
-------------------------------------------
1. get_complaint_type_id 
2. add_complaint
3. get_complaint(s)
4. update_complaint
5. get_complaint_comments
6. add_complaint_comments
7. delete_complaint_comments
8. delete_complaint 
-------------------------------------------
-------------------------------------------
*/

if (!isset($_POST['accesskey'])  || $fn->xss_clean($_POST['accesskey']) != $access_key) {
    $response['error'] = true;
    $response['message'] = "No Accsess key found!";
    print_r(json_encode($response));
    return false;
}

if (!verify_token()) {
    return false;
}

if (isset($_POST['get_complaint_types']) && !empty($_POST['get_complaint_types'])) {
    /* 
    1.get_complaint_types
        accesskey:90336
        get_complaint_types:1
        type:damaged product       //{optional}
    */
    $where = "";
    if ((isset($_POST['type'])) && ($_POST['type'])) {
        $type = $db->escapeString($fn->xss_clean($_POST['type']));
        $where = " where type LIKE '%" . $type . "%' ";
    }

    $sql = "select * from complaint_type" . $where;
    $db->sql($sql);
    $res = $db->getResult();
    $num_rows = $db->numRows($res);
    if ($num_rows > 0) {
        $response["error"]   = false;
        $response["data"]   = $res;
        echo json_encode($response);
    } else if ($num_rows == 0) {
        $response["error"]   = true;
        $response["type"] = "others";
        $response["message"] = "Your complaint is not registered! Take 1 as type_id for type as others.";
        echo json_encode($response);
    }
}


if (isset($_POST['add_complaint']) && !empty($_POST['add_complaint'])) {
    /*
    2.add_complaint
        accesskey:90336
        add_complaint:1
        title:complaint title
        email:user_email
        message:describ complaint
        type_id: 2
        user_id:1782
        image:FILE      // {optional}
    */
    if (empty($_POST['title']) && empty($_POST['email']) && empty($_POST['message']) && empty($_POST['user_id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
        print_r(json_encode($response));
        return false;
    }

    $title = $db->escapeString($fn->xss_clean($_POST['title']));
    $email = $db->escapeString($fn->xss_clean($_POST['email']));
    $message = $db->escapeString($fn->xss_clean($_POST['message']));
    $type_id = $db->escapeString($fn->xss_clean($_POST['type_id']));
    $user_id = $db->escapeString($fn->xss_clean($_POST['user_id']));

    //image path to save
    $target_path = '../upload/complaints/';

    if (!empty($title) && !empty($email) && !empty($message) && !empty($type_id) && !empty($user_id)) {
        // check image error
        if (!empty($_FILES["image"]["name"])) {
            if ($_FILES['image']['error'] == 0) {
                if (!is_dir($target_path)) {
                    mkdir($target_path, 0777, true);
                }
                $extensions = explode(".", $_FILES["image"]["name"]);
                $extension = $extensions[1];
                $result = $fn->validate_image($_FILES["image"]);
                if ($result) {
                    $response['error'] = true;
                    $response['message'] = "image type must jpg, jpeg, gif, or png!";
                    print_r(json_encode($response));
                    return false;
                }
                $string = '0123456789';
                $file = preg_replace("/\s+/", "_", $_FILES['image']['name']);
                $menu_image = $function->get_random_string($string, 4) . "-" . date("Y-m-d") . "." . $extension;

                $upload = move_uploaded_file($_FILES['image']['tmp_name'], '../upload/complaints/' . $menu_image);

                $upload_image = 'upload/complaints/' . $menu_image;
            }
            $sql_query = "INSERT INTO complaints (title,email,message, image,type_id,user_id) VALUES ('$title','$email', '$message', '$upload_image', '$type_id','$user_id')";
        } else {
            $sql_query = "INSERT INTO complaints (title,email,message,type,type_id,user_id) VALUES ('$title','$email', '$message', '$type','$type_id','$user_id')";
        }
        if ($db->sql($sql_query)) {
            $response['error'] = true;
            $response['message'] = "Complaint Added Successfully!";
        } else {
            $response['error'] = false;
            $response['message'] = "Some Error Occrred! please try again.";
        }
        print_r(json_encode($response));
    }
}

if (isset($_POST['get_complaint']) && !empty($_POST['get_complaint'])) {
    /* 
    3.get_complaints
        accesskey:90336
        get_complaint:1
        id:1                    // {optional}
        user_id:1782            // {optional}
        offset:0                // {optional}
        limit:10                // {optional}
        sort:id                 // {optional}
        order:DESC / ASC        // {optional}
        search:search_value     // {optional}
    */
    $user_id = (isset($_POST['user_id']) && is_numeric($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";
    $where = "";
    if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
        $id = $db->escapeString($fn->xss_clean_array($_POST['user_id']));
        $where .= !empty($where) ? " AND user_id = $user_id" : " where user_id = $user_id";
    }
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = $db->escapeString($fn->xss_clean_array($_POST['id']));
        $where .= !empty($where) ? " AND c.id = $id" : " where c.id = $id";
    }
    $offset = (isset($_POST['offset']) && !empty($fn->xss_clean_array($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean_array($_POST['offset'])) : 0;
    $limit = (isset($_POST['limit']) && !empty($fn->xss_clean_array($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean_array($_POST['limit'])) : 10;

    $sort = (isset($_POST['sort']) && !empty($fn->xss_clean_array($_POST['sort']))) ? $db->escapeString($fn->xss_clean_array($_POST['sort'])) : 'id';
    $order = (isset($_POST['order']) && !empty($fn->xss_clean_array($_POST['order']))) ? $db->escapeString($fn->xss_clean_array($_POST['order'])) : 'DESC';

    if (isset($_POST['search'])) {
        $search = $db->escapeString($_POST['search']);
        $where .= " AND (c.`title` like '%" . $search . "%' OR c.`message` like '%" . $search . "%' )";
    }

    $sql = "SELECT count(c.id) as total FROM `complaints` c join users u on u.id=c.user_id " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    $total = $res[0]['total'];

    $sql = "SELECT c.*,u.name as username,(SELECT type FROM complaint_type ct where ct.id=c.type_id) as complaint_type FROM `complaints` c
    join users u on u.id=c.user_id " . $where . " ORDER BY `$sort` $order LIMIT $offset,$limit";
    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res)) {
        foreach ($res as $row) {
            $tempRow['id'] = $row['id'];
            $tempRow['title'] = $row['title'];
            $tempRow['message'] = $row['message'];
            $tempRow['username'] = $row['username'];
            $tempRow['image'] = (!empty($row['image'])) ? DOMAIN_URL . $row['image'] : '';
            $tempRow['status'] = $row['status'];
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
    }
    print_r(json_encode($response));
}

if (isset($_POST['update_complaint']) && !empty($_POST['update_complaint'])) {
    /* 
    4.update_complaint
        accesskey:90336
        update_complaint:1
        id:60 
        title:complaint register
        email:user_email
        message:describ complaint yours
        type_id: 2  
        user_id:1782  
        status: reopen / resolved           
        image:file                     
    */
    if (empty($_POST['id']) || empty($_POST['title']) || empty($_POST['email']) || empty($_POST['message']) || empty($_POST['user_id']) || empty($_POST['user_id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
        print_r(json_encode($response));
        return false;
    }
    $id = $db->escapeString($fn->xss_clean_array($_POST['id']));
    $title = $db->escapeString($fn->xss_clean_array($_POST['title']));
    $email = $db->escapeString($fn->xss_clean_array($_POST['email']));
    $message = $db->escapeString($fn->xss_clean_array($_POST['message']));
    $type_id = $db->escapeString($fn->xss_clean_array($_POST['type_id']));
    $user_id = $db->escapeString($fn->xss_clean_array($_POST['user_id']));
    $status = $db->escapeString($fn->xss_clean($_POST['status']));

    $sql = "SELECT * FROM `complaints` where id = " . $id;
    $db->sql($sql);
    $res = $db->getResult();
    $target_path = '../upload/complaints/';

    if (!empty($res)) {
        if (!empty($res[0]['image']) || $res[0]['image'] != '') {
            $old_image = $res[0]['image'];
            if (!empty($old_image)) {
                unlink('../' . $old_image);
            }
        }
        if ($_FILES['image']['error'] == 0) {
            if (!is_dir($target_path)) {
                mkdir($target_path, 0777, true);
            }
            $extensions = explode(".", $_FILES["image"]["name"]);
            $extension = $extensions[1];

            $result = $fn->validate_image($_FILES["image"]);
            if ($result) {
                $response['error'] = true;
                $response['message'] = "image type must jpg, jpeg, gif, or png!";
                print_r(json_encode($response));
                return false;
            }

            $string = '0123456789';
            $file = preg_replace("/\s+/", "_", $_FILES['image']['name']);
            $menu_image = $function->get_random_string($string, 4) . "-" . date("Y-m-d") . "." . $extension;

            $upload = move_uploaded_file($_FILES['image']['tmp_name'], '../upload/complaints/' . $menu_image);

            $upload_image = 'upload/complaints/' . $menu_image;
            if (($status === 'reopen') || ($status === 'resolved')) {
                $sql_query = "UPDATE complaints SET `id` =  '" . $id . "',`title` = '" . $title . "',`email` = '" . $email . "',`message` = '" . $message . "',`type_id` = '" . $type_id . "',`user_id` = '" . $user_id . "',`status` =  '" . $status . "', `image` = '" . $upload_image . "'  where `id`=" . $id;
            } else {
                $sql_query = "UPDATE complaints SET `id` =  '" . $id . "',`title` = '" . $title . "',`email` = '" . $email . "',`message` = '" . $message . "',`type_id` = '" . $type_id . "',`user_id` = '" . $user_id . "', `image` = '" . $upload_image . "'  where `id`=" . $id;
                // $response['error'] = true;
                // $response['message'] = "You can not update status ";
                // print_r(json_encode($response));
                // return false;

            }

            if ($db->sql($sql_query)) {
                $response['error'] = false;
                $response['message'] = "complaint Updated Successfully!";
            } else {
                $response['error'] = true;
                $response['message'] = "Some Error Occrred! please try again.";
            }
            print_r(json_encode($response));
        }
    } else {
        $response['error'] = false;
        $response['message'] = "Id is not found.";
    }
}

/* 
5.get_complaint_comments
accesskey:90336
get_complaint_comments:1
complaint_id:1                    
id:1                    // {optional}
user_id:1782            // {optional}
offset:0                // {optional}
limit:10                // {optional}
sort:id                 // {optional}
order:DESC / ASC        // {optional}
search:search_value     // {optional}
*/
if (isset($_POST['get_complaint_comments']) && !empty($_POST['get_complaint_comments'])) {
    $complaint_id = $db->escapeString($fn->xss_clean($_POST['complaint_id']));

    $where = "";
    if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
        $user_id = $db->escapeString($fn->xss_clean($_POST['user_id']));
        $where .= " AND type_id = $user_id";
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
        $sql = " select count(cc.id) as total from complaint_comments cc left join admin a on a.id = cc.type_id
        left join users u on u.id = cc.type_id where 1" . $where;
        // $sql = "SELECT count(cc.id) as total FROM `complaint_comments` cc join complaints c on c.id=cc.complaint_id join users u on u.id=c.user_id Where cc.complaint_id = $complaint_id" . $where;
        $db->sql($sql);
        $res = $db->getResult();
        $total = $res[0]['total'];

        $sql = "select cc.*,case when cc.type='admin' then a.username else u.name end as username from complaint_comments cc left join admin a on a.id = cc.type_id
        left join users u on u.id = cc.type_id where 1" . $where . " ORDER BY `$sort` $order LIMIT $offset,$limit";

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
if (isset($_POST['add_complaint_comments']) && !empty($_POST['add_complaint_comments'])) {
    /*
    6.add_complaint_comments
        accesskey:90336
        add_complaint_comments:1
        complaint_id: 2
        type: user
        user_id: 1782
        message: I have ordered this product
    */
    if (empty($_POST['complaint_id']) && empty($_POST['type']) && empty($_POST['message'] && empty($_POST['user_id']))) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
        print_r(json_encode($response));
        return false;
    }
    $complaint_id = $db->escapeString($fn->xss_clean($_POST['complaint_id']));
    $type = $db->escapeString($fn->xss_clean($_POST['type']));
    $type_id = $db->escapeString($fn->xss_clean($_POST['user_id']));
    $message = $db->escapeString($fn->xss_clean($_POST['message']));

    if (!empty($complaint_id) && !empty($type) && !empty($message) && !empty($type_id)) {

        $sql = "INSERT INTO complaint_comments (complaint_id,message,type,type_id) VALUES ($complaint_id, '$message','$type','$type_id')";

        if ($db->sql($sql)) {
            $response['error'] = true;
            $response['message'] = "Complaint Comment Added Successfully!";
        } else {
            $response['error'] = false;
            $response['message'] = "Some Error Occrred! please try again.";
        }
        print_r(json_encode($response));
    }
}

if ((isset($_POST['delete_complaint_comments'])) && ($_POST['delete_complaint_comments'] == 1)) {
    /*
    7.delete_complaint_comments 
        accesskey:90336
        delete_complaint_comments:1
        id:4
    */
    if (empty($_POST['id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass id!";
        print_r(json_encode($response));
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_POST['id']));

    $sql_query = "DELETE FROM `complaint_comments` WHERE id=" . $id;
    if ($db->sql($sql_query)) {
        $response['error'] = true;
        $response['message'] = "Complaints Comments Deleted Successfully!";
    } else {
        $response['error'] = false;
        $response['message'] = "Some Error Occrred! please try again.";
    }
    print_r(json_encode($response));
}

if ((isset($_POST['delete_complaint'])) && ($_POST['delete_complaint'] == 1)) {
    /* 
    8.delete_complaint 
        accesskey:90336
        delete_complaint:1
        id:4
    */
    if (empty($_POST['id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass id!";
        print_r(json_encode($response));
        return false;
    }

    $id = $db->escapeString($fn->xss_clean($_POST['id']));

    $sql_query = "SELECT image FROM complaints WHERE id =" . $id;
    $db->sql($sql_query);
    $res = $db->getResult();

    if (!empty($res[0]['image'])) {
        unlink('../' . $res[0]['image']);
    }

    $sql_query = "DELETE FROM `complaints` WHERE id=" . $id;
    if ($db->sql($sql_query)) {
        $response['error'] = true;
        $response['message'] = "Complaints Deleted Successfully!";
    } else {
        $response['error'] = false;
        $response['message'] = "Some Error Occrred! please try again.";
    }
    print_r(json_encode($response));
}
