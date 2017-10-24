<?php
/**
 * Tencent is pleased to support the open source community by making TSF Solution available.
 * Copyright (C) 2017 THL A29 Limited, a Tencent company. All rights reserved.
 * Licensed under the BSD 3-Clause License (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * https://opensource.org/licenses/BSD-3-Clause
 * Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
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