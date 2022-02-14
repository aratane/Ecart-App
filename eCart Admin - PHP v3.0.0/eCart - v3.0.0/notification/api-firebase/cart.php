<?php
session_start();
include '../includes/crud.php';
include_once('../includes/variables.php');
include_once('../includes/custom-functions.php');
header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Access-Control-Allow-Origin: *');
$fn = new custom_functions;
include_once('verify-token.php');
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
1. add_to_cart
2. add_multiple_items
3. remove_from_cart
4. get_user_cart
-------------------------------------------
-------------------------------------------
*/

if (!isset($_POST['accesskey'])) {
    $response['error'] = true;
    $response['message'] = "Access key is invalid or not passed!";
    print_r(json_encode($response));
    return false;
}

$accesskey = $db->escapeString($fn->xss_clean_array($_POST['accesskey']));
if ($access_key != $accesskey) {
    $response['error'] = true;
    $response['message'] = "invalid accesskey!";
    print_r(json_encode($response));
    return false;
}

if (!verify_token()) {
    return false;
}

if ((isset($_POST['add_to_cart'])) && ($_POST['add_to_cart'] == 1)) {
    /*
    1.add_to_cart
        accesskey:90336
        add_to_cart:1
        user_id:3
        product_id:1
        product_variant_id:4
        qty:2
    */
    $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['user_id'])) : "";
    $product_id = (isset($_POST['product_id']) && !empty($_POST['product_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['product_id'])) : "";
    $product_variant_id  = (isset($_POST['product_variant_id']) && !empty($_POST['product_variant_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['product_variant_id'])) : "";
    $qty = (isset($_POST['qty']) && !empty($_POST['qty'])) ? $db->escapeString($fn->xss_clean_array($_POST['qty'])) : "";
    if (!empty($user_id) && !empty($product_id)) {
        if (!empty($product_variant_id)) {
            if ($fn->is_item_available($product_id, $product_variant_id)) {
                $sql = "select serve_for,stock from product_variant where id = " . $product_variant_id;
                $db->sql($sql);
                $stock = $db->getResult();
                if ($stock[0]['stock'] > 0 && $stock[0]['serve_for'] == 'Available') {
                    if ($fn->is_item_available_in_user_cart($user_id, $product_variant_id)) {
                        if (empty($qty) || $qty == 0) {
                            $sql = "DELETE FROM cart WHERE user_id = $user_id AND product_variant_id = $product_variant_id";
                            if ($db->sql($sql)) {
                                $response['error'] = false;
                                $response['message'] = 'Item removed user\'s cart due to 0 quantity';
                            } else {
                                $response['error'] = true;
                                $response['message'] = 'Something went wrong please try again!';
                            }
                            print_r(json_encode($response));
                            return false;
                        }
                        $data = array(
                            'qty' => $qty
                        );
                        if ($db->update('cart', $data, 'user_id=' . $user_id . ' AND product_variant_id=' . $product_variant_id)) {
                            $response['error'] = false;
                            $response['message'] = 'Item updated in user\'s cart successfully';
                        } else {
                            $response['error'] = true;
                            $response['message'] = 'Something went wrong please try again!';
                        }
                    } else {
                        $data = array(
                            'user_id' => $user_id,
                            'product_id' => $product_id,
                            'product_variant_id' => $product_variant_id,
                            'qty' => $qty
                        );
                        if ($db->insert('cart', $data)) {
                            $response['error'] = false;
                            $response['message'] = 'Item added to user\'s cart successfully';
                        } else {
                            $response['error'] = true;
                            $response['message'] = 'Something went wrong please try again!';
                        }
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Opps stock is not available!';
                }
            } else {
                $response['error'] = true;
                $response['message'] = 'No such item available!';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Please choose atleast one item!';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }
    print_r(json_encode($response));
    return false;
}

if ((isset($_POST['add_multiple_items'])) && ($_POST['add_multiple_items'] == 1)) {
    /*
    2.add_multiple_items
        accesskey:90336
        add_multiple_items:1
        user_id:3
        product_variant_id:203,198,202
        qty:1,2,1
    */
    $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['user_id'])) : "";
    $product_variant_id  = (isset($_POST['product_variant_id']) && !empty($_POST['product_variant_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['product_variant_id'])) : "";
    $qty = (isset($_POST['qty']) && !empty($_POST['qty'])) ? $db->escapeString($fn->xss_clean_array($_POST['qty'])) : "";
    $empty_qty = $is_variant =  $is_product = false;
    $empty_qty_1 = false;
    $item_exists = false;
    if (!empty($user_id)) {
        if (!empty($product_variant_id)) {
            $product_variant_id = explode(",", $product_variant_id);
            $qty = explode(",", $qty);
            for ($i = 0; $i < count($product_variant_id); $i++) {
                if ($fn->get_product_id_by_variant_id($product_variant_id[$i])) {
                    $product_id = $fn->get_product_id_by_variant_id($product_variant_id[$i]);
                    if ($fn->is_item_available($product_id, $product_variant_id[$i])) {
                        $item_exists = true;
                        if ($fn->is_item_available_in_user_cart($user_id, $product_variant_id[$i])) {
                            if (empty($qty[$i]) || $qty[$i] == 0) {
                                $empty_qty = true;
                                $sql = "DELETE FROM cart WHERE user_id = $user_id AND product_variant_id = $product_variant_id[$i]";
                                $db->sql($sql);
                            } else {
                                $data = array(
                                    'qty' => $qty[$i]
                                );
                                $db->update('cart', $data, 'user_id=' . $user_id . ' AND product_variant_id=' . $product_variant_id[$i]);
                            }
                        } else {
                            if (!empty($qty[$i]) && $qty[$i] != 0) {
                                $data = array(
                                    'user_id' => $user_id,
                                    'product_id' => $product_id,
                                    'product_variant_id' => $product_variant_id[$i],
                                    'qty' => $qty[$i]
                                );
                                $db->insert('cart', $data);
                            } else {
                                $empty_qty_1 = true;
                            }
                        }
                    } else {
                        $is_variant = true;
                    }
                } else {
                    $is_product = true;
                }
            }
            $response['error'] = false;
            $response['message'] = $item_exists = true ? 'Cart Updated successfully!' : 'Items Added Successfully';
            $response['message'] .= $empty_qty == true ? 'Some items removed due to 0 quantity' : '';
            $response['message'] .= $empty_qty_1 == true ? 'Some items not added due to 0 quantity' : '';
            $response['message'] .= $is_variant == true ? 'Some items not present in product list now' : '';
            $response['message'] .= $is_product == true ? 'Some items not present in product list now' : '';
        } else {
            $response['error'] = true;
            $response['message'] = 'Please choose atleast one item!';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }
    print_r(json_encode($response));
    return false;
}

if ((isset($_POST['remove_from_cart'])) && ($_POST['remove_from_cart'] == 1)) {
    /*
    3.remove_from_cart
        accesskey:90336
        remove_from_cart:1
        user_id:3
        product_variant_id:4    // {optional}
    */
    $user_id  = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['user_id'])) : "";
    $product_variant_id = (isset($_POST['product_variant_id']) && !empty($_POST['product_variant_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['product_variant_id'])) : "";
    if (!empty($user_id)) {
        if ($fn->is_item_available_in_user_cart($user_id, $product_variant_id)) {
            $sql = "DELETE FROM cart WHERE user_id=" . $user_id;
            $sql .= !empty($product_variant_id) ? " AND product_variant_id=" . $product_variant_id : "";
            if ($db->sql($sql) && !empty($product_variant_id)) {
                $response['error'] = false;
                $response['message'] = 'Item removed from user\'s cart successfully';
            } elseif ($db->sql($sql) && empty($product_variant_id)) {
                $response['error'] = false;
                $response['message'] = 'All items removed from user\'s cart successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Something went wrong please try again!';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Item not found in user\'s cart!';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }

    print_r(json_encode($response));
    return false;
}

if ((isset($_POST['get_user_cart'])) && ($_POST['get_user_cart'] == 1)) {
    /*
    4.get_user_cart
        accesskey:90336
        get_user_cart:1
        user_id:3
    */

    $ready_to_add = false;
    $user_id  = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['user_id'])) : "";
    if (!empty($user_id)) {
        if ($fn->is_item_available_in_user_cart($user_id)) {
            $sql = "SELECT count(id) as total from cart where user_id=" . $user_id;
            $db->sql($sql);
            $total = $db->getResult();
            $sql = "select * from cart where user_id=" . $user_id . " ORDER BY date_created DESC ";
            $db->sql($sql);
            $res = $db->getResult();
            $i = 0;
            $j = 0;
            $total_amount = 0;
            $sql = "select qty,product_variant_id from cart where user_id=" . $user_id;
            $db->sql($sql);
            $res_1 = $db->getResult();
            foreach ($res_1 as $row_1) {
                $sql = "select price,discounted_price from product_variant where id=" . $row_1['product_variant_id'];
                $db->sql($sql);
                $result_1 = $db->getResult();
                $price = $result_1[0]['discounted_price'] == 0 ? $result_1[0]['price'] * $row_1['qty'] : $result_1[0]['discounted_price'] * $row_1['qty'];
                $total_amount += $price;
            }
            foreach ($res as $row) {

                $sql = "select pv.*,p.shipping_delivery,p.name,p.slug,p.image,p.other_images,p.size_chart,p.ratings,p.number_of_ratings,pr.review,t.percentage as tax_percentage,t.title as tax_title,pv.measurement,(select short_code from unit u where u.id=pv.measurement_unit_id) as unit from product_variant pv left join products p on p.id=pv.product_id left join taxes t on t.id=p.tax_id left join product_reviews pr on p.id = pr.product_id  where pv.id=" . $row['product_variant_id'];
                $db->sql($sql);
                $res[$i]['item'] = $db->getResult();

                for ($k = 0; $k < count($res[$i]['item']); $k++) {
                    $res[$i]['item'][$k]['other_images'] = json_decode($res[$i]['item'][$k]['other_images']);
                    $res[$i]['item'][$k]['other_images'] = empty($res[$i]['item'][$k]['other_images']) ? array() : $res[$i]['item'][$k]['other_images'];
                    $res[$i]['item'][$k]['tax_percentage'] = empty($res[$i]['item'][$k]['tax_percentage']) ? "0" : $res[$i]['item'][$k]['tax_percentage'];
                    $res[$i]['item'][$k]['tax_title'] = empty($res[$i]['item'][$k]['tax_title']) ? "" : $res[$i]['item'][$k]['tax_title'];
                    $res[$i]['item'][$k]['shipping_delivery'] = empty($res[$i]['item'][$k]['shipping_delivery']) ? "" : $res[$i]['item'][$k]['shipping_delivery'];
                    $res[$i]['item'][$k]['size_chart'] = empty($res[$i]['item'][$k]['size_chart']) ? "" : $res[$i]['item'][$k]['size_chart'];
                    $res[$i]['item'][$k]['number_of_ratings'] = !empty($res[$k]['item'][$j]['number_of_ratings']) ? $res[$i]['item'][$k]['number_of_ratings'] : "";
                    $res[$i]['item'][$k]['ratings'] = !empty($res[$i]['item'][$k]['ratings']) ?  $res[$i]['item'][$k]['ratings'] : "";
                    $res[$i]['item'][$k]['review'] = !empty($res[$i]['item'][$k]['review']) ?  $res[$i]['item'][$k]['review'] : "";
                    if ($res[$i]['item'][$k]['stock'] <= 0 || $res[$i]['item'][$k]['serve_for'] == 'Sold Out') {
                        $res[$i]['item'][$k]['isAvailable'] = false;
                        $ready_to_add = true;
                    } else {
                        $res[$i]['item'][$k]['isAvailable'] = true;
                    }
                    for ($l = 0; $l < count($res[$i]['item'][$k]['other_images']); $l++) {
                        $other_images = DOMAIN_URL . $res[$i]['item'][$k]['other_images'][$l];
                        $res[$i]['item'][$k]['other_images'][$l] = $other_images;
                    }
                }
                for ($j = 0; $j < count($res[$i]['item']); $j++) {
                    $res[$i]['item'][$j]['image'] = !empty($res[$i]['item'][$j]['image']) ? DOMAIN_URL . $res[$i]['item'][$j]['image'] : "";
                    $res[$i]['item'][$j]['size_chart'] = !empty($res[$i]['item'][$j]['size_chart']) ? DOMAIN_URL . $res[$i]['item'][$j]['size_chart'] : "";
                }
                $i++;
            }
            if (!empty($res)) {
                $response['error'] = false;
                $response['total'] = $total[0]['total'];
                $response['ready_to_cart'] = $ready_to_add;
                $response['total_amount'] = number_format($total_amount, 2, '.', '');
                $response['message'] = 'Cart Data Retrived Successfully!';
                $response['data'] = array_values($res);
            } else {
                $response['error'] = true;
                $response['message'] = "No item(s) found in user\'s cart!";
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'No item(s) found in user\'s cart!';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }
    print_r(json_encode($response));
    return false;
}
