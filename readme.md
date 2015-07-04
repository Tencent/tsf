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

- Tencent Server Framework includes components for different network protocols: TCP,UDP,HTTP
- beside that,we also support Muticall:
- you can use Muticall to send TCP,UDP,HTTP packets at the sametime,when all the requests come back，return to interrupt


## Contribution

Your contribution to TSF development is very welcome!

You may contribute in the following ways:

* [Repost issues and feedback](https://github.com/tencent-php/tsf/issues)
* Submit fixes, features via Pull Request
* Write/polish documentation


## License
Apache License Version 2.0 see http://www.apache.org/licenses/LICENSE-2.0.html
