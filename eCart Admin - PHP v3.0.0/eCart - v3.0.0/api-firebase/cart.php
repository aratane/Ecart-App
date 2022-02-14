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
5. add_save_for_later
6. remove_save_for_later
7. get_save_for_later
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

    $sql = "SELECT * FROM users where id = $user_id";
    $db->sql($sql);
    $res1 = $db->getResult();
    if ($res1[0]['status'] == 1) {
        if (!empty($user_id) && !empty($product_id)) {
            if (!empty($product_variant_id)) {
                if ($fn->is_item_available($product_id, $product_variant_id)) {
                    $sql = "select serve_for,stock from product_variant where id = " . $product_variant_id;
                    $db->sql($sql);
                    $stock = $db->getResult();
                    if ($stock[0]['stock'] > 0 && $stock[0]['serve_for'] == 'Available') {
                        if ($fn->is_item_available_in_save_for_later($user_id, $product_variant_id)) {
                            $data = array(
                                'save_for_later' => 0
                            );
                            $db->update('cart', $data, 'user_id=' . $user_id . ' AND product_variant_id=' . $product_variant_id);
                        }
                        if ($fn->is_item_available_in_user_cart($user_id, $product_variant_id)) {
                            if (empty($qty) || $qty == 0) {
                                $sql = "DELETE FROM cart WHERE user_id = $user_id AND product_variant_id = $product_variant_id";
                                if ($db->sql($sql)) {
                                    $response['error'] = false;
                                    $response['message'] = 'Item removed user cart due to 0 quantity';
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
                                $response['message'] = 'Item updated in user cart successfully';
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
                                $response['message'] = 'Item added to user cart successfully';
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
    } else {
        $response['error'] = true;
        $response['message'] = 'Your Account is De-active ask on Customer Support!';
    }
    print_r(json_encode($response));
    return false;
}

if (((isset($_POST['add_multiple_items'])) && ($_POST['add_multiple_items'] == 1)) || ((isset($_POST['save_for_later_items'])) && ($_POST['save_for_later_items'] == 1))) {
    /*
    2.add_multiple_items
        accesskey:90336
        add_multiple_items OR save_for_later_items:1
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
    $item_exists_1 = false;
    $item_exists_2 = false;

    $sql = "SELECT * FROM users where id = $user_id";
    $db->sql($sql);
    $res1 = $db->getResult();
    if ($res1[0]['status'] == 1) {
        if (!empty($user_id)) {
            if (!empty($product_variant_id)) {
                $product_variant_id = explode(",", $product_variant_id);
                $qty = explode(",", $qty);
                for ($i = 0; $i < count($product_variant_id); $i++) {
                    if ((isset($_POST['add_multiple_items'])) && ($_POST['add_multiple_items'] == 1)) {
                        if ($fn->get_product_id_by_variant_id($product_variant_id[$i])) {
                            $product_id = $fn->get_product_id_by_variant_id($product_variant_id[$i]);
                            if ($fn->is_item_available($product_id, $product_variant_id[$i])) {
                                if ($fn->is_item_available_in_save_for_later($user_id, $product_variant_id[$i])) {
                                    $data = array(
                                        'save_for_later' => 0
                                    );
                                    $db->update('cart', $data, 'user_id=' . $user_id . ' AND product_variant_id=' . $product_variant_id[$i]);
                                }
                                if ($fn->is_item_available_in_user_cart($user_id, $product_variant_id[$i])) {
                                    $item_exists = true;
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
                    } else if ((isset($_POST['save_for_later_items'])) && ($_POST['save_for_later_items'] == 1)) {
                        if ($fn->is_item_available_in_user_cart($user_id, $product_variant_id[$i])) {
                            $item_exists_1 = true;
                            $data = array(
                                'save_for_later' => 1
                            );
                            $db->update('cart', $data, 'user_id=' . $user_id . ' AND product_variant_id=' . $product_variant_id[$i]);
                        } else {
                            $item_exists_2 = true;
                        }
                    }
                }
                $response['error'] = false;
                $response['message'] = $item_exists == true ? 'Cart Updated successfully!' : 'Cart Added Successfully';
                $response['message'] .= $item_exists_1 == true ? 'Item add to save for later!' : '';
                $response['message'] .= $item_exists_2 == true ? 'Item not add into cart!' : '';
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
    } else {
        $response['error'] = true;
        $response['message'] = 'Your Account is De-active ask on Customer Support!';
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
            $sql = "DELETE FROM cart WHERE user_id=" . $user_id . " AND save_for_later = 0";
            $sql .= !empty($product_variant_id) ? " AND product_variant_id=" . $product_variant_id : "";
            if ($db->sql($sql) && !empty($product_variant_id)) {
                $response['error'] = false;
                $response['message'] = 'Item removed from user cart successfully';
            } elseif ($db->sql($sql) && empty($product_variant_id)) {
                $response['error'] = false;
                $response['message'] = 'All items removed from user cart successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Something went wrong please try again!';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Item not found in user cart!';
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
            $i = 0;
            $j = 0;
            $x = 0;
            $total_amount = 0;

            $sql1 = "SELECT c.*,p.status FROM `cart`c LEFT JOIN products p ON p.id = c.product_id WHERE c.user_id = " . $user_id . " AND p.status = 0";
            $db->sql($sql1);
            $pro_result = $db->getResult();
            if (!empty($pro_result)) {
                foreach ($pro_result as $res) {
                    $sql = "DELETE FROM cart WHERE user_id = " . $user_id . " AND product_variant_id=" . $res['product_variant_id'] . "";
                    $db->sql($sql);
                    $results = $db->getResult();
                }
            }

            $sql = "SELECT count(id) as total from cart where save_for_later = 0 AND user_id=" . $user_id;
            $db->sql($sql);
            $total = $db->getResult();

            $sql = "select * from cart where save_for_later = 0 AND user_id=" . $user_id . " ORDER BY date_created DESC ";
            $db->sql($sql);
            $res = $db->getResult();

            $sql = "select qty,product_variant_id from cart where user_id=" . $user_id;
            $db->sql($sql);
            $res_1 = $db->getResult();
            foreach ($res_1 as $row_1) {
                $sql = "select price,discounted_price from product_variant where id=" . $row_1['product_variant_id'];
                $db->sql($sql);
                $result_1 = $db->getResult();
                foreach ($result_1 as $result_2) {
                    $price = $result_2['discounted_price'] == 0 ? $result_2['price'] * $row_1['qty'] : $result_2['discounted_price'] * $row_1['qty'];
                }
                $total_amount += $price;
            }
            foreach ($res as $row) {
                $sql = "select pv.*,p.shipping_delivery,p.name,p.is_cod_allowed,p.slug,p.image,p.other_images,p.size_chart,p.ratings,p.number_of_ratings,pr.review,t.percentage as tax_percentage,t.title as tax_title,pv.measurement,(select short_code from unit u where u.id=pv.measurement_unit_id) as unit from product_variant pv left join products p on p.id=pv.product_id left join taxes t on t.id=p.tax_id left join product_reviews pr on p.id = pr.product_id  where pv.id=" . $row['product_variant_id'] . " GROUP BY pv.id";
                $db->sql($sql);
                $res[$i]['item'] = $db->getResult();

                for ($k = 0; $k < count($res[$i]['item']); $k++) {
                    $sql_result = "SELECT * FROM flash_sales_products WHERE status = 1 AND product_id = " . $res[$i]['item'][$k]['product_id'] . " AND product_variant_id = " . $res[$i]['item'][$k]['id'] . " ";
                    $db->sql($sql_result);
                    $res1 = $db->getResult();
                    $res[$i]['item'][$k]['other_images'] = json_decode($res[$i]['item'][$k]['other_images']);
                    $res[$i]['item'][$k]['other_images'] = empty($res[$i]['item'][$k]['other_images']) ? array() : $res[$i]['item'][$k]['other_images'];
                    $res[$i]['item'][$k]['tax_percentage'] = empty($res[$i]['item'][$k]['tax_percentage']) ? "0" : $res[$i]['item'][$k]['tax_percentage'];
                    $res[$i]['item'][$k]['is_cod_allowed'] = empty($res[$i]['item'][$k]['is_cod_allowed']) ? "0" : $res[$i]['item'][$k]['is_cod_allowed'];
                    $res[$i]['item'][$k]['tax_title'] = empty($res[$i]['item'][$k]['tax_title']) ? "" : $res[$i]['item'][$k]['tax_title'];
                    $res[$i]['item'][$k]['shipping_delivery'] = empty($res[$i]['item'][$k]['shipping_delivery']) ? "" : $res[$i]['item'][$k]['shipping_delivery'];
                    $res[$i]['item'][$k]['size_chart'] = empty($res[$i]['item'][$k]['size_chart']) ? "" : $res[$i]['item'][$k]['size_chart'];
                    $res[$i]['item'][$k]['number_of_ratings'] = !empty($res[$k]['item'][$j]['number_of_ratings']) ? $res[$i]['item'][$k]['number_of_ratings'] : "";
                    $res[$i]['item'][$k]['ratings'] = !empty($res[$i]['item'][$k]['ratings']) ?  $res[$i]['item'][$k]['ratings'] : "";
                    $res[$i]['item'][$k]['review'] = !empty($res[$i]['item'][$k]['review']) ?  $res[$i]['item'][$k]['review'] : "";
                    $res[$i]['item'][$k]['price'] = empty($res1) ?  $res[$i]['item'][$k]['price'] : $res1[0]['price'];
                    $res[$i]['item'][$k]['discounted_price'] = empty($res1) ?  $res[$i]['item'][$k]['discounted_price'] : $res1[0]['discounted_price'];
                    if ($res[$i]['item'][$k]['serve_for'] == 'Sold Out') {
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

            $sql = "select * from cart where save_for_later = 1 AND user_id=" . $user_id . " ORDER BY date_created DESC ";
            $db->sql($sql);
            $result = $db->getResult();

            $sql = "select qty,product_variant_id from cart where save_for_later = 1 AND user_id=" . $user_id;
            $db->sql($sql);
            $res1 = $db->getResult();

            foreach ($res1 as $row1) {
                $sql = "select price,discounted_price from product_variant where id=" . $row1['product_variant_id'];
                $db->sql($sql);
                $result1 = $db->getResult();
                foreach ($result1 as $result2) {
                    $price = $result2['discounted_price'] == 0 ? $result2['price'] * $row_1['qty'] : $result2['discounted_price'] * $row1['qty'];
                }
                $total_amount += $price;
            }

            foreach ($result as $rows) {
                $sql = "select pv.*,p.shipping_delivery,p.name,p.is_cod_allowed,p.slug,p.image,p.other_images,p.size_chart,p.ratings,p.number_of_ratings,pr.review,t.percentage as tax_percentage,t.title as tax_title,pv.measurement,(select short_code from unit u where u.id=pv.measurement_unit_id) as unit from product_variant pv left join products p on p.id=pv.product_id left join taxes t on t.id=p.tax_id left join product_reviews pr on p.id = pr.product_id  where pv.id=" . $rows['product_variant_id'] . " GROUP BY pv.id";
                $db->sql($sql);
                $result[$x]['item'] = $db->getResult();

                for ($z = 0; $z < count($result[$x]['item']); $z++) {
                    $sql_result = "SELECT * FROM flash_sales_products WHERE status = 1 AND product_id = " . $result[$x]['item'][$z]['product_id'] . " AND product_variant_id = " . $result[$x]['item'][$z]['id'] . " ";
                    $db->sql($sql_result);
                    $res1 = $db->getResult();

                    $result[$x]['item'][$z]['other_images'] = json_decode($result[$x]['item'][$z]['other_images']);
                    $result[$x]['item'][$z]['other_images'] = empty($result[$x]['item'][$z]['other_images']) ? array() : $result[$x]['item'][$z]['other_images'];
                    $result[$x]['item'][$z]['tax_percentage'] = empty($result[$x]['item'][$z]['tax_percentage']) ? "0" : $result[$x]['item'][$z]['tax_percentage'];
                    $result[$x]['item'][$z]['is_cod_allowed'] = empty($result[$x]['item'][$z]['is_cod_allowed']) ? "0" : $result[$x]['item'][$z]['is_cod_allowed'];
                    $result[$x]['item'][$z]['tax_title'] = empty($result[$x]['item'][$z]['tax_title']) ? "" : $result[$x]['item'][$z]['tax_title'];
                    $result[$x]['item'][$z]['shipping_delivery'] = empty($result[$x]['item'][$z]['shipping_delivery']) ? "" : $result[$x]['item'][$z]['shipping_delivery'];
                    $result[$x]['item'][$z]['size_chart'] = empty($result[$x]['item'][$z]['size_chart']) ? "" : $result[$x]['item'][$z]['size_chart'];
                    $result[$x]['item'][$z]['number_of_ratings'] = !empty($result[$x]['item'][$z]['number_of_ratings']) ? $result[$x]['item'][$z]['number_of_ratings'] : "";
                    $result[$x]['item'][$z]['ratings'] = !empty($result[$x]['item'][$z]['ratings']) ?  $result[$x]['item'][$z]['ratings'] : "";
                    $result[$x]['item'][$z]['review'] = !empty($result[$x]['item'][$z]['review']) ?  $result[$x]['item'][$z]['review'] : "";
                    $result[$x]['item'][$z]['price'] = empty($res1) ?  $result[$x]['item'][$z]['price'] : $res1[0]['price'];
                    $result[$x]['item'][$z]['discounted_price'] = empty($res1) ?  $result[$x]['item'][$z]['discounted_price'] : $res1[0]['discounted_price'];

                    if ($result[$x]['item'][$z]['serve_for'] == 'Sold Out') {
                        $result[$x]['item'][$z]['isAvailable'] = false;
                        $ready_to_add = true;
                    } else {
                        $result[$x]['item'][$z]['isAvailable'] = true;
                    }

                    for ($y = 0; $y < count($result[$x]['item'][$z]['other_images']); $y++) {
                        $other_images = DOMAIN_URL . $result[$x]['item'][$z]['other_images'][$y];
                        $result[$x]['item'][$z]['other_images'][$y] = $other_images;
                    }
                }
                for ($j = 0; $j < count($result[$x]['item']); $j++) {
                    $result[$x]['item'][$j]['image'] = !empty($result[$x]['item'][$j]['image']) ? DOMAIN_URL . $result[$x]['item'][$j]['image'] : "";
                    $result[$x]['item'][$j]['size_chart'] = !empty($result[$x]['item'][$j]['size_chart']) ? DOMAIN_URL . $result[$x]['item'][$j]['size_chart'] : "";
                }
                $x++;
            }

            if (!empty($res) || !empty($result)) {
                $response['error'] = false;
                $response['total'] = $total[0]['total'];
                $response['ready_to_cart'] = $ready_to_add;
                $response['total_amount'] = number_format($total_amount, 2, '.', '');
                $response['message'] = 'Cart Data Retrived Successfully!';
                $response['data'] = array_values($res);
                $response['save_for_later'] = array_values($result);
            } else {
                $response['error'] = true;
                $response['message'] = "No item(s) found in users cart!";
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'No item(s) found in user cart!';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }
    print_r(json_encode($response));
    return false;
}

if ((isset($_POST['add_save_for_later'])) && ($_POST['add_save_for_later'] == 1)) {
    /*
    5.add_save_for_later
        accesskey:90336
        add_save_for_later:1
        user_id:221
        product_variant_id:462
    */

    if (empty($_POST['user_id']) || empty($_POST['product_variant_id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
        print_r(json_encode($response));
        return false;
    }
    $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['user_id'])) : "";
    $product_variant_id  = (isset($_POST['product_variant_id']) && !empty($_POST['product_variant_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['product_variant_id'])) : "";
    if ($fn->is_item_available_in_user_cart($user_id, $product_variant_id)) {
        $sql1 = "UPDATE cart SET save_for_later = 1 WHERE user_id = $user_id AND product_variant_id = $product_variant_id";

        if ($db->sql($sql1)) {
            $x = 0;
            $total_amount = 0;

            $sql = "select * from cart where save_for_later = 1 AND user_id=" . $user_id . " AND product_variant_id = " . $product_variant_id . "";
            $db->sql($sql);
            $result = $db->getResult();

            $sql = "select qty,product_variant_id from cart where save_for_later = 1 AND user_id=" . $user_id;
            $db->sql($sql);
            $res1 = $db->getResult();

            foreach ($res1 as $row1) {
                $sql = "select price,discounted_price from product_variant where id=" . $row1['product_variant_id'];
                $db->sql($sql);
                $result1 = $db->getResult();
                foreach ($result1 as $result2) {
                    $price = $result2['discounted_price'] == 0 ? $result2['price'] * $row_1['qty'] : $result2['discounted_price'] * $row1['qty'];
                }
                $total_amount += $price;
            }

            foreach ($result as $rows) {
                $sql = "select pv.*,p.shipping_delivery,p.name,p.is_cod_allowed,p.slug,p.image,p.other_images,p.size_chart,p.ratings,p.number_of_ratings,pr.review,t.percentage as tax_percentage,t.title as tax_title,pv.measurement,(select short_code from unit u where u.id=pv.measurement_unit_id) as unit from product_variant pv left join products p on p.id=pv.product_id left join taxes t on t.id=p.tax_id left join product_reviews pr on p.id = pr.product_id  where pv.id=" . $rows['product_variant_id'] . " GROUP BY pv.id";
                $db->sql($sql);
                $result[$x]['item'] = $db->getResult();

                for ($z = 0; $z < count($result[$x]['item']); $z++) {
                    $sql_result = "SELECT * FROM flash_sales_products WHERE status = 1 AND product_id = " . $result[$x]['item'][$z]['product_id'] . " AND product_variant_id = " . $result[$x]['item'][$z]['id'] . " ";
                    $db->sql($sql_result);
                    $res1 = $db->getResult();

                    $result[$x]['item'][$z]['other_images'] = json_decode($result[$x]['item'][$z]['other_images']);
                    $result[$x]['item'][$z]['other_images'] = empty($result[$x]['item'][$z]['other_images']) ? array() : $result[$x]['item'][$z]['other_images'];
                    $result[$x]['item'][$z]['tax_percentage'] = empty($result[$x]['item'][$z]['tax_percentage']) ? "0" : $result[$x]['item'][$z]['tax_percentage'];
                    $result[$x]['item'][$z]['is_cod_allowed'] = empty($result[$x]['item'][$z]['is_cod_allowed']) ? "0" : $result[$x]['item'][$z]['is_cod_allowed'];
                    $result[$x]['item'][$z]['tax_title'] = empty($result[$x]['item'][$z]['tax_title']) ? "" : $result[$x]['item'][$z]['tax_title'];
                    $result[$x]['item'][$z]['shipping_delivery'] = empty($result[$x]['item'][$z]['shipping_delivery']) ? "" : $result[$x]['item'][$z]['shipping_delivery'];
                    $result[$x]['item'][$z]['size_chart'] = empty($result[$x]['item'][$z]['size_chart']) ? "" : $result[$x]['item'][$z]['size_chart'];
                    $result[$x]['item'][$z]['number_of_ratings'] = !empty($result[$x]['item'][$z]['number_of_ratings']) ? $result[$x]['item'][$z]['number_of_ratings'] : "";
                    $result[$x]['item'][$z]['ratings'] = !empty($result[$x]['item'][$z]['ratings']) ?  $result[$x]['item'][$z]['ratings'] : "";
                    $result[$x]['item'][$z]['review'] = !empty($result[$x]['item'][$z]['review']) ?  $result[$x]['item'][$z]['review'] : "";
                    $result[$x]['item'][$z]['price'] = empty($res1) ?  $result[$x]['item'][$z]['price'] : $res1[0]['price'];
                    $result[$x]['item'][$z]['discounted_price'] = empty($res1) ?  $result[$x]['item'][$z]['discounted_price'] : $res1[0]['discounted_price'];

                    if ($result[$x]['item'][$z]['stock'] <= 0 || $result[$x]['item'][$z]['serve_for'] == 'Sold Out') {
                        $result[$x]['item'][$z]['isAvailable'] = false;
                        $ready_to_add = true;
                    } else {
                        $result[$x]['item'][$z]['isAvailable'] = true;
                    }

                    for ($y = 0; $y < count($result[$x]['item'][$z]['other_images']); $y++) {
                        $other_images = DOMAIN_URL . $result[$x]['item'][$z]['other_images'][$y];
                        $result[$x]['item'][$z]['other_images'][$y] = $other_images;
                    }
                }
                for ($j = 0; $j < count($result[$x]['item']); $j++) {
                    $result[$x]['item'][$j]['image'] = !empty($result[$x]['item'][$j]['image']) ? DOMAIN_URL . $result[$x]['item'][$j]['image'] : "";
                    $result[$x]['item'][$j]['size_chart'] = !empty($result[$x]['item'][$j]['size_chart']) ? DOMAIN_URL . $result[$x]['item'][$j]['size_chart'] : "";
                }
                $x++;
            }
        }

        if (!empty($result)) {
            $response['error'] = false;
            $response['message'] = 'Item add to save for later!';
            $response['data'] = $result;
        } else {
            $response['error'] = true;
            $response['message'] = 'Item cannot add to save for later!';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Item not found in user cart!';
        $response['data'] = array();
    }
    print_r(json_encode($response));
    return false;
}
// if ((isset($_POST['add_save_for_later'])) && ($_POST['add_save_for_later'] == 1)) {
//     /*
//     5.add_save_for_later
//         accesskey:90336
//         add_save_for_later:1
//         user_id:221
//         product_variant_id:462
//     */

//     if (empty($_POST['user_id']) || empty($_POST['product_variant_id'])) {
//         $response['error'] = true;
//         $response['message'] = "Please pass all fields!";
//         print_r(json_encode($response));
//         return false;
//     }
//     $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['user_id'])) : "";
//     $product_variant_id  = (isset($_POST['product_variant_id']) && !empty($_POST['product_variant_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['product_variant_id'])) : "";
//     if ($fn->is_item_available_in_user_cart($user_id, $product_variant_id)) {
//         $sql1 = "UPDATE cart SET save_for_later = 1 WHERE user_id = $user_id AND product_variant_id = $product_variant_id";
//         $db->sql($sql1);
//         $result = $db->getResult();

//         $sql = "SELECT * FROM `cart` WHERE user_id = $user_id AND product_variant_id = $product_variant_id ";
//         $db->sql($sql);
//         $res = $db->getResult();
//         if (!empty($res)) {
//             $response['error'] = false;
//             $response['message'] = 'Item add to save for later!';
//         } else {
//             $response['error'] = true;
//             $response['message'] = 'Item cannot add to save for later!';
//         }
//     } else {
//         $response['error'] = true;
//         $response['message'] = 'Item not found in user cart!';
//         $response['data'] = array();
//     }
//     print_r(json_encode($response));
//     return false;
// }

if ((isset($_POST['remove_save_for_later'])) && ($_POST['remove_save_for_later'] == 1)) {
    /*
    6.remove_save_for_later
        accesskey:90336
        remove_save_for_later:1
        user_id:3
        product_variant_id:456
    */
    $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['user_id'])) : "";
    $product_variant_id  = (isset($_POST['product_variant_id']) && !empty($_POST['product_variant_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['product_variant_id'])) : "";
    if ($fn->is_item_available_in_user_cart($user_id, $product_variant_id)) {
        $sql1 = "UPDATE cart SET save_for_later = 0 WHERE user_id = $user_id AND product_variant_id = $product_variant_id";
        if ($db->sql($sql1)) {
            $x = 0;
            $total_amount = 0;

            $sql = "SELECT * FROM `cart` WHERE user_id = $user_id AND product_variant_id = $product_variant_id ";
            $db->sql($sql);
            $result = $db->getResult();

            $sql = "select qty,product_variant_id from cart where save_for_later = 1 AND user_id=" . $user_id;
            $db->sql($sql);
            $res1 = $db->getResult();

            foreach ($res1 as $row1) {
                $sql = "select price,discounted_price from product_variant where id=" . $row1['product_variant_id'];
                $db->sql($sql);
                $result1 = $db->getResult();
                foreach ($result1 as $result2) {
                    $price = $result2['discounted_price'] == 0 ? $result2['price'] * $row_1['qty'] : $result2['discounted_price'] * $row1['qty'];
                }
                $total_amount += $price;
            }

            foreach ($result as $rows) {
                $sql = "select pv.*,p.shipping_delivery,p.name,p.is_cod_allowed,p.slug,p.image,p.other_images,p.size_chart,p.ratings,p.number_of_ratings,pr.review,t.percentage as tax_percentage,t.title as tax_title,pv.measurement,(select short_code from unit u where u.id=pv.measurement_unit_id) as unit from product_variant pv left join products p on p.id=pv.product_id left join taxes t on t.id=p.tax_id left join product_reviews pr on p.id = pr.product_id  where pv.id=" . $rows['product_variant_id'] . " GROUP BY pv.id";
                $db->sql($sql);
                $result[$x]['item'] = $db->getResult();

                for ($z = 0; $z < count($result[$x]['item']); $z++) {
                    $sql_result = "SELECT * FROM flash_sales_products WHERE status = 1 AND product_id = " . $result[$x]['item'][$z]['product_id'] . " AND product_variant_id = " . $result[$x]['item'][$z]['id'] . " ";
                    $db->sql($sql_result);
                    $res1 = $db->getResult();

                    $result[$x]['item'][$z]['other_images'] = json_decode($result[$x]['item'][$z]['other_images']);
                    $result[$x]['item'][$z]['other_images'] = empty($result[$x]['item'][$z]['other_images']) ? array() : $result[$x]['item'][$z]['other_images'];
                    $result[$x]['item'][$z]['tax_percentage'] = empty($result[$x]['item'][$z]['tax_percentage']) ? "0" : $result[$x]['item'][$z]['tax_percentage'];
                    $result[$x]['item'][$z]['is_cod_allowed'] = empty($result[$x]['item'][$z]['is_cod_allowed']) ? "0" : $result[$x]['item'][$z]['is_cod_allowed'];
                    $result[$x]['item'][$z]['tax_title'] = empty($result[$x]['item'][$z]['tax_title']) ? "" : $result[$x]['item'][$z]['tax_title'];
                    $result[$x]['item'][$z]['shipping_delivery'] = empty($result[$x]['item'][$z]['shipping_delivery']) ? "" : $result[$x]['item'][$z]['shipping_delivery'];
                    $result[$x]['item'][$z]['size_chart'] = empty($result[$x]['item'][$z]['size_chart']) ? "" : $result[$x]['item'][$z]['size_chart'];
                    $result[$x]['item'][$z]['number_of_ratings'] = !empty($result[$x]['item'][$z]['number_of_ratings']) ? $result[$x]['item'][$z]['number_of_ratings'] : "";
                    $result[$x]['item'][$z]['ratings'] = !empty($result[$x]['item'][$z]['ratings']) ?  $result[$x]['item'][$z]['ratings'] : "";
                    $result[$x]['item'][$z]['review'] = !empty($result[$x]['item'][$z]['review']) ?  $result[$x]['item'][$z]['review'] : "";
                    $result[$x]['item'][$z]['price'] = empty($res1) ?  $result[$x]['item'][$z]['price'] : $res1[0]['price'];
                    $result[$x]['item'][$z]['discounted_price'] = empty($res1) ?  $result[$x]['item'][$z]['discounted_price'] : $res1[0]['discounted_price'];

                    if ($result[$x]['item'][$z]['stock'] <= 0 || $result[$x]['item'][$z]['serve_for'] == 'Sold Out') {
                        $result[$x]['item'][$z]['isAvailable'] = false;
                        $ready_to_add = true;
                    } else {
                        $result[$x]['item'][$z]['isAvailable'] = true;
                    }

                    for ($y = 0; $y < count($result[$x]['item'][$z]['other_images']); $y++) {
                        $other_images = DOMAIN_URL . $result[$x]['item'][$z]['other_images'][$y];
                        $result[$x]['item'][$z]['other_images'][$y] = $other_images;
                    }
                }
                for ($j = 0; $j < count($result[$x]['item']); $j++) {
                    $result[$x]['item'][$j]['image'] = !empty($result[$x]['item'][$j]['image']) ? DOMAIN_URL . $result[$x]['item'][$j]['image'] : "";
                    $result[$x]['item'][$j]['size_chart'] = !empty($result[$x]['item'][$j]['size_chart']) ? DOMAIN_URL . $result[$x]['item'][$j]['size_chart'] : "";
                }
                $x++;
            }
        }

        if (!empty($result)) {
            $response['error'] = false;
            $response['message'] = 'Item Remove from save for later!';
            $response['data'] = array_values($result);
        } else {
            $response['error'] = true;
            $response['message'] = 'Item cannot Remove from save for later!';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Item not found in user cart!';
    }
    print_r(json_encode($response));
    return false;
}
// if ((isset($_POST['remove_save_for_later'])) && ($_POST['remove_save_for_later'] == 1)) {
//     /*
//     6.remove_save_for_later
//         accesskey:90336
//         remove_save_for_later:1
//         user_id:3
//         product_variant_id:456
//     */
//     $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['user_id'])) : "";
//     $product_variant_id  = (isset($_POST['product_variant_id']) && !empty($_POST['product_variant_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['product_variant_id'])) : "";
//     if ($fn->is_item_available_in_user_cart($user_id, $product_variant_id)) {
//         $sql1 = "UPDATE cart SET save_for_later = 0 WHERE user_id = $user_id AND product_variant_id = $product_variant_id";
//         $db->sql($sql1);
//         $result = $db->getResult();

//         $sql = "SELECT * FROM `cart` WHERE user_id = $user_id AND product_variant_id = $product_variant_id ";
//         $db->sql($sql);
//         $res = $db->getResult();
//         if (!empty($res)) {
//             $response['error'] = false;
//             $response['message'] = 'Item Remove from save for later!';

//         } else {
//             $response['error'] = true;
//             $response['message'] = 'Item cannot Remove from save for later!';
//         }
//     } else {
//         $response['error'] = true;
//         $response['message'] = 'Item not found in user cart!';
//     }
//     print_r(json_encode($response));
//     return false;
// }


// if ((isset($_POST['get_save_for_later'])) && ($_POST['get_save_for_later'] == 1)) {
//     /*
//     7.get_save_for_later
//         accesskey:90336
//         get_save_for_later:1
//         user_id:3
//     */

//     if (empty($_POST['user_id'])) {
//         $response['error'] = true;
//         $response['message'] = "Please pass all fields!";
//         print_r(json_encode($response));
//         return false;
//     }
//     $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['user_id'])) : "";
//     if (!empty($user_id)) {
//         $sql = "SELECT * FROM `cart` WHERE user_id = $user_id AND save_for_later = 1";
//         $db->sql($sql);
//         $res = $db->getResult();
//         if (!empty($res)) {
//             $response['error'] = false;
//             $response['message'] = 'Data retrived Successfully!';
//             $response['total'] = count($res);
//             $response['data'] = $res;
//         } else {
//             $response['error'] = true;
//             $response['message'] = 'Data not found!';
//         }
//     } else {
//         $response['error'] = true;
//         $response['message'] = 'Users not found in save for later!';
//     }
//     print_r(json_encode($response));
//     return false;
// }

if ((isset($_POST['get_save_for_later'])) && ($_POST['get_save_for_later'] == 1)) {
    /*
    7.get_save_for_later
        accesskey:90336
        get_save_for_later:1
        user_id:3
    */

    if (empty($_POST['user_id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
        print_r(json_encode($response));
        return false;
    }
    $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean_array($_POST['user_id'])) : "";
    if (!empty($user_id)) {
        $x = 0;
        $total_amount = 0;

        $sql = "select * from cart where save_for_later = 1 AND user_id=" . $user_id . " ORDER BY date_created DESC ";
        $db->sql($sql);
        $result = $db->getResult();

        $sql = "select qty,product_variant_id from cart where save_for_later = 1 AND user_id=" . $user_id;
        $db->sql($sql);
        $res1 = $db->getResult();

        foreach ($res1 as $row1) {
            $sql = "select price,discounted_price from product_variant where id=" . $row1['product_variant_id'];
            $db->sql($sql);
            $result1 = $db->getResult();
            foreach ($result1 as $result2) {
                $price = $result2['discounted_price'] == 0 ? $result2['price'] * $row_1['qty'] : $result2['discounted_price'] * $row1['qty'];
            }
            $total_amount += $price;
        }

        foreach ($result as $rows) {
            $sql = "select pv.*,p.shipping_delivery,p.name,p.is_cod_allowed,p.slug,p.image,p.other_images,p.size_chart,p.ratings,p.number_of_ratings,pr.review,t.percentage as tax_percentage,t.title as tax_title,pv.measurement,(select short_code from unit u where u.id=pv.measurement_unit_id) as unit from product_variant pv left join products p on p.id=pv.product_id left join taxes t on t.id=p.tax_id left join product_reviews pr on p.id = pr.product_id  where pv.id=" . $rows['product_variant_id'] . " GROUP BY pv.id";
            $db->sql($sql);
            $result[$x]['item'] = $db->getResult();

            for ($z = 0; $z < count($result[$x]['item']); $z++) {
                $sql_result = "SELECT * FROM flash_sales_products WHERE status = 1 AND product_id = " . $result[$x]['item'][$z]['product_id'] . " AND product_variant_id = " . $result[$x]['item'][$z]['id'] . " ";
                $db->sql($sql_result);
                $res1 = $db->getResult();

                $result[$x]['item'][$z]['other_images'] = json_decode($result[$x]['item'][$z]['other_images']);
                $result[$x]['item'][$z]['other_images'] = empty($result[$x]['item'][$z]['other_images']) ? array() : $result[$x]['item'][$z]['other_images'];
                $result[$x]['item'][$z]['tax_percentage'] = empty($result[$x]['item'][$z]['tax_percentage']) ? "0" : $result[$x]['item'][$z]['tax_percentage'];
                $result[$x]['item'][$z]['is_cod_allowed'] = empty($result[$x]['item'][$z]['is_cod_allowed']) ? "0" : $result[$x]['item'][$z]['is_cod_allowed'];
                $result[$x]['item'][$z]['tax_title'] = empty($result[$x]['item'][$z]['tax_title']) ? "" : $result[$x]['item'][$z]['tax_title'];
                $result[$x]['item'][$z]['shipping_delivery'] = empty($result[$x]['item'][$z]['shipping_delivery']) ? "" : $result[$x]['item'][$z]['shipping_delivery'];
                $result[$x]['item'][$z]['size_chart'] = empty($result[$x]['item'][$z]['size_chart']) ? "" : $result[$x]['item'][$z]['size_chart'];
                $result[$x]['item'][$z]['number_of_ratings'] = !empty($result[$x]['item'][$z]['number_of_ratings']) ? $result[$x]['item'][$z]['number_of_ratings'] : "";
                $result[$x]['item'][$z]['ratings'] = !empty($result[$x]['item'][$z]['ratings']) ?  $result[$x]['item'][$z]['ratings'] : "";
                $result[$x]['item'][$z]['review'] = !empty($result[$x]['item'][$z]['review']) ?  $result[$x]['item'][$z]['review'] : "";
                $result[$x]['item'][$z]['price'] = empty($res1) ?  $result[$x]['item'][$z]['price'] : $res1[0]['price'];
                $result[$x]['item'][$z]['discounted_price'] = empty($res1) ?  $result[$x]['item'][$z]['discounted_price'] : $res1[0]['discounted_price'];

                if ($result[$x]['item'][$z]['stock'] <= 0 || $result[$x]['item'][$z]['serve_for'] == 'Sold Out') {
                    $result[$x]['item'][$z]['isAvailable'] = false;
                    $ready_to_add = true;
                } else {
                    $result[$x]['item'][$z]['isAvailable'] = true;
                }

                for ($y = 0; $y < count($result[$x]['item'][$z]['other_images']); $y++) {
                    $other_images = DOMAIN_URL . $result[$x]['item'][$z]['other_images'][$y];
                    $result[$x]['item'][$z]['other_images'][$y] = $other_images;
                }
            }
            for ($j = 0; $j < count($result[$x]['item']); $j++) {
                $result[$x]['item'][$j]['image'] = !empty($result[$x]['item'][$j]['image']) ? DOMAIN_URL . $result[$x]['item'][$j]['image'] : "";
                $result[$x]['item'][$j]['size_chart'] = !empty($result[$x]['item'][$j]['size_chart']) ? DOMAIN_URL . $result[$x]['item'][$j]['size_chart'] : "";
            }
            $x++;
        }

        if (!empty($result)) {
            $response['error'] = false;
            $response['message'] = 'Data retrived Successfully!';
            $response['total'] = count($result);
            $response['data'] = array_values($result);
        } else {
            $response['error'] = true;
            $response['message'] = 'Data not found!';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Users not found in save for later!';
    }
    print_r(json_encode($response));
    return false;
}
