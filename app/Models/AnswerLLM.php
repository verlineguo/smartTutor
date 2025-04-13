<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class AnswerLLM extends Model
{
    use HasFactory, Uuid;

    protected $table = 'answer_llm';
    protected $primaryKey = 'guid';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'question_guid',
        'answer',
        'source'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_guid', 'guid');
    }
    public function plagiarisms()
    {
        return $this->hasMany(Plagiarism::class, 'ai_answer_guid', 'guid');
    }
}
