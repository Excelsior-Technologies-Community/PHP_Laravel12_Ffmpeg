<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'title',
        'filename',
        'thumbnail',
        'duration',
        'size',
        'converted_720',
        'converted_1080',
        'converted_480',
        'converted_h265',
        'compressed_file',
        'hls_path',
        'trimmed_file',
        'merged_file',
        'audio_file',
        'converted_audio_file',
        'thumbnail_gallery',
        'watermarked_file',
        'subtitled_file',
        'is_muted',
        'muted_file',
        'original_duration_seconds',
    ];

    protected $casts = [
        'thumbnail_gallery' => 'array',
        'is_muted' => 'boolean',
    ];
}
