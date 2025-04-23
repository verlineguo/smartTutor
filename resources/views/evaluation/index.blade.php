@extends('layouts.template')

@section('add-css')
    <style>
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

        .score-overview,
        .plagiarism-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .score-card,
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

        .score-title,
        .plagiarism-source {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }

        .score-value,
        .chart-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #cb0c9f;
        }

        .score-metric,
        .plagiarism-metrics {
            font-size: 0.85rem;
        }

        .metric-badge {
            padding: 4px 12px;
            border-radius: 50px;
            background-color: #e9ecef;
            color: #495057;
        }

        .metric-badge.success {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .metric-badge.warning {
            background-color: #f8d7da;
            color: #842029;
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

        .comparison-panel {
            display: grid;
            grid-template-columns: 1fr 1fr;
            margin-bottom: 20px;
        }

        .comparison-side {
            border: 1px solid #eee;
            border-radius: 10px;
        }

        .comparison-header {
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            font-weight: 500;
            border-radius: 10px 10px 0 0;
        }

        .comparison-content {
            padding: 15px;
            max-height: 300px;
            overflow-y: auto;
            line-height: 1.6;
        }

        .action-buttons {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 20px;
        }

        .highlight {
            background-color: rgba(254, 202, 202, 0.5);
            padding: 2px 0;
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

        .detection-method {
            text-align: center;
            padding: 15px;
        }

        .detection-method-value {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 10px 0;
            color: #0d6efd;
        }

        .detection-method-name {
            font-size: 0.9rem;
            color: #666;
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
                            <li class="custom-tab-item">
                                <a href="#" class="custom-tab-link" data-target="history-tab">
                                    <i class="fas fa-history"></i>
                                    Submission History
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
                                                                <span class="badge"></span>
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
                                        <span class="metric-badge">
                                        </span>
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Detection Methods</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row" id="detection-methods">

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
                                            <a class="nav-link" data-bs-toggle="tab" href="#deepseek-llm">Deepseek</a>
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
                                        <div class="tab-pane fade" id="deepseek-llm">
                                            <!-- Deepseek content -->
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>

                    <!-- History Tab -->
                    <div id="history-tab" class="tab-container">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Submission History</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="historyTable">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Question</th>
                                                <th>Score</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        {{-- <tbody>
                                                @if (isset($submissionHistory) && count($submissionHistory) > 0)
                                                    @foreach ($submissionHistory as $submission)
                                                        <tr>
                                                            <td>{{ $submission->created_at->format('M d, Y') }}</td>
                                                            <td>{{ Str::limit(strip_tags($submission->question->question_fix), 50) }}</td>
                                                            <td>{{ number_format($submission->cosine_similarity, 1) }}%</td>
                                                            <td>
                                                                <span class="badge bg-{{ $submission->cosine_similarity >= $submission->question->threshold ? 'success' : 'danger' }}">
                                                                    {{ $submission->cosine_similarity >= $submission->question->threshold ? 'Passed' : 'Failed' }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <a href="{{ route('evaluation.show', [$submission->question_guid, $submission->answer_guid]) }}" class="btn btn-sm btn-info">
                                                                    <i class="fas fa-eye"></i> View
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @else
                                                    <tr>
                                                        <td colspan="5" class="text-center">Loading submission history...</td>
                                                    </tr>
                                                @endif
                                            </tbody> --}}
                                    </table>
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
@endsection

@section('custom-javascript')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
        $(document).ready(function() {

            // Get URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            let questionGuid = urlParams.get('questionGuid');
            let answerGuid = urlParams.get('answerGuid');
            const token = "{{ $token }}";
            const userId = "{{ $id }}";
            let evaluationData = null;

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
                } else if (targetId === "history-tab") {
                    loadHistoryData();
                }
            });

            // Button handlers
            $("#download-report").on("click", function() {
                downloadReport(questionGuid, answerGuid);
            });


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
                            updatePlagiarismUI(response.data);
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

            // Find next question
            function findNextQuestion(currentQuestionGuid) {
                $.ajax({
                    type: "GET",
                    url: "{{ env('URL_API') }}/api/v1/question/next/" + currentQuestionGuid,
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },

                    success: function(response) {
                        if (response.success && response.data.guid) {
                            window.location.href = `/user/answer/${response.data.guid}`;
                        } else {
                            toastr.info("No more questions available.");
                        }
                    },
                    error: function(xhr) {
                        toastr.error("Gagal mendapatkan data evaluasi. Silakan coba lagi.");
                        console.error("Error fetch:", xhr);
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
                $("#overall-score").text(`${parseFloat(data.userAnswer.cosine_similarity).toFixed(1)}%`);
                updateScoreBadge(data.passed);

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
                $(".list-group-item:contains('Similarity Score') .badge").text(
                    `${parseFloat(data.userAnswer.cosine_similarity).toFixed(1)}%`);
                $(".list-group-item:contains('Required Threshold') span").text(`${data.question.threshold}%`);
                $(".list-group-item:contains('Submission Date') span").text(formatDate(data.userAnswer.created_at));
                $(".list-group-item:contains('Status') .badge").text(data.passed ? 'Passed' : 'Failed')
                    .removeClass('bg-success bg-danger')
                    .addClass(data.passed ? 'bg-success' : 'bg-danger');

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

            $("#llm-tabs .nav-link").on("click", function(e) {
                const targetId = $(this).attr("href"); // e.g., "#openai-llm"
                const llmSource = targetId.replace("-llm", "").replace("#", ""); // e.g., "openai"

                // Find the data for this LLM source
                const plagiarismData = window.plagiarismData || []; // Store data globally
                const selectedData = plagiarismData.find(item => item.source === llmSource);

                if (selectedData) {
                    // Update detection methods section with this LLM's data
                    updateDetectionMethods(selectedData);
                }
            });

            function updateDetectionMethods(data) {
                const methodsHtml = `
        <div class="col-md-3 detection-method">
            <div class="detection-method-value">${(data.cosine_similarity || 0).toFixed(1)}%</div>
            <div class="detection-method-name">Cosine Similarity</div>
        </div>
        <div class="col-md-3 detection-method">
            <div class="detection-method-value">${(data.jaccard_similarity || 0).toFixed(1)}%</div>
            <div class="detection-method-name">Jaccard Index</div>
        </div>
        <div class="col-md-3 detection-method">
            <div class="detection-method-value">${(data.bert_score || 0).toFixed(1)}%</div>
            <div class="detection-method-name">BERT Embedding</div>
        </div>
    `;
                $("#detection-methods").html(methodsHtml);
            }

            // Update plagiarism UI
            function updatePlagiarismUI(data) {
                // Find highest similarity score for display in overview
                let highestScore = 0;
                let highestSource = '';

                data.forEach(item => {
                    if (item.average > highestScore) {
                        highestScore = item.average;
                        highestSource = item.source;
                    }
                });

                // Update UI elements
                $(".chart-value").text(`${highestScore.toFixed(1)}%`);
                $(".plagiarism-source").text(`AI Similarity Score (${capitalizeFirstLetter(highestSource)})`);
                updatePlagiarismMetricBadge(highestScore);

                // Update detection methods
                $("#detection-methods").empty();

                if (data.length > 0) {
                    updateDetectionMethods(data[0]);
                }
                // Update comparison tabs
                updateComparisonTabs(data);
            }

            // Update history UI
            function updateHistoryUI(data) {
                const tableBody = $("#historyTable tbody");
                tableBody.empty();

                if (data.length === 0) {
                    tableBody.append(`
                        <tr>
                            <td colspan="5" class="text-center">No submission history found</td>
                        </tr>
                    `);
                    return;
                }

                data.forEach(item => {
                    const truncatedQuestion = item.question.question_fix.length > 50 ?
                        item.question.question_fix.replace(/<[^>]*>/g, '').substring(0, 50) + "..." :
                        item.question.question_fix.replace(/<[^>]*>/g, '');

                    const row = `
                        <tr>
                            <td>${formatDate(item.created_at)}</td>
                            <td>${truncatedQuestion}</td>
                            <td>${parseFloat(item.cosine_similarity).toFixed(1)}%</td>
                            <td>
                                <span class="badge bg-${item.cosine_similarity >= item.question.threshold ? 'success' : 'danger'}">
                                    ${item.cosine_similarity >= item.question.threshold ? 'Passed' : 'Failed'}
                                </span>
                            </td>
                            <td>
                                <a href="/evaluation/${item.question_guid}/${item.answer_guid}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    `;
                    tableBody.append(row);
                });
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

            function updatePlagiarismMetricBadge(score) {
                const badge = $(".plagiarism-metrics .metric-badge");
                badge.removeClass("success warning");

                if (score < 30) {
                    badge.addClass("success").text("Low Match");
                } else if (score < 70) {
                    badge.text("Medium Match");
                } else {
                    badge.addClass("warning").text("High Match");
                }
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

            function updateComparisonTabs(data) {
                data.forEach((item, index) => {
                    const tabId = `#${item.source}-llm`;
                    const tabContent = $(tabId);

                    if (tabContent.length) {
                        // Create comparison panel for each tab
                        const comparisonHTML = `
                <div class="comparison-panel">
                    <div class="comparison-side">
                        <div class="comparison-header">
                            <span><i class="fas fa-user me-2"></i>Your Answer</span>
                        </div>
                        <div class="comparison-content">
                            <p>${$("#userAnswer").html()}</p>
                        </div>
                    </div>
                    <div class="comparison-side">
                        <div class="comparison-header">
                            <span><i class="fas fa-robot me-2"></i>${capitalizeFirstLetter(item.source)}</span>
                            <span>${(item.average || 0).toFixed(1)}% Match</span>
                        </div>
                        <div class="comparison-content">
                            <p>${item.answer}</p>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">Highlighted Similarities</h6>
                    </div>
                    <div class="card-body">
                        <div class="comparison-content">
                            <p>${highlightSimilarities($("#userAnswer").html(), item.answer)}</p>
                        </div>
                    </div>
                </div>
            `;

                        tabContent.html(comparisonHTML);
                    }
                });
                data.forEach((item, index) => {
                    const tabId = `#${item.source}-llm`;
                    const tabContent = $(tabId);

                    if (tabContent.length) {
                        // Create comparison panel for each tab
                        const comparisonHTML = `
                <div class="comparison-panel">
                    <div class="comparison-side">
                        <div class="comparison-header">
                            <span><i class="fas fa-user me-2"></i>Your Answer</span>
                        </div>
                        <div class="comparison-content">
                            <p>${$("#userAnswer").html()}</p>
                        </div>
                    </div>
                    <div class="comparison-side">
                        <div class="comparison-header">
                            <span><i class="fas fa-robot me-2"></i>${capitalizeFirstLetter(item.source)}</span>
                            <span>${(item.average || 0).toFixed(1)}% Match</span>
                        </div>
                        <div class="comparison-content">
                            <p>${item.answer}</p>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">Highlighted Similarities</h6>
                    </div>
                    <div class="card-body">
                        <div class="comparison-content">
                            <p>${highlightSimilarities($("#userAnswer").html(), item.answer)}</p>
                        </div>
                    </div>
                </div>
            `;

                        tabContent.html(comparisonHTML);
                    }
                });
            }

            function updateComparisonContent(tabElement, userAnswer, data) {
                // Update source icon
                tabElement.find(".comparison-side:nth-child(2) .comparison-header span:first-child i")
                    .removeClass("fa-book fa-robot")
                    .addClass(data.is_ai ? "fa-robot" : "fa-book");

                // Update source name
                tabElement.find(".comparison-side:nth-child(2) .comparison-header span:first-child")
                    .html(
                        `<i class="fas ${data.is_ai ? 'fa-robot' : 'fa-book'} me-2"></i>${capitalizeFirstLetter(data.source)}`
                    );

                // Update match percentage
                tabElement.find(".comparison-side:nth-child(2) .comparison-header span:last-child")
                    .text(`${data.average.toFixed(1)}% Match`);

                // Update content
                tabElement.find(".comparison-side:nth-child(2) .comparison-content p")
                    .text(data.answer);

                // Update highlighted similarities
                tabElement.find(".card .comparison-content p")
                    .html(highlightSimilarities(userAnswer, data.answer));
            }

            function highlightSimilarities(text1, text2) {
                // Simple implementation - for production you'd want a more sophisticated algorithm
                const words1 = text1.toLowerCase().split(/\s+/);
                const words2 = text2.toLowerCase().split(/\s+/);

                let highlightedText = text1;

                // Look for phrases of 3+ words that match
                for (let i = 0; i < words1.length - 2; i++) {
                    for (let j = 0; j < words2.length - 2; j++) {
                        if (words1[i] === words2[j] &&
                            words1[i + 1] === words2[j + 1] &&
                            words1[i + 2] === words2[j + 2]) {

                            // Find the actual phrase in the original text
                            const phraseRegex = new RegExp(`\\b${words1[i]}\\s+${words1[i+1]}\\s+${words1[i+2]}\\b`,
                                "i");
                            const matches = text1.match(phraseRegex);

                            if (matches) {
                                // Highlight it
                                highlightedText = highlightedText.replace(
                                    matches[0],
                                    `<span class="highlight">${matches[0]}</span>`
                                );
                            }
                        }
                    }
                }

                return highlightedText;
            }

            // Utility functions
            function capitalizeFirstLetter(string) {
                if (!string) return "";
                return string.charAt(0).toUpperCase() + string.slice(1);
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
                } else if (targetId === "history-tab") {
                    loadHistoryData();
                }
            });
        });
    </script>
@endsection
