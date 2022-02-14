<?php
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

include_once('includes/custom-functions.php');
include_once('includes/crud.php');
include_once('includes/functions.php');
include_once('includes/variables.php');

$function = new functions;
$fn = new custom_functions;
$db = new Database();
$db->connect();

$id = (isset($_GET['id']) && !empty($fn->xss_clean($_GET['id'])) && is_numeric($_GET['id'])) ? $db->escapeString($fn->xss_clean($_GET['id'])) : "";
if (empty($id)) {
    header("location:support-system.php");
    exit();
}

// MySQL query that selects complaint by the ID 
$sql = "SELECT c.*,u.name FROM complaints c left join users u on u.id = c.user_id WHERE c.id = $id ORDER BY `created` ASC ";
$db->sql($sql);
$complaint = $db->getResult();
if (empty($complaint)) {
    header("location:support-system.php");
    exit();
}
$id1 = $complaint[0]['id'];

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php include "header.php"; ?>
<html>

<head>
    <title>View Complaints Support | <?= $settings['app_name'] ?> - Dashboard</title>
</head>
</body>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <?php include('public/support-view-data.php'); ?>
</div><!-- /.content-wrapper -->
</body>

</html>
<?php include "footer.php"; ?>