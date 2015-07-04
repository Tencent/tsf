Tencent Server Framework
=======================

## overview

Tencent Server Framework is a coroutine and Swoole based server framework for fast server deployment which developed by Tencent engineers.


## features

- PHP Based. Compared with C++, the framework is more efficient in developing and programing.
- based on Swoole extension. powerful async IO, timers and other infrastructure capacity can be used in this framework.
- support PHP coroutine. Synchronous programing is possible using the coroutine schedule system, and can lead to the similar server capability with that of server deveoped in an asynchronous way.
- support server monitor and provide interface to add more rules 


##Requirements

- php5+ 
- Swoole1.7.18+
- linux,OS X

## Installation
- [PHP install](https://github.com/php/php-src)
- [Swoole extension install](https://github.com/swoole/swoole-src)

## Introduction

- Tencent Server Framework can help you to start your server quickly,you just need to set a few settings
```php
[server]
//所有的server信息
;服务类型 
type = http
; 监听端口
listen[] = 12312
; 入口文件
root = '/data/web_deployment/serv/test/index.php'
;用来支持多php版本
php = '/usr/local/php/bin/php'

[setting]
; worker进程数
worker_num = 16
; task进程数
task_worker_num = 0
; 转发模式
dispatch_mode = 2
; 守护进程
daemonize = 1
; 系统日志
log_file = '/data/log/test.log'

```
- 
- 
- includes components for different network protocols: TCP,UDP,HTTP
- Beside that,we also support Muticall:
- you can use Muticall to send TCP,UDP,HTTP packets at the sametime,when all the requests come back，return to interrupt

### Server config
  



## Contribution

Your contribution to TSF development is very welcome!

You may contribute in the following ways:

* [Repost issues and feedback](https://github.com/tencent-php/tsf/issues)
* Submit fixes, features via Pull Request
* Write/polish documentation


## License
Apache License Version 2.0 see http://www.apache.org/licenses/LICENSE-2.0.html
