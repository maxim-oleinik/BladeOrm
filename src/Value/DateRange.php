<?php namespace BladeOrm\Value;


/**
 * Диапазон двух дат
 */
class DateRange
{

    /**
     * Дата начала периода
     */
    private $dateStart;

    /**
     * Дата окончания периода
     */
    private $dateEnd;


    /**
     * Конструктор
     * Для начала периода выставляет время 00:00:00, для окончания - 23:59:59.
     * Копирует получаемые даты во избежания их изменения без ведома самого объекта
     *
     * @param \DateTime $start
     * @param \DateTime $end
     */
    public function __construct(\DateTime $start, \DateTime $end)
    {

        $this->dateStart = clone $start;
        if ($this->dateStart instanceof NullValueInterface) {
            $this->dateStart = new \DateTime('1970-01-01');
        }
        $this->dateStart->setTime(0, 0, 0);

        $this->dateEnd = clone $end;
        if ($this->dateEnd instanceof NullValueInterface) {
            $this->dateEnd = new \DateTime('2020-01-01');
        }
        $this->dateEnd->setTime(23, 59, 59);

        if ($this->dateStart > $this->dateEnd) {
            throw new \InvalidArgumentException('Failed to create DateRange: start date greater then end date');
        }
    }


    /**
     * Получить дату начала периода
     * Копирует возвращаемую дату во избежание ее изменения без ведома самого объекта
     *
     * @return \DateTime
     */
    public function getStart()
    {
        return clone $this->dateStart;
    }


    /**
     * Получить дату окончания периода
     * Копирует возвращаемую датуво избежание ее изменения без ведома самого объекта
     *
     * @return \DateTime
     */
    public function getEnd()
    {
        return clone $this->dateEnd;
    }


    /**
     * String
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s-%s', $this->dateStart->format(DATE_DB_DATETIME), $this->dateEnd->format(DATE_DB_DATETIME));
    }

}
