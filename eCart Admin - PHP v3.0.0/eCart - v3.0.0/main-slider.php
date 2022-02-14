<?php
// start session
session_start();

// set time for session timeout
$currentTime = time() + 25200;
$expired = 3600;

// if session not set go to login page
if (!isset($_SESSION['user'])) {
    header("location:index.php");
}

// if current time is more than session timeout back to login page
if ($currentTime > $_SESSION['timeout']) {
    session_destroy();
    header("location:index.php");
}

// destroy previous session timeout and create new one
unset($_SESSION['timeout']);
$_SESSION['timeout'] = $currentTime + $expired;
?>
<?php include "header.php";
$allowed = ALLOW_MODIFICATION; ?>
<html>

<head>
    <title>Main Slider Images | <?= $settings['app_name'] ?> - Dashboard</title>
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" crossorigin="anonymous"></script> -->
    <script src="dist/js/jquery.min.js" crossorigin="anonymous"></script>

    <style type="text/css">
        .container {
            width: 950px;
            margin: 0 auto;
            padding: 0;
        }

        h1 {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 24px;
            color: #777;
        }

        h1 .send_btn {
            background: -webkit-gradient(linear, 0% 0%, 0% 100%, from(#0096FF), to(#005DFF));
            background: -webkit-linear-gradient(0% 0%, 0% 100%, from(#0096FF), to(#005DFF));
            background: -moz-linear-gradient(center top, #0096FF, #005DFF);
            background: linear-gradient(#0096FF, #005DFF);
            text-shadow: 0 1px 0 rgba(0, 0, 0, 0.3);
            border-radius: 3px;
            color: #fff;
            padding: 3px;
        }

        div.clear {
            clear: both;
        }

        ul.devices {
            margin: 0;
            padding: 0;
        }

        ul.devices li {
            float: left;
            list-style: none;
            border: 1px solid #dedede;
            padding: 10px;
            margin: 0 15px 25px 0;
            border-radius: 3px;
            -webkit-box-shadow: 0 1px 5px rgba(0, 0, 0, 0.35);
            -moz-box-shadow: 0 1px 5px rgba(0, 0, 0, 0.35);
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.35);
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #555;
            width: 100%;
            height: 150px;
            background-color: #ffffff;
        }

        ul.devices li label,
        ul.devices li span {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            font-style: normal;
            font-variant: normal;
            font-weight: bold;
            color: #393939;
            display: block;
            float: left;
        }

        ul.devices li label {
            height: 25px;
            width: 50px;
        }

        ul.devices li textarea {
            float: left;
            resize: none;
        }

        ul.devices li .send_btn {
            background: -webkit-gradient(linear, 0% 0%, 0% 100%, from(#0096FF), to(#005DFF));
            background: -webkit-linear-gradient(0% 0%, 0% 100%, from(#0096FF), to(#005DFF));
            background: -moz-linear-gradient(center top, #0096FF, #005DFF);
            background: linear-gradient(#0096FF, #005DFF);
            text-shadow: 0 1px 0 rgba(0, 0, 0, 0.3);
            border-radius: 7px;
            color: #fff;
            padding: 4px 24px;
        }

        a {
            text-decoration: none;
            color: rgb(245, 134, 52);
        }
    </style>
</head>

<body>
    <div class="content-wrapper">
        <?php
        if (isset($_POST['add_btn'])) {
            if (ALLOW_MODIFICATION == 0 && !defined(ALLOW_MODIFICATION)) {
                echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
                return false;
            }
            $permissions = $fn->get_permissions($_SESSION['id']);
            if ($permissions['home_sliders']['create'] == 0) {
                $response["message"] = "<p class='alert alert-danger'>You have no permission to create home slider.</p>";
                echo json_encode($response);
                return false;
            }
            $target_path = 'upload/slider/';
            if (!is_dir($target_path)) {
                mkdir($target_path, 0777, true);
            }

            $image = $db->escapeString($fn->xss_clean($_FILES['image']['name']));
            $image_error1 = $db->escapeString($fn->xss_clean($_FILES['image']['error']));
            $image_type1 = $db->escapeString($fn->xss_clean($_FILES['image']['type']));

            $image2 = $db->escapeString($fn->xss_clean($_FILES['image2']['name']));
            $image_error2 = $db->escapeString($fn->xss_clean($_FILES['image2']['error']));
            $image_type2 = $db->escapeString($fn->xss_clean($_FILES['image2']['type']));

            $type = $db->escapeString($fn->xss_clean($_POST['type']));
            $title = $db->escapeString($fn->xss_clean($_POST['title']));
            $short_description = $db->escapeString($fn->xss_clean($_POST['short_description']));
            $id = ($type != 'default') ? $db->escapeString($fn->xss_clean($_POST[$type])) : "0";
            $error = array();
            $allowedExts = array("gif", "jpeg", "jpg", "png");

            error_reporting(E_ERROR | E_PARSE);
            $extension1 = end(explode(".", $_FILES["image"]["name"]));
            if ($image_error1 > 0) {
                $error['image'] = " <span class='label label-danger'>Not uploaded!</span>";
            } else {
                $result = $fn->validate_image($_FILES['image']);
                if ($result) {
                    $response["message"] = "<span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
                    echo json_encode($response);
                    $error['image'] = " <span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
                    return false;
                }
            }
            if (!empty($image2)) {
                $extension2 = end(explode(".", $_FILES["image2"]["name"]));
                if ($image_error2 > 0) {
                    $error['image2'] = " <span class='label label-danger'>Not uploaded!</span>";
                } else {
                    $result = $fn->validate_image($_FILES['image2']);
                    if ($result) {
                        $response["message"] = "<span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
                        echo json_encode($response);
                        $error['image2'] = " <span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
                        return false;
                    }
                }
            }
            if (empty($error['image'])) {
                $mt1 = explode(' ', microtime());
                $microtime1 = ((int)$mt1[1]) * 1000 + ((int)round($mt1[0] * 1000));
                $file1 = preg_replace("/\s+/", "_", $_FILES['image']['name']);

                $image = $microtime1 . "." . $extension1;
                $upload1 = move_uploaded_file($_FILES['image']['tmp_name'], 'upload/slider/' . $image);
                if (!empty($image2)) {
                    $mt2 = explode(' ', microtime());
                    $microtime2 = ((int)$mt2[1]) * 1000 + ((int)round($mt2[0] * 1000));
                    $file2 = preg_replace("/\s+/", "_", $_FILES['image2']['name']);

                    $image2 = $microtime2 . "." . $extension2;
                    $upload2 = move_uploaded_file($_FILES['image2']['tmp_name'], 'upload/slider/' . $image2);
                    $upload_image2 = !empty($image2) ? 'upload/slider/' . $image2 : "";
                }
                $upload_image = 'upload/slider/' . $image;
                $sql = "INSERT INTO `slider`(`image`,`image2`,`type`, `type_id`,`title`,`short_description`) VALUES ('$upload_image','$upload_image2','" . $type . "','" . $id . "','" . $title . "','" . $short_description . "')";
                $db->sql($sql);
                $res = $db->getResult();
                $sql = "SELECT id FROM `slider` ORDER BY id DESC";
                $db->sql($sql);
                $res = $db->getResult();
                $response["message"] = "<p class='alert alert-success'>Slider Add Successfully!</p>";
            } else {
                $response["message"] = "<p class='alert alert-danger'>Failed add slider</p>";
            }
        }
        ?>
        <section class="content-header">
            <h1>Main Slider Image for Extra Offers and Benefits for Customers</h1>
            <ol class="breadcrumb">
                <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
            </ol>
            <hr />
        </section>
        <div id="result">
            <?php echo isset($response["message"]) ? $response["message"] : ''; ?>
        </div>
        <?php
        include_once('includes/functions.php');
        ?>
        <section class="content">
            <div class="row">
                <div class="col-md-6">
                    <?php if ($permissions['home_sliders']['create'] == 0) { ?>
                        <div class="alert alert-danger">You have no permission to create home slider</div>
                    <?php } ?>
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">Add / Update Exciting Offers Images here</h3>
                        </div>
                        <form id="slider_form" method="post" enctype="multipart/form-data">
                            <div class="box-body">
                                <input type='hidden' name='accesskey' id='accesskey' value='90336' />
                                <input type='hidden' name='add-image' id='add-image' value='1' />
                                <div class="form-group">
                                    <label for="type">Type :</label>
                                    <select name="type" id="type" class="form-control" required>
                                        <option value="default">Default</option>
                                        <option value="category">Category</option>
                                        <option value="product">Product</option>
                                    </select>
                                </div>
                                <div class="form-group" id="categories" style="display:none;">
                                    <label for="category">Categories :</label>
                                    <select name="category" id="category" class="form-control">
                                        <?php
                                        $sql = "SELECT * FROM `category` order by id DESC";
                                        $db->sql($sql);
                                        $categories_result = $db->getResult();
                                        ?>
                                        <?php if ($permissions['categories']['read'] == 1) { ?>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories_result as $value) { ?>
                                                <option value="<?= $value['id'] ?>"><?= $value['name'] ?></option>
                                            <?php
                                            }
                                        } else {
                                            ?>
                                            <option value="">Select Category</option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div class="form-group" id="products" style="display:none;">
                                    <label for="product">Products :</label>
                                    <select name="product" id="product" class="form-control">
                                        <?php
                                        $sql = "SELECT * FROM `products` order by id DESC";
                                        $db->sql($sql);
                                        $products_result = $db->getResult();

                                        ?>
                                        <?php if ($permissions['products']['read'] == 1) { ?>
                                            <option value="">Select Product</option>
                                            <?php foreach ($products_result as $value) { ?>
                                                <option value="<?= $value['id'] ?>"><?= $value['name'] ?></option>
                                            <?php
                                            }
                                        } else {
                                            ?>
                                            <option value="">Select Product</option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="image">Slider Image1 : <small> ( Recommended Size : 1024 x 512 pixels )</small></label>
                                    <input type='file' name="image" id="image" required />
                                </div>
                                <div class="form-group">
                                    <label for="image2">Slider Image2 : <small> ( Recommended Size : 700 x 650 pixels )</small></label>
                                    <input type='file' name="image2" id="image2" />
                                </div>
                                <div class="form-group">
                                    <label for="title">Title : </label>
                                    <textarea rows="5" cols="5" class="form-control" name="title" id="title"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="short_description">Description : </label>
                                    <textarea rows="5" cols="5" class="form-control" name="short_description" id="short_description"></textarea>
                                </div>
                            </div>
                            <div class="box-footer">
                                <input type="submit" id="submit_btn" class="btn-primary btn" value="Upload" name="add_btn" />
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-6">
                    <?php if ($permissions['home_sliders']['read'] == 1) { ?>
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">Main Slider Image</h3>
                            </div>
                            <table id="notifications_table" class="table table-hover" data-toggle="table" data-url="api-firebase/get-bootstrap-table-data.php?table=slider" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-refresh="true" data-show-columns="true" data-side-pagination="server" data-pagination="true" data-search="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">ID</th>
                                        <th data-field="image">Image1</th>
                                        <th data-field="image2">Image2</th>
                                        <th data-field="type">Type</th>
                                        <th data-field="title">Title</th>
                                        <th data-field="short_description">Description</th>
                                        <th data-field="type_id">ID</th>
                                        <th data-field="operate" data-events="actionEvents">Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    <?php } else { ?>
                        <div class=" alert alert-danger">You have no permission to view home slider images.
                        </div>
                    <?php } ?>
                </div>
            </div>
        </section>
    </div>
    <div class="separator"> </div>
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.16.0/jquery.validate.min.js"></script> -->
    <script src="plugins/jQuery/jquery.validate.min.js"></script>
    <script type="text/javascript" src="css/js/ckeditor/ckeditor.js"></script>
    <script>
        var allowed = '<?= $allowed; ?>';
        $("#type").change(function() {
            type = $("#type").val();
            if (type == "default") {
                $("#categories").hide();
                $("#products").hide();
            }
            if (type == "category") {
                $("#categories").show();
                $("#products").hide();
            }
            if (type == "product") {
                $("#categories").hide();
                $("#products").show();
            }
        });

        $(document).on('click', '#submit_btn', function() {
            $.ajax({
                success: function(response) {
                    $('#notifications_table').bootstrapTable('refresh');
                }
            });
        });
    </script>
    <script>
        $(document).on('click', '.delete-slider', function() {
            if (confirm('Are you sure?')) {
                id = $(this).data("id");
                image = $(this).data("image");
                image2 = $(this).data("image2");
                $.ajax({
                    url: 'api-firebase/slider-images.php',
                    type: "get",
                    data: 'accesskey=90336&id=' + id + '&image=' + image + '&image2=' + image2 + '&type=delete-slider',
                    success: function(result) {
                        if (result == 1) {
                            $('#notifications_table').bootstrapTable('refresh');
                        }
                        if (result == 2) {
                            alert('You have no permission to delete home slider');
                        }
                        if (result == 0) {
                            alert('Error! slider could not be deleted');
                        }

                    }
                });
            }
        });
    </script>
</body>

</html>
<?php include "footer.php"; ?>