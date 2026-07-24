<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('converted_720')->nullable()->after('size');
            $table->string('converted_1080')->nullable()->after('converted_720');
            $table->string('converted_480')->nullable()->after('converted_1080');
            $table->string('hls_path')->nullable()->after('converted_480');
            $table->string('trimmed_file')->nullable()->after('hls_path');
            $table->string('merged_file')->nullable()->after('trimmed_file');
            $table->string('audio_file')->nullable()->after('merged_file');
            $table->json('thumbnail_gallery')->nullable()->after('audio_file');
            $table->string('watermarked_file')->nullable()->after('thumbnail_gallery');
            $table->string('subtitled_file')->nullable()->after('watermarked_file');
            $table->boolean('is_muted')->default(false)->after('subtitled_file');
            $table->integer('original_duration_seconds')->nullable()->after('is_muted');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn([
                'converted_720',
                'converted_1080',
                'converted_480',
                'hls_path',
                'trimmed_file',
                'merged_file',
                'audio_file',
                'thumbnail_gallery',
                'watermarked_file',
                'subtitled_file',
                'is_muted',
                'original_duration_seconds',
            ]);
        });
    }
};
