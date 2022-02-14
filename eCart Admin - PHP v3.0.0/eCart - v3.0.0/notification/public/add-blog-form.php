<?php
include_once('includes/functions.php');
$function = new functions;
include_once('includes/custom-functions.php');
$fn = new custom_functions;

?>
<?php
if (isset($_POST['btnAdd'])) {
    if (ALLOW_MODIFICATION == 0 && !defined(ALLOW_MODIFICATION)) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    if ($permissions['categories']['create'] == 1) {
        $target_path = './upload/blogs/';
        if (!is_dir($target_path)) {
            mkdir($target_path, 0777, true);
        }

        $title = $db->escapeString($fn->xss_clean($_POST['title']));
        $slug = $function->slugify($db->escapeString($fn->xss_clean($_POST['title'])), 'blogs');
        $description = $db->escapeString($fn->xss_clean($_POST['description']));
        $category_id = $db->escapeString($fn->xss_clean($_POST['category_id']));

        $menu_image = $db->escapeString($fn->xss_clean($_FILES['image']['name']));
        $image_error = $db->escapeString($fn->xss_clean($_FILES['image']['error']));
        $image_type = $db->escapeString($fn->xss_clean($_FILES['image']['type']));

        $error = array();

        $allowedExts = array("gif", "jpeg", "jpg", "png");

        error_reporting(E_ERROR | E_PARSE);
        $extension = end(explode(".", $_FILES["image"]["name"]));

        if ($image_error > 0) {
            $error['image'] = " <span class='label label-danger'>Not Uploaded!!</span>";
        } else {
            $result = $fn->validate_image($_FILES["image"]);
            if ($result) {
                $error['image'] = " <span class='label label-danger'>Image type must jpg, jpeg, gif, or png!</span>";
            }
        }

        if (!empty($title) && !empty($description) && empty($error['image'])) {

            $string = '0123456789';
            $file = preg_replace("/\s+/", "_", $_FILES['image']['name']);
            $menu_image = $function->get_random_string($string, 4) . "-" . date("Y-m-d") . "." . $extension;

            $upload = move_uploaded_file($_FILES['image']['tmp_name'], 'upload/blogs/' . $menu_image);

            $upload_image = 'upload/blogs/' . $menu_image;
            $sql_query = "INSERT INTO blogs (category_id,title,slug,description, image)VALUES('$category_id','$title', '$slug', '$description', '$upload_image')";
            $db->sql($sql_query);
            $result = $db->getResult();
            if (!empty($result)) {
                $result = 0;
            } else {
                $result = 1;
            }

            if ($result == 1) {
                $error['add_category'] = " <section class='content-header'><span class='label label-success'>Blogs Added Successfully</span></section>";
            } else {
                $error['add_category'] = " <span class='label label-danger'>Failed add blog</span>";
            }
        }
    } else {
        $error['check_permission'] = " <section class='content-header'><span class='label label-danger'>You have no permission to create blog</span></section>";
    }
}
?>
<section class="content-header">
    <h1>Add Blog <small><a href='blogs.php'> <i class='fa fa-angle-double-left'></i>&nbsp;&nbsp;&nbsp;Back to Blogs</a></small></h1>

    <?php echo isset($error['add_category']) ? $error['add_category'] : ''; ?>
    <ol class="breadcrumb">
        <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
    </ol>
    <hr />
</section>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <?php if ($permissions['categories']['create'] == 0) { ?>
                <div class="alert alert-danger">You have no permission to create blog.</div>
            <?php } ?>
            <!-- general form elements -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Add Blog</h3>

                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="box-body">
                        <?php $sql = "Select * from blog_categories";
                        $db->sql($sql);
                        $result = $db->getResult();
                        ?>
                        <div class="form-group">
                            <label class="control-label " for='category'>Blog Categories</label>
                            <select name='category_id' id='category_id' class='form-control'>
                            <option value="">Select Category</option>
                            <?php foreach ($result as $row) { ?>
                                <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
                            <?php } ?>
                            </select>
                            <br>
                        </div>

                        <div class="form-group">
                            <label for="exampleInputEmail1">Blog Name</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>

                        <div class="form-group">
                            <label for="exampleInputFile">Image&nbsp;&nbsp;&nbsp;*Please choose square image of larger than 350px*350px & smaller than 550px*550px.</label><?php echo isset($error['image']) ? $error['image'] : ''; ?>
                            <input type="file" name="image" id="image" required />
                        </div>
                        <div class="form-group">
                            <label for="app_name">Description:</label>
                            <textarea rows="10" cols="10" class="form-control" name="description" id="description" required></textarea>
                        </div>
                    </div><!-- /.box-body -->

                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary" name="btnAdd">Add</button>
                        <input type="reset" class="btn-warning btn" value="Clear" />

                    </div>

                </form>

            </div><!-- /.box -->
            <?php echo isset($error['check_permission']) ? $error['check_permission'] : ''; ?>
        </div>
    </div>
</section>

<div class="separator"> </div>
<script type="text/javascript" src="css/js/ckeditor/ckeditor.js"></script>
<script type="text/javascript">
    CKEDITOR.replace('description');
</script>

<?php $db->disconnect(); ?>