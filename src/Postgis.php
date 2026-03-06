<?php

declare(strict_types=1);

namespace Jeorgy\LaravelPostgis;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Jeorgy\LaravelPostgis\Geometries\Point;
use Jeorgy\LaravelPostgis\Geometries\Polygon;

trait Postgis
{
    public function scopeWithDistance(Builder $query, Point|array|null $location): Builder
    {
        $this->ensureSelects($query);

        if ($location === null) {
            return $query->selectRaw('0 as distance');
        }

        [$lng, $lat] = $this->normalizePoint($location);
        $division = $this->getDivisionFactor();
        $expr = "ST_DistanceSphere({$this->getLocationColumn()}, ST_SetSRID(ST_MakePoint(?, ?), 4326)) / {$division}";

        return $query->selectRaw("{$expr} as distance", [$lng, $lat]);
    }

    public function scopeWhereDistance(Builder $query, Point|array $location, string $operator, float|int $units): Builder
    {
        $this->ensureSelects($query);

        $this->assertValidOperator($operator);
        [$lng, $lat] = $this->normalizePoint($location);
        $expr = "ST_DistanceSphere({$this->getLocationColumn()}, ST_SetSRID(ST_MakePoint(?, ?), 4326)) {$operator} ?";

        return $query->whereRaw($expr, [$lng, $lat, $units]);
    }

    public function scopeOrWhereDistance(Builder $query, Point|array $location, string $operator, float|int $units): Builder
    {
        $this->ensureSelects($query);

        $this->assertValidOperator($operator);
        [$lng, $lat] = $this->normalizePoint($location);
        $expr = "ST_DistanceSphere({$this->getLocationColumn()}, ST_SetSRID(ST_MakePoint(?, ?), 4326)) {$operator} ?";

        return $query->orWhereRaw($expr, [$lng, $lat, $units]);
    }

    public function scopeWhereCovers(Builder $query, Polygon|\stdClass $geoJson): Builder
    {
        $this->ensureSelects($query);

        $polygon = $this->normalizePolygon($geoJson);
        $wkt = $this->buildWktPolygon($polygon);
        $expr = "ST_Covers(ST_GeogFromText(?), {$this->getLocationColumn()})";

        return $query->whereRaw($expr, [$wkt]);
    }

    public function scopeOrWhereCovers(Builder $query, Polygon|\stdClass $geoJson): Builder
    {
        $this->ensureSelects($query);

        $polygon = $this->normalizePolygon($geoJson);
        $wkt = $this->buildWktPolygon($polygon);
        $expr = "ST_Covers(ST_GeogFromText(?), {$this->getLocationColumn()})";

        return $query->orWhereRaw($expr, [$wkt]);
    }

    private function ensureSelects(Builder $query): void
    {
        $classQuery = $query->getQuery();
        if ($classQuery && !$classQuery->columns) {
            $query->select([$classQuery->from . '.*']);
        }
    }

    private function getLocationColumn(): string
    {
        $column = property_exists($this, 'location') && $this->location ? $this->location : 'location';

        return $this->getTable() . '.' . $column;
    }

    private function getDivisionFactor(): float
    {
        if (property_exists($this, 'unit')) {
            if ($this->unit === 'mile') {
                return 1609.344;
            }

            if ($this->unit === 'km') {
                return 1000.0;
            }
        }

        return 1.0;
    }

    private function normalizePoint(Point|array|string $location): array
    {
        if ($location instanceof Point) {
            return [$location->getLng(), $location->getLat()];
        }

        if (is_string($location)) {
            $parts = array_map('trim', explode(',', $location));
            if (count($parts) !== 2) {
                throw new InvalidArgumentException('Point string must contain two comma-separated values.');
            }

            return [(float) $parts[1], (float) $parts[0]];
        }

        if (is_array($location)) {
            if (array_key_exists('lat', $location) && array_key_exists('lng', $location)) {
                return [(float) $location['lng'], (float) $location['lat']];
            }

            if (count($location) === 2) {
                return [(float) $location[1], (float) $location[0]];
            }
        }

        throw new InvalidArgumentException('Invalid point representation.');
    }

    private function normalizePolygon(Polygon|\stdClass $geoJson): Polygon
    {
        if ($geoJson instanceof Polygon) {
            return $geoJson;
        }

        return Polygon::fromGeoJson($geoJson);
    }

    private function buildWktPolygon(Polygon $polygon): string
    {
        return 'SRID=4326;' . $polygon->toWkt();
    }

    private function assertValidOperator(string $operator): void
    {
        if (!in_array($operator, ['<', '<=', '>', '>=', '='], true)) {
            throw new InvalidArgumentException('Invalid distance operator.');
        }
    }
}
