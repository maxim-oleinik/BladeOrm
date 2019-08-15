<?php namespace Blade\Orm\Value;

/**
 * @see \Blade\Orm\Test\Table\Value\DateTimeNullTest
 */
class DateTimeNull extends DateTime implements NullValueInterface
{
    private static $instance;

    /**
     * Создать минимальную дату
     *
     * @return self
     */
    public static function make(): self
    {
        if (!self::$instance) {
            self::$instance = new self('0000-01-01');
        }
        return self::$instance;
    }

    // phpcs:disable
    public function modify($modify) { return $this; }
    public function format($format) {}
    public function getTimestamp()  {}
    public function __toString()    { return ''; }
}
