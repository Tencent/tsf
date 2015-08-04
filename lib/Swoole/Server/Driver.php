<?php
namespace Swoole\Server;

interface Driver
{
    function run($setting);

    function send($client_id, $data);

    function close($client_id);

    function setProtocol($protocol);
}