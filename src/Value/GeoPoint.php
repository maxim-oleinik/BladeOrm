<?php namespace BladeOrm\Value;

/**
 * Гео-Координаты
 *
 * @see \Test\GeoPointTest
 */
class GeoPoint
{
    const EARTH_RADIUS = 6371; //км, радиус земли

    /**
     * Координата широты
     */
    private $latitude;

    /**
     * Координата долготы
     */
    private $longitude;


    /**
     * Конструктор
     *
     * @param double $latitude
     * @param double $longitude
     */
    public function __construct($latitude, $longitude)
    {
        $this->latitude = (double) $latitude;
        $this->longitude = (double) $longitude;
    }


    /**
     * Широта
     *
     * @return double
     */
    public function getLatitude()
    {
        return $this->latitude;
    }


    /**
     * Долгота
     *
     * @return double
     */
    public function getLongitude()
    {
        return $this->longitude;
    }


    /**
     * String
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s,%s', $this->latitude, $this->longitude);
    }


    /**
     * расчет расстояния в км до точки
     *
     * @param GeoPoint $point
     * @return float
     */
    public function getDistance(GeoPoint $point)
    {
        $R = self::EARTH_RADIUS; // km
        $latDiffRad = $this->_get_rad($point->getLatitude() - $this->getLatitude());
        $lonDiffRad = $this->_get_rad($point->getLongitude() - $this->getLongitude());
        $currentLatRad = $this->_get_rad($this->getLatitude());
        $pointLatRad = $this->_get_rad($point->getLatitude());

        $a = sin($latDiffRad / 2) * sin($latDiffRad / 2) + sin($lonDiffRad / 2) * sin($lonDiffRad / 2) * cos($currentLatRad) * cos($pointLatRad);
        $b = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $R * $b;

        return round($distance, 2);
    }

    /**
     * Радиан из градуса
     *
     * @param double $degree
     * @return float|int
     */
    private function _get_rad($degree)
    {
        return $degree * pi() / 180;
    }

}
