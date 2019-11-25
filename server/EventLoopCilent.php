<?php
$socket = stream_socket_client('tcp://127.0.0.1:9501',$errno,$errstr,30);
/*读客户端 读取服务端的数据*/
function onRead()
{
    global  $socket;
    
    $buffer = stream_socket_recvfrom($socket, 1024);
    
    if(!$buffer) {
        echo 'server closed' .PHP_EOL;
        swoole_event_del($socket);
    }
    echo PHP_EOL.'RECV:'.$buffer.PHP_EOL;
    fwrite(STDOUT, 'Enter msg:');
}

function onWrite()
{
    global $socket;
    echo 'on write' . PHP_EOL;
}
//客户端把消息发送到服务服务端
function onInput()
{
    global $socket;
    
    $msg = trim(fgets(STDIN));
    
    if ($msg == "exit") {
        swoole_event_exit();
        exit();
    }
    swoole_event_write($socket, $msg);
    fwrite(STDOUT, "Enter Msg:");
}
swoole_event_add($socket, 'onRead','onWrite');

swoole_event_add(STDIN, 'onInput');

 fwrite(STDOUT, "Enter Msg:");

