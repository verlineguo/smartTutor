@extends('layouts.template')
@section('vendor-css')
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-checkboxes-jquery/datatables.checkboxes.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
@endsection

@section('add-css')
    <style>
        .language-selection {
            margin-bottom: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .question-box {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            display: none;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }

        .question-meta {
            margin-top: 10px;
            font-size: 0.9rem;
        }

        .assignment-container {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            display: none;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }

        /* Editor styling */
        .tox-tinymce {
            border-radius: 8px !important;
            border: 1px solid #ced4da !important;
        }

        /* Send button styling */
        #send-button {
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }



        #send-button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        /* History section */
        .view-evaluation {
            transition: all 0.2s ease;
        }

        .view-evaluation:hover {
            transform: translateY(-2px);
        }

        /* Animation for new history items */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .history-section {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            display: none;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .history-card {
            animation: fadeIn 0.5s ease forwards;
        }

        /* Score badge styles */
        .badge {
            font-size: 0.85rem;
            padding: 0.4em 0.6em;
        }

        .history-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: transform 0.2s;
        }

        .history-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .history-header {
            background-color: #f8f9fa;
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .history-content {
            padding: 15px;
        }

        .history-question {
            background-color: #e8f4f8;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .history-answer {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 6px;
        }

        .history-footer {
            padding: 12px 15px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .history-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .history-date {
                margin-top: 5px;
            }

            .history-footer {
                flex-direction: column;
                gap: 10px;
            }
        }

        .bloom-level-indicator {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: bold;
            margin-bottom: 15px;
            display: inline-block;
        }

        .remembering {
            background-color: #e3f2fd;
            color: #0d47a1;
        }

        .understanding {
            background-color: #e8f5e9;
            color: #1b5e20;
        }

        .applying {
            background-color: #fff3e0;
            color: #e65100;
        }

        .analyzing {
            background-color: #fce4ec;
            color: #880e4f;
        }

        .progress-container {
            margin: 15px 0;
        }

        .streak-counter {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
    </style>
@endsection

@section('add-css')
    <style>
        /* Keep existing styles and add new ones for Bloom's taxonomy */
        .bloom-level-indicator {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: bold;
            margin-bottom: 15px;
            display: inline-block;
        }

        .remembering {
            background-color: #e3f2fd;
            color: #0d47a1;
        }

        .understanding {
            background-color: #e8f5e9;
            color: #1b5e20;
        }

        .applying {
            background-color: #fff3e0;
            color: #e65100;
        }

        .analyzing {
            background-color: #fce4ec;
            color: #880e4f;
        }

        .progress-container {
            margin: 15px 0;
        }

        .streak-counter {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }

        /* Rest of your existing styles... */
    </style>
@endsection

@section('info-page')
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
        <li class="breadcrumb-item text-sm text-dark active text-capitalize" aria-current="page">Assignment</li>
    </ol>
    <h5 class="font-weight-bolder mb-0 text-capitalize">Assignment: {{ $name }}</h5>
@endsection

@section('content')
    <div class="container mt-4">
        <!-- Bloom's Taxonomy Level Indicator -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div id="bloom-level-display" class="bloom-level-indicator remembering">
                    Level: Remembering
                </div>
                <div class="progress-container">
                    <div class="progress">
                        <div id="bloom-progress" class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                    <div id="streak-counter" class="streak-counter">Correct streak: 0/4</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 d-flex flex-column">
                <!-- Question Box -->
                <div class="question-box" id="question-box">
                    <h6 class="mb-3">Question:</h6>
                    <div id="question-text"></div>
                    <div class="question-meta mt-2">
                        <span id="question-category" class="badge bg-secondary"></span>
                        <span id="question-page" class="badge bg-info ms-2"></span>
                    </div>
                </div>

                <!-- Answer Container -->
                <div class="assignment-container" id="assignment-container">
                    <h6 class="mb-3">Your Answer:</h6>
                    <textarea id="user-input" class="form-control" rows="6" placeholder="Type your answer here..."></textarea>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <button id="submit-answer" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Submit Answer
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Evaluation Results -->
                <div class="card mb-4" id="evaluation-results" style="display:none;">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Evaluation Results</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Similarity Score:</strong>
                            <div class="progress mt-2">
                                <div id="similarity-score-bar" class="progress-bar" role="progressbar" style="width: 0%">
                                </div>
                            </div>
                            <small id="similarity-score-text" class="text-muted">0%</small>
                        </div>
                        <div id="evaluation-details"></div>
                        <button id="next-question" class="btn btn-success w-100 mt-2" style="display:none;">
                            Next Question
                        </button>
                    </div>
                </div>

                <!-- Answer References -->
                <div class="card" id="answer-references">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Answer References</h6>
                    </div>
                    <div class="card-body">
                        <div id="reference-answer">
                            <h6>Reference Answer:</h6>
                            <div id="reference-text" class="mb-3 p-2 bg-light rounded"></div>
                        </div>
                        <div id="llm-answers">
                            <h6>LLM Answers:</h6>
                            <div id="llm-answers-list"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay" style="display:none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Processing your answer...</p>
    </div>
@endsection

@section('vendor-javascript')
    <!-- Keep existing JS imports -->
    <script src="https://cdn.tiny.cloud/1/lvz6goxyxn405p74zr5vcn0xmwy7mmff6jf5wjqki5abvi3g/tinymce/7/tinymce.min.js"
        referrerpolicy="origin"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
@endsection

@section('custom-javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            // Configuration variables
            const userId = "{{ $id }}";
            const topicGuid = "{{ $guid }}";
            const token = "{{ $token }}";
            const apiUrl = "{{ env('URL_API') }}";

            // Bloom's Taxonomy state
            let currentLevel = "remembering";
            let correctStreak = 0;
            let currentQuestion = null;
            let evaluationResult = null;

            // Initialize TinyMCE
            tinymce.init({
                selector: '#user-input',
                height: 300,
                menubar: false,
                plugins: 'lists link image table code help wordcount',
                toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | table | code',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 16px; }',
                setup: function(editor) {
                    editor.on('change', function() {
                        const content = editor.getContent().trim();
                        $("#submit-answer").prop("disabled", content === '');
                    });
                }
            });

            // Initialize the assignment
            startAssignment();

            function startAssignment() {
                showLoading();
                generateQuestion();
            }

            function sendToTfidfDocument(language, topic) {
                showLoading("Calculating TF-IDF for " + language + "...");
                $.ajax({
                    type: "POST",
                    url: "{{ env('URL_API') }}/api/v1/question/tfidf",
                    data: {
                        'language': language,
                        'topic_guid': topic
                    },
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", "Bearer {{ $token }}");
                    },
                    success: function() {
                        hideLoading();
                        generateQuestion();
                    },
                    error: function() {
                        hideLoading();
                        alert(`Failed to calculate TF-IDF for ${language}.`);
                    }
                });
            }



            function generateQuestion() {
                // Call your Flask API to generate a question
                $.ajax({
                    type: "GET",
                    url: "{{ env('URL_API') }}/api/v1/question/get-tfidf-data",
                    data: {
                        topic_guid: topicGuid,
                        language: "Indonesian"
                    },
                    contentType: "application/json",
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(tfidfData) {
                        $.ajax({
                            type: "POST",
                            url: "{{ env('URL_API') }}/api/v1/question/generate",
                            data: JSON.stringify({
                                topic_guid: topicGuid,
                                current_level: currentLevel,
                                correct_streak: correctStreak,
                                language: "indonesia" // Or get from user selection
                            }),

                            contentType: "application/json",
                            beforeSend: function(request) {
                                request.setRequestHeader("Authorization",
                                    `Bearer ${token}`);
                            },
                            success: function(response) {
                                console.log(response);
                                if (response.data && response.data.length > 0) {
                                    currentQuestion = response.data[0];
                                    displayQuestion(currentQuestion);
                                    updateBloomLevelUI();
                                } else {
                                    toastr.error(
                                        "Failed to generate question. Please try again."
                                    );
                                }
                                hideLoading();
                            },
                            error: function(error) {
                                console.error("Error generating question:", error);
                                toastr.error("Failed to generate question.");
                                hideLoading();
                            }
                        });
                    }
                });
            }

            function displayQuestion(question) {
                $("#question-text").html(question.question);
                $("#reference-text").html(question.reference_answer);
                $("#question-category").text(question.category || currentLevel);
                $("#question-page").text(`Page ${question.page || 1}`);

                // Clear previous answer and evaluation
                tinymce.get("user-input").setContent('');
                $("#evaluation-results").hide();
                $("#next-question").hide();

                // Show question and answer boxes
                $("#question-box").show();
                $("#assignment-container").show();
                $("#answer-references").show();
            }

            function updateBloomLevelUI() {
                // Update level display
                const levelNames = {
                    'remembering': 'Remembering',
                    'understanding': 'Understanding',
                    'applying': 'Applying',
                    'analyzing': 'Analyzing'
                };

                $("#bloom-level-display")
                    .removeClass('remembering understanding applying analyzing')
                    .addClass(currentLevel)
                    .text(`Level: ${levelNames[currentLevel]}`);

                // Update progress
                const progressPercent = (correctStreak / 4) * 100;
                $("#bloom-progress").css('width', `${progressPercent}%`);
                $("#streak-counter").text(`Correct streak: ${correctStreak}/4`);
            }

            // Replace your submit-answer click handler with this updated function
            $("#submit-answer").on("click", function() {
                const userAnswer = tinymce.get("user-input").getContent();
                if (!userAnswer.trim()) {
                    toastr.error("Please enter your answer before submitting.");
                    return;
                }

                showLoading();
                $("#submit-answer").prop("disabled", true).html(
                    '<i class="fas fa-spinner fa-spin me-2"></i>Evaluating...');

                // Evaluate the answer using Laravel backend
                $.ajax({
                    type: "POST",
                    url: `${apiUrl}/api/v1/assignment/evaluate`, // Use Laravel endpoint instead of Flask
                    data: JSON.stringify({
                        reference_answer: currentQuestion.reference_answer,
                        user_answer: userAnswer
                    }),
                    contentType: "application/json",
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(response) {
                        // Check for the data property from ResponseController format
                        if (response.code === 200 && response.data) {
                            evaluationResult = response.data;
                            displayEvaluationResults(response.data);

                            // Update Bloom's taxonomy state
                            if (response.data.is_correct) {
                                correctStreak++;
                                if (correctStreak >= 4 && currentLevel !== "analyzing") {
                                    // Move to next level
                                    const levels = ["remembering", "understanding", "applying",
                                        "analyzing"
                                    ];
                                    const currentIndex = levels.indexOf(currentLevel);
                                    currentLevel = levels[currentIndex + 1];
                                    correctStreak = 0;
                                    toastr.success(`Advanced to ${currentLevel} level!`);
                                }
                            } else {
                                correctStreak = 0;
                            }

                            updateBloomLevelUI();
                        } else {
                            toastr.error(response.message || "Failed to evaluate your answer.");
                        }

                        $("#submit-answer").prop("disabled", false).html(
                            '<i class="fas fa-paper-plane me-2"></i>Submit Answer');
                        hideLoading();
                    },
                    error: function(error) {
                        console.error("Error evaluating answer:", error);
                        toastr.error("Failed to evaluate your answer.");
                        $("#submit-answer").prop("disabled", false).html(
                            '<i class="fas fa-paper-plane me-2"></i>Submit Answer');
                        hideLoading();
                    }
                });
            
            });

            function generateQuestion() {
                $.ajax({
                    type: "POST",
                    url: `${apiUrl}/api/v1/question/generate`,
                    data: JSON.stringify({
                        topic_guid: topicGuid,
                        current_level: currentLevel,
                        correct_streak: correctStreak,
                        language: "Indonesia",
                    }),
                    contentType: "application/json",
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(response) {
                        console.log(response);
                        if (response.code === 200 && response.data && response.data.length > 0) {
                            currentQuestion = response.data[0];
                            displayQuestion(currentQuestion);
                            updateBloomLevelUI();
                        } else {
                            toastr.error(response.message ||
                                "Failed to generate question. Please try again.");
                        }
                        hideLoading();
                    },
                    error: function(error) {
                        console.error("Error generating question:", error);
                        toastr.error("Failed to generate question.");
                        hideLoading();
                    }
                });
            }

            function displayEvaluationResults(evaluation) {
                const scorePercent = Math.round(evaluation.combined_score * 100);

                $("#similarity-score-bar").css('width', `${scorePercent}%`);
                $("#similarity-score-text").text(`${scorePercent}% similarity`);

                // Color the progress bar based on score
                $("#similarity-score-bar")
                    .removeClass('bg-danger bg-warning bg-success')
                    .addClass(
                        scorePercent >= 70 ? 'bg-success' :
                        scorePercent >= 50 ? 'bg-warning' : 'bg-danger'
                    );

                // Show detailed evaluation
                $("#evaluation-details").html(`
                    <div class="mb-2"><strong>TF-IDF Score:</strong> ${(evaluation.tfidf_score * 100).toFixed(1)}%</div>
                    <div class="mb-2"><strong>BERT Score:</strong> ${(evaluation.bert_score * 100).toFixed(1)}%</div>
                    <div class="mb-2"><strong>Result:</strong> ${evaluation.is_correct ? 'Correct' : 'Incorrect'}</div>
                `);

                // Show evaluation results and next question button
                $("#evaluation-results").show();
                $("#next-question").show();
            }

            $("#next-question").on("click", function() {
                showLoading();
                generateQuestion();
            });

            function showLoading() {
                $("#loading-overlay").fadeIn(300);
            }

            function hideLoading() {
                $("#loading-overlay").fadeOut(300);
            }
        });
    </script>
@endsection
