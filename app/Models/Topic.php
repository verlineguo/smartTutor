<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class Topic extends Model
{
    use HasFactory, Uuid;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'guid';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'course_code',
        'file_path',
        'file_language',
        'translation_metadata',
        'max_attempt_gpt',
        'time_start',
        'time_end'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'translation_metadata' => 'array',
        // 'status' => StatusEnum::class
    ];
    /**
     * QUESTION OBJECT
     */
    public function question()
    {
        return $this->hasMany(Question::class);
    }
    /**
     * COURSE OBJECT
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    /**
     * GRADE OBJECT
     */
    public function grade()
    {
        return $this->hasMany(Grade::class);
    }
}
