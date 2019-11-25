<?php

/**
 * base-process（监听管道）
 * 异步操作
 */

class BaseProcess
{
    private $proccess = null;
    
    public function __construct() {
        /* 参数[$this,'run']子进程 false 输出到页面上，*/
        $this->proccess = new \Swoole\Process([$this,'run'],false,true);
        $this->proccess->start();
        /*监听管道*/
        swoole_event_add($this->proccess->pipe, function ($data){
            $this->proccess->read();
            echo 'RECV:' . $data . PHP_EOL;
        });
    }
    public function run($worker) {
        swoole_timer_tick(1000, function($timer_id){
            static $index = 0;
            $index = $this->proccess->wait('Hello');
            var_dump($index);
            if($index == 10) {
                swoole_timer_clear($timer_id);
            }
        });
        
    }                                                                         
    
}

new BaseProcess();

//使用Process作为监控父进程，创建管理子进程时，父类必须注册信号SIGCHLD对退出的进程执行wait，否则子进程一旦被kill会引起父进程退出
//设置异步信号监听 (父进程注册SIGCHID)
\Swoole\Process::signal(SIGCHLD, function ($msg){
      //必须为false , 非阻塞模式
    while ($ref = \Swoole\Process::wait(FALSE)){ //回收子进程
      
        echo "PID =" . $ref['pid'] .PHP_EOL;
    }
});
