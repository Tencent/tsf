Tencent Server Framework
=======================

## overview

Tencent Server Framework 是由腾讯开发的一套基于协程机制和swoole扩展的server框架。可以用于快速搭建后台服务

## features

- 基于PHP，相比c/c++,大大提升搭建服务的效率和速度
- 基于swoole扩展，可以有异步IO，定时器，共享内存等丰富的基础能力
- 支持协程，通过协程调度机制，可以同步开发代码，而server的伺服能力不逊于纯异步的server
- 支持server监控，包括server的自拉起等能力，提供接口来扩展监控能力

## requirements

- php5及以上版本（建议php5.5.25版本）
- swoole扩展（建议1.7.18版本）
- linux,OS X

## install

## demo 