<?php

namespace App\Http\Controllers;

use App\Models\Pixel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TileController extends Controller
{
    private const TILE_SIZE = 256;

    private const WORLD_ZOOM = 10;

    /**
     * Standard XYZ tile (z,x,y) to geographic bounds (lat/lng).
     */
    private function tileToLngLatBounds(int $z, int $x, int $y): array
    {
        $n = 2 ** $z;
        $lngMin = $x / $n * 360 - 180;
        $lngMax = ($x + 1) / $n * 360 - 180;
        $latMax = rad2deg(2 * atan(exp(M_PI - 2 * M_PI * $y / $n)) - M_PI / 2);
        $latMin = rad2deg(2 * atan(exp(M_PI - 2 * M_PI * ($y + 1) / $n)) - M_PI / 2);
        return [
            'lngMin' => $lngMin,
            'lngMax' => $lngMax,
            'latMin' => $latMin,
            'latMax' => $latMax,
        ];
    }

    /**
     * Geographic (lng, lat) to world pixel at WORLD_ZOOM.
     */
    private function lngLatToWorldPx(float $lng, float $lat): array
    {
        $scale = 256 * (2 ** self::WORLD_ZOOM);
        $x = ($lng + 180) / 360 * $scale;
        $latRad = $lat * M_PI / 180;
        $mercN = log(tan(M_PI / 4 + $latRad / 2));
        $y = $scale / 2 - $mercN * $scale / (2 * M_PI);
        return ['x' => $x, 'y' => $y];
    }

    /**
     * Tile (z,x,y) -> world pixel bounds (minX, maxX, minY, maxY).
     */
    private function tileToWorldBounds(int $z, int $x, int $y): array
    {
        $geo = $this->tileToLngLatBounds($z, $x, $y);
        $nw = $this->lngLatToWorldPx($geo['lngMin'], $geo['latMax']);
        $se = $this->lngLatToWorldPx($geo['lngMax'], $geo['latMin']);
        return [
            'minX' => (int) floor($nw['x']),
            'maxX' => (int) ceil($se['x']),
            'minY' => (int) floor($nw['y']),
            'maxY' => (int) ceil($se['y']),
        ];
    }

    /**
     * Parse hex color (#RGB or #RRGGBB) to [r,g,b].
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return [$r, $g, $b];
    }

    public function __invoke(Request $request, int $z, int $x, int $y): Response
    {
        $extent = (2 ** $z) - 1;
        if ($x < 0 || $x > $extent || $y < 0 || $y > $extent) {
            return response('', 404);
        }

        $w = $this->tileToWorldBounds($z, $x, $y);
        $pixels = Pixel::select('x', 'y', 'color')
            ->whereBetween('x', [$w['minX'], $w['maxX']])
            ->whereBetween('y', [$w['minY'], $w['maxY']])
            ->get();

        $img = imagecreatetruecolor(self::TILE_SIZE, self::TILE_SIZE);
        if (! $img) {
            return response('', 500);
        }
        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $transparent);

        $rangeX = $w['maxX'] - $w['minX'] + 1;
        $rangeY = $w['maxY'] - $w['minY'] + 1;
        $rangeX = $rangeX <= 0 ? 1 : $rangeX;
        $rangeY = $rangeY <= 0 ? 1 : $rangeY;

        foreach ($pixels as $p) {
            $x0 = (int) floor(($p->x - $w['minX']) / $rangeX * self::TILE_SIZE);
            $y0 = (int) floor(($p->y - $w['minY']) / $rangeY * self::TILE_SIZE);
            $x1 = (int) ceil(($p->x + 1 - $w['minX']) / $rangeX * self::TILE_SIZE);
            $y1 = (int) ceil(($p->y + 1 - $w['minY']) / $rangeY * self::TILE_SIZE);
            $x0 = max(0, min(self::TILE_SIZE, $x0));
            $y0 = max(0, min(self::TILE_SIZE, $y0));
            $x1 = max(0, min(self::TILE_SIZE, $x1));
            $y1 = max(0, min(self::TILE_SIZE, $y1));
            if ($x1 <= $x0) { $x1 = $x0 + 1; }
            if ($y1 <= $y0) { $y1 = $y0 + 1; }
            [$r, $g, $b] = $this->hexToRgb($p->color ?? '#000000');
            $c = imagecolorallocate($img, $r, $g, $b);
            imagefilledrectangle($img, $x0, $y0, $x1 - 1, $y1 - 1, $c);
        }

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=60',
        ]);
    }
}
