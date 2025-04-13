<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class AnswerUser extends Model
{
    use HasFactory, Uuid;

    protected $table = 'answer_user';
    protected $primaryKey = 'guid';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'question_guid',
        'answer',
        'page',
        'cosine_similarity'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_guid', 'guid');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function plagiarisms()
    {
        return $this->hasMany(Plagiarism::class, 'user_answer_guid', 'guid');
    }
    
}
