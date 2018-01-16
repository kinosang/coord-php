<?php

namespace labs7in0\coord\Tests;

use labs7in0\coord\Coord;
use PHPUnit\Framework\TestCase;

class CoordTest extends TestCase
{
    public function testToString()
    {
        $coord = new Coord(116.404, 39.915);
        $this->assertEquals('116.404,39.915', $coord);
    }

    public function testIsOutOfChina()
    {
        $coord = new Coord(35.765, 140.386);
        $this->assertTrue($coord->isOutOfChina());

        $coord = new Coord(116.404, 39.915);
        $this->assertFalse($coord->isOutOfChina());
    }

    public function testTransformFromBd09()
    {
        $coord = new Coord(116.404, 39.915, Coord::BD09);
        $this->assertEquals('116.39138369951,39.907253214522', $coord->clone()->toWgs84());
        $this->assertEquals('116.39762729119,39.908656739576', $coord->clone()->toGcj02());
        $this->assertEquals($coord, $coord->clone()->toBd09());
    }

    public function testTransformFromGcj02()
    {
        $coord = new Coord(116.404, 39.915, Coord::GCJ02);
        $this->assertEquals('116.39775550083,39.913595718498', $coord->clone()->toWgs84());
        $this->assertEquals($coord, $coord->clone()->toGcj02());
        $this->assertEquals('116.41036949371,39.92133699351', $coord->clone()->toBd09());

        $coord = new Coord(35.765, 140.386, Coord::GCJ02);
        $this->assertEquals($coord, $coord->clone()->toWgs84());
    }

    public function testTransformFromWgs84()
    {
        $coord = new Coord(116.404, 39.915, Coord::WGS84);
        $this->assertEquals($coord, $coord->clone()->toWgs84());
        $this->assertEquals('116.41024449917,39.916404281502', $coord->clone()->toGcj02());
        $this->assertEquals('116.41662724379,39.922699552216', $coord->clone()->toBd09());
        $this->assertEquals($coord->clone()->toGcj02(), $coord->clone()->to(Coord::GCJ02));
        $this->assertEquals($coord->clone()->toBd09(), $coord->clone()->to(Coord::BD09));

        $coord = new Coord(35.765, 140.386, Coord::WGS84);
        $this->assertEquals($coord, $coord->clone()->toGcj02());
    }

    public function testDistance()
    {
        $coord = new Coord(116.404, 39.915);
        $this->assertEquals(0, $coord->distanceTo($coord));
        $this->assertNotEquals(0, $coord->distanceTo(new Coord(116.404, 39.915, Coord::GCJ02)));
    }

    /**
     * @expectedException labs7in0\coord\Exceptions\UnknownTypeException
     */
    public function testNewUnknownType()
    {
        $coord = new Coord(116.404, 39.915, 0);
    }

    /**
     * @expectedException labs7in0\coord\Exceptions\UnknownTypeException
     */
    public function testTransformToUnknownType()
    {
        $coord = new Coord(116.404, 39.915);
        $coord->to(0);
    }
}
