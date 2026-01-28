<?php

namespace App\Http\Controllers;

use App\Models\Pixel;
use App\Models\PixelHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
        $timeParam = $request->query('time');
        $pixels = $this->resolvePixelsForTile($w, $timeParam);

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

        $cacheControl = $timeParam ? 'public, max-age=10' : 'public, max-age=60';
        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => $cacheControl,
        ]);
    }

    /**
     * Resolve pixels for a tile: current state from Pixel, or state-at-time from PixelHistory.
     *
     * @param  array{minX: int, maxX: int, minY: int, maxY: int}  $w
     * @return \Illuminate\Support\Collection<int, object{x: int, y: int, color: string}>
     */
    private function resolvePixelsForTile(array $w, ?string $timeParam): \Illuminate\Support\Collection
    {
        if ($timeParam === null || $timeParam === '') {
            return Pixel::select('x', 'y', 'color')
                ->whereBetween('x', [$w['minX'], $w['maxX']])
                ->whereBetween('y', [$w['minY'], $w['maxY']])
                ->where('hidden', false)
                ->get();
        }

        try {
            $at = Carbon::parse($timeParam);
        } catch (\Throwable $e) {
            return Pixel::select('x', 'y', 'color')
                ->whereBetween('x', [$w['minX'], $w['maxX']])
                ->whereBetween('y', [$w['minY'], $w['maxY']])
                ->get();
        }
        // Optimize wayback query: use a more efficient approach
        // Get the latest pixel for each (x,y) coordinate in the tile bounds
        $rows = PixelHistory::select('x', 'y', 'color', 'created_at')
            ->whereBetween('x', [$w['minX'], $w['maxX']])
            ->whereBetween('y', [$w['minY'], $w['maxY']])
            ->where('created_at', '<=', $at)
            ->orderByDesc('created_at')
            ->limit(10000) // Safety limit to prevent memory issues on very dense tiles
            ->get();

        $seen = [];
        $pixels = collect();
        foreach ($rows as $p) {
            $key = "{$p->x},{$p->y}";
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $pixels->push((object) ['x' => $p->x, 'y' => $p->y, 'color' => $p->color ?? '#000000']);
        }

        return $pixels;
    }

    /**
     * Return tile pixel data as JSON for PBF client-side encoding.
     */
    public function json(Request $request, int $z, int $x, int $y): \Illuminate\Http\JsonResponse
    {
        $extent = (2 ** $z) - 1;
        if ($x < 0 || $x > $extent || $y < 0 || $y > $extent) {
            return response()->json([]);
        }

        $w = $this->tileToWorldBounds($z, $x, $y);
        $timeParam = $request->query('time');
        $pixels = $this->resolvePixelsForTile($w, $timeParam);

        $data = $pixels->map(fn ($p) => [
            'x' => $p->x,
            'y' => $p->y,
            'color' => $p->color ?? '#000000',
        ])->values();

        $cacheControl = $timeParam ? 'public, max-age=10' : 'public, max-age=60';
        return response()->json($data)->header('Cache-Control', $cacheControl);
    }

    /**
     * Return tile as PBF (Mapbox Vector Tile) with proper MIME type.
     */
    public function pbf(Request $request, int $z, int $x, int $y): Response
    {
        $extent = (2 ** $z) - 1;
        if ($x < 0 || $x > $extent || $y < 0 || $y > $extent) {
            return response('', 404);
        }

        $timeParam = $request->query('time');
        $cacheKey = "tile:pbf:{$z}:{$x}:{$y}:" . ($timeParam ?? 'live');
        $cacheTtl = $timeParam ? 10 : 60; // 10 seconds for wayback, 60 for live
        
        $pbfData = Cache::remember($cacheKey, $cacheTtl, function () use ($z, $x, $y, $timeParam) {
            $w = $this->tileToWorldBounds($z, $x, $y);
            $pixels = $this->resolvePixelsForTile($w, $timeParam);

            $data = $pixels->map(fn ($p) => [
                'x' => $p->x,
                'y' => $p->y,
                'color' => $p->color ?? '#000000',
            ])->values();

            $response = $this->generatePbf($data->toArray(), $z, $x, $y, $timeParam);
            return $response->getContent();
        });

        $cacheControl = $timeParam ? 'public, max-age=10' : 'public, max-age=60';
        return response($pbfData, 200, [
            'Content-Type' => 'application/vnd.mapbox-vector-tile',
            'Cache-Control' => $cacheControl,
        ]);
    }

    /**
     * Generate PBF tile from pixel data using Node.js script.
     */
    private function generatePbf(array $pixels, int $z, int $x, int $y, ?string $timeParam = null): Response
    {
        $scriptPath = base_path('scripts/json-to-pbf.js');
        $projectPath = base_path();
        $input = json_encode([
            'pixels' => $pixels,
            'z' => $z,
            'x' => $x,
            'y' => $y,
            'zoom' => self::WORLD_ZOOM,
        ]);

        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        // Use absolute path to node and set working directory to project root for module resolution
        // ES modules resolve relative to the script file, so cwd must be project root
        // Try multiple methods to find node executable
        $nodePath = null;
        // Try multiple ways to get HOME directory
        $home = getenv('HOME') ?: (isset($_SERVER['HOME']) ? $_SERVER['HOME'] : (isset($_ENV['HOME']) ? $_ENV['HOME'] : ''));
        // Fallback: try to get home from posix_getpwuid if available
        if (empty($home) && function_exists('posix_getpwuid')) {
            $userInfo = posix_getpwuid(posix_geteuid());
            $home = $userInfo['dir'] ?? '';
        }
        // Additional fallback: try to get actual user's home from /Users (macOS)
        if (empty($home) && PHP_OS_FAMILY === 'Darwin') {
            // Try common macOS user directories
            $usersDir = '/Users';
            if (is_dir($usersDir)) {
                // Check for common usernames or try to detect from project path
                $projectPathParts = explode('/', $projectPath);
                foreach ($projectPathParts as $part) {
                    if ($part && $part !== 'Users' && is_dir("/Users/{$part}")) {
                        $home = "/Users/{$part}";
                        break;
                    }
                }
            }
        }
        
        // Build list of potential node paths
        $potentialPaths = [
            '/usr/local/bin/node',
            '/opt/homebrew/bin/node',
            '/usr/bin/node',
        ];
        
        // Add Herd-specific paths if running under Herd
        if ($home) {
            $herdPath = $home . '/Library/Application Support/Herd/config/nvm/versions/node';
            if (is_dir($herdPath)) {
                // Find the latest or specified version
                $versions = glob($herdPath . '/v*/bin/node');
                if (!empty($versions)) {
                    // Use the highest version number or check .nvmrc
                    usort($versions, function($a, $b) {
                        preg_match('/v(\d+)/', $a, $ma);
                        preg_match('/v(\d+)/', $b, $mb);
                        return ($mb[1] ?? 0) <=> ($ma[1] ?? 0);
                    });
                    $potentialPaths[] = $versions[0];
                }
            }
            
            // Add NVM paths
            $nvmPath = $home . '/.nvm/versions/node';
            if (is_dir($nvmPath)) {
                $versions = glob($nvmPath . '/v*/bin/node');
                if (!empty($versions)) {
                    usort($versions, function($a, $b) {
                        preg_match('/v(\d+)/', $a, $ma);
                        preg_match('/v(\d+)/', $b, $mb);
                        return ($mb[1] ?? 0) <=> ($ma[1] ?? 0);
                    });
                    $potentialPaths[] = $versions[0];
                }
            }
        }
        
        // Method 1: Check potential paths directly
        foreach ($potentialPaths as $testPath) {
            if (file_exists($testPath) && is_executable($testPath)) {
                $nodePath = $testPath;
                break;
            }
        }
        
        // Method 2: Try shell_exec with expanded PATH
        if (!$nodePath) {
            $pathDirs = array_filter([
                '/usr/local/bin',
                '/opt/homebrew/bin',
                '/usr/bin',
                $home ? $home . '/Library/Application Support/Herd/config/nvm/versions/node/v22.20.0/bin' : null,
                $home ? $home . '/.nvm/versions/node/v22.20.0/bin' : null,
                getenv('PATH'),
            ]);
            $pathEnv = implode(':', $pathDirs);
            $found = trim(shell_exec("PATH={$pathEnv} which node 2>/dev/null") ?: '');
            if ($found && file_exists($found)) {
                $nodePath = $found;
            }
        }
        
        // Method 3: Fallback to 'node' (might work if in system PATH)
        if (!$nodePath) {
            $nodePath = 'node';
        }
        
        $command = escapeshellarg($nodePath) . ' ' . escapeshellarg($scriptPath);
        $cwd = $projectPath;
        
        // Set environment variables - inherit PATH and HOME for Node to work properly
        $env = [];
        $pathDirs = array_filter([
            dirname($nodePath) !== '.' ? dirname($nodePath) : null,
            '/usr/local/bin',
            '/opt/homebrew/bin',
            '/usr/bin',
            $home ? $home . '/Library/Application Support/Herd/config/nvm/versions/node/v22.20.0/bin' : null,
            $home ? $home . '/.nvm/versions/node/v22.20.0/bin' : null,
            getenv('PATH'),
        ]);
        $env['PATH'] = implode(':', $pathDirs);
        if ($home) {
            $env['HOME'] = $home;
        }
        // NODE_PATH can help but ES modules primarily use package.json and node_modules location
        $env['NODE_PATH'] = $projectPath . '/node_modules';

        $process = proc_open($command, $descriptorspec, $pipes, $cwd, $env);
        if (! is_resource($process)) {
            Log::error('PBF: Failed to open process', [
                'command' => $command,
                'cwd' => $cwd,
                'nodePath' => $nodePath,
                'home' => $home,
                'scriptExists' => file_exists($scriptPath),
            ]);
            return response('', 500);
        }

        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        // Set stream to binary mode for PBF output
        stream_set_blocking($pipes[1], true);
        stream_set_blocking($pipes[2], true);
        
        $pbf = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0 || $pbf === false) {
            Log::error('PBF generation failed', [
                'errors' => $errors ?: 'No error output',
                'exitCode' => $exitCode,
                'pbfLength' => $pbf ? strlen($pbf) : 0,
                'command' => $command,
                'cwd' => $cwd,
                'nodePath' => $nodePath,
                'nodePathExists' => file_exists($nodePath),
                'nodePathExecutable' => $nodePath && file_exists($nodePath) ? is_executable($nodePath) : false,
                'scriptExists' => file_exists($scriptPath),
                'nodeModulesExists' => is_dir($projectPath . '/node_modules'),
                'home' => $home,
                'envPath' => $env['PATH'] ?? 'not set',
            ]);
            return response('', 500);
        }
        

        $cacheControl = $timeParam ? 'public, max-age=10' : 'public, max-age=60';
        return response($pbf, 200, [
            'Content-Type' => 'application/vnd.mapbox-vector-tile',
            'Cache-Control' => $cacheControl,
        ]);
    }

    /**
     * Return tile as PBF with versioned path pattern: /planet/{version}/{z}/{x}/{y}.pbf
     * Version format: YYYYMMDD_HHMMSS_pt (e.g., 20250806_001001_pt)
     */
    public function pbfVersioned(Request $request, string $version, int $z, int $x, int $y): Response
    {
        $extent = (2 ** $z) - 1;
        if ($x < 0 || $x > $extent || $y < 0 || $y > $extent) {
            return response('', 404);
        }

        // Parse version to extract timestamp if it's a wayback version
        // Format: YYYYMMDD_HHMMSS_pt
        $timeParam = null;
        if (preg_match('/^(\d{8})_(\d{6})_/', $version, $matches)) {
            $dateStr = $matches[1]; // YYYYMMDD
            $timeStr = $matches[2]; // HHMMSS
            try {
                $year = substr($dateStr, 0, 4);
                $month = substr($dateStr, 4, 2);
                $day = substr($dateStr, 6, 2);
                $hour = substr($timeStr, 0, 2);
                $minute = substr($timeStr, 2, 2);
                $second = substr($timeStr, 4, 2);
                $timeParam = Carbon::create($year, $month, $day, $hour, $minute, $second)->toIso8601String();
            } catch (\Throwable $e) {
                // If parsing fails, treat as live tile (no time param)
            }
        }

        // Cache key includes version, z, x, y - versioned tiles are immutable so cache forever
        $cacheKey = "tile:pbf:{$version}:{$z}:{$x}:{$y}";
        
        // For versioned tiles (wayback), cache indefinitely since they're immutable
        // For live tiles (current version), use shorter cache
        $isLiveTile = $timeParam === null;
        $cacheTtl = $isLiveTile ? 60 : 86400 * 365; // 60 seconds for live, 1 year for wayback
        
        $pbfData = Cache::remember($cacheKey, $cacheTtl, function () use ($z, $x, $y, $timeParam) {
            $w = $this->tileToWorldBounds($z, $x, $y);
            $pixels = $this->resolvePixelsForTile($w, $timeParam);

            $data = $pixels->map(fn ($p) => [
                'x' => $p->x,
                'y' => $p->y,
                'color' => $p->color ?? '#000000',
            ])->values();

            $response = $this->generatePbf($data->toArray(), $z, $x, $y, $timeParam);
            
            // Return the binary PBF data for caching
            return $response->getContent();
        });
        
        // Create response from cached data
        return response($pbfData, 200, [
            'Content-Type' => 'application/vnd.mapbox-vector-tile',
            'Cache-Control' => $isLiveTile ? 'public, max-age=60' : 'public, max-age=315360000',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}
