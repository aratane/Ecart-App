<?php
$offset = 0;
$limit = 5;
$sql = "select cc.*,case when cc.type='admin' then a.username else u.name end as name from complaint_comments cc
    left join admin a on a.id = cc.type_id
    left join users u on u.id = cc.type_id WHERE cc.complaint_id = $id1 ORDER BY created DESC limit $offset,$limit";
$db->sql($sql);
$messages = $db->getResult();
?>
<html>

<head>
    <meta charset="UTF-8">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <title>Complaint | <?= $settings['app_name'] ?> - Dashboard</title>

    <style type="text/css">
        .box .box-primary .direct-chat .direct-chat-primary {
            min-height: 400px;
        }
        .direct-chat-messages{
            min-height: 400px;
        }
        .direct-chat-text {
            width: max-content;
        }

        .triangle1 {
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0 8px 8px 8px;
            border-color: transparent transparent #6fbced transparent;
            margin-right: 20px;
            float: right;
            clear: both;
        }

        .message1 {
            padding: 10px;
            color: #f9f5f5f5;
            margin-right: 15px;
            background-color: #6fbced;
            line-height: 20px;
            max-width: 90%;
            display: inline-block;
            text-align: left;
            border-radius: 5px;
            float: right;
            clear: both;
        }

        .triangle {
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0 8px 8px 8px;
            border-color: transparent transparent #58b666 transparent;
            margin-left: 20px;
            clear: both;
        }

        .message {
            padding: 10px;
            margin-left: 15px;
            background-color: #58b666;
            line-height: 20px;
            max-width: 90%;
            display: inline-block;
            text-align: left;
            border-radius: 5px;
            clear: both;
            color: white;
            font-size: 14px;
        }

        .right .direct-chat-text {
            /* float: right; */
            margin-right: 10;
            display: table;
        }

        .user {
            color: #4787ad;
            font-size: 16px;
        }

        .right .user {
            float: right;
        }

        #loader {
            text-align: center;
            height: 50px;
        }

        #loader img {
            max-height: 100%;
        }

        .created .right {
            color: black;
            float: right;
            font-size: 10px;
            clear: both;
        }

        .box-footer {
            /* height: 100px; */
            padding: 20px 30px 10px 20px;
            /* background-color: #622569; */
            overflow: hidden;
            position: fixed;
        }

        body {
            font-family: Arial;
        }
    </style>
</head>

<body>
    <section class="content-header">
        <h1> Complaints / <small><a href="home.php"><i class="fa fa-home"></i> Home</a></small></h1>
    </section>

    <section class="content">
        <div style="margin-top: 51px;">
            <div class="col-xs-12">
                <!-- DIRECT CHAT PRIMARY -->
                <div class="box">
                    <section class="content-header" style="font-size: 1.5em; color: #3c8dbc;">
                        <b>Complaint:</b> <?= $complaint[0]['message'] ?><br>
                        <b>User:</b> <?= $complaint[0]['name'] ?><br>
                        <b>Created_ON:</b> <?= $complaint[0]['created'] ?><br><br />
                    </section>

                    <div class="box box-primary direct-chat direct-chat-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">Direct Chat</h3>
                            <div class="box-tools pull-right">
                                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                            </div>
                        </div>
                        <!-- lode more -->
                        <!-- /.box-header -->
                        <div class="box-body">
                            <!-- Conversations are loaded here -->
                            <div id="chat" class="direct-chat-messages" data-limit="<?= $limit ?>" data-offset="<?= $offset + $limit ?>" data-max-loaded="false">
                                <!-- Message. Default to the left -->
                                <div class='inner'>
                                    <?php
                                    if (!empty($messages)) {
                                        $messages = array_reverse($messages);
                                        for ($i = 0; $i < count($messages); $i++) { ?>

                                            <?php if ($messages[$i]['type'] == 'admin') { ?>
                                                <div id="" class="direct-chat-msg right">
                                                    <div>
                                                        <span class="user"><?= $_SESSION['user']; ?>:</span>
                                                    </div>
                                                    <div class="triangle1"></div>
                                                    <div id="" class="message1">
                                                        <span class="chat">
                                                            <?= $messages[$i]['message']; ?></span> <br />
                                                        <div>
                                                            <span class="created">
                                                                <?= $messages[$i]['created']; ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php  } else { ?>
                                                <div id="message" class="direct-chat-msg">
                                                    <div>
                                                        <span> <?= $messages[$i]['name']; ?>:</span>
                                                    </div>
                                                    <div class="triangle"></div>
                                                    <div id="" class="message">
                                                        <span><?= $messages[$i]['message']; ?></span><br />
                                                        <div>
                                                            <span>
                                                                <?= $messages[$i]['created']; ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php
                                            } ?>
                                    <?php  }
                                    } else {
                                        echo "<p class='text-center'>No messages found. Start sending messages</p>";
                                    } ?>
                                </div>
                            </div>

                            <div class="box-footer">
                                <form action="" method="post" id="form" enctype="multipart/form-data">
                                    <div class="input-group">
                                        <?php if ($_SESSION['email'] == $complaint[0]['email']) { ?>
                                            <input type="hidden" name="type" value="user" id="type">
                                        <?php  } else { ?>
                                            <input type="hidden" name="type" value="admin" id="type">
                                        <?php
                                        }
                                        ?>
                                        <input type="hidden" name="send" value="1" id="send">
                                        <input style="border-color: #2d5f7f; border-style: double;  border-width: 2px;" type="text" name="message" id="comment" placeholder="Type Message ..." class="form-control" required>

                                        <span class="input-group-btn">
                                            <button type="submit" id="submit" value="Post Comment" name="submit" class="btn btn-primary btn-flat">Send</button>
                                        </span>
                                    </div>
                                </form>
                            </div>
                            <!-- /.box-footer-->
                        </div>
                    </div>
                </div>
            </div>
    </section>


    <script>
        var complaint_id = "<?= (isset($_GET['id']) && !empty(trim($_GET['id'])) && is_numeric($_GET['id'])) ? $db->escapeString($fn->xss_clean($_GET['id'])) : ""; ?>";
        var type_id = "<?= (isset($_SESSION['id']) && !empty(trim($_SESSION['id'])) && is_numeric($_SESSION['id'])) ? $db->escapeString($fn->xss_clean($_SESSION['id'])) : ""; ?>";
        var username = "<?= (isset($_SESSION['user']) && !empty(trim($_SESSION['user']))) ? $db->escapeString($fn->xss_clean($_SESSION['user'])) : "Admin"; ?>";

        $(document).ready(function() {
            $("#chat").scrollTop($("#chat")[0].scrollHeight);
            $('#chat').scroll(function() {
                if ($('#chat').scrollTop() == 0) {
                    load_messages( $(this));
                }
            });
        });
        $('#chat').bind('mousewheel', function(e) {
            if (e.originalEvent.wheelDelta / 120 > 0) {
                if ($(".inner")[0].scrollHeight < 380) {
                    console.log('scrolling up !');
                    load_messages( $(this));
                }
            }
        });

        function load_messages(element) {
            var limit = element.data('limit');
            var offset = element.data('offset');

            var data = {
                get_complaint_comments: 1,
                complaint_id: complaint_id,
                limit: limit,
                offset: offset
            }
            element.data('offset', limit + offset);
            var max_loaded = element.data('max-loaded');
            if (max_loaded == false) {
                var loader = '<div id="loader"><img src="images/loader.gif" alt="Loading. please wait.. ." title="Loading. please wait.. ."></div>';
                $.ajax({
                    url: 'api-firebase/ajax-data.php',
                    method: "post",
                    data: data,
                    processData: true,
                    cache: false,
                    beforeSend: function() {
                        $('.inner').prepend(loader);
                    },
                    dataType: 'json',
                    success: function(result) {
                        var messages_html = is_right = "";
                        if (result.error == false && result.data.length > 0) {
                            result.data.forEach(message => {
                                is_right = (message.type == 'admin') ? 'right' : '';
                                msg_class = (message.type == 'admin') ? 'message1' : 'message';
                                tiangle_class = (message.type == 'admin') ? 'triangle1' : 'triangle';
                                messages_html += '<div id="message" class="direct-chat-msg ' + is_right + '">' +
                                    '<div>' +
                                    '<span class= "user"> ' + message.username + ':</span>' +
                                    '</div>' +
                                    '<div class="' + tiangle_class + '"></div>' +
                                    '<div class="' + msg_class + '">' +
                                    '<span class="chat">' + message.message + '</span><br />' +
                                    '<div>' +
                                    '<span class="created">' +
                                    message.created +
                                    '</span>' +
                                    '</div>' +
                                    '</div>' +
                                    '</div>'
                            });
                            $('.inner').prepend(messages_html);
                        } else {
                            element.data('offset', offset);
                            element.data('max-loaded', true);
                            $('.inner').prepend('<div class="text-center"> <p>You have reached the top most message!</p></div>');
                        }
                        $('#loader').remove();
                        $('#chat').scrollTop(20); // Scroll alittle way down, to allow user to scroll more
                    }
                });
            }
        }
        $('#form').on('submit', function(e) {
            e.preventDefault();
            var message = $('#comment').val().trim();
            var formData = new FormData(this);
            formData.append('complaint_id', complaint_id);
            formData.append('type_id', type_id);
            formData.append('message', message);
            if (message != "") {
                $.ajax({
                    url: 'api-firebase/ajax-data.php',
                    type: 'POST',
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(result) {
                        var messages_html = "";
                        messages_html += '<div id="message" class="direct-chat-msg right">' +
                            '<div>' +
                            '<span class= "user"> ' + username + ':</span>' +
                            '</div>' +
                            '<div class="triangle1"></div>' +
                            '<div class="message1">' +
                            '<span class="chat">' + message + '</span><br />' +
                            '<div>' +
                            '<span class="created">' +  Date.now()  + ' </span>' +
                            '</div>' +
                            '</div>' +
                            '</div>'
                        $('.inner').append(messages_html);
                        $('#comment').val('');
                        $("#chat").scrollTop($("#chat")[0].scrollHeight);
                    }
                });
            }
        });
    </script>
</body>

</html>