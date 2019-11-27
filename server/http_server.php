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

$server = new Swoole\Http\Server('127.0.0.1', 9501);
  
$server->set([
    'worker_num' => 1
]);
$server->on('Start', function(){
     swoole_set_process_name('simiple_route_master');
});
$server->on('ManagerStart', function (){
    swoole_set_process_name('simiple_route_manager');
    spl_autoload_register(function ($class){
        $baseClasspath = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        $class = __DIR__ . '/' . $baseClasspath;
        if (is_file($baseClasspath)){
            require $baseClasspath;
        }
    });
});

$server->on('Request', function ($request,$response ){
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
});
$server->start();