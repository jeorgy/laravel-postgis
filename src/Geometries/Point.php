<?php

declare(strict_types=1);

namespace Jeorgy\LaravelPostgis\Geometries;

use JsonSerializable;

class Point implements JsonSerializable
{
    private float $lat;
    private float $lng;

    public function __construct(float $lat, float $lng)
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }

    public function getLat(): float
    {
        return $this->lat;
    }

    public function getLng(): float
    {
        return $this->lng;
    }

    public function toArray(): array
    {
        return [$this->lat, $this->lng];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
