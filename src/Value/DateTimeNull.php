<?php namespace BladeOrm\Value;

class DateTimeNull extends DateTime implements NullValueInterface
{
    public function format($format) {}
    public function getTimestamp() {}

    public function __toString()
    {
        return '';
    }
}
