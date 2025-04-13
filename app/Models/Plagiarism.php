<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class Plagiarism extends Model
{
    use HasFactory, Uuid;

    protected $table = 'plagiarism';
    protected $primaryKey = 'guid';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_answer_guid',
        'ai_answer_guid',
        'cosine_similarity',
        'jaccard_similarity',
        'bert_score',
        'sequence_matching'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function userAnswer()
    {
        return $this->belongsTo(AnswerUser::class, 'user_answer_guid', 'guid');
    }

    public function llmAnswer()
    {
        return $this->belongsTo(AnswerLlm::class, 'ai_answer_guid', 'guid');
    }

}
