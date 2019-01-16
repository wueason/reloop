<?php
namespace Reloop;

/**
 * Worker Class
 */
class Worker
{
    function __construct($jobName = '', $jobIndex = '')
    {
        if($jobName){
            Context::$jobName = $jobName;
        }
        if($jobIndex){
            Context::$jobIndex = $jobIndex;
        }
        self::signalInstall();
    }

    function loop()
    {
        Context::savePid();
        while (true) {
            pcntl_signal_dispatch();
            Context::jobRun();
            usleep(Context::$loopSleepTime);
        }
    }

    function signalInstall()
    {
        pcntl_signal(SIGUSR1, array($this,'signalHandler'));
        pcntl_signal(SIGUSR2, array($this,'signalHandler'));
        pcntl_signal(SIGINT, array($this,'signalHandler'));
        pcntl_signal(SIGTERM, array($this,'signalHandler'));
    }

    function signalHandler($signal)
    {
        switch ($signal) {
            case SIGUSR2:
                Context::log("got signal: $signal");
                break;
            case SIGUSR1:
            case SIGINT:
            case SIGTERM:
                Context::removePid();
                Context::log("got signal: $signal");
                exit;
                break;
        }
    }
}