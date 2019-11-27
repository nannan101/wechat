<?php

/**
 * 功能：
 * 使用path_info获取请求的路径
 * 结合autoload和命名空间
 * 通过post get rawContent获取请求参数
 * 处理代码返回的结果
 * 彩蛋
 * 
 * 
 * 实现代码的热更新
 * ps aux | grep simple_route_master | awk '{print $2}' | xargs kill -USER1
 * 
 * swoole_http_server
 */

class HttpServer
{
    const HOST = '0.0.0.0';
    const PORT = 9501;

    private $_serv = null;
    
    public function __construct($host = null, $port = null) {
        $host = $host ? $host : self::HOST;
        $port = $port ? $port : self::PORT;
        $this->_serv = new Swoole\Http\Server($host, $port);
        $this->_serv->set([
            'worker_num' => 1
        ]);
        $this->_serv->on('Start', [$this,'onStart']);
        $this->_serv->on('ManagerStart', [$this,'onManagerStart']);
        $this->_serv->on('WorkerStart', [$this,'onWorkerStart']);
        $this->_serv->on('Request', [$this,'onRequest']);
        $this->_serv->start();
        
    }
    
    public function onStart()
    {
        swoole_set_process_name('simiple_route_master');
    }
    
    /**
     * 给进程mannger进程取别名 
     */
    public function onManagerStart()
    {
        swoole_set_process_name('simiple_route_manager');
    }
    
    public function onWorkerStart()
    {
        swoole_set_process_name('simiple_route_worker');
        spl_autoload_register(function ($class){
            $baseClasspath = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
            $class = '/home/wwwroot/wechat/ctrl' . '/' . $baseClasspath;
            if (is_file($baseClasspath)){
                require $baseClasspath;
            }
        });
    }
    
    public function onRequest(\Swoole\Http\Request $request, Swoole\Http\Response $response)
    {
        $path_info = explode('/', $request->server['path_info']);
        /*设置控制器*/
        if ( isset($path_info[1]) && !empty($path_info[1])){
            $ctrl = 'ctrl\\' . $path_info[1];
        }else{
            $ctrl = 'ctrl\\Index';
        }
        /*获取方法*/
        if( isset($path_info[2])) {
            $action = $path_info[2];
        }else {
            $action = "index";
        }
        $result = 'Ctrl not found';
        if (class_exists($ctrl)) {
            $class = new $ctrl();
            $result = 'Action not found';
            if (method_exists($class, $action)){
                $result = $class->$action($request);
            }
        }
        $response->end($result);
    }
}
$http_server = new HttpServer();