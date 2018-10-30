<?php namespace Blade\Orm\Value;

class DateTime extends \DateTime
{
    public function __toString()
    {
        return (string) $this->format(DATE_ISO8601);
    }
}
