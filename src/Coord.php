<?php

namespace labs7in0\coord;

use labs7in0\coord\Exceptions\UnknownTypeException;

class Coord
{
    const WGS84 = 1;
    const GCJ02 = 2;
    const BD09 = 3;
    const X_PI = 52.359877559829887333333333333333; // 3.14159265358979324 * 3000.0 / 180.0;
    const PI = 3.1415926535897932384626;
    const A = 6378245.0;
    const EE = 0.00669342162296594323;

    private $longitude;
    private $latitude;
    private $type;

    public function __construct($longitude, $latitude, $type = self::WGS84)
    {
        if (!in_array($type, [1, 2, 3])) {
            throw new UnknownTypeException($this->type);
        }

        $this->longitude = $longitude;
        $this->latitude = $latitude;
        $this->type = $type;
    }

    private function gcj02ToWgs84()
    {
        if ($this->isOutOfChina()) {
            return $this;
        } else {
            $dlatitude = $this->transformLatitude($this->longitude - 105.0, $this->latitude - 35.0);
            $dlongitude = $this->transformLongitude($this->longitude - 105.0, $this->latitude - 35.0);
            $radlatitude = $this->latitude / 180.0 * self::PI;
            $magic = sin($radlatitude);
            $magic = 1 - self::EE * $magic * $magic;
            $sqrtmagic = sqrt($magic);
            $dlatitude = ($dlatitude * 180.0) / ((self::A * (1 - self::EE)) / ($magic * $sqrtmagic) * self::PI);
            $dlongitude = ($dlongitude * 180.0) / (self::A / $sqrtmagic * cos($radlatitude) * self::PI);
            $mglatitude = $this->latitude + $dlatitude;
            $mglongitude = $this->longitude + $dlongitude;

            $this->longitude = $this->longitude * 2 - $mglongitude;
            $this->latitude = $this->latitude * 2 - $mglatitude;
            $this->type = self::WGS84;

            return $this;
        }
    }

    private function wgs84ToGcj02()
    {
        if ($this->isOutOfChina()) {
            return $this;
        } else {
            $dlatitude = $this->transformLatitude($this->longitude - 105.0, $this->latitude - 35.0);
            $dlongitude = $this->transformLongitude($this->longitude - 105.0, $this->latitude - 35.0);
            $radlatitude = $this->latitude / 180.0 * self::PI;
            $magic = sin($radlatitude);
            $magic = 1 - self::EE * $magic * $magic;
            $sqrtmagic = sqrt($magic);
            $dlatitude = ($dlatitude * 180.0) / ((self::A * (1 - self::EE)) / ($magic * $sqrtmagic) * self::PI);
            $dlongitude = ($dlongitude * 180.0) / (self::A / $sqrtmagic * cos($radlatitude) * self::PI);
            $mglatitude = $this->latitude + $dlatitude;
            $mglongitude = $this->longitude + $dlongitude;

            $this->longitude = $mglongitude;
            $this->latitude = $mglatitude;
            $this->type = self::GCJ02;

            return $this;
        }
    }

    private function bd09ToGcj02()
    {
        $x = $this->longitude - 0.0065;
        $y = $this->latitude - 0.006;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * self::X_PI);
        $theta = atan2($y, $x) - 0.000003 * cos($x * self::X_PI);

        $this->longitude = $z * cos($theta);
        $this->latitude = $z * sin($theta);
        $this->type = self::GCJ02;

        return $this;
    }

    private function gcj02ToBd09()
    {
        $z = sqrt($this->longitude * $this->longitude + $this->latitude * $this->latitude)
         + 0.00002 * sin($this->latitude * self::X_PI);
        $theta = atan2($this->latitude, $this->longitude) + 0.000003 * cos($this->longitude * self::X_PI);
        $bd_longitude = $z * cos($theta) + 0.0065;
        $bd_latitude = $z * sin($theta) + 0.006;

        $this->longitude = $bd_longitude;
        $this->latitude = $bd_latitude;
        $this->type = self::BD09;

        return $this;
    }

    private function bd09ToWgs84()
    {
        return $this->bd09ToGcj02()->gcj02ToWgs84();
    }

    private function wgs84ToBd09()
    {
        return $this->wgs84ToGcj02()->gcj02ToBd09();
    }

    private function transformLatitude($longitude, $latitude)
    {
        $ret = -100.0 + 2.0 * $longitude + 3.0 * $latitude + 0.2 * pow($latitude, 2)
         + 0.1 * $longitude * $latitude + 0.2 * sqrt(abs($longitude));
        $ret += (20.0 * sin(6.0 * $longitude * self::PI) + 20.0 * sin(2.0 * $longitude * self::PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($latitude * self::PI) + 40.0 * sin($latitude / 3.0 * self::PI)) * 2.0 / 3.0;
        $ret += (160.0 * sin($latitude / 12.0 * self::PI) + 320 * sin($latitude * self::PI / 30.0)) * 2.0 / 3.0;
        return $ret;
    }

    private function transformLongitude($longitude, $latitude)
    {
        $ret = 300.0 + $longitude + 2.0 * $latitude + 0.1 * pow($longitude, 2)
         + 0.1 * $longitude * $latitude + 0.1 * sqrt(abs($longitude));
        $ret += (20.0 * sin(6.0 * $longitude * self::PI) + 20.0 * sin(2.0 * $longitude * self::PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($longitude * self::PI) + 40.0 * sin($longitude / 3.0 * self::PI)) * 2.0 / 3.0;
        $ret += (150.0 * sin($longitude / 12.0 * self::PI) + 300.0 * sin($longitude / 30.0 * self::PI)) * 2.0 / 3.0;
        return $ret;
    }

    public function clone()
    {
        return clone $this;
    }

    public function isOutOfChina()
    {
        return ($this->longitude < 72.004 || $this->longitude > 137.8347)
            || ($this->latitude < 0.8293 || $this->latitude > 55.8271);
    }

    public function toWgs84()
    {
        switch ($this->type) {
            case self::WGS84:
                return $this;
                break;
            case self::GCJ02:
                return $this->gcj02ToWgs84();
                break;
            case self::BD09:
                return $this->bd09ToWgs84();
                break;
            default:
                throw new UnknownTypeException($this->type);
        }
    }

    public function toGcj02()
    {
        switch ($this->type) {
            case self::WGS84:
                return $this->wgs84ToGcj02();
                break;
            case self::GCJ02:
                return $this;
                break;
            case self::BD09:
                return $this->bd09ToGcj02();
                break;
            default:
                throw new UnknownTypeException($this->type);
        }
    }

    public function toBd09()
    {
        switch ($this->type) {
            case self::WGS84:
                return $this->wgs84ToBd09();
                break;
            case self::GCJ02:
                return $this->gcj02ToBd09();
                break;
            case self::BD09:
                return $this;
                break;
            default:
                throw new UnknownTypeException($this->type);
        }
    }

    public function to($type)
    {
        switch ($type) {
            case self::WGS84:
                return $this->toWgs84();
                break;
            case self::GCJ02:
                return $this->toGcj02();
                break;
            case self::BD09:
                return $this->toBd09();
                break;
            default:
                throw new UnknownTypeException($this->type);
        }
    }

    public function distanceTo(Coord $destination)
    {
        $destination->to($this->type);

        $latitudeRadA = deg2rad($this->latitude);
        $longitudeRadA = deg2rad($this->longitude);
        $latitudeRadB = deg2rad($destination->latitude);
        $longitudeRadB = deg2rad($destination->longitude);

        return 2 * asin(sqrt(
            pow(sin(($latitudeRadA - $latitudeRadB) / 2), 2)
             + cos($latitudeRadA) * cos($latitudeRadB)
             * pow(sin(($longitudeRadA - $longitudeRadB) / 2), 2)
        )) * 6378.137 * 1000;
    }

    public function string()
    {
        return $this->longitude . ',' . $this->latitude;
    }

    public function __toString()
    {
        return $this->string();
    }
}
