<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class AnswerPDF extends Model
{
    use HasFactory, Uuid;

    protected $table = 'answer_pdf';
    protected $primaryKey = 'guid';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'question_guid',
        'answer',
        'page',
        'combined_score',
        'qa_score',
        'retrieval_score'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_guid', 'guid');
    }
}
