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
<?php include "header.php"; ?>
<html>

<head>
    <title>Flash Sales | <?= $settings['app_name'] ?> - Dashboard</title>

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
        <section class="content-header">
            <h1>Flash Sales</h1>
            <ol class="breadcrumb">
                <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
            </ol>
            <hr />
        </section>
        <?php
        include_once('includes/functions.php');
        ?>
        <section class="content">
            <div class="row">
                <div class="col-md-6">
                    <?php if ($permissions['featured']['create'] == 0) { ?>
                        <div class="alert alert-danger" id="create">You have no permission to create flash sales.</div>
                    <?php } ?>
                    <?php if ($permissions['featured']['update'] == 0) { ?>
                        <div class="alert alert-danger" id="update" style="display: none;">You have no permission to update flash sales.</div>
                    <?php } ?>
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">Create / Manage flash sales</h3>
                        </div>
                        <form id="flash_sales_form" method="post" action="api-firebase/flash-sales.php" enctype="multipart/form-data">
                            <div class="box-body">
                                <input type='hidden' name='accesskey' id='accesskey' value='90336' />
                                <input type='hidden' name='add-flash-sales' id='add-flash-sales' value='1' />
                                <input type='hidden' name='flash-sales-id' id='flash-sales-id' value='' />
                                <input type='hidden' name='edit-flash-sales' id='edit-flash-sales' value='' />
                                <div class="form-group">
                                    <label for='title'>Title for flash sales</label>
                                    <input type='text' name='title' id='title' class='form-control' placeholder='Ex : Weekends deal' required />
                                </div>
                                <div class="form-group">
                                    <label for='short_description'>Short Description</label>
                                    <input type='text' name='short_description' id='short_description' class='form-control' placeholder='Ex : Weekends deal' required />
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label>Status</label>
                                        <div id="status" class="btn-group">
                                            <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                <input type="radio" name="status" value="0"> Deactive
                                            </label>
                                            <label class="btn btn-primary" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                <input type="radio" name="status" value="1" checked> Active
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="box-footer">
                                <input type="submit" class="btn-primary btn" value="Create" id='submit_btn' />
                                <input type="reset" class="btn-default btn" value="Reset" id='reset_btn' />
                            </div>
                        </form>
                        <div id='result' style="display: none;"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <?php if ($permissions['featured']['read'] == 1) { ?>
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">Flash Sales</h3>
                            </div>
                            <table id="flash_sales_table" class="table table-hover" data-toggle="table" data-url="api-firebase/get-bootstrap-table-data.php?table=flash_sales" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-refresh="true" data-show-columns="true" data-side-pagination="server" data-pagination="true" data-search="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">ID</th>
                                        <th data-field="title" data-sortable="true">Title</th>
                                        <th data-field="short_description" data-sortable="true">Short Description</th>
                                        <th data-field="status" data-sortable="true">Status</th>
                                        <th data-field="operate" data-events="actionEvents">Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                </div>
            <?php } else { ?>
                <div class="alert alert-danger">You have no permission to view Flash Sales.</div>
            <?php } ?>
            </div>
        </section>
    </div>
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.16.0/jquery.validate.min.js"></script> -->
    <script src="dist/js/jquery.validate.min.js"></script>
    <script>
        $("#flash_sales_form").validate({
            rules: {
                title: "required",
                short_description: "required"
            }
        });
        $('#flash_sales_form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            if ($("#flash_sales_form").validate().form()) {
                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: formData,
                    dataType: 'json',
                    beforeSend: function() {
                        $('#submit_btn').val('Please wait..').attr('disabled', true);
                    },
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(result) {
                        $('#result').html(result.message);
                        $('#result').show().delay(6000).fadeOut();
                        $('#submit_btn').attr('disabled', false);
                        $('#add-flash-sales').val(1);
                        $('#edit-flash-sales').val('');
                        $('#flash-sales-id').val('');
                        $('#title').val('');
                        $('#short_description').val('');
                        $('#submit_btn').val('Create');
                        $('#flash_sales_table').bootstrapTable('refresh');
                    }
                });
            }
        });
    </script>
    <script>
        window.actionEvents = {
            'click .edit-flash-sales': function(e, value, row, index) {
                $("input[name=status][value=1]").prop('checked', true);
                if ($(row.status).text() == 'Deactive')
                    $("input[name=status][value=0]").prop('checked', true);

                $('#add-flash-sales').val('');
                $('#edit-flash-sales').val(1);
                $('#flash-sales-id').val(row.id);
                $('#title').val(row.title);
                $('#short_description').val(row.short_description);
                $('#submit_btn').val('Update');
            }
        };
    </script>
    <script>
        $(document).on('click', '#reset_btn', function() {
            $('#add-flash-sales').val(1);
            $('#edit-flash-sales').val('');
            $('#flash-sales-id').val('');
            $('#submit_btn').val('Create');

        });
    </script>
    <script>
        $(document).on('click', '.delete-flash-sales', function() {
            if (confirm('Are you sure Want to Delete Flash sales?')) {
                id = $(this).data("id");
                $.ajax({
                    url: 'api-firebase/flash-sales.php',
                    type: "get",
                    data: 'accesskey=90336&id=' + id + '&type=delete-flash-sales',
                    success: function(result) {
                        if (result == 1) {
                            $('#flash_sales_table').bootstrapTable('refresh');
                        }
                        if (result == 2) {
                            alert('You have no permission to delete flash sales');
                        }
                        if (result == 0) {
                            alert('Error! flash sales could not be deleted');
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>
<?php include "footer.php"; ?>