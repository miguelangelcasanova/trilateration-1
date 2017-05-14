<?php

namespace Tuupola;

use Nubs\Vectorix\Vector;
use Tuupola\Trilateration\Circle;
use Tuupola\Trilateration\Point;

class Trilateration
{
    const EARTH_RADIUS = 6371;

    private $circleA;
    private $circleB;
    private $circleC;

    public function __construct(
        Circle $circleA,
        Circle $circleB,
        Circle $circleC
    )
    {
        $this->circleA = $circleA;
        $this->circleB = $circleB;
        $this->circleC = $circleC;
    }

    public function intersection()
    {
        /* http://en.wikipedia.org/wiki/Trilateration */
        /* https://gis.stackexchange.com/a/415 */
        /* https://gist.github.com/dav-/bb7103008cdf9359887f */

        $P1 = $this->circleA->toVector();
        $P2 = $this->circleB->toVector();
        $P3 = $this->circleC->toVector();

        $ex = $P2->subtract($P1)->normalize();
        $i = $ex->dotProduct($P3->subtract($P1));
        $temp = $ex->multiplyByScalar($i);
        $ey = $P3->subtract($P1)->subtract($temp)->normalize();
        $ez = $ex->crossProduct($ey);
        $d = $P2->subtract($P1)->length();
        $j = $ey->dotProduct($P3->subtract($P1));

        $x = (
            pow($this->circleA->distance(), 2) -
            pow($this->circleB->distance(), 2) +
            pow($d, 2)
        ) / (2 * $d);

        $y = ((
            pow($this->circleA->distance(), 2) -
            pow($this->circleC->distance(), 2) +
            pow($i, 2) + pow($j, 2)
        ) / (2 * $j)) - (($i / $j) * $x);

        $z = sqrt(abs(pow($this->circleA->distance(), 2) - pow($x, 2) - pow($y,2)));

        /* triPt is an array with ECEF x,y,z of trilateration point */
        $triPt = $P1
            ->add($ex->multiplyByScalar($x))
            ->add($ey->multiplyByScalar($y))
            ->add($ez->multiplyByScalar($z));

        $triPtX = $triPt->components()[0];
        $triPtY = $triPt->components()[1];
        $triPtZ = $triPt->components()[2];

        /* Convert back to lat/long from ECEF. Convert to degrees. */
        $latitude = rad2deg(asin($triPtZ / self::EARTH_RADIUS));
        $longitude = rad2deg(atan2($triPtY,$triPtX));

        return new Point($latitude, $longitude);
    }
}