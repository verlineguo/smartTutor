<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlagiarismDetail extends Model
{
    use HasFactory;

    protected $table = 'plagiarism_details';
    protected $primaryKey = 'guid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'guid',
        'plagiarism_guid',
        'student_text',
        'best_match',
        'is_plagiarized',
        'weighted_score',
        'individual_scores',

    ];

    protected $casts = [
        'is_plagiarized' => 'boolean',
        'weighted_score' => 'double',
        'individual_scores' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Optional: Relationship to Plagiarism, if exists
    public function plagiarism()
    {
        return $this->belongsTo(Plagiarism::class, 'plagiarism_guid', 'guid');
    }
}
