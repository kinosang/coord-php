<?php

namespace labs7in0\coord;

use labs7in0\coord\Exceptions\UnknownTypeException;

class Coord
{
    const WGS84 = 1;
    const GCJ02 = 2;
    const BD09 = 3;

    const PI = 3.1415926535897932384626;
    const X_PI = 52.359877559829887333333333333333; // PI * 3000.0 / 180.0;

    const ELLIPSOIDS = [
        self::WGS84 => [
            // World Geodetic System of 1984
            'a'    => 6378137.0,
            'f'    => 298.257223563,
        ],
        self::GCJ02 => [
            // Krasovsky 1940
            'a'    => 6378245.0,
            'f'    => 298.3,
            'ee'   => 0.00669342162296594323,
        ],
        self::BD09 => [
            // Krasovsky 1940
            'a'    => 6378245.0,
            'f'    => 298.3,
        ],
    ];

    protected $longitude;
    protected $latitude;
    protected $type;

    public function __construct($longitude, $latitude, $type = self::WGS84)
    {
        if (!in_array($type, [1, 2, 3])) {
            throw new UnknownTypeException($this->type);
        }

        $this->longitude = $longitude;
        $this->latitude = $latitude;
        $this->type = $type;
    }

    private function transform($x, $y)
    {
        $xy = $x * $y;
        $absX = sqrt(abs($x));
        $xPi = $x * self::PI;
        $yPi = $y * self::PI;
        $d = 20.0 * sin(6.0 * self::X_PI) + 20.0 * sin(2.0 * self::X_PI);

        $lat = $d;
        $lng = $d;

        $lat += 20.0 * sin($yPi) + 40.0 * sin($yPi / 3.0);
        $lng += 20.0 * sin($xPi) + 40.0 * sin($xPi / 3.0);

        $lat += 160.0 * sin($yPi / 12.0) + 320 * sin($yPi / 30.0);
        $lng += 150.0 * sin($xPi / 12.0) + 300.0 * sin($xPi / 30.0);

        $lat *= 2.0 / 3.0;
        $lng *= 2.0 / 3.0;

        $lat += -100.0 + 2.0 * $x + 3.0 * $y + 0.2 * pow($y, 2) + 0.1 * $xy + 0.2 * $absX;
        $lng += 300.0 + $x + 2.0 * $y + 0.1 * pow($x, 2) + 0.1 * $xy + 0.1 * $absX;

        return [$lng, $lat];
    }

    private function delta($longitude, $latitude)
    {
        list($dLng, $dLat) = $this->transform($longitude - 105.0, $latitude - 35.0);
        $radLat = deg2rad($latitude);
        $magic = 1 - self::ELLIPSOIDS[self::GCJ02]['ee'] * pow(sin($radLat), 2);
        $sqrtMagic = sqrt($magic);
        return [
            ($dLng * 180.0) / (self::ELLIPSOIDS[self::GCJ02]['a'] / $sqrtMagic * cos($radLat) * self::PI),
            ($dLat * 180.0) / ((self::ELLIPSOIDS[self::GCJ02]['a'] * (1 - self::ELLIPSOIDS[self::GCJ02]['ee'])) / ($magic * $sqrtMagic) * self::PI)
        ];
    }

    private function gcj02ToWgs84($exact = false)
    {
        if ($this->isOutOfChina()) {
            return $this;
        } else {
            if ($exact) {
                $threshold = pow(10, -6); // ~0.55 m equator & latitude

                $newLat = $this->getLat();
                $newLng = $this->getLng();

                $rough = $this->copy()->toWgs84();
                $oldLat = $rough->getLat();
                $oldLng = $rough->getLng();

                for ($i = 0;
                    $i < 30 && max(abs($oldLat - $newLat), abs($oldLng - $newLng)) > $threshold;
                    $i++) {
                        $oldLat = $newLat;
                        $oldLng = $newLng;

                        $tmp = new self($newLng, $newLat, self::WGS84);
                        $tmp->toGcj02();

                        $newLat -= $tmp->getLat() - $this->getLat();
                        $newLng -= $tmp->getLng() - $this->getLng();
                }

                // i == 29 usually means bad things
                if ($i == 29) {
                    //console.warn("gcj2wgs_exact: Out of iterations. Bug?");
                }

                $this->longitude = $newLng;
                $this->latitude = $newLat;
            } else {
                list($longitude, $latitude) = $this->delta($this->getLng(), $this->getLat());

                $this->longitude -= $longitude;
                $this->latitude -= $latitude;
            }

            $this->type = self::WGS84;

            return $this;
        }
    }

    private function wgs84ToGcj02()
    {
        if ($this->isOutOfChina()) {
            return $this;
        } else {
            list($longitude, $latitude) = $this->delta($this->getLng(), $this->getLat());

            $this->longitude += $longitude;
            $this->latitude += $latitude;
            $this->type = self::GCJ02;

            return $this;
        }
    }

    private function bd09ToGcj02()
    {
        $x = $this->getLng() - 0.0065;
        $y = $this->getLat() - 0.006;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * self::X_PI);
        $theta = atan2($y, $x) - 0.000003 * cos($x * self::X_PI);

        $this->longitude = $z * cos($theta);
        $this->latitude = $z * sin($theta);
        $this->type = self::GCJ02;

        return $this;
    }

    private function gcj02ToBd09()
    {
        $z = sqrt($this->getLng() * $this->getLng() + $this->getLat() * $this->getLat())
         + 0.00002 * sin($this->getLat() * self::X_PI);
        $theta = atan2($this->getLat(), $this->getLng()) + 0.000003 * cos($this->getLng() * self::X_PI);
        $bd_longitude = $z * cos($theta) + 0.0065;
        $bd_latitude = $z * sin($theta) + 0.006;

        $this->longitude = $bd_longitude;
        $this->latitude = $bd_latitude;
        $this->type = self::BD09;

        return $this;
    }

    private function bd09ToWgs84($exact = false)
    {
        return $this->bd09ToGcj02()->gcj02ToWgs84($exact);
    }

    private function wgs84ToBd09()
    {
        return $this->wgs84ToGcj02()->gcj02ToBd09();
    }

    public function copy()
    {
        return clone $this;
    }

    public function isOutOfChina()
    {
        return ($this->getLng() < 72.004 || $this->getLng() > 137.8347)
            || ($this->getLat() < 0.8293 || $this->getLat() > 55.8271);
    }

    public function toWgs84($exact = false)
    {
        switch ($this->type) {
            case self::WGS84:
                return $this;
                break;
            case self::GCJ02:
                return $this->gcj02ToWgs84($exact);
                break;
            case self::BD09:
                return $this->bd09ToWgs84($exact);
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

    public function to($type, $exact = false)
    {
        switch ($type) {
            case self::WGS84:
                return $this->toWgs84($exact);
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

    public function getLat()
    {
        return $this->latitude;
    }

    public function getLng()
    {
        return $this->longitude;
    }

    public function getArithmeticMeanRadius()
    {
        return self::ELLIPSOIDS[$this->type]['a'] * (1 - 1 / self::ELLIPSOIDS[$this->type]['f'] / 3);
    }

    public function distanceTo(Coord $destination)
    {
        $point2 = $destination->copy()->to($this->type);

        $lat1 = deg2rad($this->getLat());
        $lat2 = deg2rad($point2->getLat());
        $lng1 = deg2rad($this->getLng());
        $lng2 = deg2rad($point2->getLng());

        $dLat = $lat2 - $lat1;
        $dLng = $lng2 - $lng1;

        $radius = $this->getArithmeticMeanRadius();

        return 2 * $radius * asin(
            sqrt(
                (sin($dLat / 2) ** 2)
                + cos($lat1) * cos($lat2) * (sin($dLng / 2) ** 2)
            )
        );
    }

    public function string($latitudeFirst = false)
    {
        if ($latitudeFirst) {
            return $this->getLat() . ',' . $this->getLng();
        }

        return $this->getLng() . ',' . $this->getLat();
    }

    public function __toString()
    {
        return $this->string();
    }
}
