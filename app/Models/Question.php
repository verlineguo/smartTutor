<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class Question extends Model
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
        'question',
        'answer_pdf_guid',
        'question_fix',
        'answer_fix',
        'weight',
        'category',
        'topic_guid',
        'threshold',
        'user_id',
        'question_nouns',
        'attempt',
        'page',
        'cossine_similarity',
        // 'openai_cosine',
        // 'gemini_cosine',
        // 'deepseek_cosine',
        'language'
        
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
     * ANSWER OBJECT
     */
    public function user_answer()
    {
        return $this->hasMany(Answer::class);
    }
    public function answers()
    {
        return $this->hasMany(AnswerUser::class, 'question_guid', 'guid');
    }
    /**
     * ANSWER PDF OBJECT
     */
    public function answer_pdf()
    {
        return $this->hasMany(AnswerPDF::class, 'question_guid', 'guid');
    }
    /**
     * ANSWER LLM OBJECT
     */
    public function answer_llm()
    {
        return $this->hasMany(AnswerLLM::class, 'question_guid', 'guid');
    }
}
