<?php

namespace App\Support;

class ServiceArea
{
    public static function contains(float $latitude, float $longitude): bool
    {
        $geo = json_decode(file_get_contents(resource_path('geo/kurdistan.geojson')), true, flags: JSON_THROW_ON_ERROR);
        foreach ($geo['features'] as $feature) {
            $geometry = $feature['geometry'];
            $polygons = $geometry['type'] === 'Polygon' ? [$geometry['coordinates']] : $geometry['coordinates'];
            foreach ($polygons as $rings) {
                if (! self::inRing($longitude, $latitude, $rings[0])) {
                    continue;
                }
                foreach (array_slice($rings, 1) as $hole) {
                    if (self::inRing($longitude, $latitude, $hole)) {
                        continue 2;
                    }
                }
                return true;
            }
        }
        return false;
    }

    private static function inRing(float $x, float $y, array $ring): bool
    {
        $inside = false;
        for ($i = 0, $j = count($ring) - 1; $i < count($ring); $j = $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];
            if (($yi > $y) !== ($yj > $y) && $x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi) {
                $inside = ! $inside;
            }
        }
        return $inside;
    }
}
