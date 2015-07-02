<?php
class TSFHttpServ extends Swoole\Network\Protocol\BaseServer{


    public   $env=array();
    private  $root_path;
    //解析成功
    const HTTP_OK = 0x00;
    //请求方式错误
    const HTTP_ERROR_METHOD = 0x01;
    //请求uri错误
    const HTTP_ERROR_URI = 0x02;

//    //为了协程，去除
//    public function onRequest($request, $response) {
//
//
//
//        echo __LINE__.'go to controller';
//
//        //直接去调用index------》通过servername下面的root
//        //怎么调用index？require 或者 方法
//
//        //路由解析
//        $method = $request -> server['request_method'];
//        $uri = $request -> server['request_uri'];
//        $appRoute = HttpRoute::getRoute($uri,$method);
//        SysLog::info(__METHOD__.print_r($appRoute,true),__CLASS__);
//        if(!$appRoute){
//            return array('r' => self::HTTP_ERROR_URI);
//        }
//
//
//            //     'get' => $req -> get,
//            'get' => array_merge((array)($req -> get),(array)$appRoute['get']),
//            'post' => $req -> post ? $req -> post : $req -> rawContent(),
//            'files' => $req -> files,
//
//
//
//        SysLog::info(__METHOD__.print_r($req,true),__CLASS__);
//        if($req['r'] !==0){
//            $response -> status(405);
//            //todo:log
//            $response -> end("http route error");
//            return;
//        }
//
//        //判断类是否存在
//        if (! class_exists(($req['controller'].'Controller'))  || !method_exists(($req['controller'].'Controller'),('Action'.$req['action']))) {
//            SysLog::error(__METHOD__  .print_r($req,true),__CLASS__);
//            $response -> status(404);
//            $response -> end("not found");
//            return;
//        };
//
//
//        $class =  $req['controller'] . 'Controller';
//        $obj = new $class($this->server,array('request' => $request, 'response' => $response));
//        $fun = 'Action' .$req['action'];
//        $obj -> $fun();
//
//      //  echo 'on onRequest'.PHP_EOL;
//      //  $response->end("<h1>Hello gggg Swoole. #".rand(1000, 9999).' and return is '."</h1>");
//        //进行路由解析，到具体的controller
//
//
//    }

    public function onRequest($request, $response) {





      //  $data=$request;

//
//        $ret=$this->checkSigNature($data);  //之后直接调用来自用户的
//        if(!$ret){
//            if(($data->server['request_uri'])=='/token'){
//                SysLog::warn(__METHOD__ . 'request_uri:/token',__CLASS__);
//                $response -> status(MessageError::HTTP_NOT_FOUND);
//                $response -> end(MessageError::getErrorMessage(MessageError::ACCESS_TOKEN_ERROR));
//                return;
//            }else{
//                SysLog::warn(__METHOD__ . "request_uri:{$data->server['request_uri']}",__CLASS__);
//                $response -> status(MessageError::HTTP_NOT_FOUND);
//                $response -> end("no permission");
//                return;
//            }
//        }
        //统一进行路由和数据的预处理
        $req = HttpHelper::httpReqHandle($request);
        SysLog::info(__METHOD__.print_r($req,true),__CLASS__);
        if($req['r'] === HttpHelper::HTTP_ERROR_URI){
            $response -> status(404);
            //todo:log
            $response -> end("not found");
            return;
        };

        SysLog::warn(__METHOD__.'  '.__LINE__ . " REQUEST IS ".print_r($req,true),__CLASS__);
        $class = $req['route']['controller']. 'Controller';
        $fun= 'action'.$req['route']['action'];
        //判断类是否存在
        if (! class_exists($class)  || !method_exists(($class),($fun))) {
            $response -> status(404);
            SysLog::warn(__METHOD__.'  '.__LINE__ . " REQUEST IS ".print_r($req,true),__CLASS__);
            $response -> end("not found");
            return;
        };
        SysLog::warn(__METHOD__.'  '.__LINE__ . " REQUEST IS ".print_r($req,true),__CLASS__);

//
//        $class = $req['route']['controller']. 'Controller';
//        $fun= 'action'.$req['route']['action'];
        $this ->report($class);
        $obj = new $class($this -> server,array('request' => $req['request'], 'response' => $response),$request->fd);
        //代入参数
        $request ->scheduler -> newTask($obj->doFun($fun)) ;
        $request ->scheduler -> run();
    }

    /**
     * [report 分业务report]
     * @param  [type] $controller [description]
     * @return [type]             [description]
     */
    public function report($controller){

        switch ($controller) {
            case 'TemplateController':
                //template report
                TNM2::sumReport(MPConst::BUSI_TEMPLATE_SUM, 1);
                break;
            case 'CustomController':
                //structure report
                TNM2::sumReport(MPConst::BUSI_STRUC_SUM, 1);
                break;
            default:
                # code...
                break;
        }
    }

    /**
     * [onStart 协程调度器单例模式]
     * @return [type] [description]
     */
    public function onHttpWorkInit($request, $response){

        $scheduler = new \Swoole\Coroutine\Scheduler();
        $request ->scheduler = $scheduler;
    }

}
