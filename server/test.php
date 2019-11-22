<?php
/**
 * 创建TCP服务器
 */
class tcp{
    
    const HOST = "0.0.0.0";
    const PORT = "9501";

    private $server = null;
    


    public function __construct($host = null, $port = null) {
        $host = $host ? $host : self::HOST;
        $port = $port ? $host : self::PORT;
        /*创建Server对象，监听 127.0.0.1:9501端口*/
        $this->server = new Swoole\Server($host, $port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        /*监听连接事件*/
        $this->server->on("Connect", [$this,"onConnect"]);
        /*监听客户端发送的消息事件*/
        $this->server->on("Receive", [$this,"onReceive"]);
        /*监听连接关闭事件*/
        $this->server->on('Close', [$this,"onClose"]);
        /*启动服务*/
        $this->server->start();
    }
    
    public function onConnect(Swoole\Server $server, $fd)
    {
        echo '客户端连接' . "client 连接中";
    }
    
    public function onReceive(Swoole\Server $server, $fd, $from_id, $data)
    {
        // 服务端主动发送消息给客户端
     
         $server->send($fd, $json);
    }
    
    public function onClose(Swoole\Server $server, $fd)
    {
        echo 'client:close';
    }
    
}

/**
 * UDP服务器与TCP服务器不同，UDP没有连接的概念。启动Server后，客户端无需Connect，直接可以向Server监听的9502端口发送数据包。对应的事件为onPacket。
 */
class udp{
    const HOST = "0.0.0.0";
    const PORT = "9501";
    
    private $server;

    public function __construct() {
        $this->server= new Swoole\Server(self::HOST, self::PORT, SWOOLE_PROCESS, SWOOLE_SOCK_UDP);
        $this->server->on("Packet", [$this,"onPacket"]);
        $this->server->start();
    }
    
    public function onPacket(Swoole\Server $server, $data, $clientInfo)
    {
        $server->sendto($clientInfo['address'], $clientInfo['port'], "Server ".$send_data);
        var_dump($clientInfo);
    }
    
    
}
/**
 * 创建Web服务器
 */
class web{
    
    const HOST = "0.0.0.0";
    const PORT = "9501";
    
    private $server;
    
    public function __construct() {
       
        $this->server = new Swoole\Http\Server(self::HOST, self::PORT);
        
        $this->server->on("request", [$this, "onRequest"]);
        $this->server->start();
        
    }
    
    public function onRequest(Swoole\Http\Request $request,$response)
    {
        var_dump($request->get , $request->post);
        $response->header("Content-Type", "text/html; charset=utf-8");
        $response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>");
        
    }
}

class  websocket
{
    const HOST = "0.0.0.0";
    const PORT = "9501";
    
    private $server;
    
    public function __construct() {
        $this->server = new Swoole\WebSocket\Server($host, $port);
        
        $this->server->on("open", [$this,"onOpen"]);
        $this->server->on('message',[$this,'onMessage']);
        $this->server->on('task',[$this,'onTask']);
        $this->server->on('finish',[$this,'onFinish']);
        $this->server->on("close", [$this,"onClose"]);
    }
    
    public function onOpen(Swoole\WebSocket\Server $server, Swoole\Http\Request $request)
    {
        var_dump($request->fd , $request->get , $request->post);
        $server->push($request->fd, "hello , welcome \n");
        
    }
    public function onMessage(Swoole\WebSocket\Server $server, Swoole\WebSocket\Frame $frame)
    {
        echo 'Message: ' .  $frame->data;
        $server->push($frame->fd, 'server : ' . $frame->data);
        /*每隔2000毫秒执行一次(函数就相当于setInterval，是持续触发的)*/
        swoole_timer_tick(2000, function ($timer_id){
            echo 'tick-2000ms\n';
        });
        /*函数相当于setTimeout，仅在约定的时间触发一次*/
        swoole_timer_after(2000, function($timer_id){
            echo 'after-2000ms\n';
        });
        $data = [
            'name' => '张三',
            'age' => '18',
            'sex' => 'falme',
        ];
        $server->task($data);
    }
    public function onTask(Swoole\WebSocket\Server $server, $task_id , $from_id, $data)
    {
        echo 'New AsyncTask[id=' . $task_id . ']'. PHP_EOL;
        $server->finish($data);
    }
    public function onFinish(Swoole\WebSocket\Server $server, $task_id, $data)
    {
        echo "AsyncTask[$task_id] Finish: $data".PHP_EOL;
    }

    public function onClose(Swoole\WebSocket\Server $server, $fd)
    {
        echo 'client - ' . $fd . "is closed";
        $server->close($fd);
        
    }
    
}

//创建同步TCP客户端
$client = new \Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
if(!$client->connect('127.0.0.1',9501,0.5)){
    die('connect failed .' );
}

if(! $client->send($data)){
    die('snd failed');
}

//等待服务器发送消息
$data = $client->recv();

if(!$data) {
    die('recv failed');
}
echo $data;

//关闭客户端
$client->close();


//创建异步tcp
$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

//注册连接成功回调
$client->on("connect", function($cli) {
    $cli->send("hello world\n");
});

//注册数据接收回调
$client->on("receive", function($cli, $data){
    echo "Received: ".$data."\n";
});

//注册连接失败回调
$client->on("error", function($cli){
    echo "Connect failed\n";
});

//注册连接关闭回调
$client->on("close", function($cli){
    echo "Connection close\n";
});

//发起连接
$client->connect('127.0.0.1', 9501, 0.5);