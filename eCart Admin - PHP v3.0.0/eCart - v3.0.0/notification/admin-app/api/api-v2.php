<?php
header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Access-Control-Allow-Origin: *');


include_once('../../includes/crud.php');
include_once('../../includes/custom-functions.php');
include_once('verify-token.php');
include_once('../../includes/functions.php');
require_once('../../includes/firebase.php');
require_once('../../includes/push.php');
$function = new functions;
$fn = new custom_functions();
$db = new Database();
$db->connect();
include_once('../../includes/variables.php');

include_once('../../delivery-boy/api/send-email.php');
$config = $fn->get_configurations();
$time_slot_config = $fn->time_slot_config();
$time_zone = $fn->set_timezone($config);
if(!$time_zone){
    $response['error'] = true;
    $response['message'] = "Time Zone is not set.";
    print_r(json_encode($response));
    return false;
    exit();
}
$low_stock_limit = $config['low-stock-limit'];

/* 
-------------------------------------------
APIs for Admin App
-------------------------------------------
1. add_category
2. update_category
3. delete_category
4. get_categories
5. add_subcategory
6. update_subcategory
7. delete_subcategory
8. get_subcategories
9. add_delivery_boy
10.update_delivery_boy
11.delete_delivery_boy
12.get_delivery_boys
13.add_products
14.update_products
15.delete_products
16.get_products
17.send_notification
18.delete_notification
19.get_notification

-------------------------------------------
-------------------------------------------
*/

if (!verify_token()) {
    return false;
}

if (!isset($_POST['accesskey'])  || trim($_POST['accesskey']) != $access_key) {
    $response['error'] = true;
    $response['message'] = "No Accsess key found!";
    print_r(json_encode($response));
    return false;
    exit();
}

/*
1.add_category
    accesskey:90336
    add_category:1
    category_name:Beverages
    category_subtitle:Cold Drinks, Soft Drinks, Sodas
    image:FILE
*/

if (isset($_POST['add_category']) && !empty($_POST['add_category'])) {
    if (empty($_POST['category_name']) || empty($_POST['category_subtitle'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
        print_r(json_encode($response));
        return false;
    }
    $category_name = $db->escapeString($fn->xss_clean_array($_POST['category_name']));
    $category_subtitle = $db->escapeString($fn->xss_clean_array($_POST['category_subtitle']));

    $target_path = '../../upload/images/';
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

        $upload = move_uploaded_file($_FILES['image']['tmp_name'], '../../upload/images/' . $menu_image);

        $upload_image = 'upload/images/' . $menu_image;

        $sql_query = "INSERT INTO category (name,subtitle, image,status,web_image)VALUES('$category_name', '$category_subtitle', '$upload_image','1','')";
        if ($db->sql($sql_query)) {
            $response['error'] = true;
            $response['message'] = "Category Added Successfully!";
        } else {
            $response['error'] = false;
            $response['message'] = "Some Error Occrred! please try again.";
        }
        print_r(json_encode($response));
    }
}

/*
2.update_category
    accesskey:90336
    update_category:1
    id:122
    category_name:Beverages
    category_subtitle:Cold Drinks, Soft Drinks, Sodas
    upload_image:FILE
*/

if (isset($_POST['update_category']) && !empty($_POST['update_category'])) {
    if (empty($_POST['category_name']) || empty($_POST['category_subtitle']) || empty($_POST['id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
        print_r(json_encode($response));
        return false;
    }
    $category_name = $db->escapeString($fn->xss_clean_array($_POST['category_name']));
    $category_subtitle = $db->escapeString($fn->xss_clean_array($_POST['category_subtitle']));

    $id = $db->escapeString($fn->xss_clean_array($_POST['id']));
    $sql = "SELECT * FROM `category` where id=" . $id;
    $db->sql($sql);
    $res = $db->getResult();

    if (!empty($res)) {
        if (!empty($res[0]['image']) || $res[0]['image'] != '') {
            $old_image = $res[0]['image'];
            if (!empty($old_image)) {
                unlink('../../' . $old_image);
            }
        }
        $target_path = '../../upload/images/';
        if ($_FILES['upload_image']['error'] == 0) {
            if (!is_dir($target_path)) {
                mkdir($target_path, 0777, true);
            }
            $extensions = explode(".", $_FILES["upload_image"]["name"]);
            $extension = $extensions[1];

            $result = $fn->validate_image($_FILES["upload_image"]);
            if ($result) {
                $response['error'] = true;
                $response['message'] = "image type must jpg, jpeg, gif, or png!";
                print_r(json_encode($response));
                return false;
            }

            $string = '0123456789';
            $file = preg_replace("/\s+/", "_", $_FILES['upload_image']['name']);
            $menu_image = $function->get_random_string($string, 4) . "-" . date("Y-m-d") . "." . $extension;

            $upload = move_uploaded_file($_FILES['upload_image']['tmp_name'], '../../upload/images/' . $menu_image);

            $upload_image = 'upload/images/' . $menu_image;

            $sql_query = "UPDATE category SET `name` =  '" . $category_name . "',`subtitle` = '" . $category_subtitle . "', `image` = '" . $upload_image . "',`status` = '1' where `id`=" . $id;
            if ($db->sql($sql_query)) {
                $response['error'] = true;
                $response['message'] = "Category Updated Successfully!";
            } else {
                $response['error'] = false;
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
3.delete_category
    accesskey:90336
    delete_category:1
    id:122
*/

if ((isset($_POST['delete_category'])) && ($_POST['delete_category'] == 1)) {
    if (empty($_POST['id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass id fields!";
        print_r(json_encode($response));
        return false;
    }

    $id = $db->escapeString($fn->xss_clean($_POST['id']));

    $sql_query = "SELECT image FROM category WHERE id =" . $id;
    $db->sql($sql_query);
    $res = $db->getResult();

    if ($res[0]['image']) {
        unlink('../../' . $res[0]['image']);
    }
    return false;

    $sql_query = "DELETE FROM `category` WHERE id=" . $id;
    if ($db->sql($sql_query)) {
        $response['error'] = true;
        $response['message'] = "Category Deleted Successfully!";
    } else {
        $response['error'] = false;
        $response['message'] = "Some Error Occrred! please try again.";
    }
    print_r(json_encode($response));
}

/* 
4.get_categories
    accesskey:90336
    get_categories:1
    category_id:28      // {optional}
    limit:10            // {optional}
    offset:0            // {optional}
    sort:id             // {optional}
    order:ASC/DESC      // {optional}
*/

if (isset($_POST['get_categories']) && !empty($_POST['get_categories']) && ($_POST['get_categories'] == 1)) {

    $where = '';
    $offset = (isset($_POST['offset']) && !empty(trim($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString(trim($fn->xss_clean($_POST['offset']))) : 0;
    $limit = (isset($_POST['limit']) && !empty(trim($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString(trim($fn->xss_clean($_POST['limit']))) : 10;
    $sort = (isset($_POST['sort']) && !empty(trim($_POST['sort']))) ? $db->escapeString(trim($fn->xss_clean($_POST['sort']))) : 'id';
    $order = (isset($_POST['order']) && !empty(trim($_POST['order']))) ? $db->escapeString(trim($fn->xss_clean($_POST['order']))) : 'DESC';

    $category_id = (isset($_POST['category_id']) && !empty(trim($_POST['category_id']))) ? $db->escapeString($fn->xss_clean($_POST['category_id'])) : '';
    if (empty($category_id) && $category_id == '') {
        $sql_query = "SELECT * FROM category ORDER BY id ASC";
    } else {
        $sql_query = "SELECT * FROM category WHERE id = '" . $category_id . "'";
    }
    $db->sql($sql_query);
    $res = $db->getResult();
    if (!empty($res)) {
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['image'] = (!empty($res[$i]['image'])) ? DOMAIN_URL . '' . $res[$i]['image'] : '';
            $res[$i]['web_image'] = (!empty($res[$i]['web_image'])) ? DOMAIN_URL . '' . $res[$i]['web_image'] : '';
        }
        $tmp = [];
        foreach ($res as $r) {
            $r['childs'] = [];

            $db->sql("SELECT * FROM subcategory WHERE category_id = '" . $r['id'] . "' ORDER BY id DESC");
            $childs = $db->getResult();
            if (!empty($childs)) {
                for ($i = 0; $i < count($childs); $i++) {
                    $childs[$i]['image'] = (!empty($childs[$i]['image'])) ? DOMAIN_URL . '' . $childs[$i]['image'] : '';
                    $r['childs'][$childs[$i]['slug']] = (array)$childs[$i];
                }
            }
            $tmp[] = $r;
        }
        $res = $tmp;
        $response['error'] = "false";
        $response['data'] = $res;
    } else {
        $response['error'] = "true";
        $response['message'] = "No data found!";
    }
    print_r(json_encode($response));
    return false;
}

/*
5.add_subcategory
    accesskey:90336
    add_subcategory:1
    subcategory_name:baverages
    category_subtitle:Cold Drinks, Soft Drinks, Sodas
    main_category: 46
    upload_image:FILE
*/

if ((isset($_POST['add_subcategory'])) && ($_POST['add_subcategory'] == 1)) {
    if (empty($_POST['subcategory_name']) || empty($_POST['category_subtitle']) || empty($_POST['main_category'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
        print_r(json_encode($response));
        return false;
        exit();
    }
    $subcategory_name = $db->escapeString($fn->xss_clean_array($_POST['subcategory_name']));
    $slug = $function->slugify($db->escapeString($fn->xss_clean($_POST['subcategory_name'])));
    $sql = "SELECT slug FROM subcategory";
    $db->sql($sql);
    $res = $db->getResult();
    $i = 1;
    foreach ($res as $row) {
        if ($slug == $row['slug']) {
            $slug = $slug . '-' . $i;
            $i++;
        }
    }

    $category_subtitle = $db->escapeString($fn->xss_clean_array($_POST['category_subtitle']));
    $main_category = $db->escapeString($fn->xss_clean_array($_POST['main_category']));

    $target_path = '../../upload/images/';

    if ($_FILES['upload_image']['error'] == 0) {
        if (!is_dir($target_path)) {
            mkdir($target_path, 0777, true);
        }
        $extensions = explode(".", $_FILES["upload_image"]["name"]);
        $extension = $extensions[1];

        $result = $fn->validate_image($_FILES["upload_image"]);
        if ($result) {
            $response['error'] = true;
            $response['message'] = "image type must jpg, jpeg, gif, or png!";
            print_r(json_encode($response));
            return false;
        }

        $string = '0123456789';
        $file = preg_replace("/\s+/", "_", $_FILES['upload_image']['name']);
        $menu_image = $function->get_random_string($string, 4) . "-" . date("Y-m-d") . "." . $extension;

        $upload = move_uploaded_file($_FILES['upload_image']['tmp_name'], '../../upload/images/' . $menu_image);

        $upload_image = 'upload/images/' . $menu_image;

        $sql_query = "INSERT INTO subcategory (category_id, name, slug, subtitle, image)VALUES('$main_category', '$subcategory_name', '$slug', '$category_subtitle', '$upload_image')";
        if ($db->sql($sql_query)) {
            $response['error'] = true;
            $response['message'] = "Subcategory Added Successfully!";
        } else {
            $response['error'] = false;
            $response['message'] = "Some Error Occrred! please try again.";
        }
        print_r(json_encode($response));
    }
}

/*
6.update_subcategory
    accesskey:90336
    update_subcategory:1
    id:122
    subcategory_name:baverages
    category_subtitle:Cold Drinks, Soft Drinks, Sodas
    main_category: 46
    image:FILE
*/

if ((isset($_POST['update_subcategory'])) && ($_POST['update_subcategory'] == 1)) {
    if (empty($_POST['id']) || empty($_POST['subcategory_name']) || empty($_POST['category_subtitle']) || empty($_POST['main_category'])) {
        $response['error'] = true;
        $response['message'] = "Please pass All fields!";
        print_r(json_encode($response));
        return false;
    }

    $id = $db->escapeString($fn->xss_clean($_POST['id']));

    $sql = "SELECT * FROM `subcategory` where id=" . $id;
    $db->sql($sql);
    $res = $db->getResult();

    $subcategory_name = $db->escapeString($fn->xss_clean_array($_POST['subcategory_name']));
    $slug = $function->slugify($db->escapeString($fn->xss_clean($_POST['subcategory_name'])));
    $sql = "SELECT slug FROM subcategory";
    $db->sql($sql);
    $res = $db->getResult();
    $i = 1;
    foreach ($res as $row) {
        if ($slug == $row['slug']) {
            $slug = $slug . '-' . $i;
            $i++;
        }
    }

    $category_subtitle = $db->escapeString($fn->xss_clean_array($_POST['category_subtitle']));
    $main_category = $db->escapeString($fn->xss_clean_array($_POST['main_category']));

    $sql = "SELECT id,image FROM `subcategory` where id=$id";
    $db->sql($sql);
    $res1 = $db->getResult();

    if (!empty($res1[0]['image'])) {
        $old_image = $res1[0]['image'];
        if (!empty($old_image)) {
            unlink('../../' . $old_image);
        }
    }
    $target_path = '../../upload/images/';
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

        $upload = move_uploaded_file($_FILES['image']['tmp_name'], '../../upload/images/' . $menu_image);

        $upload_image = 'upload/images/' . $menu_image;
        $sql_query = "UPDATE subcategory SET `category_id` =  '" . $main_category . "',`name` = '" . $subcategory_name . "', `slug` = '" . $slug . "', `subtitle` = '" . $category_subtitle . "', `image` = '" . $upload_image . "' where `id`=" . $id;
        if ($db->sql($sql_query)) {
            $response['error'] = true;
            $response['message'] = "Subcategory updated Successfully!";
        } else {
            $response['error'] = false;
            $response['message'] = "Some Error Occrred! please try again.";
        }
        print_r(json_encode($response));
    }
}

/*
7.delete_subcategory
    accesskey:90336
    delete_subcategory:1
    id:122
*/

if ((isset($_POST['delete_subcategory'])) && ($_POST['delete_subcategory'] == 1)) {
    if (empty($_POST['id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass id fields!";
        print_r(json_encode($response));
        return false;
    }

    $id = $db->escapeString($fn->xss_clean($_POST['id']));

    $sql_query = "SELECT image FROM subcategory WHERE id =" . $id;
    $db->sql($sql_query);
    $res = $db->getResult();

    if ($res[0]['image']) {
        unlink('../../' . $res[0]['image']);
    }

    $sql_query = "DELETE FROM `subcategory` WHERE id=" . $id;
    if ($db->sql($sql_query)) {
        $response['error'] = true;
        $response['message'] = "subcategory Deleted Successfully!";
    } else {
        $response['error'] = false;
        $response['message'] = "Some Error Occrred! please try again.";
    }
    print_r(json_encode($response));
}

/* 
8.get_subcategories
    accesskey:90336
    get_subcategories:1
    category_id:28      // {optional}
    limit:10            // {optional}
    offset:0            // {optional}
    sort:id             // {optional}
    order:ASC/DESC      // {optional}
*/
if (isset($_POST['get_subcategories']) && !empty($_POST['get_subcategories']) && ($_POST['get_subcategories'] == 1)) {
    $where = '';
    $offset = (isset($_POST['offset']) && !empty(trim($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString(trim($fn->xss_clean($_POST['offset']))) : 0;
    $limit = (isset($_POST['limit']) && !empty(trim($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString(trim($fn->xss_clean($_POST['limit']))) : 10;
    $sort = (isset($_POST['sort']) && !empty(trim($_POST['sort']))) ? $db->escapeString(trim($fn->xss_clean($_POST['sort']))) : 'id';
    $order = (isset($_POST['order']) && !empty(trim($_POST['order']))) ? $db->escapeString(trim($fn->xss_clean($_POST['order']))) : 'DESC';

    $category_id = (isset($_POST['category_id']) && !empty(trim($_POST['category_id']))) ? $db->escapeString($fn->xss_clean($_POST['category_id'])) : '';
    if (empty($category_id) && $category_id == '') {
        $sql_query = "SELECT * FROM subcategory ORDER BY id ASC";
    } else {
        $sql_query = "SELECT * FROM subcategory WHERE category_id = '" . $category_id . "'";
    }
    $db->sql($sql_query);
    $res = $db->getResult();
    if (!empty($res)) {
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['image'] = (!empty($res[$i]['image'])) ? DOMAIN_URL . '' . $res[$i]['image'] : '';
            $res[$i]['web_image'] = (!empty($res[$i]['web_image'])) ? DOMAIN_URL . '' . $res[$i]['web_image'] : '';
        }
        $tmp = [];
        foreach ($res as $r) {
            $r['childs'] = [];

            $db->sql("SELECT * FROM subcategory WHERE category_id = '" . $r['category_id'] . "' ORDER BY id DESC");
            $childs = $db->getResult();
            if (!empty($childs)) {
                for ($i = 0; $i < count($childs); $i++) {
                    $childs[$i]['image'] = (!empty($childs[$i]['image'])) ? DOMAIN_URL . '' . $childs[$i]['image'] : '';
                    $r['childs'][$childs[$i]['slug']] = (array)$childs[$i];
                }
            }
            $tmp[] = $r;
        }
        $res = $tmp;
        $response['error'] = "false";
        $response['data'] = $res;
    } else {
        $response['error'] = "true";
        $response['message'] = "No data found!";
    }
    print_r(json_encode($response));
    return false;
}

/* 
9. add_delivery_boy
    accesskey:90336
    add_delivery_boy:1		
    name:delivery_boy
    mobile:9963258652
    address:time square
    bonus:10
    dob:2020-09-12
    bank_name:SBI
    other_payment_info:description  // {optional}
    account_number:12547896523652
    account_name:DEMO
    ifsc_code:254SBIfbfg
    password:asd124
    driving_license:FILE
    national_identity_card:FILE
*/
if (isset($_POST['add_delivery_boy']) && !empty($_POST['add_delivery_boy'])  && ($_POST['add_delivery_boy'] == 1)) {

    if (empty($_POST['name']) || empty($_POST['mobile']) || empty($_POST['address']) || empty($_POST['bonus']) || empty($_POST['dob']) || empty($_POST['bank_name']) ||  empty($_POST['account_number']) || empty($_POST['account_name']) || empty($_POST['ifsc_code']) || empty($_POST['password'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
        print_r(json_encode($response));
        return false;
    }

    $name = $db->escapeString(trim($fn->xss_clean($_POST['name'])));
    $mobile = $db->escapeString(trim($fn->xss_clean($_POST['mobile'])));
    $address = $db->escapeString(trim($fn->xss_clean($_POST['address'])));
    $bonus = $db->escapeString(trim($fn->xss_clean($_POST['bonus'])));
    $dob = $db->escapeString(trim($fn->xss_clean($_POST['dob'])));
    $bank_name = $db->escapeString(trim($fn->xss_clean($_POST['bank_name'])));
    $other_payment_info = (isset($_POST['other_payment_info']) && !empty(trim($_POST['other_payment_info']))) ? $db->escapeString(trim($fn->xss_clean($_POST['other_payment_info']))) : '';
    $account_number = $db->escapeString(trim($fn->xss_clean($_POST['account_number'])));
    $account_name = $db->escapeString(trim($fn->xss_clean($_POST['account_name'])));
    $ifsc_code = $db->escapeString(trim($fn->xss_clean($_POST['ifsc_code'])));
    $password = $db->escapeString(trim($fn->xss_clean($_POST['password'])));

    $sql = 'SELECT id FROM delivery_boys WHERE mobile=' . $mobile;
    $db->sql($sql);
    $res = $db->getResult();
    $count = $db->numRows($res);

    if ($count > 0) {
        $response['error'] = true;
        $response['message'] = "Mobile Number Already Exists!";
        print_r(json_encode($response));
        return false;
    }

    $target_path = '../../upload/delivery-boy/';
    if ($_FILES['driving_license']['error'] == 0 && $_FILES['driving_license']['size'] > 0) {
        if (!is_dir($target_path)) {
            mkdir($target_path, 0777, true);
        }
        $extension = pathinfo($_FILES["driving_license"]["name"])['extension'];

        $result = $fn->validate_image($_FILES["driving_license"]);
        if ($result) {
            $response['error'] = true;
            $response['message'] = "Driving License image type must jpg, jpeg, gif, or png!";
            print_r(json_encode($response));
            return false;
        }
        $dr_filename = microtime(true) . '.' . strtolower($extension);
        $dr_full_path = $target_path . "" . $dr_filename;
        if (!move_uploaded_file($_FILES["driving_license"]["tmp_name"], $dr_full_path)) {
            $response['error'] = true;
            $response['message'] = "Invalid directory to load image!";
            print_r(json_encode($response));
            return false;
        }
    }
    if ($_FILES['national_identity_card']['error'] == 0 && $_FILES['national_identity_card']['size'] > 0) {
        if (!is_dir($target_path)) {
            mkdir($target_path, 0777, true);
        }
        $extension = pathinfo($_FILES["national_identity_card"]["name"])['extension'];

        $result = $fn->validate_image($_FILES["national_identity_card"]);
        if ($result) {
            $response['error'] = true;
            $response['message'] = "National Identity Card image type must jpg, jpeg, gif, or png!";
            print_r(json_encode($response));
            return false;
        }

        $nic_filename = microtime(true) . '.' . strtolower($extension);
        $nic_full_path = $target_path . "" . $nic_filename;
        if (!move_uploaded_file($_FILES["national_identity_card"]["tmp_name"], $nic_full_path)) {
            $response['error'] = true;
            $response['message'] = "Invalid directory to load image!";
            print_r(json_encode($response));
            return false;
        }
    }
    $sql = "INSERT INTO delivery_boys (`name`,`mobile`,`password`,`address`,`bonus`, `driving_license`, `national_identity_card`, `dob`, `bank_account_number`, `bank_name`, `account_name`, `ifsc_code`,`other_payment_information`) VALUES ('$name', '$mobile', '$password', '$address','$bonus','$dr_filename', '$nic_filename', '$dob','$account_number','$bank_name','$account_name','$ifsc_code','$other_payment_info')";
    if ($db->sql($sql)) {
        $response['error'] = true;
        $response['message'] = "Delivery Boy Added Successfully!";
    } else {
        $response['error'] = true;
        $response['message'] = "Some Error Occrred! please try again.";
    }
    print_r(json_encode($response));
}

/* 
10. update_delivery_boy
    accesskey:90336
    update_delivery_boy:1
    id:12
    name:delivery_boy
    mobile:9963258652
    address:time square
    bonus:10
    dob:2020-09-12
    bank_name:SBI
    other_payment_info:description 
    account_number:12547896523652
    account_name:DEMO
    ifsc_code:254SBIfbfg
    password:asd124
    status:1
    driving_license:FILE         // {optional}
    national_identity_card:FILE  // {optional}
*/
if (isset($_POST['update_delivery_boy']) && !empty($_POST['update_delivery_boy'])  && ($_POST['update_delivery_boy'] == 1)) {

    if (empty($_POST['name']) || empty($_POST['mobile']) || empty($_POST['address']) || empty($_POST['bonus']) || empty($_POST['dob']) || empty($_POST['bank_name']) ||  empty($_POST['account_number']) || empty($_POST['account_name']) || empty($_POST['ifsc_code']) || empty($_POST['password'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
        print_r(json_encode($response));
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_POST['id']));
    $sql = "SELECT id FROM `delivery_boys` where id=$id";
    $db->sql($sql);
    $res1 = $db->getResult();

    if (!empty($res1)) {
        if ($id == 104) {
            $response['error'] = true;
            $response['message'] = "Sorry you can not update this delivery boy.";
            print_r(json_encode($response));
            return false;
        }
        $name = $db->escapeString($fn->xss_clean($_POST['name']));
        $mobile = $db->escapeString($fn->xss_clean($_POST['mobile']));
        $password = !empty($_POST['password']) ? $db->escapeString($fn->xss_clean($_POST['password'])) : '';
        $other_payment_info = !empty($_POST['other_payment_info']) ? $db->escapeString($fn->xss_clean($_POST['other_payment_info'])) : '';
        $address = $db->escapeString($fn->xss_clean($_POST['address']));
        $bonus = $db->escapeString($fn->xss_clean($_POST['bonus']));
        $status = $db->escapeString($fn->xss_clean($_POST['status']));
        $dob = $db->escapeString($fn->xss_clean($_POST['dob']));
        $bank_name = $db->escapeString($fn->xss_clean($_POST['bank_name']));
        $account_number = $db->escapeString($fn->xss_clean($_POST['account_number']));
        $account_name = $db->escapeString($fn->xss_clean($_POST['account_name']));
        $ifsc_code = $db->escapeString($fn->xss_clean($_POST['ifsc_code']));
        $password = !empty($password) ? md5($password) : '';
        $target_path = '../../upload/delivery-boy/';

        $sql = "SELECT id,driving_license,national_identity_card,other_payment_information FROM `delivery_boys` where id=$id";
        $db->sql($sql);
        $res = $db->getResult();
        if ($other_payment_info == '') {
            $other_payment_info = $res[0]['other_payment_information'];
        }

        if (!is_dir($target_path)) {
            mkdir($target_path, 0777, true);
        }
        if (isset($_FILES['driving_license']) && $_FILES['driving_license']['size'] != 0 && $_FILES['driving_license']['error'] == 0 && !empty($_FILES['driving_license'])) {
            if (!empty($res[0]['driving_license'])) {
                $old_image = $res[0]['driving_license'];
                if (!empty($old_image)) {
                    unlink($target_path . $old_image);
                }
            }
            $extension = pathinfo($_FILES["driving_license"]["name"])['extension'];

            $result = $fn->validate_image($_FILES["driving_license"]);
            if ($result) {
                $response['error'] = true;
                $response['message'] = "Driving License image type must jpg, jpeg, gif, or png!";
                print_r(json_encode($response));
                return false;
            }

            $dr_filename = microtime(true) . '.' . strtolower($extension);
            $dr_full_path = $target_path . "" . $dr_filename;
            if (!move_uploaded_file($_FILES["driving_license"]["tmp_name"], $dr_full_path)) {
                $response['error'] = true;
                $response['message'] = "Can not upload driving license.";
                print_r(json_encode($response));
                return false;
            }
            $sql = "UPDATE delivery_boys SET `driving_license`='" . $dr_filename . "' WHERE `id`=" . $id;
            $db->sql($sql);
        }
        if (isset($_FILES['national_identity_card']) && $_FILES['national_identity_card']['size'] != 0 && $_FILES['national_identity_card']['error'] == 0 && !empty($_FILES['national_identity_card'])) {
            if (!empty($res[0]['national_identity_card'])) {
                $old_image = $res[0]['national_identity_card'];
                if (!empty($old_image)) {
                    unlink($target_path . $old_image);
                }
            }
            $extension = pathinfo($_FILES["national_identity_card"]["name"])['extension'];

            $result = $fn->validate_image($_FILES["national_identity_card"]);
            if ($result) {
                $response['error'] = true;
                $response['message'] = "National Identity Card image type must jpg, jpeg, gif, or png!";
                print_r(json_encode($response));
                return false;
            }
            $nic_filename = microtime(true) . '.' . strtolower($extension);
            $nic_full_path = $target_path . "" . $nic_filename;
            if (!move_uploaded_file($_FILES["national_identity_card"]["tmp_name"], $nic_full_path)) {
                $response['error'] = true;
                $response['message'] = "Can not upload national identity card";
                print_r(json_encode($response));
                return false;
            }
            $sql = "UPDATE delivery_boys SET `national_identity_card`='" . $nic_filename . "' WHERE `id`=" . $id;
            $db->sql($sql);
        }

        if (!empty($password)) {
            $sql = "Update delivery_boys set `name`='" . $name . "',`mobile`='" . $mobile . "',password='" . $password . "',`address`='" . $address . "',`bonus`='" . $bonus . "',`status`='" . $status . "',`dob`='$dob',`bank_account_number`='$account_number',`bank_name`='$bank_name',`account_name`='$account_name',`ifsc_code`='$ifsc_code',`other_payment_information`='$other_payment_info' where `id`=" . $id;
        } else {
            $sql = "Update delivery_boys set `name`='" . $name . "',`mobile`='" . $mobile . "',`address`='" . $address . "',`bonus`='" . $bonus . "',`status`='" . $status . "',`dob`='$dob',`bank_account_number`='$account_number',`bank_name`='$bank_name',`account_name`='$account_name',`ifsc_code`='$ifsc_code',`other_payment_information`='$other_payment_info'  where `id`=" . $id;
        }
        if ($db->sql($sql)) {
            $response['error'] = false;
            $response['message'] = "Information Updated Successfully.";
        } else {
            $response['error'] = true;
            $response['message'] = "Some Error Occurred! Please Try Again.";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Delivery boy does not exist";
    }
    print_r(json_encode($response));
}

/* 
11.delete_delivery_boy
    accesskey:90336
    delete_delivery_boy:1		
    id:302
*/
if (isset($_POST['delete_delivery_boy']) && !empty($_POST['delete_delivery_boy'])  && ($_POST['delete_delivery_boy'] == 1)) {

    if (empty($_POST['id'])) {
        $response['error'] = true;
        $response['message'] = "delivery boy id is missing!";
        print_r(json_encode($response));
        return false;
    }

    $id = $db->escapeString($fn->xss_clean($_POST['id']));
    $sql = "SELECT id FROM `delivery_boys` where id=$id";
    $db->sql($sql);
    $res1 = $db->getResult();
    if (!empty($res1)) {
        $target_path = '../../upload/delivery-boy/';

        if ($id == 104) {
            $response['error'] = true;
            $response['message'] = "Sorry you can not delete this delivery boy.";
            print_r(json_encode($response));
            return false;
        }
        $sql1 = "SELECT id,driving_license,national_identity_card,other_payment_information FROM `delivery_boys` where id=$id";
        $db->sql($sql1);
        $res1 = $db->getResult();
        $sql = "DELETE FROM `delivery_boys` WHERE id=" . $id;
        if ($db->sql($sql)) {
            $sql = "DELETE FROM `fund_transfers` WHERE delivery_boy_id=" . $id;
            $db->sql($sql);
            $sql = "DELETE FROM `withdrawal_requests` WHERE `type_id`=" . $id . " AND `type`='delivery_boy'";
            $db->sql($sql);

            if (!empty($res1[0]['driving_license']) || $res1[0]['driving_license'] != '') {
                $old_image = $res1[0]['driving_license'];
                if (!empty($old_image)) {
                    unlink($target_path . $old_image);
                }
            }
            if (!empty($res1[0]['national_identity_card']) || $res1[0]['national_identity_card'] != '') {
                $old_image = $res1[0]['national_identity_card'];
                if (!empty($old_image)) {
                    unlink($target_path . $old_image);
                }
            }
            $response['error'] = false;
            $response['message'] = "Delivery boy deleted successfully";
        } else {
            $response['error'] = true;
            $response['message'] = "Delivery boy not deleted.";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Delivery boy does not exist.";
    }
    print_r(json_encode($response));
}

/* 
12.get_delivery_boys
   accesskey:90336
   get_delivery_boys:1
   id:292           // {optional}
   limit:10         // {optional}
   offset:0         // {optional}
   sort:id          // {optional}
   order:ASC/DESC   // {optional}
   search:value     // {optional}
*/
if (isset($_POST['get_delivery_boys']) && !empty($_POST['get_delivery_boys'])  && ($_POST['get_delivery_boys'] == 1)) {
    $where = '';
    $offset = (isset($_POST['offset']) && !empty(trim($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString(trim($fn->xss_clean($_POST['offset']))) : 0;
    $limit = (isset($_POST['limit']) && !empty(trim($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString(trim($fn->xss_clean($_POST['limit']))) : "";

    $sort = (isset($_POST['sort']) && !empty(trim($_POST['sort']))) ? $db->escapeString(trim($fn->xss_clean($_POST['sort']))) : 'id';
    $order = (isset($_POST['order']) && !empty(trim($_POST['order']))) ? $db->escapeString(trim($fn->xss_clean($_POST['order']))) : 'DESC';

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = $db->escapeString($fn->xss_clean($_POST['id']));
        $where .= ' where id=' . $id;
    }
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $search = $db->escapeString($fn->xss_clean($_POST['search']));
        if (!empty($where)) {
            $where = " AND `id` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `mobile` like '%" . $search . "%' OR `address` like '%" . $search . "%'";
        } else {
            $where = " WHERE `id` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `mobile` like '%" . $search . "%' OR `address` like '%" . $search . "%'";
        }
    }
    $sql = "SELECT COUNT(id) as total FROM `delivery_boys` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res)) {
        foreach ($res as $row)
            $total = $row['total'];
        if ($limit == "") {
            $sql = "SELECT * FROM `delivery_boys` " . $where . " ORDER BY " . $sort . " " . $order;
        } else {
            $sql = "SELECT * FROM `delivery_boys` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
        }
        $db->sql($sql);
        $res = $db->getResult();
        $rows = array();
        $tempRow = array();

        $path = 'upload/delivery-boy/';
        foreach ($res as $row) {
            $tempRow['id'] = $row['id'];
            $tempRow['name'] = $row['name'];
            $tempRow['mobile'] = $row['mobile'];
            $tempRow['address'] = !empty($row['address']) ? $row['address'] : "";
            $tempRow['bonus'] = $row['bonus'];
            $tempRow['balance'] = ceil($row['balance']);
            if (!empty($row['driving_license'])) {
                $tempRow['driving_license'] = DOMAIN_URL . $path . $row['driving_license'];
                $tempRow['national_identity_card'] = DOMAIN_URL . $path . $row['national_identity_card'];
            } else {
                $tempRow['national_identity_card'] = "No National Identity Card";
                $tempRow['driving_license'] = "No Driving License";
            }
            $tempRow['dob'] = !empty($row['dob']) ? $row['dob'] : "";
            $tempRow['bank_account_number'] = !empty($row['bank_account_number']) ? $row['bank_account_number'] : "";
            $tempRow['bank_name'] = !empty($row['bank_name']) ? $row['bank_name'] : "";
            $tempRow['account_name'] = !empty($row['account_name']) ? $row['account_name'] : "";
            $tempRow['other_payment_information'] = (!empty($row['other_payment_information'])) ? $row['other_payment_information'] : "";
            $tempRow['ifsc_code'] = !empty($row['ifsc_code']) ? $row['ifsc_code'] : "";
            if ($row['status'] == 0)
                $tempRow['status'] = "Deactive";
            else
                $tempRow['status'] = "Active";
            $rows[] = $tempRow;
        }
        $response['error'] = false;
        $response['message'] = "Delivery Boys fatched successfully.";
        $response['total'] = $total;
        $response['data'] = $rows;
    } else {
        $response['error'] = true;
        $response['message'] = "Something went wrong, please try again leter.";
    }
    print_r(json_encode($response));
}

/* 
13.add_products
    accesskey:90336
    add_products:1
    name:potato
    category_id:31
    subcategory_id:115
    serve_for:Available / Sold Out
    description:potatos
    tax_id:4                    // {optional}
    manufacturer:india          // {optional}
    made_in:india               // {optional}
    return_status:0 / 1         // {optional}
    cancelable_status:0 / 1     // {optional}
    till_status: received / processed / shipped           // {optional}
    indicator:	0 - none / 1 - veg / 2 - non-veg          // {optional}
    image:FILE          
    other_images[]:FILE

    type:packet
    measurement:500,400
    measurement_unit_id:4,1
    price:175,145
    discounted_price:60,30     // {optional}
    serve_for:Available / Sold Out,Available / Sold Out
    stock:992,458
    stock_unit_id:4,1

    type:loose
    measurement:1,1
    measurement_unit_id:1,5
    price:100,500
    discounted_price:20,15       // {optional}
    serve_for:Available / Sold Out 
    stock:997
    stock_unit_id:1
*/

if (isset($_POST['add_products']) && !empty($_POST['add_products']) && ($_POST['add_products'] == 1)) {

    if (empty($_POST['name']) || empty($_POST['category_id']) || empty($_POST['subcategory_id']) || empty($_POST['serve_for']) || empty($_POST['description']) || empty($_POST['type'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
        print_r(json_encode($response));
        return false;
    }

    if ($_POST['type']) {
        if (empty($_POST['measurement']) || empty($_POST['measurement_unit_id']) || empty($_POST['price']) || empty($_POST['serve_for']) || empty($_POST['stock']) || empty($_POST['stock_unit_id'])) {
            $response['error'] = true;
            $response['message'] = "Please pass product variants fields!";
            print_r(json_encode($response));
            return false;
        }
    }

    $name = $db->escapeString($fn->xss_clean($_POST['name']));
    $tax_id = (isset($_POST['tax_id']) && $_POST['tax_id'] != '') ? $db->escapeString($fn->xss_clean($_POST['tax_id'])) : 0;
    $slug = $function->slugify($db->escapeString($fn->xss_clean($_POST['name'])));
    $category_id = $db->escapeString($fn->xss_clean($_POST['category_id']));
    $subcategory_id = (isset($_POST['subcategory_id']) && $_POST['subcategory_id'] != '') ? $db->escapeString($fn->xss_clean($_POST['subcategory_id'])) : 0;
    $serve_for = $db->escapeString($fn->xss_clean($_POST['serve_for']));
    $description = $db->escapeString($fn->xss_clean($_POST['description']));
    $manufacturer = (isset($_POST['manufacturer']) && $_POST['manufacturer'] != '') ? $db->escapeString($fn->xss_clean($_POST['manufacturer'])) : '';
    $made_in = (isset($_POST['made_in']) && $_POST['made_in'] != '') ? $db->escapeString($fn->xss_clean($_POST['made_in'])) : '';
    $indicator = (isset($_POST['indicator']) && $_POST['indicator'] != '') ? $db->escapeString($fn->xss_clean($_POST['indicator'])) : '0';
    $return_status = (isset($_POST['return_status']) && $_POST['return_status'] != '') ? $db->escapeString($fn->xss_clean($_POST['return_status'])) : '0';
    $cancelable_status = (isset($_POST['cancelable_status']) && $_POST['cancelable_status'] != '') ? $db->escapeString($fn->xss_clean($_POST['cancelable_status'])) : '0';
    $till_status = (isset($_POST['till_status']) && $_POST['till_status'] != '') ? $db->escapeString($fn->xss_clean($_POST['till_status'])) : '';

    $image = $db->escapeString($fn->xss_clean($_FILES['image']['name']));
    $image_error = $db->escapeString($fn->xss_clean($_FILES['image']['error']));
    $image_type = $db->escapeString($fn->xss_clean($_FILES['image']['type']));

    $allowedExts = array("gif", "jpeg", "jpg", "png");

    error_reporting(E_ERROR | E_PARSE);
    $extension = end(explode(".", $_FILES["image"]["name"]));
    $error['other_images'] = $error['image'] = '';

    if ($image_error > 0) {
        $response['error'] = true;
        $response['message'] = "image Not uploaded!";
        print_r(json_encode($response));
        return false;
    } else {
        $result = $fn->validate_image($_FILES["image"]);
        if ($result) {
            $response['error'] = true;
            $response['message'] = "image type must jpg, jpeg, gif, or png!";
            print_r(json_encode($response));
            return false;
        }
    }

    if ($_FILES["other_images"]["error"] == 0) {
        for ($i = 0; $i < count($_FILES["other_images"]["name"]); $i++) {
            if ($_FILES["other_images"]["error"][$i] > 0) {
                $response['error'] = true;
                $response['message'] = "Other Images not uploaded!";
                print_r(json_encode($response));
                return false;
            } else {
                $result = $fn->validate_other_images($_FILES["other_images"]["tmp_name"][$i], $_FILES["other_images"]["type"][$i]);
                if ($result) {
                    $response['error'] = true;
                    $response['message'] = "other image type must jpg, jpeg, gif, or png!";
                    print_r(json_encode($response));
                    return false;
                }
            }
        }
    }

    $string = '0123456789';
    $file = preg_replace("/\s+/", "_", $_FILES['image']['name']);

    $image = $function->get_random_string($string, 4) . "-" . date("Y-m-d") . "." . $extension;
    $upload = move_uploaded_file($_FILES['image']['tmp_name'], '../../upload/images/' . $image);
    $other_images = '';

    if (isset($_FILES['other_images']) && ($_FILES['other_images']['size'][0] > 0)) {
        $file_data = array();
        $target_path = '../../upload/other_images/';
        $target_path1 = 'upload/other_images/';
        for ($i = 0; $i < count($_FILES["other_images"]["name"]); $i++) {

            $filename = $_FILES["other_images"]["name"][$i];
            $temp = explode('.', $filename);
            $filename = microtime(true) . '-' . rand(100, 999) . '.' . end($temp);
            $file_data[] = $target_path1 . '' . $filename;
            if (!move_uploaded_file($_FILES["other_images"]["tmp_name"][$i], $target_path . '' . $filename)) {
                $response['error'] = true;
                $response['message'] = "Other Images not uploaded!";
                print_r(json_encode($response));
                return false;
            }
        }
        $other_images = json_encode($file_data);
    }
    $upload_image = 'upload/images/' . $image;
    $sql = "INSERT INTO products (name,tax_id,slug,category_id,subcategory_id,image,other_images,description,indicator,manufacturer,made_in,return_status,cancelable_status, till_status) VALUES('$name','$tax_id','$slug','$category_id','$subcategory_id','$upload_image','$other_images','$description','$indicator','$manufacturer','$made_in','$return_status','$cancelable_status','$till_status')";
    $db->sql($sql);
    $product_result = $db->getResult();
    if (!empty($product_result)) {
        $product_result = 0;
    } else {
        $product_result = 1;
    }

    $sql = "SELECT id from products ORDER BY id DESC";
    $db->sql($sql);
    $res_inner = $db->getResult();
    if ($product_result == 1) {
        $product_id = $db->escapeString($res_inner[0]['id']);
        $type = $db->escapeString($fn->xss_clean($_POST['type']));

        $measurement = $db->escapeString($fn->xss_clean($_POST['measurement']));
        $measurement_unit_id = $db->escapeString($fn->xss_clean($_POST['measurement_unit_id']));
        $price = $db->escapeString($fn->xss_clean($_POST['price']));
        $discounted_price = ($_POST['discounted_price'] || !empty($_POST['discounted_price']) || $_POST['discounted_price'] != "") ? $db->escapeString($fn->xss_clean($_POST['discounted_price'])) : 0;
        $serve_for = $db->escapeString($fn->xss_clean($_POST['serve_for']));
        $stock1 = $db->escapeString($fn->xss_clean($_POST['stock']));
        $serve_for1 = ($stock == 0 || $stock <= 0) ? 'Sold Out' : $serve_for;
        $stock_unit_id1 = $db->escapeString($fn->xss_clean($_POST['stock_unit_id']));

        $measurement = explode(",", $measurement);
        $measurement_unit_id = explode(",", $measurement_unit_id);
        $price = explode(",", $price);
        $discounted_price = explode(",", $discounted_price);
        $serve_for = explode(",", $serve_for1);
        $stock = explode(",", $stock1);
        $stock_unit_id = explode(",", $stock_unit_id1);
        if ($_POST['type'] == 'packet') {
            for ($i = 0; $i < count($measurement); $i++) {
                $data = array(
                    'product_id' => $product_id,
                    'type' => $type,
                    'measurement' => $measurement[$i],
                    'measurement_unit_id' => $measurement_unit_id[$i],
                    'price' => $price[$i],
                    'discounted_price' => $discounted_price[$i],
                    'serve_for' => $serve_for[$i],
                    'stock' => $stock[$i],
                    'stock_unit_id' => $stock_unit_id[$i],
                );
                $db->insert('product_variant', $data);
                $db->sql($sql);
            }
        } elseif ($_POST['type'] == "loose") {
            for ($i = 0; $i < count($measurement); $i++) {
                $data = array(
                    'product_id' => $product_id,
                    'type' => $type,
                    'measurement' => $measurement[$i],
                    'measurement_unit_id' => $measurement_unit_id[$i],
                    'price' => $price[$i],
                    'discounted_price' => $discounted_price[$i],
                    'serve_for' => $serve_for1,
                    'stock' => $stock1,
                    'stock_unit_id' => $stock_unit_id1,
                );
                $db->insert('product_variant', $data);
                $db->sql($sql);
            }
        }
        if ($product_result == 1) {
            $response['error'] = false;
            $response['message'] = "Product Added Successfully!";
        } else {
            $response['error'] = true;
            $response['message'] = "Product Not Added!";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Product Added fail!";
    }
    print_r(json_encode($response));
    return false;
}

/* 
14.update_products
    accesskey:90336
    update_products:1
    id:507
    name:potato
    category_id:31
    subcategory_id:115
    serve_for:Available / Sold Out
    description:potatos
    tax_id:4                    // {optional}
    manufacturer:india          // {optional}
    made_in:india               // {optional}
    return_status:0 / 1         // {optional}
    cancelable_status:0 / 1     // {optional}
    till_status:received / processed / shipped           // {optional}
    indicator:0 - none / 1 - veg / 2 - non-veg          // {optional}
    product_variant_id:510,209
    image:FILE          
    other_images[]:FILE

    type:packet
    measurement:500,100
    measurement_unit_id:4,2
    price:75,50
    discounted_price:10,5     // {optional}
    serve_for:Available / Sold Out,Available / Sold Out
    stock:992,987
    stock_unit_id:4,2

    type:loose
    measurement:1,1
    measurement_unit_id:1,5
    price:100,400
    discounted_price:20,15       // {optional}
    serve_for:Available / Sold Out
    stock:997
    stock_unit_id:1
*/

if (isset($_POST['update_products']) && !empty($_POST['update_products']) && ($_POST['update_products'] == 1)) {
    if (empty($_POST['id']) || empty($_POST['name']) || empty($_POST['category_id']) || empty($_POST['subcategory_id']) || empty($_POST['serve_for']) || empty($_POST['description']) || empty($_POST['type'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
        print_r(json_encode($response));
        return false;
    }

    if ($_POST['type']) {
        if (empty($_POST['measurement']) || empty($_POST['measurement_unit_id']) || empty($_POST['price']) || empty($_POST['discounted_price']) || empty($_POST['serve_for']) || empty($_POST['stock']) || empty($_POST['stock_unit_id'])) {
            $response['error'] = true;
            $response['message'] = "Please pass product variants fields!";
            print_r(json_encode($response));
            return false;
        }
    }

    $name = $db->escapeString($fn->xss_clean($_POST['name']));
    if (strpos($name, '-') !== false) {
        $temp = (explode("-", $name)[1]);
    } else {
        $temp = $name;
    }

    $slug = $function->slugify($temp);
    $id = $db->escapeString($fn->xss_clean($_POST['id']));
    $sql = "SELECT slug FROM products where id!=" . $id;
    $db->sql($sql);
    $res = $db->getResult();
    $i = 1;
    foreach ($res as $row) {
        if ($slug == $row['slug']) {
            $slug = $slug . '-' . $i;
            $i++;
        }
    }

    $category_data = array();
    $product_status = "";
    $sql = "select id,name from category order by id asc";
    $db->sql($sql);
    $category_data = $db->getResult();
    $sql = "select * from subcategory";
    $db->sql($sql);
    $subcategory = $db->getResult();
    $sql = "SELECT image, other_images FROM products WHERE id =" . $id;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $previous_menu_image = $row['image'];
        $other_images = $row['other_images'];
    }

    $subcategory_id = (isset($_POST['subcategory_id']) && $_POST['subcategory_id'] != '') ? $db->escapeString($fn->xss_clean($_POST['subcategory_id'])) : 0;
    $category_id = $db->escapeString($fn->xss_clean($_POST['category_id']));
    $serve_for = $db->escapeString($fn->xss_clean($_POST['serve_for']));
    $description = $db->escapeString($fn->xss_clean($_POST['description']));
    $manufacturer = (isset($_POST['manufacturer']) && $_POST['manufacturer'] != '') ? $db->escapeString($fn->xss_clean($_POST['manufacturer'])) : '';
    $made_in = (isset($_POST['made_in']) && $_POST['made_in'] != '') ? $db->escapeString($fn->xss_clean($_POST['made_in'])) : '';
    $indicator = (isset($_POST['indicator']) && $_POST['indicator'] != '') ? $db->escapeString($fn->xss_clean($_POST['indicator'])) : '0';
    $return_status = (isset($_POST['return_status']) && $_POST['return_status'] != '') ? $db->escapeString($fn->xss_clean($_POST['return_status'])) : '0';
    $cancelable_status = (isset($_POST['cancelable_status']) && $_POST['cancelable_status'] != '') ? $db->escapeString($fn->xss_clean($_POST['cancelable_status'])) : '0';
    $till_status = (isset($_POST['till_status']) && $_POST['till_status'] != '') ? $db->escapeString($fn->xss_clean($_POST['till_status'])) : '';

    $tax_id = (isset($_POST['tax_id']) && $_POST['tax_id'] != '') ? $db->escapeString($fn->xss_clean($_POST['tax_id'])) : 0;

    $image = $db->escapeString($fn->xss_clean($_FILES['image']['name']));
    $image_error = $db->escapeString($fn->xss_clean($_FILES['image']['error']));
    $image_type = $db->escapeString($fn->xss_clean($_FILES['image']['type']));

    $error = array();

    $allowedExts = array("gif", "jpeg", "jpg", "png");

    error_reporting(E_ERROR | E_PARSE);
    $extension = end(explode(".", $_FILES["image"]["name"]));

    if (!empty($image)) {
        $result = $fn->validate_image($_FILES["image"]);
        if ($result) {
            $response['error'] = true;
            $response['message'] = "image type must jpg, jpeg, gif, or png!";
            print_r(json_encode($response));
            return false;
        }
    }

    if (isset($_FILES['other_images']) && ($_FILES['other_images']['size'][0] > 0)) {
        $file_data = array();
        $target_path = '../../upload/other_images/';
        $target_path1 = 'upload/other_images/';

        for ($i = 0; $i < count($_FILES["other_images"]["name"]); $i++) {
            if ($_FILES["other_images"]["error"][$i] > 0) {
                $response['error'] = true;
                $response['message'] = "Other Images not uploaded!";
                print_r(json_encode($response));
                return false;
            } else {
                $result = $fn->validate_other_images($_FILES["other_images"]["tmp_name"][$i], $_FILES["other_images"]["type"][$i]);
                if ($result) {
                    $response['error'] = true;
                    $response['message'] = "Other image type must jpg, jpeg, gif, or png!";
                    print_r(json_encode($response));
                    return false;
                }
            }
            $filename = $_FILES["other_images"]["name"][$i];
            $temp = explode('.', $filename);
            $filename = microtime(true) . '-' . rand(100, 999) . '.' . end($temp);
            $file_data[] = 'upload/other_images/' . $filename;

            if (!move_uploaded_file($_FILES["other_images"]["tmp_name"][$i], $target_path . $filename)) {
                $response['error'] = true;
                $response['message'] = "Other Images not uploaded!";
                print_r(json_encode($response));
                return false;
            }
        }
        if (!empty($other_images)) {
            $sql_query = "SELECT other_images FROM products WHERE id =" . $id;
            $db->sql($sql_query);
            $res = $db->getResult();
            if (!empty($res[0]['other_images'])) {
                $other_images = json_decode($res[0]['other_images']);
                foreach ($other_images as $other_image) {
                    unlink('../../' . $other_image);
                }
            }
        }
        $all_images = $db->escapeString(json_encode($file_data));

        if (empty($error)) {
            $sql = "update `products` set `other_images`='" . $all_images . "' where `id`=" . $id;
            $db->sql($sql);
        }
    }

    if (strpos($name, "'") !== false) {
        $name = str_replace("'", "''", "$name");
        if (strpos($description, "'") !== false)
            $description = str_replace("'", "''", "$description");
    }
    if (!empty($image)) {
        $string = '0123456789';
        $file = preg_replace("/\s+/", "_", $_FILES['image']['name']);
        $function = new functions;
        $image = $function->get_random_string($string, 4) . "-" . date("Y-m-d") . "." . $extension;
        $delete = unlink('../../' . "$previous_menu_image");
        $upload = move_uploaded_file($_FILES['image']['tmp_name'], '../../upload/images/' . $image);

        $upload_image = 'upload/images/' . $image;
        $sql_query = "UPDATE products SET name = '$name' ,tax_id = '$tax_id' ,slug = '$slug' , subcategory_id = '$subcategory_id', image = '$upload_image', description = '$description', indicator = '$indicator', manufacturer = '$manufacturer', made_in = '$made_in', return_status = '$return_status', cancelable_status = '$cancelable_status', till_status = '$till_status' WHERE id = $id";
        $db->sql($sql_query);
    } else {
        $sql_query = "UPDATE products SET name = '$name' ,tax_id = '$tax_id' ,slug = '$slug' ,category_id = '$category_id' ,subcategory_id = '$subcategory_id' ,description = '$description', indicator = '$indicator', manufacturer = '$manufacturer', made_in = '$made_in', return_status = '$return_status', cancelable_status = '$cancelable_status', till_status = '$till_status' WHERE id = $id";
        $db->sql($sql_query);
    }
    $res = $db->getResult();

    $type = $db->escapeString($fn->xss_clean($_POST['type']));
    $product_variant_id = $db->escapeString($fn->xss_clean($_POST['product_variant_id']));
    $product_variant_id = explode(",", $product_variant_id);

    $measurement = $db->escapeString($fn->xss_clean($_POST['measurement']));
    $measurement_unit_id = $db->escapeString($fn->xss_clean($_POST['measurement_unit_id']));
    $price = $db->escapeString($fn->xss_clean($_POST['price']));
    $discounted_price = ($_POST['discounted_price'] || !empty($_POST['discounted_price']) || $_POST['discounted_price'] != "") ? $db->escapeString($fn->xss_clean($_POST['discounted_price'])) : 0;
    $serve_for = $db->escapeString($fn->xss_clean($_POST['serve_for']));
    $serve_for1 = ($stock == 0 || $stock <= 0) ? 'Sold Out' : $serve_for;
    $stock1 = $db->escapeString($fn->xss_clean($_POST['stock']));
    $stock_unit_id1 = $db->escapeString($fn->xss_clean($_POST['stock_unit_id']));

    $measurement = explode(",", $measurement);
    $measurement_unit_id = explode(",", $measurement_unit_id);
    $price = explode(",", $price);
    $discounted_price = explode(",", $discounted_price);
    $serve_for = explode(",", $serve_for1);
    $stock = explode(",", $stock1);
    $stock_unit_id = explode(",", $stock_unit_id1);

    for ($i = 0; $i < count($product_variant_id); $i++) {
        if ($_POST['type'] == "packet") {
            $data = array(
                'type' => $type,
                'id' => $product_variant_id[$i],
                'measurement' => $measurement[$i],
                'measurement_unit_id' => $measurement_unit_id[$i],
                'price' => $price[$i],
                'discounted_price' => $discounted_price[$i],
                'stock' => $stock[$i],
                'stock_unit_id' => $stock_unit_id[$i],
                'serve_for' => $serve_for[$i],
            );

            $db->update('product_variant', $data, 'id=' . $data['id']);
            $res = $db->getResult();
        } else if ($_POST['type'] == "loose") {
            $data = array(
                'type' => $type,
                'id' => $product_variant_id[$i],
                'measurement' => $measurement[$i],
                'measurement_unit_id' => $measurement_unit_id[$i],
                'price' => $price[$i],
                'discounted_price' => $discounted_price[$i],
                'stock' => $stock1,
                'stock_unit_id' => $stock_unit_id1,
                'serve_for' => $serve_for1,
            );
            $db->update('product_variant', $data, 'id=' . $data['id']);
            $res = $db->getResult();
        }
    }
    $response['error'] = false;
    $response['message'] = "Product Updated Successfully!";

    print_r(json_encode($response));
    return false;
}

/* 
15.delete_products
    accesskey:90336
    delete_products:1
    product_variants_id:722
*/

if (isset($_POST['delete_products']) && !empty($_POST['delete_products']) && ($_POST['delete_products'] == 1)) {
    if (empty($_POST['product_variants_id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass product variants id fields!";
        print_r(json_encode($response));
        return false;
    }
    $product_variants_id = (isset($_POST['product_variants_id'])) ? $db->escapeString($fn->xss_clean($_POST['product_variants_id'])) : "";

    $product_id = $fn->get_product_id_by_variant_id($product_variants_id);

    $sql_query = "DELETE FROM cart WHERE product_id = $product_id  AND product_variant_id = $product_variants_id";
    $db->sql($sql_query);
    $sql_query = "DELETE FROM product_variant WHERE product_id=" . $product_id;
    $db->sql($sql_query);

    $sql = "SELECT count(id) as total from product_variant WHERE product_id=" . $product_id;
    $db->sql($sql);
    $total = $db->getResult();

    if ($total[0]['total'] == 0) {
        $sql_query = "SELECT image FROM products WHERE id =" . $product_id;
        $db->sql($sql_query);
        $res = $db->getResult();
        unlink('../../' . $res[0]['image']);

        $sql_query = "SELECT other_images FROM products WHERE id =" . $product_id;
        $db->sql($sql_query);
        $res = $db->getResult();
        if (!empty($res[0]['other_images'])) {
            $other_images = json_decode($res[0]['other_images']);
            foreach ($other_images as $other_image) {
                unlink('../../' . $other_image);
            }
        }

        $sql_query = "DELETE FROM products WHERE id =" . $product_id;
        $db->sql($sql_query);

        $sql_query = "DELETE FROM favorites WHERE product_id = " . $product_id;
        $db->sql($sql_query);
    }
    $response['error'] = false;
    $response['message'] = "product delete successfully!";
    print_r(json_encode($response));
    return false;
}


/* 
16.get_products
    accesskey:90336
    get_products:1
    id:468              // {optional}
    category_id:30     // {optional}
    subcategory_id:119  // {optional}
    limit:10            // {optional}
    offset:0            // {optional}
    search:value        // {optional}
    filter:low_stock | out_stock    // {optional}
    sort:new / old / high / low     // {optional}
*/
if (isset($_POST['get_products']) && !empty($_POST['get_products']) && ($_POST['get_products'] == 1)) {
    $where = "";
    $sort = (isset($_POST['sort']) && !empty($_POST['sort'])) ? $db->escapeString($fn->xss_clean($_POST['sort'])) : 'id';
    $filter = (isset($_POST['filter']) && !empty($_POST['filter'])) ? $db->escapeString($fn->xss_clean($_POST['filter'])) : '';
    $subcategory_id = (isset($_POST['category_id']) && is_numeric($_POST['category_id'])) ? $db->escapeString($fn->xss_clean($_POST['category_id'])) : "";
    $user_id = (isset($_POST['user_id']) && is_numeric($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";
    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;

    if ($sort == 'new') {
        $sort = 'ORDER BY date_added DESC';
        $price = 'MIN(pv.price)';
        $price_sort = 'ORDER BY pv.price ASC';
    } elseif ($sort == 'old') {
        $sort = 'ORDER BY date_added ASC';
        $price = 'MIN(pv.price)';
        $price_sort = 'ORDER BY pv.price ASC';
    } elseif ($sort == 'high') {
        $sort = 'ORDER BY price DESC';
        $price = 'MAX(pv.price)';
        $price_sort = 'ORDER BY pv.price DESC';
    } elseif ($sort == 'low') {
        $sort = 'ORDER BY price ASC';
        $price = 'MIN(pv.price)';
        $price_sort = 'ORDER BY pv.price ASC';
    } else {
        $sort = 'ORDER BY p.row_order ASC';
        $price = 'MIN(pv.price)';
        $price_sort = 'ORDER BY pv.price ASC';
    }
    if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
        $category_id = $db->escapeString($fn->xss_clean($_POST['category_id']));
        $where .= ' and category_id = ' . $category_id;
    }
    if (isset($_POST['category_id']) && !empty($_POST['category_id']) && isset($_POST['subcategory_id']) && !empty($_POST['subcategory_id'])) {
        $category_id = $db->escapeString($fn->xss_clean($_POST['category_id']));
        $subcategory_id = $db->escapeString($fn->xss_clean($_POST['subcategory_id']));
        $where .= " and category_id = $category_id and subcategory_id = $subcategory_id";
    }
    if (isset($_POST['subcategory_id']) && !empty($_POST['subcategory_id'])) {
        $subcategory_id = $db->escapeString($fn->xss_clean($_POST['subcategory_id']));
        $where .= ' and subcategory_id = ' . $subcategory_id;
    }
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = $db->escapeString($fn->xss_clean($_POST['id']));
        $where .= ' and p.id = ' . $id;
    }
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $search = $db->escapeString($fn->xss_clean($_POST['search']));
        $where .= " and (p.`id` like '%" . $search . "%' OR p.`name` like '%" . $search . "%' OR p.`slug` like '%" . $search . "%' OR `category_id` like '%" . $search . "%' OR p.`subcategory_id` like '%" . $search . "%' OR p.`manufacturer` like '%" . $search . "%' OR p.`made_in` like '%" . $search . "%' OR p.`return_status` like '%" . $search . "%' OR p.`description` like '%" . $search . "%')";
    }
    if ($filter == "out_stock") {
        $where .= " AND pv.stock <=0 AND pv.serve_for = 'Sold Out'";
    }
    if ($filter == "low_stock") {
        $where .=  " AND pv.stock < $low_stock_limit AND pv.serve_for = 'Available'";
    }

    $sql = "SELECT count(p.id) as total FROM products p join product_variant pv on pv.product_id=p.id WHERE p.`status`=1 $where";
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }
    $sql = "SELECT p.* FROM products p join product_variant pv on pv.product_id=p.id WHERE p.`status`=1 $where $sort LIMIT $offset, $limit";
    $db->sql($sql);
    $res = $db->getResult();
    $product = array();

    $i = 0;
    $sql = "SELECT id FROM cart limit 1";
    $db->sql($sql);
    $res_cart = $db->getResult();
    foreach ($res as $row) {
        $sql = "SELECT pv.*,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE  pv.product_id=" . $row['id'] . " " . $price_sort . " ";
        $db->sql($sql);

        $row['other_images'] = json_decode($row['other_images'], 1);
        $row['other_images'] = (empty($row['other_images'])) ? array() : $row['other_images'];

        for ($j = 0; $j < count($row['other_images']); $j++) {
            $row['other_images'][$j] = DOMAIN_URL . $row['other_images'][$j];
        }
        if ($row['tax_id'] == 0) {
            $row['tax_title'] = "";
            $row['tax_percentage'] = "0";
        } else {
            $t_id = $row['tax_id'];
            $sql_tax = "SELECT * from taxes where id= $t_id";
            $db->sql($sql_tax);
            $res_tax1 = $db->getResult();
            foreach ($res_tax1 as $tax1) {
                $row['tax_title'] = (!empty($tax1['title'])) ? $tax1['title'] : "";
                $row['tax_percentage'] =  (!empty($tax1['percentage'])) ? $tax1['percentage'] : "0";
            }
        }

        $row['image'] = DOMAIN_URL . $row['image'];
        $product[$i] = $row;
        $variants = $db->getResult();
        for ($k = 0; $k < count($variants); $k++) {
            if ($variants[$k]['stock'] <= 0) {
                $variants[$k]['serve_for'] = 'Sold Out';
            }
            if ($variants[$k]['stock'] > 0) {
                $variants[$k]['serve_for'] = 'Available';
            }
            if (!empty($user_id)) {
                $sql = "SELECT qty as cart_count FROM cart where product_variant_id= " . $variants[$k]['id'] . " AND user_id=" . $user_id;
                $db->sql($sql);
                $res = $db->getResult();
                if (!empty($res)) {
                    foreach ($res as $row1) {
                        $variants[$k]['cart_count'] = $row1['cart_count'];
                    }
                } else {
                    $variants[$k]['cart_count'] = "0";
                }
            } else {
                $variants[$k]['cart_count'] = "0";
            }
        }
        $product[$i]['variants'] = $variants;
        $i++;
    }
    if (!empty($product)) {
        $response['error'] = false;
        $response['total'] = $total;
        $response['message'] = "products fetched successfully.";
        $response['data'] = $product;
    } else {
        $response['error'] = true;
        $response['message'] = "products not fetched.";
    }
    print_r(json_encode($response));
}

/* 
17.send_notification
    accesskey:90336
    send_notification:1  
    title:test
    message:testing
    type:default / category / product
    type_id:32
    image:FILE          // {optional}
*/

if (isset($_POST['send_notification']) && !empty($_POST['send_notification']) && ($_POST['send_notification'] == 1)) {
    if (empty($_POST['title']) || empty($_POST['message']) || empty($_POST['type'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
        print_r(json_encode($response));
        return false;
    }
    $title = $db->escapeString($fn->xss_clean($_POST['title']));
    $message = $db->escapeString($fn->xss_clean($_POST['message']));
    $type = $db->escapeString($fn->xss_clean($_POST['type']));
    $id = ($type != 'default') ? $db->escapeString($fn->xss_clean($_POST['type_id'])) : "0";

    $url  = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
    $url .= $_SERVER['SERVER_NAME'];
    $url .= $_SERVER['REQUEST_URI'];
    $server_url = dirname($url) . '/';

    $push = null;
    $include_image = (isset($_FILES['image']));
    if ($include_image) {
        $extensions = explode(".", $_FILES["image"]["name"]);
        $extension = end($extensions);
        $result = $fn->validate_image($_FILES["image"]);
        if ($result) {
            $response['error'] = true;
            $response['message'] = 'Image type must jpg, jpeg, gif, or png!';
            echo json_encode($response);
            return false;
        }
        $target_path = 'upload/notifications/';
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], '../../upload/notifications/' . "" . $filename)) {
            $response['error'] = true;
            $response['message'] = 'Image is not uploaded';
            echo json_encode($response);
            return false;
        }
        $sql = "INSERT INTO `notifications`(`title`, `message`,  `type`, `type_id`, `image`) VALUES 
			('" . $title . "','" . $message . "','" . $type . "','" . $id . "','" . $full_path . "')";
    } else {
        $sql = "INSERT INTO `notifications`(`title`, `message`, `type`, `type_id`) VALUES 
        ('" . $title . "','" . $message . "','" . $type . "','" . $id . "')";
    }
    $db->sql($sql);
    $db->getResult();

    if ($include_image) {
        $push = new Push(
            $db->escapeString($fn->xss_clean($_POST['title'])),
            $db->escapeString($fn->xss_clean($_POST['message'])),
            $server_url . '' . $full_path,
            $type,
            $id
        );
    } else {
        $push = new Push(
            $db->escapeString($fn->xss_clean($_POST['title'])),
            $db->escapeString($fn->xss_clean($_POST['message'])),
            null,
            $type,
            $id
        );
    }
    $mPushNotification = $push->getPush();

    $devicetoken = $function->getAllTokens();
    $devicetoken1 = $function->getAllTokens("devices");
    $final_tokens = array_merge($devicetoken, $devicetoken1);
    $f_tokens = array_unique($final_tokens);
    $devicetoken_chunks = array_chunk($f_tokens, 1000);

    foreach ($devicetoken_chunks as $devicetokens) {
        $firebase = new Firebase();
        $firebase->send($devicetokens, $mPushNotification);
    }
    $response['error'] = false;
    $response["message"] = "Notification Sent Successfully!";
    print_r(json_encode($response));
}

/* 
18.delete_notification
    accesskey:90336
    delete_notification:1    
    id:915
*/

if (isset($_POST['delete_notification']) && !empty($_POST['delete_notification']) && ($_POST['delete_notification'] == 1)) {
    if (empty($_POST['id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass id fields!";
        print_r(json_encode($response));
        return false;
    }

    $id = $db->escapeString($fn->xss_clean($_POST['id']));

    $sql_query = "SELECT image FROM notifications WHERE id =" . $id;
    $db->sql($sql_query);
    $res = $db->getResult();

    if ($res[0]['image']) {
        unlink('../../' . $res[0]['image']);
    }

    $sql_query = "DELETE FROM notifications WHERE id=" . $id;

    if ($db->sql($sql_query)) {
        $response['error'] = false;
        $response['message'] = "Notification delete successfully!";
    } else {
        $response['error'] = false;
        $response['message'] = "Some Error Occrred! please try again.";
    }
    print_r(json_encode($response));
    return false;
}

/* 
19.get_notification
    accesskey:90336
    get_notification:1    
*/

if (isset($_POST['get_notification']) && !empty($_POST['get_notification']) && ($_POST['get_notification'] == 1)) {
    $where = '';
    $offset = (isset($_POST['offset']) && !empty(trim($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString(trim($fn->xss_clean($_POST['offset']))) : 0;
    $limit = (isset($_POST['limit']) && !empty(trim($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString(trim($fn->xss_clean($_POST['limit']))) : 10;
    $sort = (isset($_POST['sort']) && !empty(trim($_POST['sort']))) ? $db->escapeString(trim($fn->xss_clean($_POST['sort']))) : 'id';
    $order = (isset($_POST['order']) && !empty(trim($_POST['order']))) ? $db->escapeString(trim($fn->xss_clean($_POST['order']))) : 'DESC';

    $sql = "SELECT count(id) as total FROM notifications";
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }
    $sql = "SELECT * FROM notifications ORDER BY $sort $order LIMIT $offset, $limit";
    $db->sql($sql);
    $row = $db->getResult();
    foreach ($row as $res) {
        $temp['id'] = $res['id'];
        $temp['title'] = $res['title'];
        $temp['message'] = $res['message'];
        $temp['type'] = $res['type'];
        $temp['type_id'] = $res['type_id'];
        $temp['date_sent'] = $res['date_sent'];
        $temp['image'] = !empty($res['image']) ? DOMAIN_URL . $res['image'] : "";
        $result[] = $temp;
    }

    if (!empty($result)) {
        $response['error'] = false;
        $response['total'] = $total;
        $response['message'] = "Notification fetched successfully.";
        $response['data'] = $result;
    } else {
        $response['error'] = true;
        $response['message'] = "Notification not fetched.";
    }
    print_r(json_encode($response));
    return false;
}
