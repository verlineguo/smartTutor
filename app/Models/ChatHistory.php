<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class ChatHistory extends Model
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
        'user_id',
        'topic_guid',
        'message',
        'sender',
        'page',
        'cosine_similarity',
        'question_guid'
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
        // 'status' => StatusEnum::class
    ];
    /**
     * TOPIC OBJECT
     */
    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * USER OBJECT
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    /**
     * Question OBJECT
     */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
