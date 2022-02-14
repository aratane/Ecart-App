<?php
include_once('includes/functions.php');
$function = new functions;
include_once('includes/custom-functions.php');
$fn = new custom_functions;

?>
<?php
// MySQL query that retrieves  all the tickets from the databse
$sql = "SELECT * FROM complaints ";
$result = $db->sql($sql);
$row = $db->getResult();
?>

<!-- <link href="style.css" rel="stylesheet" type="text/css"> -->
<section class="content-header">
    <h1>
        Complaints /
        <small><a href="home.php"><i class="fa fa-home"></i> Home</a></small>
    </h1>
</section>
<!-- <?php
if ($permissions['products']['read'] == 1) {
?> -->
    <!-- Main content -->
    <section class="content">
        <!-- Main row -->
        <div class="row">
            <!-- Left col -->
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header with-border">
                        <form method="POST" id="filter_form" name="filter_form">

                            <div class="form-group col-md-3">
                            </div>
                        </form>
                    </div>
                    <div class="box-header">
                        <h3 class="box-title">Complaints</h3>
                    </div>
                    <div class="box-body table-responsive">

                        <table id='products_table' class="table table-hover" data-toggle="table" data-url="api-firebase/get-bootstrap-table-data.php?table=complaints" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-refresh="true" data-show-columns="true" data-side-pagination="server" data-pagination="true" data-search="true" data-trim-on-search="false" data-filter-control="true" data-query-params="queryParams" data-sort-name="id" data-sort-order="desc" >
                            <thead>
                                <tr>
                                    <th data-field="id" data-sortable="true">ID</th>
                                    <th data-field="title" data-sortable="true">Title</th>
                                    <th data-field="message" data-sortable="true">Message</th>
                                    <th data-field="image">Image</th>
                                    <th data-field="email" data-sortable="true">Email</th>
                                    <th data-field="status" data-sortable="true">Status</th>
                                    <th data-field="type" data-sortable="true">Type</th>
                                    <th data-field="operate" data-events="actionEvents">Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <!-- /.box-body -->
                </div>
                <!-- /.box -->
            </div>
            <div class="separator"> </div>
        </div>
        <!-- /.row (main row) -->
    </section>
<?php } else { ?>
    <div class="alert alert-danger topmargin-sm" style="margin-top: 20px;">You have no permission to view products.</div>
<?php } ?>

<script>
    function queryParams(p) {
        return {
            "category_id": $('#category_id').val(),
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }
</script>
<script>
    $('#category_id').on('change', function() {
        id = $('#category_id').val();
        $('#products_table').bootstrapTable('refresh');
    });
</script>