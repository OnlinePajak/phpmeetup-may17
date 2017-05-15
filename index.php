<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Start the session
session_start();

$baseUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

// Perform Login
if (isset($_POST['username'])) {
    $_SESSION["username"] = $_POST['username'];
}

// Send new message
if (isset($_POST['message'])) {

    $connection = new AMQPStreamConnection($rabbitMQHost, $rabbitMQport, $rabbitMQUser, $rabbitMQPassword);
    $channel = $connection->channel();

    $channel->exchange_declare($rabbitMQExchangeName, 'fanout', false, false, false);

    $timestamp = new \DateTime("now");
    $timestamp = $timestamp->format("Y-m-d H:i:s");

    $data = array(
        "sender" => $_SESSION["username"],
        "message" => $_POST['message'],
        "timestamp" => $timestamp
    );

    $msg = new AMQPMessage(json_encode($data));

    $channel->basic_publish($msg, $rabbitMQExchangeName);

    $channel->close();
    $connection->close();
    exit;
}


if (isset($_SESSION["username"])) {
    $loggedIn = true;
} else {
    $loggedIn = false;
}
?>
<html>
<head>
    <title>Chat with RabbitMQ</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.0/jquery.min.js"></script>

    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://code.getmdl.io/1.3.0/material.light_green-red.min.css">
    <script defer src="https://code.getmdl.io/1.3.0/material.min.js"></script>

    <script type="text/javascript">
        var username = '<?php echo $_SESSION["username"]; ?>';

        $(document).ready(function(){
            if ($("#chat-table").length) {
                setInterval(function(){
                    $.ajax({
                        type: 'GET',
                        url: '<?php echo $baseUrl; ?>/get_chat.php',
                        data: {},
                        dataType: 'json',
                        contentType: "application/json"
                    }).done(function (msg) {
                        $.each(msg, function(index, value) {
                            var sendername = value.sender;
                            if (sendername == username) sendername = "<b>"+sendername+"</b>";

                            $('#chat-table').prepend(
                                '<tr>'+
                                '<td>'+sendername+'</td>'+
                                '<td>'+value.message+'</td>'+
                                '<td>'+value.timestamp+'</td>'+
                                '</tr>'
                            );
                        });
                    });
                }, 1000);
            }

            if ($("#msg-form").length) {
                $('#msg-form').submit(function(event){
                    event.preventDefault();

                    $.ajax({
                        method: 'POST',
                        url: '<?php echo $baseUrl; ?>/index.php',
                        data: { message: $("#message").val() }
                    }).done(function (msg) {
                        $("#message").val("");
                    });
                });
            }
        });
    </script>
    <style type="text/css">
        .mdl-data-table th, .mdl-data-table td {
            text-align: left;
        }
        .mdl-data-table tbody tr:nth-child(2n) {
            background-color: #FFFFFF;
        }
        .mdl-data-table tbody tr:nth-child(2n+1) {
            background-color: #EFEFEF;
        }
        body{
            background: #fafafa none repeat scroll 0 0;
        }
        .header{
            background: #ae2125 none repeat scroll 0 0;
            height: 90px;
            margin-bottom: 30px;
            color: #ffffff;
        }
        .mdl-button--raised.mdl-button--colored {
            background: #35aa47 none repeat scroll 0 0;
            color: rgb(255, 255, 255);
        }
    </style>

</head>
<body>
    <?php if (!$loggedIn) { ?>
        <div class="header">
            <div style="float:left; margin:30px;">
                <span style="text-decoration:underline; font-size:20px;">
                    Chat Application - RabbitMQ
                </span>
            </div>
        </div>

        <form id="login-form" style="width: 100%; text-align: center;" method="POST">
            <h3>Login</h3>
            <div class="mdl-textfield mdl-js-textfield">
                <input class="mdl-textfield__input" id="username" name="username">
                <label class="mdl-textfield__label" for="username">Username</label>

                <input style=margin-left:150px;" class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect" type="submit" value="Submit" />
            </div>
        </form>
    <?php } else { ?>
        <div class="header">
            <div style="float:left; margin:30px;">
                <span style="text-decoration:underline; font-size:20px;">
                    Chat Application - RabbitMQ
                </span>
            </div>
            <div style="float:right; margin:30px;">
                <div style="vertical-align: bottom;" class="material-icons mdl-badge mdl-badge--overlap">account_box</div>
                <span>Hello, <?php echo $_SESSION["username"]; ?></span>
            </div>
        </div>

        <form id="msg-form" style="width: 100%; text-align: center;" method="POST">
            <div class="mdl-textfield mdl-js-textfield">
                <input class="mdl-textfield__input" id="message" name="message">
                <label class="mdl-textfield__label" for="message">Message</label>

                <input style=margin-left:150px;" class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect" type="submit" value="Send" />
            </div>
        </form>

        <table id="chat-table" style="width:90%; margin:0 5%;" class="mdl-data-table mdl-js-data-table mdl-shadow--2dp">
            <thead>
            <tr>
                <th>Sender</th>
                <th>Message</th>
                <th>Timestamp</th>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>

    <?php } ?>
</body>
</html>