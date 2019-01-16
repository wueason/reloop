<?php
namespace Reloop;

/**
 * Monitor Class
 */
class Monitor extends Worker
{
    static $jobName = 'MINITOR';
    static $jobIndex = 1;

    function loop()
    {
        Context::savePid(self::$jobName, self::$jobIndex);
        while (true) {
            pcntl_signal_dispatch();
            $this->healthChecker();
            sleep(Context::$loopSleepTimeMonitor);
        }
    }

    function setMonitorIndex($jobIndex)
    {
        if($jobIndex){
            self::$jobIndex = $jobIndex;
        }
    }

    function getRunningJobs()
    {
        $runningJobs = glob(Context::$pidDir.DIRECTORY_SEPARATOR.Context::$pidPrefix.'*');
        $jobIndexes = [];
        foreach ($runningJobs as $runningJob) {
            $filename = pathinfo($runningJob)['filename'];
            $fileSeg = explode('_', $filename);
            $jobName = $fileSeg[1];
            $jobIndex = $fileSeg[2];
            if($jobName != self::$jobName && Context::jobIsRunning($jobName, $jobIndex)){
                $jobIndexes[] = intval($jobIndex);
            }
        }
        return $jobIndexes;
    }

    function healthChecker()
    {
        $jobIndexes = $this->getRunningJobs();
        $indexToStart = array_diff(range(1, Context::$processNum), $jobIndexes);
        foreach ($indexToStart as $jobIndex) {
            $pid = pcntl_fork();
            if($pid == -1){
                Context::log("fork $jobIndex failed");
            }else if($pid == 0){
                // fork twice voiding zombie process
                $pid = pcntl_fork();
                if($pid == -1){
                    Context::log("twice fork $jobIndex failed");
                }else if($pid == 0){
                    $worker = new Worker(Context::$jobName, $jobIndex);
                    $worker->loop();
                }
                exit;
            }else{
                // ingore signal of subprocess 
                pcntl_signal(SIGCLD, SIG_IGN);
            }
        }
    }

    function signalHandler($signal)
    {
        switch ($signal) {
            case SIGUSR2:
                // remove and reload
                $runningJobs = $this->getRunningJobs();
                foreach ($runningJobs as $jobIndex) {
                    if(Context::jobIsRunning(Context::$jobName, $jobIndex)){
                        $pid = Context::getPidFromFile(Context::$jobName, $jobIndex);
                        posix_kill($pid, SIGKILL);
                    }
                }
                break;
            case SIGUSR1:
            case SIGINT:
            case SIGTERM:
                // clear monitor pidfile
                Context::removePid(self::$jobName, self::$jobIndex);
                exit;
                break;
        }        
    }
}