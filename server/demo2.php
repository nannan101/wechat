<?php

/**
 * io句柄
 * 消息队列独立于进程之外
 * 管道处于进程之中
 * io多路复用
 * 
 * eventloop 添加一个描述符的监听
 * 
 * 
 */
class EventLoop {

    const HOST = '0.0.0.0';
    const PORT = 9501;
    
    private $_serv = null;

    public function __construct($host, $port) {
        $host = $host ? $host : self::HOST;
        $port = $port ? $port : self::PORT;
        $this->_serv = new Swoole\Server($host, $port);
        $this->_serv->set([
            'worker_num' => 4,
            'task_worker_num '=>4,
            'daemonize' => true,
        ]);
        $this->_serv->on("Start",[$this,"onStart"]);
        $this->_serv->on("Connect", [$this,"onConnect"]);
        $this->_serv->on('Receive',[$this,"onReceive"]);
        $this->_serv->on("Close",[$this,'onClose']);
    }
    
    public function onStart(\Swoole\Server $server)
    {
        echo 'Start' .PHP_EOL;
    }
    public function onConnect(\Swoole\Server $server,$fd, $reactor_id)
    {
        echo 'Client' . $fd . 'connect'.PHP_EOL;
    }
    public function onReceive(\Swoole\Server $server,$fd,$reactor_id,$data)
    {
        echo 'Get Message From Client'.$fd.":".$data;
        foreach ($server->connections as $client){
            if($client != $fd){
                $server->send($client, $data);
            }
        }
    }
    public function onClose(\Swoole\Server $server,$fd, $reactor_id)
    {
        echo 'Client' . $fd . 'close connection'.PHP_EOL;
    }
}
