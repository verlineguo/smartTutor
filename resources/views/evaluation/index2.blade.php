@extends('layouts.template')

@section('add-css')
    <style>
        /* Essential styling - removed redundant or unused styles */
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
            border: none;
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 1.5rem;
            font-weight: 600;
            border-radius: 10px 10px 0 0 !important;
        }

        .breadcrumb {
            background-color: transparent;
            margin-bottom: 1rem;
        }

        .custom-tabs {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 20px 0;
            border-bottom: 2px solid #eee;
        }

        .custom-tab-item {
            margin-right: 10px;
        }

        .custom-tab-link {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            color: #666;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            font-weight: 500;
            transition: all 0.3s;
        }

        .custom-tab-link i {
            margin-right: 8px;
        }

        .custom-tab-link.active {
            color: #cb0c9f;
            border-bottom: 3px solid #cb0c9f;
        }

        .custom-tab-link:hover {
            color: #cb0c9f;
        }

        .tab-container {
            display: none;
            padding: 20px 0;
        }

        .question-container {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .question-text {
            font-size: 1.2rem;
            margin-bottom: 15px;
        }

        .question-metadata {
            display: flex;
            gap: 30px;
        }

        .score-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .score-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .score-title {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }

        .score-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #cb0c9f;
        }

        .score-metric {
            font-size: 0.85rem;
        }

        .metric-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            background-color: #f8f9fa;
        }
        
        .metric-badge.success {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }
        
        .metric-badge.warning {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        
        .metric-badge.danger {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }


        .content-split {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .text-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            max-height: 600px;
            overflow-y: auto;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }

        .keywords-tag {
            display: inline-block;
            background-color: #e9ecef;
            color: #495057;
            padding: 4px 12px;
            border-radius: 20px;
            margin-right: 8px;
            margin-bottom: 8px;
            font-size: 0.85rem;
        }

        .keywords-tag.found {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .keywords-tag.missing {
            background-color: #f8d7da;
            color: #842029;
        }

        .action-buttons {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 20px;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #666;
            font-weight: 500;
            padding: 8px 16px;
        }

        .nav-tabs .nav-link.active {
            color: #344767;
            border-bottom: 2px solid #344767;
            background-color: transparent;
        }

        /* Styles for plagiarism detection */
        .plagiarism-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .plagiarism-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .chart-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 6px solid #e9ecef;
            border-top-color: #cb0c9f;
            position: relative;
            margin-bottom: 10px;
        }

        .chart-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            text-align: center;
        }

        .plagiarism-source {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }

        .plagiarism-metrics {
            font-size: 0.85rem;
        }

        .detection-method {
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #eee;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        
        .detection-method-value {
            font-size: 18px;
            font-weight: bold;
        }
        
        .detection-method-name {
            font-weight: 500;
            margin-bottom: 3px;
        }

        .strategy-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.strategy-badge {
    background-color: #e3f2fd;
    color: #0d6efd;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
}


        .plagiarism-very-high {
            background-color: #ff4d4d; /* Red for very high similarity */
            color: #fff;
        }

        /* Plagiarism highlighting */
        .plagiarism-high {
            background-color: #ff9933; 
    
        }

        .plagiarism-medium {
            background-color: #ffffcc;
       
        }

        .plagiarism-low {
            background-color: rgba(40, 167, 69, 0.3);
           
        }

        /* Comparison panels */
        .comparison-panel {
            display: flex;
            margin: 15px 0 30px;
        }

        .comparison-side {
            flex: 1;
            position: relative;
        }

        .comparison-side:first-child {
            padding-right: 20px;
            border-right: 1px solid #e0e0e0;
        }

        .comparison-side:last-child {
            padding-left: 20px;
        }

        .comparison-header {
            margin-bottom: 15px;
            font-weight: 500;
            color: #555;
            display: flex;
            justify-content: space-between;
        }

        .comparison-content {
            line-height: 1.65;
            font-size: 0.95rem;
        }

        /* Tooltip for similarity details */
        .similarity-tooltip {
            position: relative;
            cursor: pointer;
            border-radius: 3px;
            padding: 0 2px;
        }

        .similarity-tooltip .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: #333;
            color: #fff;
            text-align: left;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
        }

        .similarity-tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        .highlight-method {
            background-color: rgba(25, 135, 84, 0.1);
            border-color: rgba(25, 135, 84, 0.5);
            box-shadow: 0 0 8px rgba(25, 135, 84, 0.2);
        }
        
        .threshold-exceeded .detection-method-value {
            color: #dc3545;
        }
        

        .chart-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: conic-gradient(
                #28a745 0% var(--percentage, 0%),
                #f0f0f0 var(--percentage, 0%) 100%
            );
            margin: 0 auto 10px;
            position: relative;
        }
        
        .chart-circle.low { background: conic-gradient(#28a745 0% var(--percentage, 0%), #f0f0f0 var(--percentage, 0%) 100%); }
        .chart-circle.medium { background: conic-gradient(#ffc107 0% var(--percentage, 0%), #f0f0f0 var(--percentage, 0%) 100%); }
        .chart-circle.high { background: conic-gradient(#fd7e14 0% var(--percentage, 0%), #f0f0f0 var(--percentage, 0%) 100%); }
        .chart-circle.very-high { background: conic-gradient(#dc3545 0% var(--percentage, 0%), #f0f0f0 var(--percentage, 0%) 100%); }
        

        .chart-circle::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
        }

        .tooltip-text {
            visibility: hidden;
            width: 250px;
            background-color: #333;
            color: #fff;
            text-align: left;
            border-radius: 6px;
            padding: 8px 10px;
            position: absolute;
            z-index: 100;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
            line-height: 1.4;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.3);
        }

        .similarity-tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        .similarity-tooltip .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }
    </style>
@endsection

@section('info-page')
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
        <li class="breadcrumb-item text-sm text-dark active text-capitalize" aria-current="page">Assignment</li>
        <li class="breadcrumb-item text-sm text-dark active text-capitalize" aria-current="page">Evaluation</li>

    </ol>
    <h5 class="font-weight-bolder mb-0 text-capitalize">Assignment</h5>
@endsection


@section('content')
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <!-- Question Display -->
                        <div class="question-container">
                            <div class="question-text text-dark fw-bold" id="question-text">
                            </div>
                            <div class="question-metadata">
                                <span id="question-category">
                                </span>
                                <span id="question-threshold">
                                </span>
                            </div>
                        </div>

                        <!-- Main tabs -->
                        <ul class="custom-tabs">
                            <li class="custom-tab-item">
                                <a href="#" class="custom-tab-link active" data-target="answer-tab">
                                    <i class="fas fa-file-alt"></i>
                                    PDF Answer
                                </a>
                            </li>
                            <li class="custom-tab-item">
                                <a href="#" class="custom-tab-link" data-target="plagiarism-tab">
                                    <i class="fas fa-search"></i>
                                    Plagiarism Analysis
                                </a>
                            </li>

                        </ul>

                        <!-- Answer Tab -->
                        <div id="answer-tab" class="tab-container" style="display: block;">
                            <div class="score-overview">
                                <div class="score-card">
                                    <div class="score-title">Overall Score</div>
                                    <div class="score-value" id="overall-score">
                                    </div>
                                    <div class="score-metric">
                                        <span class="metric-badge">

                                        </span>
                                    </div>
                                </div>
                                <div class="score-card">
                                    <div class="score-title">Plagiarism Level</div>
                                    <div class="score-value" id="plagiarism-score">
                                    </div>
                                    <div class="score-metric">
                                        <span class="metric-badge">
                                        </span>
                                    </div>
                                </div>
                                <div class="score-card">
                                    <div class="score-title">Highest AI Similarity</div>
                                    <div class="score-value" id="ai-similarity-score">
                                    </div>
                                    <div class="score-metric">
                                        <span class="metric-badge">
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <ul class="nav nav-tabs card-header-tabs" id="answer-tabs">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-bs-toggle="tab" href="#your-answer">Your
                                                Answer</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#reference-answer">Reference
                                                Answer</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#keywords-analysis">Keywords
                                                Analysis</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="your-answer">
                                            <div class="content-split">
                                                <div class="text-container">
                                                    <h6 class="mb-3">Your Submitted Answer:</h6>
                                                    <div id="userAnswer">

                                                    </div>
                                                </div>
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0">Evaluation Details</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <ul class="list-group list-group-flush">
                                                            <li
                                                                class="list-group-item d-flex justify-content-between align-items-center">
                                                                Similarity Score
                                                                <span></span>
                                                            </li>
                                                            <li
                                                                class="list-group-item d-flex justify-content-between align-items-center">
                                                                Required Threshold
                                                                <span></span>
                                                            </li>
                                                            <li
                                                                class="list-group-item d-flex justify-content-between align-items-center">
                                                                Submission Date
                                                                <span></span>
                                                            </li>
                                                            <li
                                                                class="list-group-item d-flex justify-content-between align-items-center">
                                                                Status
                                                                <span class="badge"></span>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="reference-answer">
                                            <div class="content-split">
                                                <div class="text-container">
                                                    <h6 class="mb-3">Reference Answer:</h6>
                                                    <p></p>
                                                </div>
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0">Key Concepts</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        {{-- <div class="keywords-container">
                                                            @foreach ($keywords as $keyword)
                                                                <span class="keywords-tag {{ $keyword['found'] ? 'found' : 'missing' }}">{{ $keyword['keyword'] }}</span>
                                                            @endforeach
                                                        </div> --}}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="keywords-analysis">
                                            <div class="text-container">
                                                <h6 class="mb-3">Keywords Analysis:</h6>
                                                <table class="table table-bordered table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Keyword</th>
                                                            <th>Found in Answer</th>
                                                            <th>Importance</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Plagiarism Tab -->
                        <div id="plagiarism-tab" class="tab-container">
                            <div class="plagiarism-overview">
                                <div class="plagiarism-card">
                                    <div class="chart-circle"></div>
                                    <div class="chart-value"></div>
                                    <div class="plagiarism-source">AI Similarity Score</div>
                                    <div class="plagiarism-metrics">
                                        <span class="metric-badge" id="plagiarism-level-badge">
                                            Low Match
                                        </span>
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Detection Methods</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row" id="detection-methods">
                                            <!-- Detection methods will be inserted here by JavaScript -->
                                        </div>
                                    </div>
                                </div>

                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Detected Obfuscation Strategies</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="strategy-badges">
                                            <!-- Strategy badges will be inserted here by JavaScript -->
                                        </div>
                                    </div>
                                </div>



                            </div>
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-4">LLM Comparison</h5>
                                    <ul class="nav nav-tabs card-header-tabs" id="llm-tabs">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-bs-toggle="tab" href="#openai-llm">Openai</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#gemini-llm">Gemini</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#llama-llm">Llama</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="openai-llm">
                                            <!-- Openai content -->
                                        </div>
                                        <div class="tab-pane fade" id="gemini-llm">
                                            <!-- Gemini content -->
                                        </div>
                                        <div class="tab-pane fade" id="llama-llm">
                                            <!-- Llama content -->
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Similarity Level Legend</h6>
                                </div>
                                <div class="card-body">
                                    <div class="legend-container d-flex flex-wrap gap-3">
                                        <div class="legend-item d-flex align-items-center">
                                            <div class="legend-color bg-danger me-2" style="width: 20px; height: 20px;"></div>
                                            <span>Very High Match (>90%)</span>
                                        </div>
                                        <div class="legend-item d-flex align-items-center">
                                            <div class="legend-color bg-warning me-2" style="width: 20px; height: 20px;"></div>
                                            <span>High Match (80-90%)</span>
                                        </div>
                                        <div class="legend-item d-flex align-items-center">
                                            <div class="legend-color me-2" style="width: 20px; height: 20px; background-color: #ffc;"></div>
                                            <span>Medium Match (70-80%)</span>
                                        </div>
                                        <div class="legend-item d-flex align-items-center">
                                            <div class="legend-color bg-success me-2" style="width: 20px; height: 20px; opacity: 0.5;"></div>
                                            <span>Low Match (50-70%)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>


                    <!-- Action buttons -->
                    <div class="action-buttons">

                        <button id="download-report" class="btn btn-outline-primary me-2">
                            <i class="fas fa-download me-2"></i>Download Report
                        </button>
                        <button id="next-question" class="btn btn-primary me-4">
                            Next Question<i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection
@section('vendor-javascript')
    <script src="https://cdn.tiny.cloud/1/lvz6goxyxn405p74zr5vcn0xmwy7mmff6jf5wjqki5abvi3g/tinymce/7/tinymce.min.js"
        referrerpolicy="origin"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
@endsection

@section('custom-javascript')
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <script>
        $(document).ready(function() {

            // Get URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            let questionGuid = urlParams.get('questionGuid');
            let answerGuid = urlParams.get('answerGuid');
            const token = "{{ $token }}";
            const userId = "{{ $id }}";
            let evaluationData = null;
            let plagiarismData = [];


            // If not in URL parameters, try to get from URL path
            if (!questionGuid || !answerGuid) {
                const pathParts = window.location.pathname.split('/');
                if (pathParts.length >= 4) {
                    questionGuid = pathParts[2];
                    answerGuid = pathParts[3];
                }
            }

            // Initialize the page
            if (questionGuid && answerGuid) {
                loadEvaluationData(questionGuid, answerGuid);
            } else {
                toastr.error("Missing question or answer identifier");
            }
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: "toast-top-right",
                timeOut: 5000
            };



            function showLoading() {
                $("#loading-overlay").css({
                    position: "fixed",
                    top: 0,
                    left: 0,
                    width: "100%",
                    height: "100%",
                    backgroundColor: "rgba(255, 255, 255, 0.8)",
                    display: "flex",
                    flexDirection: "column",
                    justifyContent: "center",
                    alignItems: "center",
                    zIndex: 9999
                }).fadeIn(300);
            }

            function hideLoading() {
                $("#loading-overlay").fadeOut(300);
            }

            function capitalizeFirstLetter(string) {
                if (!string) return "";
                return string.charAt(0).toUpperCase() + string.slice(1);
            }





            $("#next-question").on("click", function() {
                window.location.href = `/user/answer/${evaluationData.question.topic_guid}`;
            });

            // Main function to load evaluation data
            function loadEvaluationData(questionGuid, answerGuid) {
                showLoading();
                $.ajax({
                    type: "GET",
                    url: "{{ env('URL_API') }}/api/v1/evaluation/" + questionGuid + "/" + answerGuid,
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(response) {
                        console.log(response);
                        if (response.success) {
                            evaluationData = response.data;
                            updateUI(response.data);
                        } else {
                            toastr.error("Failed to load evaluation data: " + response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error("Gagal mendapatkan data evaluasi. Silakan coba lagi.");
                        console.error("Error fetch:", xhr);
                    },
                    complete: function() {
                        hideLoading();
                    }
                });
            }

            

            


            // Update UI with evaluation data
            function updateUI(data) {
                // Question info
                $("#question-text").html(`${data.question.question_fix}`);
                $("#question-category").html(
                    `Category: ${data.question.category || 'Programming'}`
                );
                $("#question-threshold").html(
                    `Required Threshold: ${data.question.threshold}%`
                );

                // Score overview
                $("#overall-score").text(`${parseFloat(data.userAnswer.evaluation_scores).toFixed(2)}%`);
                updateScoreBadge(data.userAnswer.is_correct);

                // Plagiarism score
                $("#plagiarism-score").text(`${parseFloat(data.plagiarismLevel).toFixed(1)}%`);
                updatePlagiarismBadge(data.plagiarismLevel);

                // AI similarity
                $("#ai-similarity-score").text(
                    `${parseFloat(data.highestAISimilarity || data.plagiarismLevel).toFixed(1)}%`);
                $(".score-card:nth-child(3) .score-metric .metric-badge").text(data.plagiarismSource);

                // User answer
                $("#your-answer .text-container #userAnswer").html(data.userAnswer.answer);

                // Evaluation details
                $(".list-group-item:contains('Similarity Score') span").text(
                    `${parseFloat(data.userAnswer.evaluation_scores*100).toFixed(2)}%`);
                $(".list-group-item:contains('Required Threshold') span").text(`${data.question.threshold}%`);
                $(".list-group-item:contains('Submission Date') span").text(formatDate(data.userAnswer.created_at));
                $(".list-group-item:contains('Status') .badge").text(data.userAnswer.is_correct ? 'Passed' :
                        'Failed')
                    .removeClass('bg-success bg-danger')
                    .addClass(data.userAnswer.is_correct ? 'bg-success' : 'bg-danger');

                // Reference answer
                if (data.referenceAnswer && data.referenceAnswer.answer) {
                    $("#reference-answer .text-container p").text(data.referenceAnswer.answer);
                } else {
                    $("#reference-answer .text-container p").text('No reference answer available.');
                }

                // Keywords
                updateKeywords(data.keywords);
                updateKeywordsTable(data.keywords);
            }



            // Helper functions
            function updateScoreBadge(passed) {
                const badge = $(".score-card:first-child .score-metric .metric-badge");
                badge.removeClass("success warning")
                    .addClass(passed ? "success" : "warning")
                    .html(
                        `<i class="fas ${passed ? 'fa-check-circle' : 'fa-times-circle'} me-1"></i>${passed ? 'Passed' : 'Failed'}`
                    );
            }


            $("#llm-tabs .nav-link").on("click", function(e) {
                const targetId = $(this).attr("href"); // e.g., "#openai-llm"
                const llmSource = targetId.replace("-llm", "").replace("#", ""); // e.g., "openai"

                // Find the data for this LLM source
                const selectedData = plagiarismData.find(item => item.source === llmSource);

                if (selectedData) {
                    // Update detection methods section with this LLM's data
                    updateDetectionMethods(selectedData);
                }
            });

            
            function updatePlagiarismBadge(level) {
                const badge = $(".score-card:nth-child(2) .score-metric .metric-badge");
                badge.removeClass("success warning");

                if (level < 30) {
                    badge.addClass("success").text("Low Risk");
                } else if (level < 70) {
                    badge.text("Medium Risk");
                } else {
                    badge.addClass("warning").text("High Risk");
                }
            }

            
            // Function to load plagiarism data
            function loadPlagiarismData(questionGuid, answerGuid) {
                $.ajax({
                    type: "GET",
                    url: "{{ env('URL_API') }}/api/v1/evaluation/plagiarism/" + questionGuid + "/" +
                        answerGuid,

                    beforeSend: function(request) {
                        showLoading();
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(response) {
                        console.log(response);
                        if (response.success) {
                            plagiarismData = response.data;
                            if (plagiarismData.length > 0) {
                                // Find highest similarity for display
                                let highestItem = plagiarismData.reduce((max, item) =>
                                    item.average > max.average ? item : max, plagiarismData[0]);

                                // Update UI with the highest similarity item's data
                                updatePlagiarismUI(plagiarismData);

                                // Update tabs with all LLM data
                                updateComparisonTabs(plagiarismData);

                                // Display detailed results for the highest similarity item
                                displayPlagiarismResults(highestItem);
                            } else {
                                toastr.info("No plagiarism data found for this answer.");
                            }

                        } else {
                            toastr.error("Failed to load plagiarism data: " + response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error("Gagal mendapatkan data evaluasi. Silakan coba lagi.");
                        console.error("Error fetch:", xhr);
                    },
                    complete: function() {
                        hideLoading();
                    }
                });
            }


            function updatePlagiarismUI(data) {
                // Temukan skor tertinggi untuk ditampilkan di overview
                let highestScore = 0;
                let highestSource = '';

                data.forEach(item => {
                    if (item.average > highestScore) {
                        highestScore = item.average;
                        highestSource = item.source;
                    }
                });

                // Update elemen UI
                $(".chart-value").text(`${(highestScore * 100).toFixed(2)}%`);
                $(".plagiarism-source").text(`AI Similarity Score (${capitalizeFirstLetter(highestSource)})`);
                updatePlagiarismMetricBadge(highestScore);

                // Update chart
                updateChart(highestScore * 100);

                if (data.length > 0) {
                    const highestItem = data.reduce((max, item) =>
                        item.average > max.average ? item : max, data[0]);

                    // Update detection methods di bagian overview
                    updateDetectionMethods(highestItem);

                    // Tampilkan strategi pengaburan yang terdeteksi
                    displayStrategies(highestItem.detected_strategies);

                    // Highlight teks yang mirip
                    if (highestItem.sentence_results && highestItem.sentence_results.length > 0) {
                        highlightPlagiarizedText(
                            $("#your-answer .text-container #userAnswer"),
                            highestItem.sentence_results
                        );
                    }
                }
            }

            


            function updateChart(percentage) {
                const chartCircle = $(".chart-circle");
                chartCircle.css('--percentage', `${percentage}%`);
                
                // Add color based on percentage
                chartCircle.removeClass("low medium high very-high");
                if (percentage < 50) {
                    chartCircle.addClass("low");
                } else if (percentage < 70) {
                    chartCircle.addClass("medium");
                } else if (percentage < 90) {
                    chartCircle.addClass("high");
                } else {
                    chartCircle.addClass("very-high");
                }
            }


            function updatePlagiarismMetricBadge(score) {
                const badge = $(".plagiarism-metrics .metric-badge");
                badge.removeClass("success warning danger");

                if (score < 0.3) {
                    badge.addClass("success").text("Low Match");
                } else if (score < 0.7) {
                    badge.addClass("warning").text("Medium Match");
                } else {
                    badge.addClass("danger").text("High Match");
                }
            }


            function updateDetectionMethods(llmData) {
                const methodsHtml = `
                    <div class="col-md-3 detection-method ${llmData.cosine_similarity >= llmData.thresholds.cosine ? 'threshold-exceeded' : ''}">
                        <div class="detection-method-value">${(llmData.cosine_similarity * 100).toFixed(1)}%</div>
                        <div class="detection-method-name">Cosine Similarity</div>
                        <div class="threshold-value">Threshold: ${(llmData.thresholds.cosine * 100).toFixed(1)}%</div>
                        <div class="method-weight">Weight: ${(llmData.method_weights.cosine * 100).toFixed(0)}%</div>
                    </div>
                    <div class="col-md-3 detection-method ${llmData.jaccard_similarity >= llmData.thresholds.jaccard ? 'threshold-exceeded' : ''}">
                        <div class="detection-method-value">${(llmData.jaccard_similarity * 100).toFixed(1)}%</div>
                        <div class="detection-method-name">Jaccard Index</div>
                        <div class="threshold-value">Threshold: ${(llmData.thresholds.jaccard * 100).toFixed(1)}%</div>
                        <div class="method-weight">Weight: ${(llmData.method_weights.jaccard * 100).toFixed(0)}%</div>
                    </div>
                    <div class="col-md-3 detection-method ${llmData.bert_score >= llmData.thresholds.bert ? 'threshold-exceeded' : ''}">
                        <div class="detection-method-value">${(llmData.bert_score * 100).toFixed(1)}%</div>
                        <div class="detection-method-name">BERT Embedding</div>
                        <div class="threshold-value">Threshold: ${(llmData.thresholds.bert * 100).toFixed(1)}%</div>
                        <div class="method-weight">Weight: ${(llmData.method_weights.bert * 100).toFixed(0)}%</div>
                    </div>
                    <div class="col-md-3 detection-method ${llmData.levenshtein_similarity >= llmData.thresholds.levenshtein ? 'threshold-exceeded' : ''}">
                        <div class="detection-method-value">${(llmData.levenshtein_similarity * 100).toFixed(1)}%</div>
                        <div class="detection-method-name">Levenshtein</div>
                        <div class="threshold-value">Threshold: ${(llmData.thresholds.levenshtein * 100).toFixed(1)}%</div>
                        <div class="method-weight">Weight: ${(llmData.method_weights.levenshtein * 100).toFixed(0)}%</div>
                    </div>
                    <div class="col-md-3 detection-method ${llmData.ngram_similarity >= llmData.thresholds.ngram ? 'threshold-exceeded' : ''}">
                        <div class="detection-method-value">${(llmData.ngram_similarity * 100).toFixed(1)}%</div>
                        <div class="detection-method-name">N-gram</div>
                        <div class="threshold-value">Threshold: ${(llmData.thresholds.ngram * 100).toFixed(1)}%</div>
                        <div class="method-weight">Weight: ${(llmData.method_weights.ngram * 100).toFixed(0)}%</div>
                    </div>
                `;
                $("#detection-methods").html(methodsHtml);
                highlightTopMethod(llmData);
            }
            
            function highlightTopMethod(llmData) {
                const methods = {
                    'cosine': llmData.cosine_similarity * llmData.method_weights.cosine,
                    'jaccard': llmData.jaccard_similarity * llmData.method_weights.jaccard,
                    'bert': llmData.bert_score * llmData.method_weights.bert,
                    'levenshtein': llmData.levenshtein_similarity * llmData.method_weights.levenshtein,
                    'ngram': llmData.ngram_similarity * llmData.method_weights.ngram
                };

                const topMethod = Object.keys(methods).reduce((a, b) => methods[a] > methods[b] ? a : b);
                const methodIndex = {
                    'cosine': 0,
                    'jaccard': 1,
                    'bert': 2,
                    'levenshtein': 3,
                    'ngram': 4
                };

                // Remove highlight from all methods
                $(".detection-method").removeClass("highlight-method");

                // Add highlight to top method
                $(".detection-method").eq(methodIndex[topMethod]).addClass("highlight-method");
            }

            function displayStrategies(strategies) {
                const strategyContainer = $(".strategy-badges");
                strategyContainer.empty();

                if (!strategies || strategies.length === 0) {
                    strategyContainer.append(`<div class="strategy-badge">No Strategies Detected</div>`);
                    return;
                }

                strategies.forEach(strategy => {
                    // Convert strategy name to readable format
                    const readableStrategy = strategy.replace(/_/g, ' ')
                        .replace(/\b\w/g, l => l.toUpperCase());
                    strategyContainer.append(`<div class="strategy-badge">${readableStrategy}</div>`);
                });
            }

            function updateComparisonTabs(data) {
    // First, load the user answer once instead of multiple times
    $.ajax({
        type: "GET",
        url: "{{ env('URL_API') }}/api/v1/evaluation/plagiarism/" + questionGuid + "/" + answerGuid,
        async: true,
        beforeSend: function(request) {
            request.setRequestHeader("Authorization", `Bearer ${token}`);
        },
        success: function(response) {
            if (response.success) {
                const userAnswer = response.data.answer;
                
                // After getting user answer, update all tabs
                data.forEach((item) => {
                    const tabId = `#${item.source}-llm`;
                    const tabContent = $(tabId);

                    if (tabContent.length) {
                        // Create comparison panel with the user answer we already loaded
                        const comparisonHTML = `
                            <div class="comparison-panel">
                                <div class="comparison-side">
                                    <div class="comparison-header">
                                        <span><i class="fas fa-user me-2"></i>Your Answer</span>
                                    </div>
                                    <div class="comparison-content user-answer-content">
                                        ${userAnswer}
                                    </div>
                                </div>
                                <div class="comparison-side">
                                    <div class="comparison-header">
                                        <span><i class="fas fa-robot me-2"></i>${capitalizeFirstLetter(item.source)}</span>
                                        <span>${(item.average * 100).toFixed(1)}% Match</span>
                                    </div>
                                    <div class="comparison-content">
                                        <div class="markdown-content">${marked.parse(item.answer || '')}</div>
                                    </div>
                                </div>
                            </div>
                        `;

                        tabContent.html(comparisonHTML);

                        // Apply highlights if detailed data is available
                        if (item.sentence_results && item.sentence_results.length > 0) {
                            highlightPlagiarizedText(
                                tabContent.find(".comparison-side:first-child .comparison-content"),
                                item.sentence_results
                            );
                        }
                    }
                });
            } else {
                toastr.error("Failed to load user answer for comparison");
            }
        },
        error: function(xhr) {
            toastr.error("Failed to load user answer for comparison");
            console.error("Error fetching user answer:", xhr);
        }
    });
}

            function displayPlagiarismResults(data) {
                // Display detected strategies
                displayStrategies(data.detected_strategies);

                // Update text with highlights if available
                if (data.sentence_results && data.sentence_results.length > 0) {
                    $.ajax({
                        type: "GET",
                        url: "{{ env('URL_API') }}/api/v1/evaluation/plagiarism/" + questionGuid + "/" + answerGuid,
                        beforeSend: function(request) {
                            request.setRequestHeader("Authorization", `Bearer ${token}`);
                        },
                        success: function(response) {
                            if (response.success) {
                                // Find container or create temporary one
                                const container = $("<div></div>").html(response.data.answer);
                                highlightPlagiarizedText(container, data.sentence_results);
                            }
                        }
                    });
                }
            }

            function highlightPlagiarizedText(container, sections) {
    if (!sections || sections.length === 0) return;

    // Get the HTML content to work with
    let content = container.html();
    if (!content) return;

    // Sort sections from longest to shortest to avoid nested replacements
    sections.sort((a, b) => {
        const textA = a.student_text || a.text;
        const textB = b.student_text || b.text;
        return textB.length - textA.length;
    });

    // Process each section
    sections.forEach(section => {
        // Get text from section with appropriate property
        const sectionText = section.student_text || section.text;
        if (!sectionText || sectionText.trim() === '') return;

        // Check if this section is plagiarized - default to considering it plagiarized
        // if there's no explicit flag but it has a weighted score
        const isPlagiarized = section.is_plagiarized !== undefined ? 
            section.is_plagiarized : (section.weighted_score > 0.5);

        if (isPlagiarized) {
            // Determine highlight class based on score
            let highlightClass;
            const score = section.weighted_score || 0.5; // Default if no score

            if (score > 0.9) {
                highlightClass = "plagiarism-very-high";
            } else if (score > 0.8) {
                highlightClass = "plagiarism-high";
            } else if (score > 0.7) {
                highlightClass = "plagiarism-medium";
            } else {
                highlightClass = "plagiarism-low";
            }

            // Create tooltip content
            let tooltipContent = `<strong>Similarity Score: ${(score * 100).toFixed(1)}%</strong><br>`;

            // If individual_scores exist, add to tooltip
            if (section.individual_scores) {
                const scores = typeof section.individual_scores === 'string' ?
                    JSON.parse(section.individual_scores) : section.individual_scores;

                Object.entries(scores).forEach(([method, score]) => {
                    tooltipContent += `${capitalizeFirstLetter(method)}: ${(score * 100).toFixed(1)}%<br>`;
                });
            }

            // Add matched text if available
            if (section.best_match) {
                tooltipContent += `<br><strong>Matched with:</strong><br>"${section.best_match.substring(0, 100)}${section.best_match.length > 100 ? '...' : ''}"`;
            }

            try {
                // Safely escape for regex
                const safeText = escapeRegExp(sectionText);
                
                // Create replacement HTML with tooltip
                const replacement = `<span class="similarity-tooltip ${highlightClass}" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" title="${encodeURIComponent(tooltipContent)}">${sectionText}</span>`;
                
                // Replace text with highlighted version
                content = content.replace(new RegExp(safeText, 'g'), replacement);
            } catch (e) {
                console.error("Error highlighting text:", e);
                console.error("Text that caused the error:", sectionText);
            }
        }
    });

    // Update content
    container.html(content);
    
    // Initialize tooltips
    setTimeout(() => {
        try {
            container.find('[data-bs-toggle="tooltip"]').each(function() {
                const tooltip = $(this);
                tooltip.attr('title', decodeURIComponent(tooltip.attr('title')));
                new bootstrap.Tooltip(tooltip, {
                    html: true,
                    container: 'body',
                    trigger: 'hover'
                });
            });
        } catch (e) {
            console.error("Error initializing tooltips:", e);
        }
    }, 100);
}


             // Helper function untuk escape karakter khusus dalam regex
             function escapeRegExp(string) {
                return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }
            
            



            function updateKeywords(keywords) {
                const container = $(".keywords-container");
                container.empty();

                keywords.forEach(keyword => {
                    container.append(`
            <span class="keywords-tag ${keyword.found ? 'found' : 'missing'}">${keyword.keyword}</span>
        `);
                });
            }

            function updateKeywordsTable(keywords) {
                const tableBody = $("#keywords-analysis table tbody");
                tableBody.empty();

                keywords.forEach(keyword => {
                    tableBody.append(`
            <tr>
                <td>${keyword.keyword}</td>
                <td>
                    <i class="fas fa-${keyword.found ? 'check text-success' : 'times text-danger'}"></i>
                </td>
                <td>${keyword.importance}</td>
            </tr>
        `);
                });
            }


            
            function formatDate(dateString) {
                const date = new Date(dateString);
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                return `${months[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
            }

            // Tab switching
            $(".custom-tab-link").on("click", function(e) {
                e.preventDefault();
                const targetId = $(this).data("target");

                // Hide all tabs and remove active class
                $(".tab-container").hide();
                $(".custom-tab-link").removeClass("active");

                // Show selected tab and add active class
                $("#" + targetId).show();
                $(this).addClass("active");

                // Load data for specific tabs if needed
                if (targetId === "plagiarism-tab") {
                    loadPlagiarismData(questionGuid, answerGuid);
                }
            });



        
            
           

            function updateDetectionMethodsFromWeights(weights, thresholds) {
                const methodsHtml = `
                <div class="col-md-3 detection-method">
                    <div class="detection-method-value">${(weights.cosine * 100 || 0).toFixed(1)}%</div>
                    <div class="detection-method-name">Cosine Similarity</div>
                    <div class="threshold-value">Threshold: ${(thresholds.cosine * 100).toFixed(1)}%</div>
                </div>
                <div class="col-md-3 detection-method">
                    <div class="detection-method-value">${(weights.jaccard * 100 || 0).toFixed(1)}%</div>
                    <div class="detection-method-name">Jaccard Index</div>
                    <div class="threshold-value">Threshold: ${(thresholds.jaccard * 100).toFixed(1)}%</div>
                </div>
                
                <div class="col-md-3 detection-method">
                    <div class="detection-method-value">${(weights.bert * 100 || 0).toFixed(1)}%</div>
                    <div class="detection-method-name">BERT Embedding</div>
                    <div class="threshold-value">Threshold: ${(thresholds.bert * 100).toFixed(1)}%</div>
                </div>
                <div class="col-md-3 detection-method">
                    <div class="detection-method-value">${(weights.levenshtein * 100 || 0).toFixed(1)}%</div>
                    <div class="detection-method-name">Levenshtein</div>
                    <div class="threshold-value">Threshold: ${(thresholds.levenshtein * 100).toFixed(1)}%</div>
                </div>
                <div class="col-md-3 detection-method">
                    <div class="detection-method-value">${(weights.ngram * 100 || 0).toFixed(1)}%</div>
                    <div class="detection-method-name">N-gram</div>
                    <div class="threshold-value">Threshold: ${(thresholds.ngram * 100).toFixed(1)}%</div>
                </div>
            `;
                $("#detection-methods").html(methodsHtml);
            }


            $(".custom-tab-link[data-target='plagiarism-tab']").on("click", function(e) {
                e.preventDefault();
                const targetId = $(this).data("target");

                // Hide all tabs and remove active class
                $(".tab-container").hide();
                $(".custom-tab-link").removeClass("active");

                // Show selected tab and add active class
                $("#" + targetId).show();
                $(this).addClass("active");

                // Load plagiarism data

                if (questionGuid && answerGuid) {
                    loadPlagiarismData(questionGuid, answerGuid);
                }
            });


        });
    </script>
@endsection
