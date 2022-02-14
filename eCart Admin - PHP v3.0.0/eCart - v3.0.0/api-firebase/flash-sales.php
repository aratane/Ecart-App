<?php
header('Access-Control-Allow-Origin: *');
session_start();
include '../includes/crud.php';
include '../includes/variables.php';
include_once('verify-token.php');
include_once('../includes/custom-functions.php');
include_once('../includes/functions.php');
$function = new functions;
$fn = new custom_functions;
$db = new Database();
$db->connect();
$response = array();
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
1. get-all-flash-sales
2. get-all-flash-sales-products
-------------------------------------------
-------------------------------------------
*/

if (!isset($_POST['accesskey'])) {
    if (!isset($_GET['accesskey'])) {
        $response['error'] = true;
        $response['message'] = "Access key is invalid or not passed!";
        print_r(json_encode($response));
        return false;
    }
}

if (isset($_POST['accesskey'])) {
    $accesskey = $db->escapeString($fn->xss_clean($_POST['accesskey']));
} else {
    $accesskey = $db->escapeString($fn->xss_clean($_GET['accesskey']));
}

if ($access_key != $accesskey) {
    $response['error'] = true;
    $response['message'] = "invalid accesskey!";
    print_r(json_encode($response));
    return false;
}

if ((isset($_POST['add-flash-sales'])) && ($_POST['add-flash-sales'] == 1)) {
    if (ALLOW_MODIFICATION == 0 && !defined(ALLOW_MODIFICATION)) {
        $response["message"] =  '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        echo json_encode($response);
        return false;
    }
    $permissions = $fn->get_permissions($_SESSION['id']);
    if ($permissions['featured']['create'] == 0) {
        $response["message"] = "<p class='alert alert-danger'>You have no permission to create Flash Sales.</p>";
        echo json_encode($response);
        return false;
    }

    $title = $db->escapeString($fn->xss_clean($_POST['title']));
    $slug = $function->slugify($db->escapeString($fn->xss_clean($_POST['title'])));
    $short_description = $db->escapeString($fn->xss_clean($_POST['short_description']));
    $status = $db->escapeString($fn->xss_clean($_POST['status']));

    $sql = "INSERT INTO `flash_sales` (`title`,`slug`,`short_description`,`status`) VALUES ('$title','$slug','$short_description','$status')";
    $db->sql($sql);
    $res = $db->getResult();
    $response["message"] = "<p class = 'alert alert-success'>Flash sales created Successfully</p>";
    $sql = "SELECT id FROM flash_sales ORDER BY id DESC";
    $db->sql($sql);
    $res = $db->getResult();
    $response["id"] = $res[0]['id'];
    echo json_encode($response);
}

if ((isset($_POST['edit-flash-sales'])) && ($_POST['edit-flash-sales'] == 1)) {
    if (ALLOW_MODIFICATION == 0 && !defined(ALLOW_MODIFICATION)) {
        $response["message"] =  '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        echo json_encode($response);
        return false;
    }
    $permissions = $fn->get_permissions($_SESSION['id']);
    if ($permissions['featured']['update'] == 0) {
        $response["message"] = "<p class='alert alert-danger'>You have no permission to update Flash Sales.</p>";
        echo json_encode($response);
        return false;
    }

    $id = $db->escapeString($fn->xss_clean($_POST['flash-sales-id']));
    $short_description = $db->escapeString($fn->xss_clean($_POST['short_description']));
    $title = $db->escapeString($fn->xss_clean($_POST['title']));
    $status = $db->escapeString($fn->xss_clean($_POST['status']));

    $slug = $function->slugify($title);
    $sql = "SELECT slug FROM flash_sales where id!=" . $id;
    $db->sql($sql);
    $res = $db->getResult();
    $i = 1;
    foreach ($res as $row) {
        $slug = $slug . '-' . $i;
        $i++;
    }

    $sql = "UPDATE `flash_sales` SET `title`='$title',`slug` = '$slug',`short_description`='$short_description',`status`='$status' WHERE `flash_sales`.`id` = " . $id;
    $db->sql($sql);
    $res = $db->getResult();
    $response["message"] = "<p class='alert alert-success'>Flash Sales updated Successfully</p>";
    $response["id"] = $id;
    echo json_encode($response);
}

if (isset($_GET['type']) && $_GET['type'] != '' && $_GET['type'] == 'delete-flash-sales') {
    if (ALLOW_MODIFICATION == 0 && !defined(ALLOW_MODIFICATION)) {
        return 2;
    }
    $permissions = $fn->get_permissions($_SESSION['id']);
    if ($permissions['featured']['delete'] == 0) {
        echo 2;
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_GET['id']));

    $sql = 'DELETE FROM `flash_sales` WHERE `id`=' . $id;
    $db->sql($sql);
    $result = $db->getResult();
    if (!empty($result)) {
        $result = 0;
    } else {
        $result = 1;
    }

    $sql1 = 'DELETE FROM `flash_sales_products` WHERE `flash_sales_id`=' . $id;
    $db->sql($sql1);
    $result1 = $db->getResult();
    if (!empty($result1)) {
        $result1 = 0;
    } else {
        $result1 = 1;
    }
    if ($result == 1 && $result1 == 1) {
        echo 1;
        return false;
    } else {
        echo 0;
        return false;
    }
}

if (isset($_POST['get-all-flash-sales']) && ($_POST['get-all-flash-sales'] == 1)) {
    /*
    get-all-flash-sales
        accesskey:90336
        get-all-flash-sales:1
        flash_sales_id:1        // {optional}
        slug:summer-sales-1     // {optional}
        offset:0                // {optional}
        limit:10                // {optional}
    */

    if (!verify_token()) {
        return false;
    }

    $flash_sales_id = (isset($_POST['flash_sales_id']) && is_numeric($_POST['flash_sales_id'])) ? $db->escapeString($fn->xss_clean($_POST['flash_sales_id'])) : "";
    $slug = (isset($_POST['slug'])) ? $db->escapeString($fn->xss_clean($_POST['slug'])) : "";

    $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;
    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $sort = 'fp.id';
    $order = 'DESC';

    $where = (!empty($flash_sales_id)) ? " AND `id` = $flash_sales_id " : "";
    $where .= (!empty($slug)) ? " AND `slug` = '$slug' " : "";

    $sql1 = "SELECT count(id) as total FROM `flash_sales` where status=1 $where";
    $db->sql($sql1);
    $res1 = $db->getResult();
    $total = $res1[0]['total'];

    $sql_query = "SELECT * FROM flash_sales where status = 1 $where ORDER BY id ASC LIMIT $offset,$limit";
    $db->sql($sql_query);
    $result = $db->getResult();
    $response = $product_ids = $category_ids = $section = $variations = $temp = array();
    foreach ($result as $row) {
        $section['id'] = $row['id'];
        $section['title'] = $row['title'];
        $section['slug'] = $row['slug'];
        $section['short_description'] = $row['short_description'];
        $section['status'] = $row['status'];

        $product = $fn->get_products('', '', "", '', '', "fp.status = 1 AND fp.flash_sales_id = " . $section['id'] . " ", $limit, $offset, $sort, $order, '', '', '', '', " fp.id as flash_sales_product_id,fp.flash_sales_id,fp.product_id,fp.product_variant_id,fp.price,fp.discounted_price,fp.start_date,fp.end_date,fp.status as product_sales_status", "LEFT JOIN flash_sales_products fp ON p.id=fp.product_id", "");

        $section['products'] = $product['data'];
        $temp[] = $section;
        unset($section['products']);
    }

    if (!empty($result)) {
        $response['error'] = false;
        $response['message'] = "Flash Sales Retrived Successfully!";
        $response['total'] = $total;
        $response['data'] = $temp;
    } else {
        $response['error'] = true;
        $response['message'] = "No data found!";
    }
    print_r(json_encode($response));
}

/* 
------------------------------------------------------------------------------------------------
------------------------------------------------------------------------------------------------
*/

if ((isset($_POST['add_flash_sales_products'])) && ($_POST['add_flash_sales_products'] == 1)) {
    if (ALLOW_MODIFICATION == 0 && !defined(ALLOW_MODIFICATION)) {
        $response["message"] =  '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        echo json_encode($response);
        return false;
    }
    $permissions = $fn->get_permissions($_SESSION['id']);
    if ($permissions['featured']['create'] == 0) {
        $response["message"] = "<p class='alert alert-danger'>You have no permission to create Flash Sales.</p>";
        echo json_encode($response);
        return false;
    }
    if ($_POST['flash_sales_products_id'] == 0) {
        $response["message"] = "<p class = 'alert alert-danger'>Please Select Flash Sales</p>";
        echo json_encode($response);
        return false;
    }
    for ($i = 0; $i < count($_POST['price']); $i++) {
        if ($_POST['discounted_price'][$i] >= $_POST['price'][$i]) {
            $response["message"] = "<p class = 'alert alert-danger'>Discounted price should not be greater then price.</p>";
            echo json_encode($response);
            return false;
        }
        if ($_POST['end_date'][$i] <= $_POST['start_date'][$i]) {
            $response["message"] = "<p class = 'alert alert-danger'>End date should not be lesser then start date.</p>";
            echo json_encode($response);
            return false;
        }

        $sql = "SELECT * FROM flash_sales_products WHERE product_id IN(" . $_POST['product_id'][$i] . ")";
        $db->sql($sql);
        $res1 = $db->getResult();
        foreach ($res1 as $result1) {
            if (between($result1['end_date'], $_POST['start_date'][$i], $_POST['end_date'][$i])) {
                $response["message"] = "<p class = 'alert alert-danger'>Product already add in sale</p>";
                echo json_encode($response);
                return false;
            }
        }

        $sql = "SELECT * FROM product_variant WHERE product_id IN(" . $_POST['product_id'][$i] . ")";
        $db->sql($sql);
        $res = $db->getResult();
        foreach ($res as $result) {
            if ($result['stock'] >= 0 && $result['serve_for'] == 'Sold Out') {
                $response["message"] = "<p class = 'alert alert-danger'>Product is sold out</p>";
                echo json_encode($response);
                return false;
            }
        }

        $flash_sales_id = $db->escapeString($fn->xss_clean($_POST['flash_sales_products_id']));
        $product_id = $db->escapeString($fn->xss_clean($_POST['product_id'][$i]));
        $product_variant_id = $db->escapeString($fn->xss_clean($_POST['product_variant_id'][$i]));
        $price = $db->escapeString($fn->xss_clean($_POST['price'][$i]));
        $discounted_price = $db->escapeString($fn->xss_clean($_POST['discounted_price'][$i]));
        $start_date = $db->escapeString($fn->xss_clean($_POST['start_date'][$i]));
        $end_date = $db->escapeString($fn->xss_clean($_POST['end_date'][$i]));

        $sql = "INSERT INTO `flash_sales_products` (`flash_sales_id`,`product_id`,`product_variant_id`,`price`,`discounted_price`,`start_date`,`end_date`,`status`) VALUES ('$flash_sales_id','$product_id','$product_variant_id','$price','$discounted_price','$start_date','$end_date','1')";
        $db->sql($sql);
        $res = $db->getResult();
    }
    $response["message"] = "<p class = 'alert alert-success'>Flash sales products created Successfully</p>";
    $sql = "SELECT id FROM flash_sales_products ORDER BY id DESC";
    $db->sql($sql);
    $res = $db->getResult();
    echo json_encode($response);
}

if ((isset($_POST['edit_flash_sales_products'])) && ($_POST['edit_flash_sales_products'] == 1)) {
    if (ALLOW_MODIFICATION == 0 && !defined(ALLOW_MODIFICATION)) {
        $response["message"] =  '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        echo json_encode($response);
        return false;
    }
    $permissions = $fn->get_permissions($_SESSION['id']);

    if ($permissions['featured']['update'] == 0) {
        $response["message"] = "<p class='alert alert-danger'>You have no permission to update Flash Sales Products.</p>";
        echo json_encode($response);
        return false;
    }
    for ($i = 0; $i < count($_POST['price']); $i++) {
        if ($_POST['discounted_price'][$i] >= $_POST['price'][$i]) {
            $response["message"] = "<p class = 'alert alert-danger'>Discounted price should not be greater then price.</p>";
            echo json_encode($response);
            return false;
        }
        if ($_POST['end_date'][$i] <= $_POST['start_date'][$i]) {
            $response["message"] = "<p class = 'alert alert-danger'>End date should not be lesser then start date.</p>";
            echo json_encode($response);
            return false;
        }
        $sql = "SELECT * FROM product_variant WHERE product_id IN(" . $_POST['product_id'][$i] . ")";
        $db->sql($sql);
        $res = $db->getResult();
        foreach ($res as $result) {
            if ($result['stock'] >= 0 && $result['serve_for'] == 'Sold Out') {
                $response["message"] = "<p class = 'alert alert-danger'>Product is sold out</p>";
                echo json_encode($response);
                return false;
            }
        }
        $id = $db->escapeString($fn->xss_clean($_POST['edit_flash_sales_products_id']));
        $flash_sales_id = $db->escapeString($fn->xss_clean($_POST['update_flash_sales_id']));
        $product_id = $db->escapeString($fn->xss_clean($_POST['product_id'][$i]));
        $product_variant_id = $db->escapeString($fn->xss_clean($_POST['product_variant_id'][$i]));
        $price = $db->escapeString($fn->xss_clean($_POST['price'][$i]));
        $discounted_price = $db->escapeString($fn->xss_clean($_POST['discounted_price'][$i]));
        $start_date = $db->escapeString($fn->xss_clean($_POST['start_date'][$i]));
        $end_date = $db->escapeString($fn->xss_clean($_POST['end_date'][$i]));
        $pr_status = $db->escapeString($fn->xss_clean($_POST['status'][$i]));

        $sql = "UPDATE `flash_sales_products` SET `flash_sales_id`='$flash_sales_id',`product_id`='$product_id', `product_variant_id`='$product_variant_id', `price`='$price', `discounted_price`='$discounted_price', `start_date`='$start_date', `end_date`='$end_date', `status`='$pr_status' WHERE `id` = " . $id;
        $db->sql($sql);
        $res = $db->getResult();
    }
    $response["message"] = "<p class='alert alert-success'>Flash Sales Products updated Successfully</p>";
    $response["id"] = $id;
    echo json_encode($response);
}

if (isset($_GET['type']) && $_GET['type'] != '' && $_GET['type'] == 'delete-flash-sales-products') {
    if (ALLOW_MODIFICATION == 0 && !defined(ALLOW_MODIFICATION)) {
        return 2;
    }
    $permissions = $fn->get_permissions($_SESSION['id']);
    if ($permissions['featured']['delete'] == 0) {
        echo 2;
        return false;
    }
    $id = $db->escapeString($fn->xss_clean($_GET['id']));

    $sql = 'DELETE FROM `flash_sales_products` WHERE `id`=' . $id;
    if ($db->sql($sql)) {
        echo 1;
        return false;
    } else {
        echo 0;
        return false;
    }
}

if (isset($_POST['get-all-flash-sales-products']) && $_POST['get-all-flash-sales-products'] == 1) {
    /*
    get-all-flash-sales-products
        accesskey:90336
        get-all-flash-sales-products:1
        flash_sales_id:2                // {optional}
        slug:weekend-sumer-sales-1      // {optional}
        product_slug:safe-wash-liquid-1 // {optional}
        user_id:1       // {optional}
        limit:10        // {optional}
        offset:0        // {optional}
    */

    if (!verify_token()) {
        return false;
    }
    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;
    $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id']) && is_numeric($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";
    $flash_sales_id = (isset($_POST['flash_sales_id']) && is_numeric($_POST['flash_sales_id'])) ? $db->escapeString($fn->xss_clean($_POST['flash_sales_id'])) : "";
    $slug = (isset($_POST['slug'])) ? $db->escapeString($fn->xss_clean($_POST['slug'])) : "";
    $product_slug = (isset($_POST['product_slug'])) ? $db->escapeString($fn->xss_clean($_POST['product_slug'])) : "";

    $where = (!empty($flash_sales_id)) ? " AND fp.`flash_sales_id` = $flash_sales_id " : "";
    $where .= (!empty($product_slug)) ? " AND p.`slug` = '$product_slug' " : "";
    $where .= (!empty($slug)) ? " AND fs.`slug` = '$slug' " : "";

    $sql1 = "SELECT count(fp.id) as total FROM `flash_sales_products` fp JOIN flash_sales fs ON fs.id=fp.flash_sales_id  LEFT JOIN products p ON p.id=fp.product_id WHERE fp.status = 1 $where";
    $db->sql($sql1);
    $res1 = $db->getResult();
    $total = $res1[0]['total'];

    $sql = "select p.*,fp.id as flash_sales_id,fp.product_id,fp.product_variant_id,fp.price,fp.discounted_price,fp.end_date,fp.start_date,fp.status as sales_status,fs.title as flash_sales_Name,fs.slug as flash_sales_slug,c.name as category_name from `flash_sales_products` fp LEFT JOIN flash_sales fs ON fs.id=fp.flash_sales_id LEFT JOIN product_reviews pr ON pr.id = fp.product_id LEFT JOIN products p ON p.id=fp.product_id JOIN category c ON p.category_id=c.id WHERE fp.status=1 $where order by fp.`id` desc LIMIT $offset,$limit";
    $db->sql($sql);
    $product = $db->getResult();
    $i = 0;

    foreach ($product as $row) {
        $row['product_name'] = $row['name'];
        $row['product_ratings'] = !empty($row['ratings']) ? $row['ratings'] : "";
        $row['other_images'] = json_decode($row['other_images'], 1);
        $row['other_images'] = (empty($row['other_images'])) ? array() : $row['other_images'];
        $row['shipping_delivery'] = (!empty($row['shipping_delivery'])) ? $row['shipping_delivery'] : '';
        $row['size_chart'] = (!empty($row['size_chart'])) ? DOMAIN_URL . $row['size_chart'] : "";

        for ($j = 0; $j < count($row['other_images']); $j++) {
            $row['other_images'][$j] = DOMAIN_URL . $row['other_images'][$j];
        }
        $row['image'] = DOMAIN_URL . $row['image'];
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

        if (!empty($user_id)) {
            $sql = "SELECT id from favorites where product_id = " . $row['id'] . " AND user_id = " . $user_id;
            $db->sql($sql);
            $result = $db->getResult();
            if (!empty($result)) {
                $row['is_favorite'] = true;
            } else {
                $row['is_favorite'] = false;
            }
        } else {
            $row['is_favorite'] = false;
        }

        $product[$i] = $row;
        $sql = "SELECT *,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE pv.id = " . $row['product_variant_id'] . " AND pv.product_id=" . $row['product_id'] . " ORDER BY serve_for";
        $db->sql($sql);
        $variants = $db->getResult();
        for ($k = 0; $k < count($variants); $k++) {
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

            $sql = "SELECT fp.*,fs.title as flash_sales_name FROM flash_sales_products fp LEFT JOIN flash_sales fs ON fs.id=fp.flash_sales_id where fp.product_variant_id= " . $variants[$k]['id'] . " AND  fp.product_id = " . $variants[$k]['product_id'];
            $db->sql($sql);
            $result1 = $db->getResult();
            if (!empty($result1)) {
                $variants[$k]['is_flash_sales'] = "true";
            } else {
                $variants[$k]['is_flash_sales'] = "false";
            }
            $variants[$k]['flash_sales'] = array();
            $temp = array('id' => "", 'flash_sales_id' => "", 'product_id' => "", 'product_variant_id' => "", 'price' => "", 'discounted_price' => "", 'start_date' => "", 'end_date' => "", 'date_created' => "", 'status' => "", 'flash_sales_name' => "");
            $variants[$k]['flash_sales'] = array($temp);
            foreach ($result1 as $rows) {
                if ($variants[$k]['is_flash_sales'] = "true") {
                    $variants[$k]['flash_sales'] = array($rows);
                }
            }
        }

        $product[$i]['variants'] = $variants;
        $i++;
    }

    if (!empty($product)) {
        $response['error'] = false;
        $response['message'] = "Flash Sales Products Retrived Successfully!";
        $response['total'] = $total;
        $response['data'] = $product;
    } else {
        $response['error'] = true;
        $response['message'] = "No products available";
    }
    print_r(json_encode($response));
    return false;
}
if (isset($_POST['get_variants_of_products']) && $_POST['get_variants_of_products'] != '') {
    $product_id = $db->escapeString($_POST['product_id']);
    if (empty($product_id)) {
        echo '<option value="">Select Product Variants</option>';
        return false;
    }
    $sql = "SELECT pv.*,u.short_code FROM product_variant pv LEFT JOIN `unit` u ON u.id = pv.measurement_unit_id WHERE pv.product_id=" . $product_id;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $option) {
        $options = "<option value='" . $option['id'] . "'>" . $option['measurement'] . " " . $option['short_code'] . "</option>";
    }
    echo $options;
}

function between($number, $from, $to)
{
    return $number >= $from && $number <= $to;
}
