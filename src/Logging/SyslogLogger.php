<?php
namespace Mindbit\Mpl\Logging;

use Psr\Log\AbstractLogger;

class SyslogLogger extends AbstractLogger
{
    protected $ident;
    protected $name = LOG_SYSLOG;
    protected $maxLength = 500;
    protected $level = LOG_INFO;
    
    function __construct($ident, $maxLength = NULL)
    {
        $this->ident = $ident;
        $this->name = $name;
        if ($maxLength)
            $this->maxLength = $maxLength;
        
        return openlog($this->ident, LOG_PID, $this->name);
    }
    
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        /* If a priority hasn't been specified, use the default value. */
        if ($level === null) {
            $level = $this->level;
        }
        
        /* Abort early if the priority is above the maximum logging level. */
        if ($this->level < $level) {
            return false;
        }
         
        /* Split the string into parts based on our maximum length setting. */
        $parts = str_split($message, $this->maxLength);
        if ($parts === false) {
            return false;
        }
        
        foreach ($parts as $part) {
            if (!syslog($level, $part)) {
                return false;
            }
        }
        
    }
    
    function __destruct()
    {
        return closelog();
    }
}