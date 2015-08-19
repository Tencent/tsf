<?php
/**
 * @Author: winterswang
 * @Date:   2015-06-24 15:20:01
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-06-29 21:10:37
 */

namespace Swoole\Coroutine;

class Scheduler
{

    protected $maxTaskId = 0;
    protected $taskQueue;

    public function __construct()
    {

        $this->taskQueue = new \SplQueue();
    }

    public function newTask(\Generator $coroutine)
    {

        $taskId = ++$this->maxTaskId;
        $task = new Task($taskId, $coroutine);
        $this->taskQueue->enqueue($task);
    }

    public function schedule(Task $task)
    {

        $this->taskQueue->enqueue($task);
    }

    public function run()
    {

        while (!$this->taskQueue->isEmpty()) {
            $task = $this->taskQueue->dequeue();
            $task->run($task->getCoroutine());
        }
    }


}