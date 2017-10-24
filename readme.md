Tencent Server Framework
=======================

## Overview

Tencent Server Framework is a coroutine and Swoole based server framework for fast server deployment which developed by Tencent engineers.

## Features

- PHP Based. Compared with C++, the framework is more efficient in developing and programing.
- based on Swoole extension. powerful async IO, timers and other infrastructure capacity can be used in this framework.
- support PHP coroutine. Synchronous programing is possible using the coroutine schedule system, and can lead to the similar server capability with that of server deveoped in an asynchronous way.
- support server monitor and provide interface to add more rules 


## Requirements

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
- Support Cmds: start,stop,reload,restart,status,shutdown,startall,list

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
    $thirdReq= new Swoole\Client\HTTP("http://www.qq.com");

    $calls ->request($firstReq,'first');             //first request
    $calls ->request($secondReq,'second');             //second request
    $calls ->request($thirdReq,'third');             //third request
    yield $calls;
  }

  var_dump($res)
  
```
#### Concect to mysql async 

```php

    $sql = new Swoole\Client\MYSQL(array('host' => '127.0.0.1', 'port' => 3345, 'user' => 'root', 'password' => 'root', 'database' => 'test', 'charset' => 'utf-8',));
    $ret = (yield $sql ->query('show tables'));
    var_dump($ret);
    $ret = (yield $sql ->query('desc test'));
    var_dump($ret);
    
```

#### Router
- We support individuation route rules
- now we realize some universal route rules and restful rules
- besides that, we also support default GET parameter

```php
  URL                                       METHOD       CONTROLLER  ACTION
  http://127.0.0.1:80/Test?h=1              ANY     ==>  TestController/ActionIndex

  http://127.0.0.1:80/Test/send?h=1         ANY     ==>  TestController/ActionSend
  Restful
  http://127.0.0.1:80/rest                  GET     ==>  TestController/ActionList
  http://127.0.0.1:80/rest/Test/22          GET     ==>  TestController/ActionView
                                                         Get['id']=22
  http://127.0.0.1:80/rest/Test             POST    ==>  TestController/ActionCreate
  http://127.0.0.1:80/rest/Test/22          PUT     ==>  TestController/ActionUpdate
                                                         Get['id']=22
  http://127.0.0.1:80/rest/Test/22          DELETE  ==>  TestController/ActionDelete
                                                         Get['id']=22
  http://127.0.0.1:80/rest/Test/send/1/li   GET     ==>  TestController/ActionSend
                                                         Get['cid']=1 Get['name']=li


```
## Performance 


## Contribution

Your contribution to TSF development is very welcome!

You may contribute in the following ways:

* [Repost issues and feedback](https://github.com/tencent-php/tsf/issues)
* Submit fixes, features via Pull Request
* Write/polish documentation
