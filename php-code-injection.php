<?php
$page_data = <<<EOT
<div class="page-header">
    <h1>PHP Code Injection</h1>
</div>
<div class="row">
    <div class="col-md-12">
        <p>
            Enter your math operation:
            <div class="form-group">
                <input type="text" class="form-control" id="op" name="op" placeholder="Operation">
            </div>
            <button type="submit" class="btn btn-success" id="send">Calculate!</button>
        </p>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <p id="result">
        </p>
    </div>
</div>
EOT;

$page_script= <<<EOT
$(document).ready(function(){
//Open a WS server connection
var wsUri = "ws://dvws.local:8080/php-code-injection";
websocket = new WebSocket(wsUri);

//Connected to WS server
websocket.onopen = function(ev)
{
    console.log('Connected to server');
}

//Close WS server connection
websocket.onclose = function(ev)
{
    console.log('Disconnected from server');
};

//Message received from WS server
websocket.onmessage = function(ev)
{
    console.log('Message: '+ev.data);
    document.getElementById("result").innerHTML = "<pre>" + ev.data + "</pre>";
};

//Error
websocket.onerror = function(ev)
{
    console.log('Error: '+ev.data);
};

//Send value to WS
$('#send').click(function()
{
    var field_value = document.getElementById('op').value;
    console.log(field_value);
    websocket.send(field_value);
});
});
EOT;
?>

<?php require_once('includes/template.php'); ?>


