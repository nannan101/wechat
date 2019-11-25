<?php

/**
 * 动态进程池
 * 
 * 功能1.使用tick函数定时投递任务
 * 功能2:动态进程池，根据任务执行的多少动态调整内存池
 * 
 * 使用特点：
 * 
 * 1.Tick定时任务
 * 2.swoole_proccess管道通信
 * 3.event loop 事件循环
 * 
 * 
 * 
 */

class BaseProcess
{
    private $process;

    private $process_list = []; //存储子进程
    private $process_use = []; // 标记进程是否使用
    private $min_worker_num = 3;
    private $max_worker_num = 6;
    
    private $current_num;
    
    public function __construct() {
        /* 设置主进程 */
        $this->process = new \Swoole\Process([$this,"run"],FALSE,2);
        $this->process->start(); //启动父进程
        /*回收结束运行的子进程。*/
        \Swoole\Process::wait();
    }
    /*子进程*/
    public function run()
    {
        $this->current_num = $this->min_worker_num;
        for ($i = 0; $i < $this->current_num; $i++) {
            $process = new \Swoole\Process([$this,'task_rum'],FALSE,2);
            $pid = $process->pid;
            $this->process_list[$pid] = $process;
            $this->process_use[$pid] = 0;
        }
        foreach ($this->process_list as $process) {
            //取出管道并进行监听
            swoole_event_add($process->pipe, function($pice) use ($process){
                $data = $process->read();
                var_dump($data);
                $this->process_use[$data] = 0;
            });
        }
        
        swoole_timer_tick(1000, function ($timer_id){
            static $index = 0;
            $index = $index + 1;
            $flag = true;
            foreach ($this->process_use as $pid => $used) {
                if ($used == 0) {
                    $flag = false;
                    $this->process_use[$pid] = 1;
                    $this->process_list[$pid]->wirte($index . 'Hello');
                    break;
                }
            }
            if($flag && $this->current_num < $this->max_worker_num){
                $process = new \Swoole\Process([$this,"task_rum"],FALSE,2);
                $pid = $process->start();
                $this->process_list[$pid] = $process;
                $this->process_use[$pid] = 1;
                $this->process_list[$pid]->write($index . "Hello");
                $this->current_num ++;
            }
            var_dump($index);
            if($index == 10){
                foreach ($this->process_list as  $process) {
                    $process->write("exit");
                }
                swoole_timer_clear($timer_id);
                $this->process->exit();
            }
        });
    }
    
    public function task_rum($worker)
    {
        var_dump($worker);
        swoole_event_add($worker->pipe, function ($pipe) use ($worker){
           $data = $worker->read();
           var_dump($worker->pid . ":" .$data);
           if($data == "exit"){
               $worker->exit();
               exit();
           }
           sleep(5);
           $worker->write("" . $worker->pid); 
        });
    }
}
$dd = new BaseProcess();



