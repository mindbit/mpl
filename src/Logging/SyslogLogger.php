<?php
namespace Mindbit\Mpl\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class SyslogLogger extends AbstractLogger
{
    protected $ident;
    protected $facility;
    protected $maxLength = 500;
    protected $level = LOG_INFO;

    protected static $levelMapping = array(
            LogLevel::INFO => LOG_INFO,
            LogLevel::ALERT => LOG_ALERT,
            LogLevel::CRITICAL => LOG_CRIT,
            LogLevel::DEBUG => LOG_DEBUG,
            LogLevel::EMERGENCY => LOG_EMERG,
            LogLevel::ERROR => LOG_ERR,
            LogLevel::NOTICE => LOG_NOTICE,
            LogLevel::WARNING => LOG_WARNING
            );

    public function __construct($ident, $facility = LOG_USER, $maxLength = NULL)
    {
        $this->ident = $ident;
        $this->facility = $facility;
        if ($maxLength)
            $this->maxLength = $maxLength;

        return openlog($this->ident, LOG_PID, $this->facility);
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
        } else {
            $level = $this->translateLevel($level);
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

    protected function translateLevel($level)
    {
        if (isset(self::$levelMapping[$level]))
            return self::$levelMapping[$level];
    }

    public function __destruct()
    {
        return closelog();
    }
}
