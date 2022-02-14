<?php
header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Access-Control-Allow-Origin: *');

include_once('send-email.php');
include_once('../includes/crud.php');
include_once('../includes/custom-functions.php');
include_once('../includes/variables.php');
include_once('verify-token.php');
$db = new Database();
$db->connect();
$db->sql("SET NAMES utf8");
$fn = new custom_functions();

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
1. get-all-data.php
	accesskey:90336
	user_id:413     // {optional}
	
*/
$user_id = (isset($_POST['user_id']) && is_numeric($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";
$limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
$offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;

//categories
$sql = "SELECT * FROM category ORDER BY row_order ASC ";
$db->sql($sql);
$res_categories = $db->getResult();

for ($i = 0; $i < count($res_categories); $i++) {
    $res_categories[$i]['image'] = (!empty($res_categories[$i]['image'])) ? DOMAIN_URL . '' . $res_categories[$i]['image'] : '';
    $res_categories[$i]['web_image'] = (!empty($res_categories[$i]['web_image'])) ? DOMAIN_URL . '' . $res_categories[$i]['web_image'] : '';
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
$response = $product_ids = $category_ids = $section = $variations = $featured_sections = array();
foreach ($result as $row) {
    $product_ids = !empty($row['product_ids']) ? explode(',', $row['product_ids']) : array();
    $category_ids = !empty($row['category_ids']) ? explode(',', $row['category_ids']) : array();

    $section['id'] = $row['id'];
    $section['row_order'] = $row['row_order'];
    $section['title'] = $row['title'];
    $section['short_description'] = $row['short_description'];
    $section['style'] = $row['style'];
    $section['product_type'] = $row['product_type'];

    $sort = "";
    $where = "";
    $group = "";
    if ($row['product_type'] == 'new_added_products') {
        $sql = "SELECT id as product_id FROM `products` WHERE status = 1 ORDER BY product_id DESC";
        $sort .= " ORDER BY p.date_added DESC ";
    } elseif ($row['product_type'] == 'products_on_sale') {
        $sql = "SELECT p.id as product_id FROM `products` p LEFT JOIN product_variant pv ON p.id=pv.product_id WHERE p.status = 1 AND pv.discounted_price > 0 AND pv.price > pv.discounted_price ORDER BY p.id DESC";
        $sort .= " ORDER BY p.id DESC ";
        $where .= " AND pv.discounted_price > 0 AND pv.price > pv.discounted_price";
    } elseif ($row['product_type'] == 'top_rated_products') {
        $sql = "SELECT pr.product_id FROM `product_reviews` pr LEFT JOIN products p ON p.id=pr.product_id WHERE p.status = 1 ORDER BY rate DESC";
        $sort .= " ORDER BY pr.rate DESC ";
    } elseif ($row['product_type'] == 'most_selling_products') {
        $sql = "SELECT p.id as product_id,oi.product_variant_id, COUNT(oi.product_variant_id) AS total FROM order_items oi LEFT JOIN product_variant pv ON oi.product_variant_id = pv.id LEFT JOIN products p ON pv.product_id = p.id WHERE oi.product_variant_id != 0 AND p.id != '' GROUP BY pv.id,p.id ORDER BY total DESC";
        $sort .= " ORDER BY count(oi.product_variant_id) DESC ";
        $where .= " AND oi.product_variant_id != 0 AND p.id != ''";
    } else {
        $product_ids = implode(',', $product_ids);
    }

    if ($row['product_type'] != 'custom_products' && empty($row['product_type'] == '')) {
        $db->sql($sql);
        $product = $db->getResult();
        $rows = $tempRow = array();
        foreach ($product as $row1) {
            $tempRow['product_id'] = $row1['product_id'];
            $rows[] = $tempRow;
        }
        $pro_id = array_column($rows, 'product_id');
        $product_ids = implode(",", $pro_id);
    }
    $group .= $row['product_type'] == 'most_selling_products' ? " GROUP BY pv.id" : " GROUP BY p.id";

    $sql = "SELECT count(id) as total FROM sections";
    $db->sql($sql);
    $total = $db->getResult();

    $sql = "SELECT p.*,p.id as product_id,(SELECT name FROM category c WHERE c.id=p.category_id) as category_name
    FROM `products` p left join product_reviews pr on p.id = pr.product_id left join product_variant pv on p.id = pv.product_id left join order_items oi ON pv.id=oi.product_variant_id WHERE p.status = 1 AND p.id IN ($product_ids) $where $group $sort LIMIT $offset,$limit";

    $db->sql($sql);
    $result1 = $db->getResult();
    $product = array();
    $i = 0;
    foreach ($result1 as $row) {
        $sql = "SELECT *,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE pv.product_id=" . $row['product_id'] . " ORDER BY serve_for ASC";
        $db->sql($sql);
        $variants = $db->getResult();
        $row['other_images'] = json_decode($row['other_images'], 1);
        $row['other_images'] = (empty($row['other_images'])) ? array() : $row['other_images'];
        $row['shipping_delivery'] = (!empty($row['shipping_delivery'])) ? $row['shipping_delivery'] : "";
        $row['made_in'] = (!empty($row['made_in'])) ? $row['made_in'] : "";
        $row['return_status'] = (!empty($row['return_status'])) ? $row['return_status'] : "";
        $row['cancelable_status'] = (!empty($row['cancelable_status'])) ? $row['cancelable_status'] : "";
        $row['till_status'] = (!empty($row['till_status'])) ? $row['till_status'] : "";
        $row['manufacturer'] = (!empty($row['manufacturer'])) ? $row['manufacturer'] : "";
        $row['size_chart'] = (!empty($row['size_chart'])) ? DOMAIN_URL . $row['size_chart'] : "";
        $row['review'] = (!empty($row['review'])) ? $row['review'] : "";
        $row['rate'] = (!empty($row['rate'])) ? $row['rate'] : "";
        $row['image'] = DOMAIN_URL . $row['image'];

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
            $res_tax = $db->getResult();
            foreach ($res_tax as $tax) {
                $row['tax_title'] = $tax['title'];
                $row['tax_percentage'] = $tax['percentage'];
            }
        }

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
            $temp = array('id' => "", 'flash_sales_id' => "", 'product_id' => "", 'product_variant_id' => "", 'price' => "", 'discounted_price' => "", 'start_date' => "", 'end_date' => "", 'date_created' => "", 'status' => "", 'flash_sales_name' => "");
            $variants[$k]['flash_sales'] = array($temp);
            foreach ($result1 as $rows) {
                if ($variants[$k]['is_flash_sales'] = "true") {
                    $variants[$k]['flash_sales'] = array($rows);
                }
            }

            if (!empty($user_id)) {
                $sql = "SELECT id from favorites where product_id = " . $row['id'] . " AND user_id = " . $user_id;
                $db->sql($sql);
                $favorite = $db->getResult();
                if (!empty($favorite)) {
                    $row['is_favorite'] = true;
                } else {
                    $row['is_favorite'] = false;
                }
            } else {
                $row['is_favorite'] = false;
            }
        }
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
$data = $fn->get_settings('categories_settings', true);
$response['error'] = false;
$response['message'] = "Data fetched successfully";
if (!empty($data)) {
    $response['style'] =  $data['cat_style'];
    $response['visible_count'] = $data['max_visible_categories'];
    $response['column_count'] = ($data['cat_style'] == "style_2") ? 0 : $data['max_col_in_single_row'];
} else {
    $response['style'] =  "";
    $response['visible_count'] = 0;
    $response['column_count'] = 0;
}

// Flash sales products
$sql = "SELECT fs.* FROM `flash_sales` fs WHERE fs.status = 1 order by id ASC";
$db->sql($sql);
$result = $db->getResult();

$flash_sales = $variations = $flash_sales_section = array();
foreach ($result as $res) {
    $flash_sales['id'] = $res['id'];
    $flash_sales['title'] = $res['title'];
    $flash_sales['slug'] = $res['slug'];
    $flash_sales['short_description'] = $res['short_description'];
    $flash_sales['status'] = $res['status'];

    $sql_result = "SELECT product_id FROM flash_sales_products WHERE flash_sales_id IN (" . $res['id'] . ")";
    $db->sql($sql_result);
    $pro = $db->getResult();

    $b = array_column($pro, 'product_id');
    $a = implode(',', $b);
    if (!empty($pro)) {
        $sql = "select p.*,fp.id as flash_sales_id,fp.product_id,fp.product_variant_id,fp.price,fp.discounted_price,fp.end_date,fp.start_date,fp.status as sales_status,fs.title,fs.title as flash_sales_Name,fs.slug as flash_sales_slug,c.name as category_name from `flash_sales_products` fp LEFT JOIN flash_sales fs ON fs.id=fp.flash_sales_id LEFT JOIN product_reviews pr ON pr.id = fp.product_id LEFT JOIN products p ON p.id=fp.product_id JOIN category c ON p.category_id=c.id WHERE fp.status=1 AND fp.flash_sales_id = " . $flash_sales['id'] . " AND p.id IN ($a) order by fp.`id` DESC LIMIT $offset,$limit";
        $db->sql($sql);
        $result1 = $db->getResult();
        $product = array();
        $i = 0;
        foreach ($result1 as $row) {
            $sql = "SELECT *,(SELECT short_code FROM unit u WHERE u.id=pv.measurement_unit_id) as measurement_unit_name,(SELECT short_code FROM unit u WHERE u.id=pv.stock_unit_id) as stock_unit_name FROM product_variant pv WHERE pv.id = " . $row['product_variant_id'] . " AND pv.product_id=" . $row['product_id'] . " ORDER BY serve_for ASC";
            $db->sql($sql);
            $variants = $db->getResult();

            $row['other_images'] = json_decode($row['other_images'], 1);
            $row['other_images'] = (empty($row['other_images'])) ? array() : $row['other_images'];
            $row['shipping_delivery'] = (!empty($row['shipping_delivery'])) ? $row['shipping_delivery'] : "";
            $row['made_in'] = (!empty($row['made_in'])) ? $row['made_in'] : "";
            $row['return_status'] = (!empty($row['return_status'])) ? $row['return_status'] : "";
            $row['cancelable_status'] = (!empty($row['cancelable_status'])) ? $row['cancelable_status'] : "";
            $row['till_status'] = (!empty($row['till_status'])) ? $row['till_status'] : "";
            $row['manufacturer'] = (!empty($row['manufacturer'])) ? $row['manufacturer'] : "";
            $row['size_chart'] = (!empty($row['size_chart'])) ? DOMAIN_URL . $row['size_chart'] : "";
            $row['review'] = (!empty($row['review'])) ? $row['review'] : "";
            $row['rate'] = (!empty($row['rate'])) ? $row['rate'] : "";
            $row['image'] = DOMAIN_URL . $row['image'];

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
                $res_tax = $db->getResult();
                foreach ($res_tax as $tax) {
                    $row['tax_title'] = $tax['title'];
                    $row['tax_percentage'] = $tax['percentage'];
                }
            }

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

                $sql = "SELECT fp.*,fs.title as flash_sales_name FROM flash_sales_products fp LEFT JOIN flash_sales fs ON fs.id=fp.flash_sales_id where fp.product_variant_id= " . $variants[$k]['id'] . " AND  fp.product_id = " . $variants[$k]['product_id'] . " AND fp.flash_sales_id = " . $flash_sales['id'] . " GROUP BY fp.id";
                $db->sql($sql);
                $result1 = $db->getResult();
                if (!empty($result1)) {
                    $variants[$k]['is_flash_sales'] = "true";
                } else {
                    $variants[$k]['is_flash_sales'] = "false";
                }
                $variants[$k]['flash_sales'] = array();
                $temp_data = array('id' => "", 'flash_sales_id' => "", 'product_id' => "", 'product_variant_id' => "", 'price' => "", 'discounted_price' => "", 'start_date' => "", 'end_date' => "", 'date_created' => "", 'status' => "", 'flash_sales_name' => "");
                $variants[$k]['flash_sales'] = array($temp_data);
                foreach ($result1 as $sales_result) {
                    if ($variants[$k]['is_flash_sales'] = "true") {
                        $variants[$k]['flash_sales'] = array($sales_result);
                    }
                }

                // $sql = "SELECT fp.* FROM flash_sales_products fp where fp.product_variant_id= " . $variants[$k]['id'] . " AND  fp.product_id = " . $variants[$k]['product_id'];
                // $db->sql($sql);
                // $result1 = $db->getResult();
                // if (!empty($result1)) {
                //     $variants[$k]['is_flash_sales'] = "true";
                // } else {
                //     $variants[$k]['is_flash_sales'] = "false";
                // }
                // $variants[$k]['flash_sales'] = array();
                // $temp_data = array('id' => "", 'flash_sales_id' => "", 'product_id' => "", 'price' => "", 'discounted_price' => "", 'start_date' => "", 'end_date' => "", 'date_created' => "", 'status' => "");
                // $variants[$k]['flash_sales'] = array($temp_data);
                // foreach ($result1 as $sales_result) {
                //     if ($variants[$k]['is_flash_sales'] = "true") {
                //         $variants[$k]['flash_sales'] = array($sales_result);
                //     }
                // }

                if (!empty($user_id)) {
                    $sql = "SELECT id from favorites where product_id = " . $row['id'] . " AND user_id = " . $user_id;
                    $db->sql($sql);
                    $favorite = $db->getResult();
                    if (!empty($favorite)) {
                        $row['is_favorite'] = true;
                    } else {
                        $row['is_favorite'] = false;
                    }
                } else {
                    $row['is_favorite'] = false;
                }
            }
            $product[$i] = $row;
            $product[$i]['variants'] = $variants;
            $i++;
        }
        $flash_sales['products'] = $product;
        $flash_sales_section[] = $flash_sales;
        unset($flash_sales['products']);
    }
}

$response['categories'] = (!empty($res_categories)) ? $res_categories : [];
$response['slider_images'] = (!empty($slider_images)) ? $slider_images : [];
$response['sections'] = (!empty($featured_sections)) ? $featured_sections : [];
$response['offer_images'] = (!empty($offer_images)) ? $offer_images : [];
$response['flash_sales'] = (!empty($flash_sales_section)) ? $flash_sales_section : [];
print_r(json_encode($response));
