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
if (!$time_zone) {
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
20.get_orders
21. get_customers
22. get_financial_statistics
23. login
24. update_admin_fcm_id
25. get_privacy_and_terms
26. update_order_status
27. get_permissions
28. update_order_item_status
29. delivery_boy_fund_transfers
30. delivery_boy_transfer_fund
31. get_all_data

-------------------------------------------
-------------------------------------------
*/

// if (!verify_token()) {
//     return false;
// }

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

if (isset($_POST['add_category']) && !empty($_POST['add_category']) && $_POST['add_category'] == 1) {
    if (empty($_POST['category_name']) || empty($_POST['category_subtitle'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
        print_r(json_encode($response));
        return false;
    }
    $category_name = $db->escapeString($fn->xss_clean_array($_POST['category_name']));
    $category_subtitle = $db->escapeString($fn->xss_clean_array($_POST['category_subtitle']));

    $target_path = '../../upload/images/';
    $target_path1 = '../../upload/web-category-image/';
    if (!empty($category_name) && !empty($category_subtitle)) {
        if (isset($_FILES['upload_image'])) {
            if (!is_dir($target_path)) {
                mkdir($target_path, 0777, true);
            }
            $extension = pathinfo($_FILES["upload_image"]["name"])['extension'];

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
        }
        if (isset($_FILES['web_image'])) {
            if (!is_dir($target_path1)) {
                mkdir($target_path1, 0777, true);
            }
            $extension1 = pathinfo($_FILES["web_image"]["name"])['extension'];

            $result1 = $fn->validate_image($_FILES["web_image"]);
            if ($result1) {
                $response['error'] = true;
                $response['message'] = "web image type must jpg, jpeg, gif, or png!";
                print_r(json_encode($response));
                return false;
            }

            $string1 = '0123456789';
            $file1 = preg_replace("/\s+/", "_", $_FILES['web_image']['name']);
            $web_image = $function->get_random_string($string1, 4) . "-" . date("Y-m-d") . "." . $extension1;

            $upload1 = move_uploaded_file($_FILES['web_image']['tmp_name'], '../../upload/web-category-image/' . $web_image);
        }
        $upload_image = !empty($_FILES['upload_image']["name"]) ? 'upload/images/' . $menu_image : "";
        $web_images = !empty($_FILES["web_image"]["name"]) ? 'upload/web-category-image/' . $web_image : "";

        $sql_query = "INSERT INTO category (name,subtitle, image,status,web_image)VALUES('$category_name', '$category_subtitle', '$upload_image','1','$web_images')";
        if ($db->sql($sql_query)) {
            $sql = "SELECT * FROM category ORDER BY id DESC LIMIT 0,1 ";
            $db->sql($sql);
            $res = $db->getResult();
            $res[0]['image'] = DOMAIN_URL . $res[0]['image'];
            $res[0]['web_image'] = !empty($res[0]['web_image']) ? DOMAIN_URL . $res[0]['web_image'] : "";

            $response['error'] = false;
            $response['message'] = "Category Added Successfully!";
            $response['data'] = $res;
        } else {
            $response['error'] = true;
            $response['message'] = "Some Error Occrred! please try again.";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
    }
    print_r(json_encode($response));
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

if (isset($_POST['update_category']) && !empty($_POST['update_category']) && $_POST['update_category'] == 1) {
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
        if (isset($_FILES['upload_image'])) {
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
                $extension = pathinfo($_FILES["upload_image"]["name"])['extension'];

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
                $sql = "UPDATE category SET `image` = '" . $upload_image . "' where `id`=" . $id;
                $db->sql($sql);
            }
            if (isset($_FILES['web_image'])) {
                if (!empty($res[0]['web_image']) || $res[0]['web_image'] != '') {
                    $old_image1 = $res[0]['web_image'];
                    if (!empty($old_image1)) {
                        unlink('../../' . $old_image1);
                    }
                }
                $target_path1 = '../../upload/web-category-image/';
                if ($_FILES['web_image']['error'] == 0) {
                    if (!is_dir($target_path1)) {
                        mkdir($target_path1, 0777, true);
                    }
                    $extension1 = pathinfo($_FILES["web_image"]["name"])['extension'];

                    $result1 = $fn->validate_image($_FILES["web_image"]);
                    if ($result1) {
                        $response['error'] = true;
                        $response['message'] = "Web image type must jpg, jpeg, gif, or png!";
                        print_r(json_encode($response));
                        return false;
                    }

                    $string1 = '0123456789';
                    $file1 = preg_replace("/\s+/", "_", $_FILES['web_image']['name']);
                    $web_image = $function->get_random_string($string1, 4) . "-" . date("Y-m-d") . "." . $extension1;

                    $upload1 = move_uploaded_file($_FILES['web_image']['tmp_name'], '../../upload/web-category-image/' . $web_image);

                    $upload_image1 = 'upload/web-category-image/' . $web_image;
                    $sql1 = "UPDATE category SET  `web_image` = '" . $upload_image1 . "' where `id`=" . $id;
                    $db->sql($sql1);
                }
            }
        }
        $sql_query = "UPDATE category SET `name` =  '" . $category_name . "',`subtitle` = '" . $category_subtitle . "',`status` = '1' where `id`=" . $id;
        if ($db->sql($sql_query)) {
            $sql = "SELECT * FROM category  where id = $id ";
            $db->sql($sql);
            $res = $db->getResult();
            $res[0]['image'] = DOMAIN_URL . $res[0]['image'];
            $res[0]['web_image'] = !empty($res[0]['web_image']) ? DOMAIN_URL . $res[0]['web_image'] : "";

            $response['error'] = false;
            $response['message'] = "Category Updated Successfully!";
            $response['data'] = $res;
        } else {
            $response['error'] = true;
            $response['message'] = "Some Error Occrred! please try again.";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Id is not found.";
    }
    print_r(json_encode($response));
    return false;
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
        $response['message'] = "Please pass id field!";
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
    $sql_query = "DELETE FROM `category` WHERE id=" . $id;
    if ($db->sql($sql_query)) {
        $response['error'] = false;
        $response['message'] = "Category Deleted Successfully!";
    } else {
        $response['error'] = true;
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

    if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
        $where = " where id = '$category_id' ";
    }

    $sql = "SELECT count(id) as total FROM category $where";
    $db->sql($sql);
    $res1 = $db->getResult();
    $total = $res1[0]['total'];

    $sql_query = "SELECT * FROM category $where ORDER BY id ASC";
    $db->sql($sql_query);
    $res = $db->getResult();
    $product = array();

    $i = 0;
    foreach ($res as $row) {
        $sql = "SELECT * FROM subcategory WHERE category_id = '" . $row['id'] . "' ORDER BY id DESC";
        $db->sql($sql);

        $row['image'] = !empty($row['image']) ? DOMAIN_URL . $row['image'] : "";
        $row['web_image'] = !empty($row['web_image']) ? DOMAIN_URL . $row['web_image'] : "";
        $product[$i] = $row;
        $variants = $db->getResult();

        for ($k = 0; $k < count($variants); $k++) {
            $variants[$k]['image'] = !empty($variants[$k]['image']) ? DOMAIN_URL . $variants[$k]['image'] : "";
        }

        $product[$i]['childs'] = $variants;
        $i++;
    }
    if (!empty($product)) {
        $response['error'] = false;
        $response['message'] = "Categories fetched successfully";
        $response['total'] = $total;
        $response['data'] = $product;
    } else {
        $response['error'] = true;
        $response['message'] = "Categories not fetched";
    }
    print_r(json_encode($response));
    return false;
}

/*
5.add_subcategory
    accesskey:90336
    add_subcategory:1
    subcategory_name:baverages
    subcategory_subtitle:Cold Drinks, Soft Drinks, Sodas
    category_id:46
    upload_image:FILE
*/

if ((isset($_POST['add_subcategory'])) && ($_POST['add_subcategory'] == 1)) {
    if (empty($_POST['subcategory_name']) || empty($_POST['subcategory_subtitle']) || empty($_POST['category_id']) || empty($_FILES['upload_image'])) {
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

    $subcategory_subtitle = $db->escapeString($fn->xss_clean_array($_POST['subcategory_subtitle']));
    $category_id = $db->escapeString($fn->xss_clean_array($_POST['category_id']));

    $target_path = '../../upload/images/';

    if ($_FILES['upload_image']['error'] == 0) {
        if (!is_dir($target_path)) {
            mkdir($target_path, 0777, true);
        }
        $extension = pathinfo($_FILES["upload_image"]["name"])['extension'];

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

        $sql_query = "INSERT INTO subcategory (category_id, name, slug, subtitle, image)VALUES('$category_id', '$subcategory_name', '$slug', '$subcategory_subtitle', '$upload_image')";
        if ($db->sql($sql_query)) {
            $sql = "SELECT s.*,c.name as category_name FROM subcategory s LEFT JOIN category c ON c.id=s.category_id ORDER BY id DESC LIMIT 0,1 ";
            $db->sql($sql);
            $res = $db->getResult();
            $res[0]['image'] = DOMAIN_URL . $res[0]['image'];

            $response['error'] = false;
            $response['message'] = "Subcategory Added Successfully!";
            $response['data'] = $res;
        } else {
            $response['error'] = true;
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
    subcategory_subtitle:Cold Drinks, Soft Drinks, Sodas
    category_id: 46
    upload_image:FILE
*/

if ((isset($_POST['update_subcategory'])) && ($_POST['update_subcategory'] == 1)) {
    if (empty($_POST['id']) || empty($_POST['subcategory_name']) || empty($_POST['subcategory_subtitle']) || empty($_POST['category_id'])) {
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

    $subcategory_subtitle = $db->escapeString($fn->xss_clean_array($_POST['subcategory_subtitle']));
    $category_id = $db->escapeString($fn->xss_clean_array($_POST['category_id']));

    $sql = "SELECT id,image FROM `subcategory` where id=$id";
    $db->sql($sql);
    $res1 = $db->getResult();

    $target_path = '../../upload/images/';
    if (isset($_FILES['upload_image'])) {
        if ($_FILES['upload_image']['error'] == 0) {
            if (!empty($res1[0]['image'])) {
                $old_image = $res1[0]['image'];
                if (!empty($old_image)) {
                    unlink('../../' . $old_image);
                }
            }
            if (!is_dir($target_path)) {
                mkdir($target_path, 0777, true);
            }
            $extension = pathinfo($_FILES["upload_image"]["name"])['extension'];

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
            $sql1 = "UPDATE subcategory SET  `image` = '" . $upload_image . "' where `id`=" . $id;
            $db->sql($sql1);
        }
    }
    $sql_query = "UPDATE subcategory SET `category_id` =  '" . $category_id . "',`name` = '" . $subcategory_name . "', `slug` = '" . $slug . "', `subtitle` = '" . $subcategory_subtitle . "' where `id`=" . $id;
    if ($db->sql($sql_query)) {
        $sql = "SELECT s.*,c.name as category_name FROM subcategory s LEFT JOIN category c ON c.id=s.category_id where s.id = $id ";
        $db->sql($sql);
        $res = $db->getResult();
        $res[0]['image'] = DOMAIN_URL . $res[0]['image'];

        $response['error'] = false;
        $response['message'] = "Subcategory updated Successfully!";
        $response['data'] = $res;
    } else {
        $response['error'] = true;
        $response['message'] = "Some Error Occrred! please try again.";
    }
    print_r(json_encode($response));
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
        $response['error'] = false;
        $response['message'] = "subcategory Deleted Successfully!";
    } else {
        $response['error'] = true;
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

    if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
        $where = " where s.category_id = '$category_id' ";
    }

    $sql = "SELECT count(s.id) as total FROM subcategory s $where";
    $db->sql($sql);
    $res1 = $db->getResult();
    $total = $res1[0]['total'];

    $sql_query = "SELECT s.*,c.name as category_name FROM subcategory s LEFT JOIN category c ON c.id = s.category_id $where ORDER BY s.id ASC";
    $db->sql($sql_query);
    $res = $db->getResult();
    for ($i = 0; $i < count($res); $i++) {
        $res[$i]['image'] = (!empty($res[$i]['image'])) ? DOMAIN_URL . '' . $res[$i]['image'] : '';
    }

    if (!empty($res)) {
        $response['error'] = false;
        $response['message'] = "Subcategory fetched successfully";
        $response['total'] = $total;
        $response['data'] = $res;
    } else {
        $response['error'] = true;
        $response['message'] = "Subcategory not fetched";
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

    if (empty($_POST['name']) || empty($_POST['mobile']) || empty($_POST['address']) || empty($_POST['bonus']) || empty($_POST['dob']) || empty($_POST['bank_name']) ||  empty($_POST['account_number']) || empty($_POST['account_name']) || empty($_POST['ifsc_code']) || empty($_POST['password']) || empty($_FILES['driving_license']) || empty($_FILES['national_identity_card'])) {
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
        $sql = "SELECT * FROM delivery_boys ORDER BY id DESC LIMIT 0,1 ";
        $db->sql($sql);
        $res = $db->getResult();
        $res[0]['order_note'] = !empty($res[0]['order_note']) ? $res[0]['order_note'] : "";
        $res[0]['fcm_id'] = !empty($res[0]['fcm_id']) ? $res[0]['fcm_id'] : "";
        $res[0]['driving_license'] = DOMAIN_URL . 'upload/delivery-boy/' . $res[0]['driving_license'];
        $res[0]['national_identity_card'] =  DOMAIN_URL . 'upload/delivery-boy/' . $res[0]['national_identity_card'];

        $response['error'] = false;
        $response['message'] = "Delivery Boy Added Successfully!";
        $response['data'] = $res;
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
    status:1
    password:asd124              
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
            $sql = "SELECT * FROM delivery_boys  where id = $id ";
            $db->sql($sql);
            $res = $db->getResult();
            $res[0]['order_note'] = !empty($res[0]['order_note']) ? $res[0]['order_note'] : "";
            $res[0]['fcm_id'] = !empty($res[0]['fcm_id']) ? $res[0]['fcm_id'] : "";
            $res[0]['driving_license'] = DOMAIN_URL . 'upload/delivery-boy/' . $res[0]['driving_license'];
            $res[0]['national_identity_card'] =  DOMAIN_URL . 'upload/delivery-boy/' . $res[0]['national_identity_card'];

            $response['error'] = false;
            $response['message'] = "Information Updated Successfully!";
            $response['data'] = $res;
        } else {
            $response['error'] = true;
            $response['message'] = "Some Error Occurred! Please Try Again!";
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
    description:potatos
    tax_id:4                    // {optional}
    manufacturer:india          // {optional}
    made_in:india               // {optional}
    return_status:0 / 1         // {optional}
    cancelable_status:0 / 1     // {optional}
    till_status: received / processed / shipped    // {optional}
    indicator:	 1 - veg / 2 - non-veg             // {optional}
    size_chart:FILE         // {optional}     
    shipping_delivery:Potatos   // {optional}  
    image:FILE          
    other_images[]:FILE

    type:packet
    measurement:500,400
    measurement_unit_id:4,1
    price:175,145
    discounted_price:60,30       // {optional}
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

    if (empty($_POST['name']) || empty($_POST['category_id']) || empty($_POST['subcategory_id']) || empty($_POST['serve_for']) || empty($_POST['description']) || empty($_POST['type']) || empty($_FILES['image'])) {
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
    $shipping_delivery = (isset($_POST['shipping_delivery']) && $_POST['shipping_delivery'] != '') ? $db->escapeString($fn->xss_clean($_POST['shipping_delivery'])) : '';

    $image = $db->escapeString($fn->xss_clean($_FILES['image']['name']));
    $image_error = $db->escapeString($fn->xss_clean($_FILES['image']['error']));
    $image_type = $db->escapeString($fn->xss_clean($_FILES['image']['type']));

    $allowedExts = array("gif", "jpeg", "jpg", "png");

    error_reporting(E_ERROR | E_PARSE);
    $extension = pathinfo($_FILES["image"]["name"])['extension'];

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

    if (isset($_FILES["other_images"]) && $_FILES["other_images"]["error"] == 0) {
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

    if (isset($_FILES['size_chart'])) {
        if (!empty($res[0]['size_chart']) || $res[0]['size_chart'] != '') {
            $old_image1 = $res[0]['size_chart'];
            if (!empty($old_image1)) {
                unlink('../../' . $old_image1);
            }
        }
        $target_path1 = '../../upload/images/';
        if ($_FILES['size_chart']['error'] == 0) {
            if (!is_dir($target_path1)) {
                mkdir($target_path1, 0777, true);
            }
            $extension1 = pathinfo($_FILES["size_chart"]["name"])['extension'];

            $result1 = $fn->validate_image($_FILES["size_chart"]);
            if ($result1) {
                $response['error'] = true;
                $response['message'] = "Size chart image type must jpg, jpeg, gif, or png!";
                print_r(json_encode($response));
                return false;
            }

            $string1 = '0123456789';
            $file1 = preg_replace("/\s+/", "_", $_FILES['size_chart']['name']);
            $size_chart = $function->get_random_string($string1, 4) . "-" . date("Y-m-d") . "." . $extension1;

            $upload1 = move_uploaded_file($_FILES['size_chart']['tmp_name'], '../../upload/images/' . $size_chart);
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
        $other_images = !empty($file_data) ? json_encode($file_data) : "";
    }
    $upload_image1 = !empty($_FILES["size_chart"]) ? 'upload/images/' . $size_chart : "";
    $upload_image = 'upload/images/' . $image;
    $sql = "INSERT INTO products (size_chart,name,tax_id,slug,category_id,subcategory_id,image,other_images,description,shipping_delivery,indicator,manufacturer,made_in,return_status,cancelable_status, till_status) VALUES('$upload_image1','$name','$tax_id','$slug','$category_id','$subcategory_id','$upload_image','$other_images','$description','$shipping_delivery','$indicator','$manufacturer','$made_in','$return_status','$cancelable_status','$till_status')";
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
            }
        }

        $sql = "SELECT * from products ORDER BY id DESC LIMIT 0,1";
        $db->sql($sql);
        $res_inner = $db->getResult();
        $product = array();
        $i = 0;
        $a = $fn->get_product_id_by_variant_id($res_inner[0]);

        $sql1 = "SELECT * from products WHERE id = $a";
        $db->sql($sql1);
        $res_inner1 = $db->getResult();
        foreach ($res_inner1 as $row) {
            $sql = "SELECT *,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE pv.product_id=" . $row['id'] . " ";
            $db->sql($sql);
            $variants = $db->getResult();

            $row['other_images'] = json_decode($row['other_images'], 1);
            $row['other_images'] = (empty($row['other_images'])) ? array() : $row['other_images'];
            $row['size_chart'] = (empty($row['size_chart'])) ? '' : DOMAIN_URL . $row['size_chart'];
            $row['image'] = (empty($row['image'])) ? '' : DOMAIN_URL . $row['image'];
            for ($j = 0; $j < count($row['other_images']); $j++) {
                $row['other_images'][$j] = !empty(DOMAIN_URL . $row['other_images'][$j]) ? DOMAIN_URL . $row['other_images'][$j] : "";
            }
            $row['shipping_delivery'] = (!empty($row['shipping_delivery'])) ? $row['shipping_delivery'] : "";

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

            $product[$i] = $row;

            for ($k = 0; $k < count($variants); $k++) {
                if ($variants[$k]['stock'] <= 0 && $variants[$k]['serve_for'] = 'Sold Out') {
                    $variants[$k]['serve_for'] = 'Sold Out';
                } else {
                    $variants[$k]['serve_for'] = 'Available';
                }
            }

            $product[$i]['variants'] = $variants;
            $i++;
        }
        if ($product_result == 1 && !empty($product)) {
            $response['error'] = false;
            $response['message'] = "Product Added Successfully!";
            $response['data'] = $product;
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
    description:potatos
    product_variant_id:510,209
    tax_id:4                    // {optional}
    manufacturer:india          // {optional}
    made_in:india               // {optional}
    return_status:0 / 1         // {optional}
    cancelable_status:0 / 1     // {optional}
    till_status:received / processed / shipped           // {optional}
    indicator: 1 - veg / 2 - non-veg          // {optional}
    shipping_delivery:Potatos   // {optional} 
    size_chart:FILE             // {optional}     
    image:FILE                  // {optional} 
    other_images[]:FILE         // {optional} 

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
    $shipping_delivery = (isset($_POST['shipping_delivery']) && $_POST['shipping_delivery'] != '') ? $db->escapeString($fn->xss_clean($_POST['shipping_delivery'])) : '';

    $tax_id = (isset($_POST['tax_id']) && $_POST['tax_id'] != '') ? $db->escapeString($fn->xss_clean($_POST['tax_id'])) : 0;

    $error = array();

    $allowedExts = array("gif", "jpeg", "jpg", "png");

    error_reporting(E_ERROR | E_PARSE);
    if (!empty($_FILES['image'])) {
        $image = $db->escapeString($fn->xss_clean($_FILES['image']['name']));
        $image_error = $db->escapeString($fn->xss_clean($_FILES['image']['error']));
        $image_type = $db->escapeString($fn->xss_clean($_FILES['image']['type']));

        $extension = pathinfo($_FILES["image"]["name"])['extension'];

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
        if (isset($_FILES['size_chart'])) {
            if (!empty($res[0]['size_chart']) || $res[0]['size_chart'] != '') {
                $old_image1 = $res[0]['size_chart'];
                if (!empty($old_image1)) {
                    unlink('../../' . $old_image1);
                }
            }
            $target_path1 = '../../upload/images/';
            if ($_FILES['size_chart']['error'] == 0) {
                if (!is_dir($target_path1)) {
                    mkdir($target_path1, 0777, true);
                }
                $extension1 = pathinfo($_FILES["size_chart"]["name"])['extension'];

                $result1 = $fn->validate_image($_FILES["size_chart"]);
                if ($result1) {
                    $response['error'] = true;
                    $response['message'] = "Size chart image type must jpg, jpeg, gif, or png!";
                    print_r(json_encode($response));
                    return false;
                }

                $string1 = '0123456789';
                $file1 = preg_replace("/\s+/", "_", $_FILES['size_chart']['name']);
                $size_chart = $function->get_random_string($string1, 4) . "-" . date("Y-m-d") . "." . $extension1;

                $upload1 = move_uploaded_file($_FILES['size_chart']['tmp_name'], '../../upload/images/' . $size_chart);
                $upload_image1 =  'upload/images/' . $size_chart;
                $sql_query = "UPDATE products SET  size_chart = '$upload_image1' WHERE id = $id";
                $db->sql($sql_query);
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
    if (!empty($_FILES['image'])) {
        $string = '0123456789';
        $file = preg_replace("/\s+/", "_", $_FILES['image']['name']);
        $function = new functions;
        $image = $function->get_random_string($string, 4) . "-" . date("Y-m-d") . "." . $extension;
        $delete = unlink('../../' . "$previous_menu_image");
        $upload = move_uploaded_file($_FILES['image']['tmp_name'], '../../upload/images/' . $image);

        $upload_image = 'upload/images/' . $image;
        $sql_query = "UPDATE products SET name = '$name' ,tax_id = '$tax_id' ,slug = '$slug' , subcategory_id = '$subcategory_id', image = '$upload_image', description = '$description', indicator = '$indicator', manufacturer = '$manufacturer', made_in = '$made_in', return_status = '$return_status', cancelable_status = '$cancelable_status', till_status = '$till_status',shipping_delivery = '$shipping_delivery' WHERE id = $id";
        $db->sql($sql_query);
    } else {
        $sql_query = "UPDATE products SET name = '$name' ,tax_id = '$tax_id' ,slug = '$slug' ,category_id = '$category_id' ,subcategory_id = '$subcategory_id' ,description = '$description', indicator = '$indicator', manufacturer = '$manufacturer', made_in = '$made_in', return_status = '$return_status', cancelable_status = '$cancelable_status', till_status = '$till_status',shipping_delivery = '$shipping_delivery' WHERE id = $id";
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
    $sql1 = "SELECT * from products WHERE id = $id";
    $db->sql($sql1);
    $res_inner1 = $db->getResult();
    $product = array();
    $i = 0;
    foreach ($res_inner1 as $row) {
        $sql = "SELECT *,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE pv.product_id=" . $row['id'] . " ";
        $db->sql($sql);
        $variants = $db->getResult();

        $row['other_images'] = json_decode($row['other_images'], 1);
        $row['other_images'] = (empty($row['other_images'])) ? array() : $row['other_images'];
        $row['size_chart'] = (empty($row['size_chart'])) ? '' : DOMAIN_URL . $row['size_chart'];
        $row['image'] = (empty($row['image'])) ? '' : DOMAIN_URL . $row['image'];
        for ($j = 0; $j < count($row['other_images']); $j++) {
            $row['other_images'][$j] = !empty(DOMAIN_URL . $row['other_images'][$j]) ? DOMAIN_URL . $row['other_images'][$j] : "";
        }
        $row['shipping_delivery'] = (!empty($row['shipping_delivery'])) ? $row['shipping_delivery'] : "";

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

        $product[$i] = $row;

        for ($k = 0; $k < count($variants); $k++) {
            if ($variants[$k]['stock'] <= 0 && $variants[$k]['serve_for'] = 'Sold Out') {
                $variants[$k]['serve_for'] = 'Sold Out';
            } else {
                $variants[$k]['serve_for'] = 'Available';
            }
        }

        $product[$i]['variants'] = $variants;
        $i++;
    }
    if (!empty($product)) {
        $response['error'] = false;
        $response['message'] = "Product Updated Successfully!";
        $response['data'] = $product;
    } else {
        $response['error'] = true;
        $response['message'] = "Product Not Added!";
    }
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
        $sql = "SELECT *,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE pv.product_id=" . $row['id'] . " ";
        $db->sql($sql);
        $variants = $db->getResult();

        $row['other_images'] = json_decode($row['other_images'], 1);
        $row['other_images'] = (empty($row['other_images'])) ? array() : $row['other_images'];
        $row['size_chart'] = (empty($row['size_chart'])) ? '' : DOMAIN_URL . $row['size_chart'];
        $row['image'] = (empty($row['image'])) ? '' : DOMAIN_URL . $row['image'];
        for ($j = 0; $j < count($row['other_images']); $j++) {
            $row['other_images'][$j] = !empty(DOMAIN_URL . $row['other_images'][$j]) ? DOMAIN_URL . $row['other_images'][$j] : "";
        }
        $row['shipping_delivery'] = (!empty($row['shipping_delivery'])) ? $row['shipping_delivery'] : "";

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

        $product[$i] = $row;

        for ($k = 0; $k < count($variants); $k++) {
            if ($variants[$k]['stock'] <= 0 && $variants[$k]['serve_for'] = 'Sold Out') {
                $variants[$k]['serve_for'] = 'Sold Out';
            } else {
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
        $response['message'] = "products fetched successfully.";
        $response['total'] = $total;
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
        $extension = pathinfo($_FILES["image"]["name"])['extension'];
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
        $response['error'] = true;
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


/* 
20.get_orders
    accesskey:90336
    get_orders:1
    order_id:12             // {optional}
    start_date:2020-10-29   // {optional} {YYYY-mm-dd}
    end_date:2020-10-29     // {optional} {YYYY-mm-dd}
    limit:10                // {optional}
    offset:0                // {optional}
    sort:id                 // {optional}
    order:ASC/DESC          // {optional}
    search:value            // {optional}
    filter_order:received | processed | shipped | delivered | cancelled | returned | awaiting_payment // {optional}
*/
if (isset($_POST['get_orders']) && !empty($_POST['get_orders'])) {
    $where = '';
    $offset = (isset($_POST['offset']) && !empty(trim($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString(trim($fn->xss_clean($_POST['offset']))) : 0;
    $limit = (isset($_POST['limit']) && !empty(trim($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString(trim($fn->xss_clean($_POST['limit']))) : 10;
    $sort = (isset($_POST['sort']) && !empty(trim($_POST['sort']))) ? $db->escapeString(trim($fn->xss_clean($_POST['sort']))) : 'id';
    $order = (isset($_POST['order']) && !empty(trim($_POST['order']))) ? $db->escapeString(trim($fn->xss_clean($_POST['order']))) : 'DESC';
    if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
        $start_date = $db->escapeString($fn->xss_clean($_POST['start_date']));
        $end_date = $db->escapeString($fn->xss_clean($_POST['end_date']));
        $where .= " where DATE(date_added)>=DATE('" . $start_date . "') AND DATE(date_added)<=DATE('" . $end_date . "')";
    }
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $search = $db->escapeString($fn->xss_clean($_POST['search']));
        if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
            $where .= " AND (name like '%" . $search . "%' OR o.id like '%" . $search . "%' OR o.mobile like '%" . $search . "%' OR address like '%" . $search . "%' OR `payment_method` like '%" . $search . "%' OR `delivery_charge` like '%" . $search . "%' OR `delivery_time` like '%" . $search . "%' OR o.`status` like '%" . $search . "%' OR `date_added` like '%" . $search . "%')";
        } else {
            $where .= " where (name like '%" . $search . "%' OR o.id like '%" . $search . "%' OR o.mobile like '%" . $search . "%' OR address like '%" . $search . "%' OR `payment_method` like '%" . $search . "%' OR `delivery_charge` like '%" . $search . "%' OR `delivery_time` like '%" . $search . "%' OR o.`status` like '%" . $search . "%' OR `date_added` like '%" . $search . "%')";
        }
    }
    if (isset($_POST['filter_order']) && $_POST['filter_order'] != '') {
        $filter_order = $db->escapeString($fn->xss_clean($_POST['filter_order']));
        if (isset($_POST['search']) && $_POST['search'] != '') {
            $where .= " and `active_status`='" . $filter_order . "'";
        } elseif (isset($_POST['start_date']) && $_POST['start_date'] != '') {
            $where .= " and `active_status`='" . $filter_order . "'";
        } else {
            $where .= " where `active_status`='" . $filter_order . "'";
        }
    }
    if (isset($_POST['order_id']) && !empty($_POST['order_id']) && is_numeric($_POST['order_id'])) {
        $order_id = $db->escapeString($fn->xss_clean($_POST['order_id']));
        if ($where != "") {
            $where .= " and o.`id`=$order_id";
        } else {
            $where .= " where o.`id`=$order_id";
        }
    }
    $item_discount = 0;
    $orders_join = " JOIN users u ON u.id=o.user_id ";
    $sql = "SELECT COUNT(o.id) as total FROM `orders` o " . $orders_join . " " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res)) {
        foreach ($res as $row) {
            $total = $row['total'];
        }
        $sql = "select o.*,u.name as name,u.country_code as country_code FROM orders o " . $orders_join . " " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
        $db->sql($sql);
        $res = $db->getResult();
        if (!empty($res)) {
            for ($i = 0; $i < count($res); $i++) {
                $sql = "select oi.*,p.name as name, v.measurement,p.image, (SELECT short_code FROM unit un where un.id=v.measurement_unit_id)as mesurement_unit_name from `order_items` oi 
                join product_variant v on oi.product_variant_id=v.id 
                join products p on p.id=v.product_id 
                where oi.order_id=" . $res[$i]['id'];
                $db->sql($sql);
                $res[$i]['items'] = $db->getResult();
            }
            $rows = array();
            $tempRow = array();
            foreach ($res as $row) {
                $items = $row['items'];
                $items1 = array();
                $total_amt = 0;
                foreach ($items as $item) {
                    $price = $item['discounted_price'] == 0 ? $item['price'] : $item['discounted_price'];
                    $temp = array(
                        'id' => $item['id'],
                        'product_variant_id' => $item['product_variant_id'],
                        'name' => $item['name'],
                        'unit' => $item['measurement'] . " " . $item['mesurement_unit_name'],
                        'product_image' => DOMAIN_URL . $item['image'],
                        'price' => $price,
                        'quantity' => $item['quantity'],
                        'subtotal' => $item['quantity'] * $price,
                        'active_status' => $item['active_status']
                    );
                    $total_amt += $item['sub_total'];
                    $items1[] = $temp;
                }
                if (!empty($row['items'][0]['discount'])) {
                    $item_discount = $row['items'][0]['discount'];
                    $discounted_amount = $row['total'] * $row['items'][0]['discount'] / 100;
                } else {
                    $discounted_amount = 0;
                }
                $final_total = $row['total'] - $discounted_amount;
                $discount_in_rupees = $row['total'] - $final_total;


                $discount_in_rupees = floor($discount_in_rupees);
                $tempRow['id'] = $row['id'];
                $tempRow['user_id'] = $row['user_id'];
                $tempRow['otp'] = (!empty($row['otp']) && $row['otp'] != null) ? $row['otp'] : 0;
                $tempRow['name'] = $row['name'];
                $tempRow['mobile'] = $row['mobile'];
                $tempRow['delivery_charge'] = $row['delivery_charge'];
                $tempRow['items'] = $items1;
                $tempRow['total'] = $total_amt;
                $tempRow['tax'] = $row['tax_amount'] . '(' . $row['tax_percentage'] . '%)';
                $tempRow['promo_discount'] = $row['promo_discount'];
                $tempRow['wallet_balance'] = $row['wallet_balance'];
                $tempRow['discount'] = $discount_in_rupees . '(' . $item_discount . '%)';
                $tempRow['qty'] = (isset($row['items'][0]['quantity']) && !empty($row['items'][0]['quantity'])) ? $row['items'][0]['quantity'] : "0";
                $tempRow['final_total'] = ceil($row['final_total']);
                $tempRow['promo_code'] = $row['promo_code'];
                $tempRow['deliver_by'] = $row['delivery_boy_id'];
                if ($row['delivery_boy_id'] != 0 && $row['delivery_boy_id'] != "") {
                    $d_name = $fn->get_data($columns = ['name'], 'id=' . $row['delivery_boy_id'], 'delivery_boys');
                    $tempRow['deliver_boy_name'] = (!empty($d_name[0]['name']) && $d_name[0]['name'] != null) ? $d_name[0]['name'] : "";
                } else {
                    $tempRow['deliver_boy_name'] = "";
                }
                $tempRow['payment_method'] = $row['payment_method'];
                $tempRow['address'] = $row['address'];
                $tempRow['latitude'] = $row['latitude'];
                $tempRow['longitude'] = $row['longitude'];
                $tempRow['delivery_time'] = $row['delivery_time'];
                $tempRow['active_status'] = $row['active_status'];
                $tempRow['wallet_balance'] = $row['wallet_balance'];
                $tempRow['date_added'] = date('d-m-Y', strtotime($row['date_added']));
                $tempRow['country_code'] = $row['country_code'];
                $rows1[] = $tempRow;
            }
            $response['error'] = false;
            $response['message'] = "Orders fatched successfully.";
            $response['total'] = $total;
            $response['data'] = $rows1;
        } else {
            $response['error'] = true;
            $response['message'] = "Order not found.";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Something went wrong, please try again leter.";
    }
    print_r(json_encode($response));
}

/* 
21.get_customers
   accesskey:90336
    get_customers:1
    city_id:119     // {optional}
    limit:10        // {optional}
    offset:0        // {optional}
    sort:id         // {optional}
    order:ASC/DESC  // {optional}
    search:value    // {optional}
*/
if (isset($_POST['get_customers']) && !empty($_POST['get_customers'])) {
    $where = '';
    $offset = (isset($_POST['offset']) && !empty(trim($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString(trim($fn->xss_clean($_POST['offset']))) : 0;
    $limit = (isset($_POST['limit']) && !empty(trim($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString(trim($fn->xss_clean($_POST['limit']))) : 10;

    $sort = (isset($_POST['sort']) && !empty(trim($_POST['sort']))) ? $db->escapeString(trim($fn->xss_clean($_POST['sort']))) : 'id';
    $order = (isset($_POST['order']) && !empty(trim($_POST['order']))) ? $db->escapeString(trim($fn->xss_clean($_POST['order']))) : 'DESC';

    if (isset($_POST['city_id']) && !empty($_POST['city_id'])) {
        $filter_user = $db->escapeString($fn->xss_clean($_POST['city_id']));
        $where .= ' where u.city=' . $filter_user;
    }
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $search = $db->escapeString($fn->xss_clean($_POST['search']));
        if (isset($_POST['city_id']) && !empty($_POST['city_id'])) {
            $where .= " and `id` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `email` like '%" . $search . "%' OR `mobile` like '%" . $search . "%' OR `city` like '%" . $search . "%' OR `area` like '%" . $search . "%' OR `street` like '%" . $search . "%' OR `status` like '%" . $search . "%' OR `created_at` like '%" . $search . "%'";
        } else {
            $where .= " Where `id` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `email` like '%" . $search . "%' OR `mobile` like '%" . $search . "%' OR `city` like '%" . $search . "%' OR `area` like '%" . $search . "%' OR `street` like '%" . $search . "%' OR `status` like '%" . $search . "%' OR `created_at` like '%" . $search . "%'";
        }
    }
    $sql = "SELECT COUNT(id) as total FROM `users` u " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res)) {
        foreach ($res as $row)
            $total = $row['total'];

        $sql = "SELECT *,(SELECT name FROM area a WHERE a.id=u.area) as area_name,(SELECT name FROM city c WHERE c.id=u.city) as city_name FROM `users` u " . $where . " ORDER BY `" . $sort . "` " . $order . " LIMIT " . $offset . ", " . $limit;
        $db->sql($sql);
        $res = $db->getResult();
        $rows = array();
        $tempRow = array();

        foreach ($res as $row) {
            $tempRow['id'] = $row['id'];
            $tempRow['name'] = $row['name'];
            $path = DOMAIN_URL . 'upload/profile/';
            if (!empty($row['profile'])) {
                $tempRow['profile'] = $path . $row['profile'];
            } else {
                $tempRow['profile'] = $path . "default_user_profile.png";
            }
            $tempRow['email'] = $row['email'];
            $tempRow['mobile'] = $row['mobile'];
            $tempRow['balance'] = $row['balance'];
            $tempRow['referral_code'] = $row['referral_code'];
            $tempRow['friends_code'] = !empty($row['friends_code']) ? $row['friends_code'] : '';
            $tempRow['city_id'] = $row['city'];
            $tempRow['city'] = !empty($row['city_name']) ? $row['city_name'] : "";
            $tempRow['area_id'] = $row['area'];
            $tempRow['area'] = !empty($row['area_name']) ? $row['area_name'] : "";
            $tempRow['street'] = $row['street'];
            $tempRow['apikey'] = $row['apikey'];
            $tempRow['status'] = $row['status'];
            $tempRow['created_at'] = $row['created_at'];
            $rows[] = $tempRow;
        }
        $response['error'] = false;
        $response['message'] = "Customers fatched successfully.";
        $response['total'] = $total;
        $response['data'] = $rows;
    } else {
        $response['error'] = true;
        $response['message'] = "Something went wrong, please try again leter.";
    }
    print_r(json_encode($response));
}

/* 
22. get_financial_statistics
    accesskey:90336
    get_financial_statistics:1
*/
if (isset($_POST['get_financial_statistics']) && !empty($_POST['get_financial_statistics'])) {
    $config = $fn->get_configurations();
    $total_orders = $total_products = $total_users = $total_sold_out_products = $total_low_stock_count = 0;

    $low_stock_limit = isset($config['low-stock-limit']) && (!empty($config['low-stock-limit'])) ? $config['low-stock-limit'] : 0;
    $sql = "SELECT * FROM settings WHERE variable='currency'";
    $db->sql($sql);
    $res_currency = $db->getResult();

    $total_orders = $fn->rows_count('orders');
    $total_products = $fn->rows_count('products');
    $total_users = $fn->rows_count('users');
    $total_sold_out_products = $fn->sold_out_count();
    $total_low_stock_count = $fn->low_stock_count($low_stock_limit);

    $year = date("Y");
    $curdate = date('Y-m-d');
    $sql = "SELECT SUM(final_total) AS total_sale,DATE(date_added) AS order_date FROM orders WHERE YEAR(date_added) = '$year' AND DATE(date_added)<'$curdate' AND `active_status`='delivered' GROUP BY DATE(date_added) ORDER BY DATE(date_added) DESC  LIMIT 0,7";
    $db->sql($sql);
    $result_order = $db->getResult();
    $total_sales = array_column($result_order, "total_sale");
    if (!empty($total_products) && !empty($total_users)) {

        $response['error'] = false;
        $response['total_orders'] = $total_orders;
        $response['total_products'] = $total_products;
        $response['total_users'] = $total_users;
        $response['total_sold_out_products'] = $total_sold_out_products;
        $response['total_low_stock_count'] = $total_low_stock_count;
        $response['currency'] = $res_currency[0]['value'];
        $response['total_sale'] = (!empty($result_order)) ? strval(array_sum($total_sales)) : "0";
    } else {
        $response['error'] = true;
        $response['message'] = "Something went wrong, please try again leter.";
    }
    print_r(json_encode($response));
}

/* 
23.login
    accesskey:90336
    username:admin
    password:admin123
    fcm_id:YOUR_FCM_ID   // {optional}
    login:1
*/
if (isset($_POST['login']) && !empty($_POST['login'])) {

    if (empty(trim($_POST['username']))) {
        $response['error'] = true;
        $response['message'] = "Username should be filled!";
        print_r(json_encode($response));
        return false;
        exit();
    }
    if (empty($_POST['password'])) {
        $response['error'] = true;
        $response['message'] = "Password should be filled!";
        print_r(json_encode($response));
        return false;
        exit();
    }
    $username = $db->escapeString(trim($fn->xss_clean($_POST['username'])));
    $password = md5($db->escapeString($fn->xss_clean($_POST['password'])));
    $sql = "SELECT * FROM `admin` WHERE username = '" . $username . "' AND password = '" . $password . "'";
    $db->sql($sql);
    $res = $db->getResult();
    $num = $db->numRows($res);
    $rows = $tempRow = $permissions = $permission = array();
    if ($num == 1) {
        $admin_id = $res[0]['id'];

        $fcm_id = (isset($_POST['fcm_id']) && !empty($_POST['fcm_id'])) ? $db->escapeString($fn->xss_clean($_POST['fcm_id'])) : "";
        if (!empty($fcm_id)) {
            $sql1 = "update admin set `fcm_id` ='$fcm_id' where id = $admin_id";
            $db->sql($sql1);
            $db->sql($sql);
            $res = $db->getResult();
        }
        unset($res[0]['password']);
        $permissions = json_decode($res[0]['permissions'], true);

        foreach ($permissions as $per) {

            if (!array_key_exists('create', $per)) {
                $per['create'] = "0";
            }
            if (!array_key_exists('read', $per)) {
                $per['read'] = "0";
            }
            if (!array_key_exists('update', $per)) {
                $per['update'] = "0";
            }
            if (!array_key_exists('delete', $per)) {
                $per['delete'] = "0";
            }
            $permission[] = $per;
        }

        $asd = array_combine(array_keys($permissions), $permission);
        unset($res[0]['permissions']);
        $response['error'] = false;
        $response['message'] = "Admin login successfully";
        $response['permissions'] = $asd;
        $response['data'] = $res;
    } else {
        $response['error'] = true;
        $response['message'] = "Invalid username or password!";
    }
    print_r(json_encode($response));
}

/* 
24.update_admin_fcm_id
   accesskey:90336
    id:1
    fcm_id:YOUR_FCM_ID
    update_admin_fcm_id:1
*/
if (isset($_POST['update_admin_fcm_id']) && !empty($_POST['update_admin_fcm_id'])) {
    if (empty($_POST['fcm_id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass the fcm_id!";
        print_r(json_encode($response));
        return false;
        exit();
    }

    $id = $db->escapeString(trim($fn->xss_clean($_POST['id'])));
    if (isset($_POST['fcm_id']) && !empty($_POST['fcm_id'])) {
        $fcm_id = $db->escapeString($fn->xss_clean($_POST['fcm_id']));
        $sql1 = "update admin set `fcm_id` ='$fcm_id' where id = '" . $id . "'";
        if ($db->sql($sql1)) {
            $response['error'] = false;
            $response['message'] = "Admin fcm_id Updeted successfully.";
        } else {
            $response['error'] = true;
            $response['message'] = "Can not update fcm_id of admin.";
        }
        print_r(json_encode($response));
    }
}

/* 
25. get_privacy_and_terms
   accesskey:90336
    get_privacy_and_terms:1
*/
if (isset($_POST['get_privacy_and_terms']) && !empty($_POST['get_privacy_and_terms'])) {
    $sql = "select value from `settings` where variable='manager_app_privacy_policy'";
    $db->sql($sql);
    $res = $db->getResult();
    $sql1 = "select value from `settings` where variable='manager_app_terms_conditions'";
    $db->sql($sql1);
    $res1 = $db->getResult();
    if (!empty($res) && !empty($res1)) {
        $response['error'] = false;
        $response['message'] = "Privacy & Policy fetched!";
        $response['privacy_policy'] = $res[0]['value'];
        $response['terms_conditions'] = $res1[0]['value'];
    } else {
        $response['error'] = true;
        $response['message'] = "Something went wrong!";
    }
    print_r(json_encode($response));
}

/* 
26.update_order_status
    accesskey:90336
    update_order_status:1
    id:169
    status:cancelled
    delivery_boy_id:20  // {optional}
*/
if (isset($_POST['update_order_status']) && !empty($_POST['update_order_status'])) {
    if (empty($_POST['id']) || empty($_POST['status'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all mandatory fields!";
        print_r(json_encode($response));
        return false;
        exit();
    }

    $id = $db->escapeString(trim($fn->xss_clean($_POST['id'])));
    $postStatus = $db->escapeString($fn->xss_clean($_POST['status']));
    $delivery_boy_id = 0;
    if (isset($_POST['delivery_boy_id']) && !empty($fn->xss_clean($_POST['delivery_boy_id']))) {
        $delivery_boy_id = $db->escapeString($fn->xss_clean($_POST['delivery_boy_id']));
    }
    $response = $fn->update_order_status($id, $postStatus, $delivery_boy_id);
    print_r($response);
}

/* 
27.get_permissions
    accesskey:90336
    id:1
    get_permissions:1
    type: orders/payment/customers/featured/products_order/products/subcategories/categories/home_sliders/faqs/reports/locations/settings/transactions/notifications/return_requests/delivery_boys/promo_codes/new_offers   // {optional}
*/
if (isset($_POST['get_permissions']) && !empty($_POST['get_permissions'])) {

    if (empty(trim($_POST['id']))) {
        $response['error'] = true;
        $response['message'] = "Admin id should be filled!";
        print_r(json_encode($response));
        return false;
        exit();
    }

    $id = $db->escapeString(trim($fn->xss_clean($_POST['id'])));
    $type = (isset($_POST['type']) && !empty($_POST['type'])) ? $db->escapeString(trim($fn->xss_clean($_POST['type']))) : "";
    $sql = "SELECT `permissions` FROM `admin` WHERE id = $id ";
    $db->sql($sql);
    $res = $db->getResult();
    $num = $db->numRows($res);
    $rows = $tempRow = $per = $permissions = $permission = array();
    if ($num == 1) {
        $permissions = json_decode($res[0]['permissions'], true);
        if ($type == "products_order" || $type == "orders" || $type == "payment" || $type == "home_sliders" || $type == "categories" || $type == "subcategories" || $type == "products" || $type == "featured" || $type == "customers" || $type == "payment" || $type == "new_offers" || $type == "promo_codes" || $type == "delivery_boys" || $type == "return_requests" || $type == "notifications" || $type == "transactions" || $type == "settings" || $type == "locations" || $type == "reports" || $type == "faqs") {

            $per = $permissions[$type];

            if (!array_key_exists('create', $per)) {
                $per['create'] = "0";
            }
            if (!array_key_exists('read', $per)) {
                $per['read'] = "0";
            }
            if (!array_key_exists('update', $per)) {
                $per['update'] = "0";
            }
            if (!array_key_exists('delete', $per)) {
                $per['delete'] = "0";
            }
            $permission = $per;
            $response['error'] = false;
            $response['message'] = "Permissions fetched successfully";
            $response['data'][$type] = $permission;
        } else if ($type == "") {
            foreach ($permissions as $per) {
                if (!array_key_exists('create', $per)) {
                    $per['create'] = "0";
                }
                if (!array_key_exists('read', $per)) {
                    $per['read'] = "0";
                }
                if (!array_key_exists('update', $per)) {
                    $per['update'] = "0";
                }
                if (!array_key_exists('delete', $per)) {
                    $per['delete'] = "0";
                }
                $permission[] = $per;
            }
            $asd1 = array_combine(array_keys($permissions), $permission);
            $response['error'] = false;
            $response['message'] = "Permissions fetched successfully";
            $response['data'] = $asd1;
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Permissions can not fetched!";
    }
    print_r(json_encode($response));
}

/* 
28.update_order_item_status
    accesskey:90336
    update_order_item_status:1
    order_item_id:7166
    status:cancelled
    order_id:3445
*/
if (isset($_POST['update_order_item_status']) && !empty($_POST['update_order_item_status'])) {
    if (empty($_POST['order_item_id']) || empty($_POST['status']) || empty($_POST['order_id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all mandatory fields!";
        print_r(json_encode($response));
        return false;
        exit();
    }

    $order_item_id = $db->escapeString(trim($fn->xss_clean($_POST['order_item_id'])));
    $order_id = $db->escapeString(trim($fn->xss_clean($_POST['order_id'])));
    $postStatus = $db->escapeString($fn->xss_clean($_POST['status']));

    $response = $fn->update_order_item_status($order_item_id, $order_id, $postStatus);
    print_r($response);
}

/* 
29.delivery_boy_fund_transfers
    accesskey:90336
    delivery_boy_fund_transfers:1
    delivery_boy_id:104     // {optional}
    limit:10                // {optional}
    offset:0                // {optional}
    sort:id                 // {optional}
    order:ASC/DESC          // {optional}
    search:value            // {optional}
*/
if (isset($_POST['delivery_boy_fund_transfers']) && !empty($_POST['delivery_boy_fund_transfers'])) {
    $where = '';
    $offset = (isset($_POST['offset']) && !empty(trim($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString(trim($fn->xss_clean($_POST['offset']))) : 0;
    $limit = (isset($_POST['limit']) && !empty(trim($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString(trim($fn->xss_clean($_POST['limit']))) : 10;

    $sort = (isset($_POST['sort']) && !empty(trim($_POST['sort']))) ? $db->escapeString(trim($fn->xss_clean($_POST['sort']))) : 'id';
    $order = (isset($_POST['order']) && !empty(trim($_POST['order']))) ? $db->escapeString(trim($fn->xss_clean($_POST['order']))) : 'DESC';

    if (isset($_POST['delivery_boy_id']) && !empty($_POST['delivery_boy_id'])) {
        $delivery_boy_id = $db->escapeString($fn->xss_clean($_POST['delivery_boy_id']));
        $where .= " where f.delivery_boy_id = $delivery_boy_id";
    }

    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $search = $db->escapeString($fn->xss_clean($_POST['search']));
        if (isset($_POST['delivery_boy_id']) && !empty($_POST['delivery_boy_id'])) {
            $where .= " and f.`id` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `mobile` like '%" . $search . "%' OR `address` like '%" . $search . "%' OR `message` like '%" . $search . "%' OR f.`date_created` like '%" . $search . "%'";
        } else {
            $where .= " Where f.`id` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `mobile` like '%" . $search . "%' OR `address` like '%" . $search . "%' OR `message` like '%" . $search . "%' OR f.`date_created` like '%" . $search . "%'";
        }
    }
    $sql = "SELECT COUNT(f.`id`) as total FROM `fund_transfers` f LEFT JOIN `delivery_boys` d ON f.delivery_boy_id=d.id" . $where;
    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res)) {
        foreach ($res as $row)
            $total = $row['total'];

        $sql = "SELECT f.*,d.name,d.mobile,d.address FROM `fund_transfers` f LEFT JOIN `delivery_boys` d ON f.delivery_boy_id=d.id  $where ORDER BY $sort $order LIMIT $offset,$limit";
        $db->sql($sql);
        $res = $db->getResult();
        $rows = array();
        $tempRow = array();

        foreach ($res as $row) {
            $tempRow['id'] = $row['id'];
            $tempRow['name'] = $row['name'];
            $tempRow['mobile'] = $row['mobile'];
            $tempRow['address'] = $row['address'];
            $tempRow['delivery_boy_id'] = $row['delivery_boy_id'];
            $tempRow['opening_balance'] = $row['opening_balance'];
            $tempRow['closing_balance'] = $row['closing_balance'];
            $tempRow['amount'] = $row['amount'];
            $tempRow['type'] = $row['type'];
            $tempRow['status'] = $row['status'];
            $tempRow['message'] = $row['message'];
            $tempRow['date_created'] = $row['date_created'];
            $rows[] = $tempRow;
        }
        $response['error'] = false;
        $response['message'] = "Fund transfers fatched successfully.";
        $response['total'] = $total;
        $response['data'] = $rows;
    } else {
        $response['error'] = true;
        $response['message'] = "Something went wrong, please try again leter.";
    }
    print_r(json_encode($response));
}

/* 
30.delivery_boy_transfer_fund
    accesskey:90336
    delivery_boy_transfer_fund:1		
    delivery_boy_id:302
    delivery_boy_balance:20
    amount:20
    message: message from admin     // {optional}
*/
if (isset($_POST['delivery_boy_transfer_fund']) && !empty($_POST['delivery_boy_transfer_fund'])) {
    if (empty($_POST['delivery_boy_id']) || $_POST['delivery_boy_balance'] == '' || $_POST['amount'] == '') {
        $response['error'] = true;
        $response['message'] = "some parameters are missing!";
        print_r(json_encode($response));
        return false;
        exit();
    }

    $id = $db->escapeString($fn->xss_clean($_POST['delivery_boy_id']));
    $sql = "SELECT id FROM `delivery_boys` where id=$id";
    $db->sql($sql);
    $res1 = $db->getResult();
    if (!empty($res1)) {
        $balance = $db->escapeString($fn->xss_clean($_POST['delivery_boy_balance']));
        if ($balance == 0) {
            $response['error'] = true;
            $response['message'] = "Balance must be greater then zero.";
            print_r(json_encode($response));
            return false;
            exit();
        }
        if (!is_numeric(trim($_POST['amount']))) {
            $response['error'] = true;
            $response['message'] = "Amount must be number.";
            print_r(json_encode($response));
            return false;
            exit();
        }
        $amount = $db->escapeString($fn->xss_clean($_POST['amount']));
        if ($amount > $balance) {
            $response['error'] = true;
            $response['message'] = "Amount must be less then or equal to balance.";
            print_r(json_encode($response));
            return false;
            exit();
        }
        $message = (!empty($_POST['message'])) ? $db->escapeString($fn->xss_clean($_POST['message'])) : 'Fund Transferred By Admin';
        $bal = $balance - $amount;
        $sql = "Update delivery_boys set `balance`='" . $bal . "' where `id`=" . $id;
        $db->sql($sql);
        $sql = "INSERT INTO `fund_transfers` (`delivery_boy_id`,`amount`,`opening_balance`,`closing_balance`,`status`,`message`) VALUES ('" . $id . "','" . $amount . "','" . $balance . "','" . $bal . "','SUCCESS','" . $message . "')";
        if ($db->sql($sql)) {
            $response['error'] = false;
            $response['message'] = "Amount transferred successfully.";
        } else {
            $response['error'] = true;
            $response['message'] = "Amount does not transferred, somthing went wrong.";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Delivery boy does not exist";
    }
    print_r(json_encode($response));
}

/* 
17.get_all_data
    accesskey:90336
    get_all_data:1
*/
if (isset($_POST['get_all_data']) && !empty($_POST['get_all_data'])) {
    //categories
    $sql = "SELECT * FROM category ORDER BY id DESC ";
    $db->sql($sql);
    $res_categories = $db->getResult();

    for ($i = 0; $i < count($res_categories); $i++) {
        $res_categories[$i]['image'] = (!empty($res_categories[$i]['image'])) ? DOMAIN_URL . '' . $res_categories[$i]['image'] : '';
    }
    // slider images
    $sql = 'SELECT * from slider order by id DESC';
    $db->sql($sql);
    $res_slider_image = $db->getResult();
    $temp = $slider_images = array();
    if (!empty($res_slider_image)) {
        $response['error'] = false;
        foreach ($res_slider_image as $row) {
            $name = "";
            if ($row['type'] == 'category') {
                $sql = 'select `name` from category where id = ' . $row['type_id'] . ' order by id desc';
                $db->sql($sql);
                $result1 = $db->getResult();
                $name = (!empty($result1[0]['name'])) ? $result1[0]['name'] : "";
            }
            if ($row['type'] == 'product') {
                $sql = 'select `name` from products where id = ' . $row['type_id'] . ' order by id desc';
                $db->sql($sql);
                $result1 = $db->getResult();
                $name = (!empty($result1[0]['name'])) ? $result1[0]['name'] : "";
            }

            $temp['type'] = $row['type'];
            $temp['type_id'] = $row['type_id'];
            $temp['name'] = $name;
            $temp['image'] = DOMAIN_URL . $row['image'];
            $slider_images[] = $temp;
        }
    }

    // featured sections
    $sql = 'select * from `sections` order by id desc';
    $db->sql($sql);
    $result = $db->getResult();
    $response = $product_ids = $section = $variations = $featured_sections = array();
    foreach ($result as $row) {
        $product_ids = explode(',', $row['product_ids']);

        $section['id'] = $row['id'];
        $section['title'] = $row['title'];
        $section['short_description'] = $row['short_description'];
        $section['style'] = $row['style'];
        $section['product_ids'] = array_map('trim', $product_ids);
        $product_ids = $section['product_ids'];

        $product_ids = implode(',', $product_ids);

        $sql = 'SELECT * FROM `products` WHERE `status` = 1 AND id IN (' . $product_ids . ')';
        $db->sql($sql);
        $result1 = $db->getResult();
        $product = array();
        $i = 0;
        foreach ($result1 as $row) {
            $sql = "SELECT *,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE pv.product_id=" . $row['id'] . " ORDER BY serve_for ASC";
            $db->sql($sql);
            $variants = $db->getResult();

            $row['other_images'] = json_decode($row['other_images'], 1);
            $row['other_images'] = (empty($row['other_images'])) ? array() : $row['other_images'];

            for ($j = 0; $j < count($row['other_images']); $j++) {
                $row['other_images'][$j] = DOMAIN_URL . $row['other_images'][$j];
            }
            if ($row['tax_id'] == 0) {
                $row['tax_title'] = "";
                $row['tax_percentage'] = "";
            } else {
                $t_id = $row['tax_id'];
                $sql_tax = "SELECT * from taxes where id= $t_id";
                $db->sql($sql_tax);
                $res_tax = $db->getResult();
                foreach ($res_tax as $tax) {
                    $row['tax_title'] = $tax['title'];
                    $row['tax_percentage'] = $tax['percentage'];
                }
            }
            for ($k = 0; $k < count($variants); $k++) {
                if ($variants[$k]['stock'] <= 0) {
                    $variants[$k]['serve_for'] = 'Sold Out';
                } else {
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
            $row['image'] = DOMAIN_URL . $row['image'];
            $product[$i] = $row;
            $product[$i]['variants'] = $variants;
            $i++;
        }
        $section['products'] = $product;
        $featured_sections[] = $section;
        unset($section['products']);
    }
    // offer images
    $sql = 'SELECT * from offers order by id desc';
    $db->sql($sql);
    $res_offer_images = $db->getResult();
    $response = $temp = $offer_images = array();
    foreach ($res_offer_images as $row) {
        $temp['image'] = DOMAIN_URL . $row['image'];
        $offer_images[] = $temp;
    }

    $response['error'] = false;
    $response['message'] = "Data fetched successfully";
    $response['categories'] = $res_categories;
    $response['slider_images'] = $slider_images;
    $response['sections'] = $featured_sections;
    $response['offer_images'] = $offer_images;
    print_r(json_encode($response));
}
