<?php
require '../src/Transform.php';
require '../src/Coord.php';

$coord = new \leoding86\CoordTransform\Coord(116.404, 39.915, \leoding86\CoordTransform\Transform::BD09);
echo $coord->togcj02();
// 2:116.39762729119, 39.908656739576

$coord = new \leoding86\CoordTransform\Coord(116.404, 39.915, \leoding86\CoordTransform\Transform::GCJ02);
// 3:116.41036949371, 39.92133699351
echo $coord->tobd09();

$coord = new \leoding86\CoordTransform\Coord(116.404, 39.915, \leoding86\CoordTransform\Transform::WGS84);
echo $coord->togcj02();
// 2:116.41024449917, 39.916404281502

$coord = new \leoding86\CoordTransform\Coord(116.404, 39.915, \leoding86\CoordTransform\Transform::GCJ02);
echo $coord->towgs84();
//1:116.39775550083, 39.913595718498
