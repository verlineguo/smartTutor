@extends('layouts.template')

@section('vendor-css') 
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-bs5/datatables.bootstrap5.css') }}"> 
    <style>
        .comparison-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 20px;
        }
        .comparison-box {
            width: 48%;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .comparison-header {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .similarity-score {
            margin-top: 15px;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            border-radius: 8px;
        }
        .high-similarity {
            background-color: #ffdddd;
            color: #a94442;
        }
        .low-similarity {
            background-color: #d4edda;
            color: #155724;
        }
        .algorithm-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .algorithm-table th, .algorithm-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .algorithm-table th {
            background-color: #f2f2f2;
        }
    </style>
@endsection

@section('info-page')
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
        <li class="breadcrumb-item text-sm text-dark active text-capitalize" aria-current="page">Chatbot</li>
    </ol>
    <h5 class="font-weight-bolder mb-0 text-capitalize">Chatbot</h5>
@endsection

@section('content') 
    <h5 class="font-weight-bolder mt-4 mb-4">Detail Perbandingan Algoritma</h5>
    <table class="algorithm-table mb-6">
        <thead>
            <tr>
                <th>Algoritma</th>
                <th>Skor Kemiripan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Cosine Similarity</td>
                <td>{{ $similarityScores['cosine'] }}%</td>
            </tr>
            <tr>
                <td>Jaccard Similarity</td>
                <td>{{ $similarityScores['jaccard'] }}%</td>
            </tr>
            <tr>
                <td>Levenshtein Distance</td>
                <td>{{ $similarityScores['levenshtein'] }}%</td>
            </tr>
        </tbody>
    </table>
    
    <h5 class="font-weight-bolder mb-4">Perbandingan Jawaban Mahasiswa vs LLM</h5>
    <div class="comparison-container">
        <div class="comparison-box">
            <div class="comparison-header">Jawaban Mahasiswa</div>
            <p>{{ $studentAnswer }}</p>
        </div>
        <div class="comparison-box">
            <div class="comparison-header">Jawaban LLM ({{ $llmType }})</div>
            <p>{{ $llmAnswer }}</p>
        </div>
    </div>
    <div class="similarity-score">
        Kemiripan: <span>{{ $similarityScores['overall'] }}%</span>
   
@endsection