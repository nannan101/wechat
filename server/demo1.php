<?php
/**
 * ipcs  -m (查看共享内存的分片)
 * swoole 的结构
 *    Master   主进程
 *    Manager  管理worker 和 task
 *    worker 主逻辑进程
 *    task   处理耗时较长逻辑
 * 
 * task 传递数据的大小 数据小于8k:直接通过管道传输；数据量大于8k:写入临时文件
 * 
 * 
 * 
 * 创建TCP服务器 
 * 
 * 
 * 
 * 
 */

class Server
{
    const HOST = '0.0.0.0';
    const PORT = 9501;

    private $server = null;
    
    public function __callStatic($host = null, $port) {
        $host = $host ? $host : self::HOST;
        $port = $port ? $port : self::PORT;
        
        $this->server = new Swoole\Server($host, $port);
        $this->server->set([
            'worker_num' => 4, // 设置启动的Worker进程数。业务代码是全异步非阻塞的，这里设置为CPU核数的1-4倍最合理
            'daemonize' => 0, // 守护进程化。设置daemonize => 1时，程序将转入后台作为守护进程运行。长时间运行的服务器端程序必须启用此项。如果不启用守护进程，当ssh终端退出后，程序将被终止运行。
            'max_request' => 1000, 
            'dispatch_mode' => 2, //固定模式，根据连接的文件描述符分配Worker。这样可以保证同一个连接发来的数据只会被同一个Worker处理
            'task_worker_num'=> 4,
        ]);
        $this->server->on('Start', [$this,'onStart']);
        $this->server->on('Connect',[$this,'onConnect']);
        $this->server->on("Receive", [$this,'onReceive']);
        $this->server->on("Close", [$this,'onClose']);
        /*bind collback*/
        $this->server->on("Task", [$this,"onTask"]);
        $this->server->on("Finish", [$this,"onFinish"]);
        
        $this->server->start();
        
    }
    public function onStart($serv)
    {
        echo 'start';
    }

    public function onConnect(Swoole\Server $server, $fd, $from_id)
    {
        echo 'client' . $fd . "connect". PHP_EOL;
    }
    
    public function onReceive(Swoole\Server $server, $fd, $from_id, $data)
    {
        echo 'Get Message From Client' . $fd . ":" . $data;
        $data = [
            'task' => 'task_one',
            'params' => $data,
            'fd' => $fd,
        ];
        $server->task(json_encode($data));
        
    }
    
    public function onClose(Swoole\Server $server,$fd,$form_id)
    {
        echo 'client' . $fd . "close connection";
    }
    
    public function onTask(Swoole\Server $server ,$task_id, $from_id, $data)
    {
        echo 'this task'.$task_id.'from worker'. $from_id;
        echo 'data:' . $data . PHP_EOL;
    
        return "finished";
    }
    
    public function onFinish(Swoole\Server $server, $task_id, $data)
    {
        echo 'task' .$task_id. "finsh";
        echo 'dd';
    }
}
//新建连接池
class Connect{
    const HOST = '0.0.0.0';
    const PORT = 9501;

    private $_serv = null;
    
    
    public function __construct($host = null , $port = null) 
    {
        $host = $host ? $host : self::HOST;
        $port = $port ? $port : self::HOST;
        $this->_serv = new Swoole\Server($host, $port);
        $this->_serv->set([
            'worker_num' => 4,
            'dispatch_mode' =>2,
            'task_worker_num' => 4,
        ]);
        $this->_serv->on('Start', [$this,'onStart']);
        $this->_serv->on('WorkerStart', [$this,'onWorkerStart']);
        $this->_serv->on('Connect',[$this,'onConnect']);
        $this->_serv->on('Receive', [$this,'onReceive']);
        $this->_serv->on('Task', [$this,'onTask']);
        $this->_serv->on('Finish', [$this,'onFinish']);
        $this->_serv->start();
    }
    
    public function onStart(\Swoole\Server $server)
    {
        echo 'master start';
        
        var_dump($server);
        
        echo PHP_EOL;
    }

    public function onWorkerStart(Swoole\Server $server, $worker_id)
    {
        if($server->taskworker){
             
            echo 'task '.$worker_id.' proccess!!!' .PHP_EOL;
        } else {
            echo 'worker proccess' . PHP_EOL;
        }
    }
    /*$server是Swoole\Server对象  $fd客户端连接的唯一标识符 $reactorId来自哪个Reactor线程*/
    public function onConnect(\Swoole\Server $server, $fd , $reactorId)
    {
        echo 'client connect' .$fd . 'reactor_nu :' . $reactorId ;
       
    }
    /**
     * 
     * @param \Swoole\Server $server Server对象
     * @param type $fd  客户端连接的唯一标识符
     * @param type $reactorId $reactor_id，TCP连接所在的Reactor线程ID
     * @param type $data 收到的数据内容，可能是文本或者二进制内容
     */
    public function onReceive(\Swoole\Server $server,$fd,$reactorId, $data)
    {
        echo 'get client message:'.$data;
    }
    
    /**
     * 
     * @param \Swoole\Server $server server对象
     * @param type $task_id task_id是任务ID
     * @param type $src_worker_id 来源于那个工作进程
     * @param type $data 是任务的内容
     */
    public function onTask(\Swoole\Server $server,$task_id,$src_worker_id,$data )
    {
        echo 'task porccess message: '.$data . PHP_EOL;
        
        return 'finished';
    }
    public function onFinish(\Swoole\Server $server, $task_id, $data)
    {
        echo 'finish '.$task_id.' end';
    }
}
$dd = new Connect();