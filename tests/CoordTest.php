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
        $this->assertEquals('116.39175825601,39.907541664069', (string)$coord->copy()->toWgs84());
        $this->assertEquals('116.39762729119,39.908656739576', (string)$coord->copy()->toGcj02());
        $this->assertEquals($coord, $coord->copy()->toBd09());
    }

    public function testTransformFromGcj02()
    {
        $coord = new Coord(116.404, 39.915, Coord::GCJ02);
        $this->assertEquals('116.39813053332,39.913884507655', (string)$coord->copy()->toWgs84());
        $this->assertEquals($coord, $coord->copy()->toGcj02());
        $this->assertEquals('116.41036949371,39.92133699351', (string)$coord->copy()->toBd09());

        $coord = new Coord(35.765, 140.386, Coord::GCJ02);
        $this->assertEquals($coord, $coord->copy()->toWgs84());
    }

    public function testTransformFromWgs84()
    {
        $coord = new Coord(116.404, 39.915, Coord::WGS84);
        $this->assertEquals($coord, $coord->copy()->toWgs84());
        $this->assertEquals('116.40986946668,39.916115492345', (string)$coord->copy()->toGcj02());
        $this->assertEquals('116.41625121875,39.922414273244', (string)$coord->copy()->toBd09());
        $this->assertEquals($coord->copy()->toGcj02(), $coord->copy()->to(Coord::GCJ02));
        $this->assertEquals($coord->copy()->toBd09(), $coord->copy()->to(Coord::BD09));

        $coord = new Coord(35.765, 140.386, Coord::WGS84);
        $this->assertEquals($coord, $coord->copy()->toGcj02());
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
