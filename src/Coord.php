<?php
namespace leoding86\CoordTransform;

class Coord extends Transform
{
    public function __construct($lng, $lat, $type)
    {
        $this->lng = $lng;
        $this->lat = $lat;
        $this->type = $type;
    }

    public function __toString()
    {
        return $this->type . ':' . $this->lng . ', ' . $this->lat . PHP_EOL;
    }
}