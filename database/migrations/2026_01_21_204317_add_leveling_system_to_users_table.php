<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('level')->default(1)->after('pixels_placed');
            $table->integer('pixels_available')->default(60)->after('level');
            $table->timestamp('last_pixel_regeneration_time')->nullable()->after('pixels_available');
        });

        // Initialize existing users with their calculated level and pixel pool
        $levelService = app(\App\Services\LevelService::class);
        \App\Models\User::chunk(100, function ($users) use ($levelService) {
            foreach ($users as $user) {
                $level = $levelService->calculateLevel($user->pixels_placed);
                $pixelLimit = $levelService->getPixelLimit($level);
                
                $user->level = $level;
                $user->pixels_available = $pixelLimit;
                $user->last_pixel_regeneration_time = now();
                $user->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['level', 'pixels_available', 'last_pixel_regeneration_time']);
        });
    }
};
