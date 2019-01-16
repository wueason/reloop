<?php
namespace Reloop;
declare(ticks=1);

/**
 * Manager Class
 */
class Manager
{
    function __construct(array $config)
    {
        Context::init($config);
    }

    function run(\Closure $job){
        Context::setJob($job);
        if(!Context::jobIsRunning(Monitor::$jobName, Monitor::$jobIndex)){
            $this->forkMonitor();
        }
        for ($jobIndex=1; $jobIndex <= Context::$processNum; $jobIndex++) {
            if(!Context::jobIsRunning(Context::$jobName, $jobIndex)){
                $this->fork($jobIndex);
            }
        }
    }

    function fork($jobIndex = 1)
    {
        $pid = pcntl_fork();
        if($pid == -1){
            Context::log("fork $jobIndex failed");
        }else if($pid == 0){
            $worker = new Worker(Context::$jobName, $jobIndex);            
            $worker->loop();
        }
        exit;
    }

    function forkMonitor()
    {
        $pid = pcntl_fork();
        if($pid == -1){
            Context::log("fork $jobIndex failed");
        }else if($pid == 0){
            if(!posix_setsid()){
                Context::log("daemon $jobIndex failed");
            }
            $worker = new Monitor();
            $worker->setMonitorIndex(Context::$jobName);
            $worker->loop();
        }
        exit;
    }
}