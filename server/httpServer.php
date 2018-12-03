<?php
/**
 * User: 张世路
 * Date: 2018/12/4 0004
 * Time: 上午 0:42
 */

//实例化一个http对象
use think\Container;
use think\Log;

$httpObj = new swoole_http_server('0.0.0.0', '900');

//设置启动参数
$httpObj->set([
    'enable_static_handler' => true,
    'document_root' => "/home/vagrant/code/live/public/static/live",
]);

$httpObj->on('WorkerStart',function (swoole_server $server, int $worker_id){
    // 加载基础文件
    require __DIR__ . '/../thinkphp/base.php';
});

//注册request事件
$httpObj->on('request', function ($request, $response) use ($httpObj) {

    if(isset($request->get)){
        foreach ($request->get as $k=>$v){
            $_GET[$k]=$v;
        }
    }
    if(isset($request->post)){
        foreach ($request->post as $k=>$v){
            $_POST[$k]=$v;
        }
    }
    if(isset($request->server)){
        foreach ($request->server as $k=>$v){
            $_SERVER[strtoupper($k)]=$v;
        }
    }
    if(isset($request->header)){
        foreach ($request->header as $k=>$v){
            $_SERVER[strtoupper($k)]=$v;
        }
    }


    // 执行应用并响应
    ob_start();

    try{
        Container::get('app')->run()->send();

    }catch (Exception $e){
        Log::error($e->getTraceAsString());
        exit('系统繁忙，请稍后再试');
    }

    $result = ob_get_contents();
    ob_end_clean();
    $response->end($result);

    $httpObj->close($request->fd);

});

//启动服务
$httpObj->start();