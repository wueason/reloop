<?php
namespace Reloop;

/**
 * Context Class
 */
class Context
{
    static $loopSleepTime = 200000;
    
    static $loopSleepTimeMonitor = 20000;
    
    static $processNum = 1;
    
    static $pidPrefix = 'reloop';
    
    static $jobName = 'DEFAULTJOB';
    
    static $jobIndex = 1;

    static $noOutput = false;
    
    static $jobClosure;

    static $logDir;

    static $pidDir;
    
    static function init($config)
    {
        if(isset($config['processNum']) && is_int($config['processNum'])){
            self::$processNum = $config['processNum'];
        }
        if(isset($config['pidPrefix']) && is_scalar($config['pidPrefix'])){
            self::$pidPrefix = $config['pidPrefix'];
        }
        if(isset($config['jobName']) && is_scalar($config['jobName'])){
            self::$jobName = $config['jobName'];
        }
        if(isset($config['jobIndex']) && is_scalar($config['jobIndex'])){
            self::$jobIndex = $config['jobIndex'];
        }
        if(isset($config['pidDir']) && is_scalar($config['pidDir'])){
            self::$pidDir = $config['pidDir'];
        }else{
            self::$pidDir = __DIR__.DIRECTORY_SEPARATOR.'/pid';
        }        
        // ensure the pid dir is set
        if(!file_exists(self::$pidDir)){
            if(!mkdir(self::$pidDir, 0744, true)){
                self::log('fail to create pid dir');
            }
        }
        if(isset($config['logDir']) && is_scalar($config['logDir'])){
            self::$logDir = $config['logDir'];
            if(defined('STDIN')){
                fclose(STDIN);
            }
            if(defined('STDOUT')){
                fclose(STDOUT);
            }
            if(defined('STDERR')){
                fclose(STDERR);
            }
        }
        // ensure the log dir is set
        if(self::$logDir && !file_exists(self::$logDir)){
            mkdir(self::$logDir, 0744, true);
        }
    }

    static function setJob(\Closure $jobClosure)
    {
        self::$jobClosure = $jobClosure;
    }

    static function jobRun()
    {
        $jobFunc = self::$jobClosure;
        $jobFunc();
    }

    static function savePid($jobName = '', $jobIndex = '')
    {
        $jobName = $jobName ?: self::$jobName;
        $jobIndex = $jobIndex ?: self::$jobIndex;
        $pid = posix_getpid();
        $file = Context::getPidFile($jobName, $jobIndex);
        return file_put_contents($file, $pid);
    }

    static function removePid($jobName = '', $jobIndex = '')
    {
        $jobName = $jobName ?: self::$jobName;
        $jobIndex = $jobIndex ?: self::$jobIndex;
        $file = Context::getPidFile($jobName, $jobIndex);
        return file_exists($file) && unlink($file);
    }

    static function getPidFromFile($jobName = '', $jobIndex = '')
    {
        $jobName = $jobName ?: self::$jobName;
        $jobIndex = $jobIndex ?: self::$jobIndex;
        return file_get_contents(self::getPidFile($jobName, $jobIndex));
    }

    static function getPidFile($jobName = '', $jobIndex = '')
    {
        $jobName = $jobName ?: self::$jobName;
        $jobIndex = $jobIndex ?: self::$jobIndex;
        $paths = [self::$pidPrefix, $jobName, $jobIndex];
        return self::$pidDir.DIRECTORY_SEPARATOR.implode('_', $paths).'.pid';
    }

    static function jobIsRunning($jobName = '', $jobIndex = '')
    {
        $jobName = $jobName ?: self::$jobName;
        $jobIndex = $jobIndex ?: self::$jobIndex;
        $pidFile = self::getPidFile($jobName, $jobIndex);        
        return file_exists($pidFile) && posix_kill(file_get_contents($pidFile), 0);
    }

    static function log($message, $level = 'info')
    {
        $message = sprintf("[%s] %s # %s\n", date('Y-m-d H:i:s'), $level, $message);
        if(self::$logDir){
            error_log($message, 3, self::$logDir.DIRECTORY_SEPARATOR.'reloop_'.date('Y_m_d').'.log');
        }else{
            echo $message;
        }
    }
}