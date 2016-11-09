<?php
namespace leoding86\CoordTransform;

abstract class Transform
{
    const WGS84 = 1;
    const GCJ02 = 2;
    const BD09  = 3;
    const x_PI  = 52.359877559829887333333333333333; // 3.14159265358979324 * 3000.0 / 180.0;
    const PI    = 3.1415926535897932384626;
    const a     = 6378245.0;
    const ee    = 0.00669342162296594323;

    protected $type;
    protected $lng;
    protected $lat;

    private function gcj02towgs84()
    {
        if ($this->outOfChina($this->lng, $this->lat)) {
            // return $this;
        } else {
            $dlat = $this->transformlat($this->lng - 105.0, $this->lat - 35.0);
            $dlng = $this->transformlng($this->lng - 105.0, $this->lat - 35.0);
            $radlat = $this->lat / 180.0 * self::PI;
            $magic = sin($radlat);
            $magic = 1 - self::ee * $magic * $magic;
            $sqrtmagic = sqrt($magic);
            $dlat = ($dlat * 180.0) / ((self::a * (1 - self::ee)) / ($magic * $sqrtmagic) * self::PI);
            $dlng = ($dlng * 180.0) / (self::a / $sqrtmagic * cos($radlat) * self::PI);
            $mglat = $this->lat + $dlat;
            $mglng = $this->lng + $dlng;

            $this->type = self::WGS84;
            $this->lng = $this->lng * 2 - $mglng;
            $this->lat = $this->lat * 2 - $mglat;
        }
    }

    private function wgs84togcj02()
    {
        if ($this->outOfChina($this->lng, $this->lat)) {
            // return $this;
        } else {
            $dlat = $this->transformlat($this->lng - 105.0, $this->lat - 35.0);
            $dlng = $this->transformlng($this->lng - 105.0, $this->lat - 35.0);
            $radlat = $this->lat / 180.0 * self::PI;
            $magic = sin($radlat);
            $magic = 1 - self::ee * $magic * $magic;
            $sqrtmagic = sqrt($magic);
            $dlat = ($dlat * 180.0) / ((self::a * (1 - self::ee)) / ($magic * $sqrtmagic) * self::PI);
            $dlng = ($dlng * 180.0) / (self::a / $sqrtmagic * cos($radlat) * self::PI);
            $mglat = $this->lat + $dlat;
            $mglng = $this->lng + $dlng;

            $this->type = self::GCJ02;
            $this->lng = $mglng;
            $this->lat = $mglat;
        }
    }

    private function bd09togcj02()
    {
        $x = $this->lng - 0.0065;
        $y = $this->lat - 0.006;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * self::x_PI);
        $theta = atan2($y, $x) - 0.000003 * cos($x * self::x_PI);
        $gg_lng = $z * cos($theta);
        $gg_lat = $z * sin($theta);

        $this->type = self::GCJ02;
        $this->lng = $gg_lng;
        $this->lat = $gg_lat;
    }

    private function gcj02tobd09()
    {
        $z = sqrt($this->lng * $this->lng + $this->lat * $this->lat) + 0.00002 * sin($this->lat * self::x_PI);
        $theta = atan2($this->lat, $this->lng) + 0.000003 * cos($this->lng * self::x_PI);
        $bd_lng = $z * cos($theta) + 0.0065;
        $bd_lat = $z * sin($theta) + 0.006;

        $this->type = self::BD09;
        $this->lng = $bd_lng;
        $this->lat = $bd_lat;
    }

    private function transformlat($lng, $lat) {
        $ret = -100.0 + 2.0 * $lng + 3.0 * $lat + 0.2 * $lat * $lat + 0.1 * $lng * $lat + 0.2 * sqrt(abs($lng));
        $ret += (20.0 * sin(6.0 * $lng * self::PI) + 20.0 * sin(2.0 * $lng * self::PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($lat * self::PI) + 40.0 * sin($lat / 3.0 * self::PI)) * 2.0 / 3.0;
        $ret += (160.0 * sin($lat / 12.0 * self::PI) + 320 * sin($lat * self::PI / 30.0)) * 2.0 / 3.0;
        return $ret;
    }

    private function transformlng($lng, $lat) {
        $ret = 300.0 + $lng + 2.0 * $lat + 0.1 * $lng * $lng + 0.1 * $lng * $lat + 0.1 * sqrt(abs($lng));
        $ret += (20.0 * sin(6.0 * $lng * self::PI) + 20.0 * sin(2.0 * $lng * self::PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($lng * self::PI) + 40.0 * sin($lng / 3.0 * self::PI)) * 2.0 / 3.0;
        $ret += (150.0 * sin($lng / 12.0 * self::PI) + 300.0 * sin($lng / 30.0 * self::PI)) * 2.0 / 3.0;
        return $ret;
    }

    private function outOfChina($lng, $lat) {
        return ($lng < 72.004 || $lng > 137.8347) || (($lat < 0.8293 || $lat > 55.8271) || false);
    }

    public function getType()
    {
        return $this->type;
    }

    public function getLng()
    {
        return $this->lng;
    }

    public function getLat()
    {
        return $this->lat;
    }

    /**
     * 转化坐标到WGS84坐标系
     * @return object
     */
    public function towgs84()
    {
        if ($this->type === self::BD09) {
            $this->bd09togcj02();
        }

        $this->gcj02towgs84();

        return $this;
    }

    /**
     * 转化坐标到GCJ02坐标系
     * @return object
     */
    public function togcj02()
    {
        if ($this->type === self::WGS84) {
            $this->wgs84togcj02();
        } else if ($this->type === self::BD09) {
            $this->bd09togcj02();
        }

        return $this;
    }

    /**
     * 转化坐标到BD09坐标系
     * @return object
     */
    public function tobd09()
    {
        if ($this->type === self::WGS84) {
            $this->wgs84togcj02();
        }

        $this->gcj02tobd09();

        return $this;
    }

    /**
     * 转化坐标到指定坐标系
     * @return object
     */
    public function to($type)
    {
        if ($type === $this->type) {
            return $this;
        }

        if ($type === self::WGS84) {
            return $this->towgs84();
        } else if ($type === self::GCJ02) {
            return $this->togcj02();
        } else if ($type === self::BD09) {
            return $this->tobd09();
        }

        return $this;
    }
}
