<?php

class testTcpServ extends Swoole\Network\Protocol\BaseServer
{

    public function onReceive($server, $fd, $fromId, $data)
    {
        //echo "receive \n";

    }

    public function onClose($server, $fd, $fromId)
    {
        //echo 'client close : ' . microtime(true);
    }


    public function onTask($serv, $task_id, $from_id, $data)
    {

    }

    public function onFinish($serv, $task_id, $data)
    {

    }

}
