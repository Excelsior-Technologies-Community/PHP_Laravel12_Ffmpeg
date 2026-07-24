<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('converted_h265')->nullable()->after('converted_480');
            $table->string('compressed_file')->nullable()->after('converted_h265');
            $table->string('muted_file')->nullable()->after('compressed_file');
            $table->string('converted_audio_file')->nullable()->after('muted_file');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['converted_h265', 'compressed_file', 'muted_file', 'converted_audio_file']);
        });
    }
};
