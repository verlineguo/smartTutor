<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Similarity extends Model
{
    use HasFactory;

    protected $table = 'similarity';
    protected $primaryKey = 'guid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'guid',
        'user_answer_guid',
        'llm_type',
        'algorithm',
        'similarity_score',
    ];

    public function userAnswer()
    {
        return $this->belongsTo(ChatHistory::class, 'user_answer_guid', 'guid');
    }


}
