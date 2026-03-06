<?php

declare(strict_types=1);

namespace Jeorgy\LaravelPostgis\Geometries;

use InvalidArgumentException;
use JsonSerializable;

class Polygon implements JsonSerializable
{
    private array $rings;

    public function __construct(array $rings)
    {
        $this->rings = array_map([$this, 'normalizeRing'], $rings);
        if (count($this->rings) === 0) {
            throw new InvalidArgumentException('Polygon must contain at least one ring.');
        }
    }

    public static function fromGeoJson(object $geoJson): self
    {
        if (!isset($geoJson->geometry->coordinates) || !is_array($geoJson->geometry->coordinates)) {
            throw new InvalidArgumentException('Invalid GeoJSON polygon.');
        }

        return new self(array_map(function ($coordinates) {
            if (!is_array($coordinates)) {
                throw new InvalidArgumentException('Invalid GeoJSON polygon coordinates.');
            }

            return array_map(function ($point) {
                if (!is_array($point) || count($point) < 2) {
                    throw new InvalidArgumentException('Invalid GeoJSON polygon point.');
                }

                return [
                    (float) $point[0],
                    (float) $point[1],
                ];
            }, $coordinates);
        }, $geoJson->geometry->coordinates));
    }

    public function toWkt(): string
    {
        $rings = array_map(function (array $ring) {
            $closed = $this->closeRing($ring);
            $pairs = array_map(fn (array $point) => $point[0] . ' ' . $point[1], $closed);

            return '(' . implode(', ', $pairs) . ')';
        }, $this->rings);

        return 'POLYGON(' . implode(', ', $rings) . ')';
    }

    public function jsonSerialize(): array
    {
        return $this->rings;
    }

    private function normalizeRing(array $ring): array
    {
        $normalized = [];
        foreach ($ring as $point) {
            if (!is_array($point) || count($point) < 2) {
                throw new InvalidArgumentException('Each point must contain at least two values.');
            }
            $normalized[] = [
                (float) $point[0],
                (float) $point[1],
            ];
        }

        if (count($normalized) < 3) {
            throw new InvalidArgumentException('Ring must contain at least three points.');
        }

        return $normalized;
    }

    private function closeRing(array $ring): array
    {
        $first = $ring[0];
        $last = $ring[count($ring) - 1];

        if ($first[0] === $last[0] && $first[1] === $last[1]) {
            return $ring;
        }

        $ring[] = $first;

        return $ring;
    }
}
