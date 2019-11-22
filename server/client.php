<?php
/**
 * client-tcp 客户端
 * 
 * 第一步 运行 开启 server/tcp.php开启server服务端
 * 第二步 如何查看工作进程 ps aft | grep tcp.php
 * 第三步 杀死进程  kill 2455 强制杀死进程 kill -KILL 2455
 * 第四步 执行 php tcp_client.php
 * 普通的同步阻塞+select的使用方法外，Client还支持异步非阻塞回调
 */

//创建swoole tcp-client 客户端  //同步阻塞客户端

$client = new swoole_client(SWOOLE_SOCK_TCP);

if (!$client->connect('127.0.0.1',9501)) {
    echo '连接失败';
    exit();
}
//    php cli模式下的常量

fwrite(STDOUT, "请输入消息:"); //    输入msg
$msg = trim(fgets(STDIN)); //    获取msg-cli
$client->send($msg); //    发送消息
echo $client->recv(); //    获取客户端发送的消息

$client->close(); //    关闭客户端连接
