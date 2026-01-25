<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class LevelService
{
    const MAX_LEVEL = 50;

    public function calculateLevel(int $totalPixels): int
    {
        if ($totalPixels <= 0) {
            return 1;
        }

        $level = floor(sqrt($totalPixels / 50)) + 1;
        return min($level, self::MAX_LEVEL);
    }

    public function getPixelLimit(int $level): int
    {
        $level = min($level, self::MAX_LEVEL);

        if ($level <= 10) {
            return 60 + (($level - 1) * 2);
        } elseif ($level <= 25) {
            $base = 78 ;
            return $base + (($level - 10) * 3);
        } else {
            $base = 120;
            return $base + (($level - 25) * 4);
        }
    }

    public function getRegenerationTime(int $level): int
    {
        $level = min($level, self::MAX_LEVEL);

        if ($level <= 10) {
            return 30;
        } elseif ($level <= 25) {
            return 25;
        } else {
            return 20;
        }
    }

    public function calculateRegeneratedPixels(User $user): int
    {
        $pixelLimit = $this->getPixelLimit($user->level);
        
        if (!$user->last_pixel_regeneration_time) {
            $user->last_pixel_regeneration_time = now();
            $user->pixels_available = $pixelLimit;
            $user->save();
            return $pixelLimit;
        }

        $currentPixels = max(0, $user->pixels_available);
        
        if ($currentPixels >= $pixelLimit) {
            return $currentPixels;
        }

        if ($currentPixels > 0) {
            return $currentPixels;
        }

        $regenerationTime = $this->getRegenerationTime($user->level);
        $secondsSinceLastRegen = abs(now()->diffInSeconds($user->last_pixel_regeneration_time));
        
        if ($secondsSinceLastRegen >= $regenerationTime) {
            $user->last_pixel_regeneration_time = now();
            return $pixelLimit;
        }

        return 0;
    }

    public function updateUserLevel(User $user, bool $skipRegeneration = false): array
    {
        $oldLevel = $user->level;
        $newLevel = $this->calculateLevel($user->pixels_placed);
        $leveledUp = false;
        
        if ($newLevel > $oldLevel) {
            $leveledUp = true;
            $user->level = $newLevel;
            $newLimit = $this->getPixelLimit($newLevel);
            $user->pixels_available = $newLimit;
            $user->last_pixel_regeneration_time = now();
            $user->save();
        } elseif (!$skipRegeneration) {
            $user->pixels_available = $this->calculateRegeneratedPixels($user);
            $user->save();
        }
        
        return [
            'level' => $user->level,
            'leveled_up' => $leveledUp,
            'old_level' => $oldLevel,
            'pixels_available' => $user->pixels_available,
            'pixel_limit' => $this->getPixelLimit($user->level),
        ];
    }

    public function getTimeUntilRegeneration(User $user): ?float
    {
        $pixelLimit = $this->getPixelLimit($user->level);
        
        if ($user->pixels_available >= $pixelLimit || $user->pixels_available > 0) {
            return null;
        }

        $regenerationTime = $this->getRegenerationTime($user->level);
        $secondsSinceLastRegen = abs(now()->diffInSeconds($user->last_pixel_regeneration_time));
        
        return max(0, $regenerationTime - $secondsSinceLastRegen);
    }

    public function getLevelProgress(User $user): array
    {
        $currentLevel = $user->level;
        $totalPixels = $user->pixels_placed;
        $pixelsForCurrentLevel = $this->getPixelsForLevel($currentLevel);
        $nextLevel = min($currentLevel + 1, self::MAX_LEVEL);
        $pixelsForNextLevel = $this->getPixelsForLevel($nextLevel);
        
        $progress = $totalPixels - $pixelsForCurrentLevel;
        $needed = $pixelsForNextLevel - $pixelsForCurrentLevel;
        $percentage = $needed > 0 ? ($progress / $needed) * 100 : 100;
        
        return [
            'current_level' => $currentLevel,
            'next_level' => $nextLevel,
            'total_pixels' => $totalPixels,
            'pixels_for_current_level' => $pixelsForCurrentLevel,
            'pixels_for_next_level' => $pixelsForNextLevel,
            'progress' => $progress,
            'needed' => $needed,
            'percentage' => min(100, max(0, $percentage)),
            'is_max_level' => $currentLevel >= self::MAX_LEVEL,
        ];
    }

    private function getPixelsForLevel(int $level): int
    {
        if ($level <= 1) {
            return 0;
        }
        return (int) pow($level - 1, 2) * 50;
    }
}
