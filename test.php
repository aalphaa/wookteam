<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Chat Client</title>
</head>
<body>
<script>
    window.onload = function () {
        var nick = prompt("Enter your nickname");
        var input = document.getElementById("input");
        input.focus();

        // 初始化客户端套接字并建立连接
        var socket = new WebSocket("ws://127.0.0.1:5200");

        // 连接建立时触发
        socket.onopen = function (event) {
            console.log("Connection open ...");
        }

        // 接收到服务端推送时执行
        socket.onmessage = function (event) {
            var msg = event.data;
            var node = document.createTextNode(msg);
            var div = document.createElement("div");
            div.appendChild(node);
            document.body.insertBefore(div, input);
            input.scrollIntoView();
        };

        // 连接关闭时触发
        socket.onclose = function (event) {
            console.log("Connection closed ...");
        }

        input.onchange = function () {
            var msg = nick + ": " + input.value;
            // 将输入框变更信息通过 send 方法发送到服务器
            socket.send(msg);
            input.value = "";
        };
    }
</script>
<input id="input" style="width: 100%;">
</body>
</html>
