<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
include_once('../includes/variables.php');
include_once('../includes/crud.php');
include_once('verify-token.php');
$db = new Database();
$db->connect();
include_once('../includes/custom-functions.php');
$fn = new custom_functions;

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

// if (!verify_token()) {
//     return false;
// }

if (!isset($_POST['accesskey'])  || trim($_POST['accesskey']) != $access_key) {
    $response['error'] = true;
    $response['message'] = "No Accsess key found!";
    print_r(json_encode($response));
    return false;
}


if (isset($_POST['get_all_products']) && $_POST['get_all_products'] == 1) {
    /* 
    1.get_all_products
        accesskey:90336
        get_all_products:1
        product_id:219      // {optional}
        user_id:1782        // {optional}
        category_id:29      // {optional}
        subcategory_id:63   // {optional}
        limit:5             // {optional}
        offset:1            // {optional}
        sort:id             // {optional}
        order:asc/desc      // {optional}
    */

    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;

    $sort = (isset($_POST['sort']) && !empty($_POST['sort'])) ? $db->escapeString($fn->xss_clean($_POST['sort'])) : "row_order + 0 ";
    $order = (isset($_POST['order']) && !empty($_POST['order'])) ? $db->escapeString($fn->xss_clean($_POST['order'])) : "DESC";

    $product_id = (isset($_POST['product_id']) && !empty($_POST['product_id'])) ? $db->escapeString($fn->xss_clean($_POST['product_id'])) : "";
    $user_id = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";

    $category_id = (isset($_POST['category_id']) && !empty($_POST['category_id'])) ? $db->escapeString($fn->xss_clean($_POST['category_id'])) : "";
    $subcategory_id = (isset($_POST['subcategory_id']) && !empty($_POST['subcategory_id'])) ? $db->escapeString($fn->xss_clean($_POST['subcategory_id'])) : "";

    $where = "";
    if (isset($_POST['product_id']) && !empty($_POST['product_id']) && is_numeric($_POST['product_id'])) {
        $where .=  !empty($where) ? " AND `id` = " . $product_id :  " WHERE `id`=" . $product_id;
    }

    if (isset($_POST['category_id']) && !empty($_POST['category_id']) && is_numeric($_POST['category_id'])) {
        $where .=  !empty($where) ? " AND `category_id`=" . $category_id : " WHERE `category_id`=" . $category_id;
    }
    if (isset($_POST['subcategory_id']) && !empty($_POST['subcategory_id']) && is_numeric($_POST['subcategory_id'])) {
        $where .=  !empty($where) ? " AND `subcategory_id`=" . $subcategory_id : " WHERE `subcategory_id`=" . $subcategory_id;
    }


    $sql = "SELECT count(id) as total FROM products $where ";
    $db->sql($sql);
    $total = $db->getResult();

    $sql = "SELECT *,(SELECT c.name FROM category c WHERE c.id=products.category_id) as category_name FROM products $where ORDER BY $sort $order LIMIT $offset,$limit ";
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
            // if ($variants[$k]['stock'] <= 0 && $variants[$k]['serve_for'] = 'Sold Out') {
            //     $variants[$k]['serve_for'] = 'Sold Out';
            // } else {
            //     $variants[$k]['serve_for'] = 'Available';
            // }
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
        $response['message'] = "Products retrieved successfully";
        $response['total'] = $total[0]['total'];
        $response['data'] = $product;
    } else {
        $response['error'] = true;
        $response['message'] = "No products available";
        $response['total'] = $total[0]['total'];
        $response['data'] = array();
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['get_all_products_name']) && $_POST['get_all_products_name'] == 1) {
    /*
    2.get_all_products_name
        accesskey:90336
		get_all_products_name:1
    */
    $sql = "SELECT name FROM `products`";
    $db->sql($sql);
    $res = $db->getResult();
    $rows = $tempRow = $blog_array = $blog_array1 = array();
    foreach ($res as $row) {
        $tempRow['name'] = $row['name'];
        $rows[] = $tempRow;
    }
    $names = array_column($rows, 'name');

    $pr_names = implode(",", $names);
    $response['error'] = false;
    $response['data'] = $pr_names;

    print_r(json_encode($response));
}

if (isset($_POST['add_products_review']) && $_POST['add_products_review'] == 1) {
    /*
    3.add_products_review
        accesskey:90336
        add_products_review:1
        product_id:219      
        user_id:23        
        rate:value
        review:string
        images[]:FILE
    */

    if (empty($_POST['product_id']) || empty($_POST['user_id']) || empty($_POST['rate']) || empty($_POST['review'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
        print_r(json_encode($response));
        return false;
        exit();
    }

    $product_id = $db->escapeString(trim($fn->xss_clean($_POST['product_id'])));
    $user_id = $db->escapeString(trim($fn->xss_clean($_POST['user_id'])));
    $rate = $db->escapeString(trim($fn->xss_clean($_POST['rate'])));
    $review = $db->escapeString(trim($fn->xss_clean($_POST['review'])));
    $product_variant_id = $fn->get_variant_id_by_product_id($product_id);

    $sql = "SELECT * FROM `order_items` WHERE user_id = $user_id and active_status ='delivered' and product_variant_id = $product_variant_id ";
    $db->sql($sql);
    $res = $db->getResult();
    if (empty($res)) {
        $response['error'] = true;
        $response['message'] = "You can not review this product!";
        print_r(json_encode($response));
        return false;
    }

    $sql = "SELECT id FROM users WHERE id=" . $user_id;
    $db->sql($sql);
    $res = $db->getResult();
    if (empty($res)) {
        $response['error'] = true;
        $response['message'] = "User id Does not exists!";
        print_r(json_encode($response));
        return false;
    }

    $sql = "SELECT * FROM product_reviews WHERE product_id=" . $product_id . " AND user_id=" . $user_id;
    $db->sql($sql);
    $res = $db->getResult();
    $count = $db->numRows($res);

    if (!empty($_FILES['images'])) {
        if ($_FILES["images"]["error"] == 0) {
            for ($i = 0; $i < count($_FILES["images"]["name"]); $i++) {
                if ($_FILES["images"]["error"][$i] > 0) {
                    $response['error'] = true;
                    $response['message'] = "Images not uploaded!";
                    print_r(json_encode($response));
                    return false;
                } else {
                    $result = $fn->validate_other_images($_FILES["images"]["tmp_name"][$i], $_FILES["images"]["type"][$i]);
                    if ($result) {
                        $response['error'] = true;
                        $response['message'] = "image type must jpg, jpeg, gif, or png!";
                        print_r(json_encode($response));
                        return false;
                    }
                }
            }
        }

        $sql_query = "SELECT images FROM product_reviews WHERE product_id = $product_id AND user_id=" . $user_id;
        $db->sql($sql_query);
        $res = $db->getResult();
        if (!empty($res[0]['images'])) {
            $other_images = json_decode($res[0]['images']);
            foreach ($other_images as $other_image) {
                unlink('../' . $other_image);
            }
        }

        $other_images = '';
        if (isset($_FILES['images']) && ($_FILES['images']['size'][0] > 0)) {
            $file_data = array();
            $target_path = '../upload/reviews/';
            if (!is_dir($target_path)) {
                mkdir($target_path, 0777, true);
            }
            $target_path1 = 'upload/reviews/';
            for ($i = 0; $i < count($_FILES["images"]["name"]); $i++) {
                $filename = $_FILES["images"]["name"][$i];
                $temp = explode('.', $filename);
                $filename = microtime(true) . '-' . rand(100, 999) . '.' . end($temp);
                $file_data[] = $target_path1 . '' . $filename;
                if (!move_uploaded_file($_FILES["images"]["tmp_name"][$i], $target_path . '' . $filename)) {
                    $response['error'] = true;
                    $response['message'] = "Images not uploaded!";
                    print_r(json_encode($response));
                    return false;
                }
            }
            $other_images = json_encode($file_data);
        }
    }
    if ($count > 0) {
        if (!empty($other_images)) {
            $sql1 = "UPDATE product_reviews SET rate= $rate ,review= '$review',images = '$other_images' WHERE product_id=" . $product_id  . " AND user_id=" . $user_id;
        } else {
            $sql1 = "UPDATE product_reviews SET rate= $rate ,review= '$review' WHERE product_id=" . $product_id  . " AND user_id=" . $user_id;
        }
        $db->sql($sql1);
        $res = $db->getResult();
    } else {
        if (!empty($other_images)) {
            $sql = "INSERT INTO product_reviews (product_id,user_id,rate,review,images) VALUES('$product_id','$user_id','$rate','$review','$other_images')";
        } else {
            $sql = "INSERT INTO product_reviews (product_id,user_id,rate,review) VALUES('$product_id','$user_id','$rate','$review')";
        }
        $db->sql($sql);
        $res = $db->getResult();
    }

    $sql = "select AVG(rate) as average, ";
    for ($i = 0; $i < 5; $i++) {
        $n = $i + 1;
        $sql .= " ( SELECT COUNT(review) as r$n from product_reviews where rate = $n and product_id = $product_id ) r$n,";
    }
    $sql = rtrim($sql, ",");
    $sql .= " from product_reviews where product_id = $product_id GROUP BY r5 ";
    $db->sql($sql);
    $r5 = $db->getResult();

    $sql = "UPDATE `products` p
        INNER JOIN ( SELECT product_id, COUNT(id) as total_ratings, AVG(rate) as average FROM product_reviews WHERE product_id = $product_id ) pr ON p.id = pr.product_id
        SET p.ratings = pr.average, p.number_of_ratings = pr.total_ratings
    WHERE p.id = $product_id ";
    $res = $db->getResult();
    $product = array();

    if ($db->sql($sql)) {
        $sql1 = "select pr.*,u.name as username,u.profile as user_profile,u.id as user_id,pr.date_added from product_reviews pr join users u on u.id= pr.user_id where pr.product_id = $product_id and pr.user_id=$user_id ";
        $db->sql($sql1);
        $data = $db->getResult();
        $data[0]['images'] = json_decode($data[0]['images'], 1);
        $data[0]['images'] = (empty($data[0]['images'])) ? array() : $data[0]['images'];

        for ($j = 0; $j < count($data[0]['images']); $j++) {
            $data[0]['images'][$j] = DOMAIN_URL . $data[0]['images'][$j];
        }

        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['user_profile'] = (!empty($data[$i]['user_profile'])) ? DOMAIN_URL . 'upload/profile/' . $data[$i]['user_profile'] : '';
        }

        $response['error'] = false;
        $response['message'] = "Review retrived Successfully!";
        $response['data'] = $data;
    } else {
        $response['error'] = true;
        $response['message'] = "Some Error Occrred! please try again.";
    }
    print_r(json_encode($response));
}

if (isset($_POST['get_product_reviews']) && $_POST['get_product_reviews'] == 1) {
    /*
    4.get_product_reviews
        accesskey:90336
        get_product_reviews:1
        product_id:220      //{optional}
        slug:product-slug   // { product_id or slug should be pass}
        user_id:29          // {optional}
        limit:5             // {optional}
        offset:1            // {optional}
        sort:id             // {optional}
        order:asc/desc      // {optional}
    */

    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;

    $sort = (isset($_POST['sort']) && !empty($_POST['sort'])) ? $db->escapeString($fn->xss_clean($_POST['sort'])) : "row_order + 0 ";
    $order = (isset($_POST['order']) && !empty($_POST['order'])) ? $db->escapeString($fn->xss_clean($_POST['order'])) : "DESC";

    $where = "";
    $p_id = 0;
    if (isset($_POST['product_id']) && !empty($_POST['product_id']) && is_numeric($_POST['product_id'])) {
        $product_id = $db->escapeString($fn->xss_clean($_POST['product_id']));
        $where .=  !empty($where) ? " AND `product_id` = " . $product_id :  " WHERE `product_id`=" . $product_id;
    }
    if (isset($_POST['user_id']) && !empty($_POST['user_id']) && is_numeric($_POST['user_id'])) {
        $user_id = $db->escapeString($fn->xss_clean($_POST['user_id']));
        $where .=  !empty($where) ? " AND `user_id` = " . $user_id :  " WHERE `user_id`=" . $user_id;
    }
    if (isset($_POST['slug']) && !empty($_POST['slug'])) {
        $slug = $db->escapeString($fn->xss_clean($_POST['slug']));
        $where .= !empty($where) ? " AND `product_id`= (select id from products where slug='$slug')" : " WHERE  `product_id`= (select id from products where slug='$slug')";
    }

    $sql = "SELECT count(id) as total FROM product_reviews  $where ";
    $db->sql($sql);
    $total = $db->getResult();

    $sql = "SELECT pr.*,p.slug,p.ratings,p.number_of_ratings,p.name as product_name,u.name as username,u.profile as user_profile FROM product_reviews pr join products p on p.id = pr.product_id join users u on u.id = pr.user_id" . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res)) {
        $product_variant_id = $fn->get_variant_id_by_product_id($res[0]['product_id']);

        $sql = "SELECT * FROM `order_items` WHERE user_id = " . $res[0]['user_id'] . " and active_status ='delivered' and product_variant_id = $product_variant_id ";
        $db->sql($sql);
        $res1 = $db->getResult();
        if (empty($res1)) {
            $row1['review_eligible'] = "0";
        } else {
            $row1['review_eligible'] = "1";
        }

        $product = array();

        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['images'] = json_decode($res[$i]['images'], 1);
            $res[$i]['images'] = (empty($res[$i]['images'])) ? array() : $res[$i]['images'];

            for ($j = 0; $j < count($res[$i]['images']); $j++) {
                $res[$i]['images'][$j] = DOMAIN_URL . $res[$i]['images'][$j];
            }
            $res[$i]['user_profile'] = (!empty($res[$i]['user_profile'])) ? DOMAIN_URL . 'upload/profile/' . $res[$i]['user_profile'] : '';
        }

        $response['error'] = false;
        $response['message'] = "Products review retrieved successfully";
        $response['number_of_reviews'] = $total[0]['total'];
        $response['avg_ratings'] = $res[0]['ratings'];
        $response['number_of_ratings'] = $res[0]['number_of_ratings'];
        $response['review_eligible'] = $row1['review_eligible'];
        $response['product_review'] = $res;
    } else {
        $response['error'] = true;
        $response['message'] = "No products available";
        $response['review_eligible'] = "0";
    }
    print_r(json_encode($response));
    return false;
}

if ((isset($_POST['delete_product_review'])) && ($_POST['delete_product_review'] == 1)) {
    /*
    5.delete_product_review
        accesskey:90336
        delete_product_review:1
        product_id:220     
        user_id:29          
    */
    if (empty($_POST['product_id'] && $_POST['user_id'])) {
        $response['error'] = true;
        $response['message'] = "Please pass all fields!";
        print_r(json_encode($response));
        return false;
    }

    $product_id = $db->escapeString($fn->xss_clean($_POST['product_id']));
    $user_id = $db->escapeString($fn->xss_clean($_POST['user_id']));

    $sql_query = "SELECT images FROM product_reviews WHERE product_id = $product_id AND user_id = $user_id";
    $db->sql($sql_query);
    $res = $db->getResult();

    if (!empty($res[0]['images'])) {
        $other_images = json_decode($res[0]['images']);
        foreach ($other_images as $other_image) {
            unlink('../' . $other_image);
        }
    }

    $sql = "SELECT * FROM product_reviews WHERE product_id=" . $product_id . " AND user_id=" . $user_id;
    $db->sql($sql);
    $res = $db->getResult();
    $count = $db->numRows($res);

    if ($count > 0) {
        $sql1 = "DELETE FROM `product_reviews` WHERE product_id = $product_id AND user_id = $user_id";
        $db->sql($sql1);
        $res = $db->getResult();
    }

    $sql = "select AVG(rate) as average, ";
    for ($i = 0; $i < 5; $i++) {
        $n = $i + 1;
        $sql .= " ( SELECT COUNT(review) as r$n from product_reviews where rate = $n and product_id = $product_id ) r$n,";
    }
    $sql = rtrim($sql, ",");
    $sql .= " from product_reviews where product_id = $product_id GROUP BY r5 ";
    $db->sql($sql);
    $r5 = $db->getResult();

    $r = "SELECT product_id, COUNT(id) as total_ratings, AVG(rate) as average FROM product_reviews WHERE product_id = $product_id";
    $db->sql($r);
    $res = $db->getResult();
    if ($res[0]['total_ratings'] != 0) {
        $sql = "UPDATE `products` p
        INNER JOIN ( SELECT product_id, COUNT(id) as total_ratings, AVG(rate) as average FROM product_reviews WHERE product_id = $product_id ) pr ON p.id = pr.product_id
        SET p.ratings = pr.average, p.number_of_ratings = pr.total_ratings WHERE p.id = $product_id ";
    } else {
        $sql = "UPDATE `products` p SET p.ratings = 0, p.number_of_ratings = 0 WHERE p.id = $product_id ";
    }
    $res = $db->getResult();
    if ($db->sql($sql)) {
        $response['error'] = false;
        $response['message'] = "Product Review Deleted Successfully!";
    } else {
        $response['error'] = true;
        $response['message'] = "Some Error Occrred! please try again.";
    }
    print_r(json_encode($response));
}
