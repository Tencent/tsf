Tencent Server Framework
=======================

## Overview

Tencent Server Framework is a coroutine and Swoole based server framework for fast server deployment which developed by Tencent engineers.


## Features

- PHP Based. Compared with C++, the framework is more efficient in developing and programing.
- based on Swoole extension. powerful async IO, timers and other infrastructure capacity can be used in this framework.
- support PHP coroutine. Synchronous programing is possible using the coroutine schedule system, and can lead to the similar server capability with that of server deveoped in an asynchronous way.
- support server monitor and provide interface to add more rules 


##Requirements

- php5.5+ 
- Swoole1.7.18+
- linux,OS X

## Installation
- [PHP install](https://github.com/php/php-src)
- [Swoole extension install](https://github.com/swoole/swoole-src)

## Introduction

- Tencent Server Framework can help you to start your server quickly,you just need to set a few settings

### Server config

```php
vim server.ini

[server]
;server type:tcp，udp，http
type = http
; port
listen[] = 12312
; entrance file
root = '/data/web_deployment/serv/test/index.php'
;php start path
php = '/usr/local/php/bin/php'

[setting]
; worker process num
worker_num = 16
; task process num
task_worker_num = 0
; dispatch mode
dispatch_mode = 2
; daemonize
daemonize = 1
; system log
log_file = '/data/log/test.log'

```
#### How to start your server
```php
cd /root/tsf/bin/
php swoole testHttpServ start

```
- Support Cmds: start,stop,reload,restart,status,shutdown

#### How to use TCP/UDP/HTTP Client
- we support different network protocols: TCP,UDP,HTTP

```php

  $tcpReturn=(yield $this->tcpTest());
  
  $udpReturn=(yield $this->udpTest());

  $httpReturn=(yield $this->httpTest());

  public function tcpTest(){
    $ip = '127.0.0.1';
    $port = '9905';
    $data = 'test';
    $timeout = 0.5; //second
    yield new Swoole\Client\TCP($ip, $port, $data, $timeout);
  }
  
  public function udpTest(){
    $ip = '127.0.0.1';
    $port = '9905';
    $data = 'test';
    $timeout = 0.5; //second
    yield new Swoole\Client\UDP($ip, $port, $data, $timeout);
  }
  
  public function httpTest(){
    $url='http://www.qq.com';
    $httpRequest= new Swoole\Client\HTTP($url);
    $data='testdata';
    $header = array(
      'Content-Length' => 12345,
    );
    yield $httpRequest->get($url); //yield $httpRequest->post($path, $data, $header);
  }



```

#### How to use Muticall

- Beside that,we also support Muticall:
- you can use Muticall to send TCP,UDP packets at the sametime
- when all the requests come back,return to interrupt

```php
  
  $res = (yield $this->muticallTest());
  
  public function muticallTest(){
    $calls=new Swoole\Client\Multi();
    $firstReq=new Swoole\Client\TCP($ip, $port, $data, $timeout);
    $secondReq=new Swoole\Client\UDP($ip, $port, $data, $timeout);
    $calls ->request($firstReq,'first');             //first request
    $calls ->request($secondReq,'second');             //second request
    yield $calls;
  }

  var_dump($res)
  
```


#### Router
- We support individuation route rules
- now we realize some universal route rules and restful rules
- besides that, we also support default GET parameter

```php
  127.0.0.1：12345/Test?h=1  ==>  TestController/ActionIndex
  127.0.0.1：12345/Test/send?h=1  ==>  TestController/ActionSend
  127.0.0.1：12345/rest/Test?h=1 （GET） ==>  TestController/ActionList
  127.0.0.1：12345/rest/Test/22 （GET） ==>  TestController/ActionView  Get['id']=22
  127.0.0.1：12345/rest/Test （POST） ==>  TestController/ActionCreate
  …………………………………………………………
  Route Config

        array(
            'regx' => '^/(<controller>\w+)$',  //默认到index
            'mvc' => 'controller/Index',  //必须匹配
            'verb' => '',  //必须匹配 方法
            'default' => array(),  //添加默认参数
        ),
        array(
            'regx' => '^/(<controller>\w+)/(<action>\w+)$',
            'mvc' => 'controller/Index',
            'verb' => '',  //必须匹配 方法
            'default' => array(),  //添加默认参数
        ),

        //特殊
        array(
            'regx' => '^/(<controller>\w+)/(<action>\w+)/(<s_action>\w+)$',
            'mvc' => 'controller/Index',
            'verb' => '',  //必须匹配 方法
            'default' => array(),  //添加默认参数
        ),

        //添加rest
        array(
            'regx' => '^/(<controller>\w+)$',
            'mvc' => 'Controller/Index',
            'verb' => '',
            'default' => array(),
        ),
        array(
            'regx' => '^/(<controller>\w+)/(<action>\w+)$',
            'mvc' => 'Controller/View',  //必须匹配
            'verb' => '',  //必须匹配 方法
            'default' => array(),  //添加默认参数
        ),
        array(
            'regx' => '^/rest/(<controller>\w+)$',
            'mvc' => 'Controller/List',
            'verb' => 'GET',
            'default' => array('ggg'=>33333),
        ),
        array(
            'regx' => '^/rest/(<controller>\w+)/(<id>\d+)$',
            'mvc' => 'Controller/View',
            'verb' => 'GET',
            'default' => array(),
        ),
        array(
            'regx' => '^/rest/(<controller>\w+)/(<id>\d+)$',
            'mvc' => 'Controller/Update',
            'verb' => 'PUT',
            'default' => array(),
        ),
        array(
            'regx' => '^/rest/(<controller>\w+)$',
            'mvc' => 'Controller/View',
            'verb' => 'GET',
            'default' => array(),
        ),
        array(
            'regx' => '^/rest/(<controller>\w+)/(<id>\d+)$',
            'mvc' => 'Controller/Update',  //必须匹配
            'verb' => 'PUT',  //必须匹配 方法
            'default' => array(),  //添加默认参数
        ),
        array(
            'regx' =>  '^/rest/(<controller>\w+)$',
            'mvc' => 'Controller/Create',  //必须匹配
            'verb' => 'POST',  //必须匹配 方法
            'default' => array(),  //添加默认参数
        ),
        array(
            'regx' => '^/rest/(<controller>\w+)/(<id>\d+)$',
            'mvc' => 'Controller/Delete',  //必须匹配
            'verb' => 'DELETE',  //必须匹配 方法
            'default' => array(),  //添加默认参数
        ),
        array(
            'regx' =>  '^/rest/(<controller>\w+)$',
            'mvc' => 'Controller/Delete',  //必须匹配
            'verb' => 'DELETE',  //必须匹配 方法
            'default' => array(),  //添加默认参数
        ),
        //默认的话就是/controller/action?id=32131 直接定位过去  必须要有
        array(
            /**
             * 默认的==》controller/action
             */
            'regx' => '^/(<controller>\w+)/(<action>\w+)/(<cid>\d+)/(<name>\w+)$',
            'mvc' => 'Controller/Action',  //必须匹配
            'verb' => 'GET',  //必须匹配 方法
            'default' => array(),  //添加默认参数
        ),


```


## Contribution

Your contribution to TSF development is very welcome!

You may contribute in the following ways:

* [Repost issues and feedback](https://github.com/tencent-php/tsf/issues)
* Submit fixes, features via Pull Request
* Write/polish documentation


## License
Apache License Version 2.0 see http://www.apache.org/licenses/LICENSE-2.0.html
