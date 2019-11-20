<?php

class server
{
    const HOST = "0.0.0.0";
    const PORT = 9501;
    
    private $server;
    private $event;
    private $sendMessage;


    public function __construct($host = null, $port = null) {
       $host = $host ? $host : self::HOST;
       $port = $port ? $port : self::PORT;
       $this->server = new Swoole\WebSocket\Server($host, $port);
       $this->server->set([
           'worker_num' => 2, // 工作进程
           'task_worker_num' => 8, //task进程数量
       ]);
       $this->server->on('open', [$this,'onOpen']);
       $this->server->on('message',[$this,'onMessage']);
       $this->server->on("task",[$this,"onTask"]);
       $this->server->on("finish",[$this,"onFinish"]);
       $this->server->on('close', [$this,'onClose']);
       $this->server->start();
    }
    
    public function onOpen(Swoole\WebSocket\Server $server, $request)
    {
        var_dump($request);
    }
    
    public function onMessage(Swoole\WebSocket\Server $server, $frame)
    {
        $data = json_decode($frame->data,true);
        $this->event = $data['event'];
        $this->events($data);
    }
    public function onTask($server,$task_id,$src_worker_id,$data)
    {
       
    }
    public function onFinish ($server,$task_id,$data)
    {
       
    }
    public function onClose(swoole_websocket_server $ws,$fd)
    {
        
    }
    //发送消息的事件
    public function events($data)
    {
        $this->sendMessage = [];
        switch ($this->event) {
            case "bind":
               
                break;
            case "say":
                break;
            case "say_img":
                break;
            case "online":
                break;
        }
        return $this->sendMessage;
    }
}


$server = new server(); 



