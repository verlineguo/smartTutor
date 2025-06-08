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
        #submit-answer {
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        #submit-answer:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .history-section {
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-radius: 8px;
            display: none;
        }


        .history-section .card {
            height: 100%;
            /* Pastikan card juga mengikuti tinggi */
            display: flex;
            flex-direction: column;
        }



        .history-section .card-header {
            border-bottom: 4px solid rgba(0, 0, 0, 0.05);
            padding-bottom: 1rem;

        }

        .history-section .card-body {
            /* Isi ruang kosong */
            overflow-y: auto;
            /* Tambahkan scroll jika konten terlalu panjang */
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

        .history-card {
            animation: fadeIn 0.5s ease forwards;
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

        /* Bloom's Taxonomy levels styling */
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


        .chat-message {
            padding-bottom: 15px;
            padding-top: 10px;
            border: none;
            overflow: hidden;
            transition: all 0.2s ease-in-out;
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);


        }

        .chat-message:hover {
            background-color: rgba(0, 0, 0, 0.07);
        }

        .message-header {
            padding: 12px 15px;
        }

        .message-answer {
            padding: 15px;
        }


        .message-footer {
            padding: 10px 15px !important;
        }

        .dropdown-item:hover {
            background-color: #f5f8ff;
        }

        .dropdown-item.active {
            background-color: #4e73df;
            color: white;
        }

        #no-history-message {
            color: #6c757d;
        }

        #no-history-message i {
            color: #d1d3e2;
        }

        /* No results message for filtering */
        #no-filter-results {
            padding: 20px;
            text-align: center;
            color: #6c757d;
            display: none;
        }

        /* For scroll behavior */
        .history-list {
            max-height: 685px;
            overflow-y: auto;
            scrollbar-width: thin;
        }

        .history-list::-webkit-scrollbar {
            width: 6px;
        }

        .history-list::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }

        .history-list::-webkit-scrollbar-track {
            background-color: rgba(0, 0, 0, 0.05);
        }


        /* Responsive adjustments */
        @media (max-width: 1000px) {

            .history-header,
            .message-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .history-section {
                margin-top: 20px;
            }

            .history-date,
            .message-timestamp {
                margin-top: 5px;
            }

            .history-footer {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
@endsection

@section('info-page')
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
        <li class="breadcrumb-item text-sm text-dark active text-capitalize" aria-current="page">Assignment</li>
    </ol>
    <h5 class="font-weight-bolder mb-0 text-capitalize">Assignment</h5>
@endsection

@section('content')
    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-8 d-flex flex-column">
                <!-- Bloom's Taxonomy Level Display -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h6 class="mb-3">Your Current Progress</h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <span id="bloom-level-display" class="bloom-level-indicator remembering">Level:
                                Remembering</span>
                        </div>
                        <div class="progress-container">
                            <div class="progress">
                                <div id="bloom-progress" class="progress-bar bg-success" role="progressbar"
                                    style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Question Box -->


                <!-- Assignment Container -->
                <div class="card" id="assignment-container">
                    <div class="card-body">
                        <h6 class="mb-3">Question:</h6>
                        <div id="question-text"></div>
                        <div class="question-meta mt-2">
                            <span id="question-category" class="badge bg-secondary"></span>
                            <span id="question-page" class="badge bg-info ms-2"></span>
                        </div>
                        <h6 class="mb-3 mt-3">Your Answer:</h6>
                        <div class="mb-3">
                            <textarea id="user-input" class="form-control" rows="6" placeholder="Type your answer here..."></textarea>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <button class="btn btn-primary" id="submit-answer" disabled>
                                <i class="fas fa-paper-plane me-2"></i>Submit Answer
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Evaluation Results (shown after submission) -->
                <div class="card mt-4" id="evaluation-results" style="display: none;">
                    <div class="card-header">
                        <h6 class="mb-0">Evaluation Results</h6>
                    </div>
                    <div class="card-body">
                        <div id="evaluation-feedback"></div>
                        <div class="mt-0">
                            <span id="is-correct" class="badge"></span>
                            <span id="score" class="ms-2"></span>
                        </div>
                        <!-- Reference Pages Section - Show when answer is incorrect -->
                        <div id="reference-pages-section" class="mt-3" style="display: none;">
                            <div class="alert">
                                <h6 class="mb-2"><i class="fas fa-book-open me-2"></i>Study Recommendation</h6>
                                <p>Please review the following pages to better understand this topic:</p>
                                <div id="reference-pages-list" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="history-section" id="history-section">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between">
                                <h6>History</h6>

                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle d-flex align-items-center"
                                        type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-filter me-2"></i>
                                        <span id="current-filter-label">All Levels</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="filterDropdown">
                                        <li><a class="dropdown-item active" href="#" data-filter="all">All Levels</a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item" href="#"
                                                data-filter="remembering">Remembering</a></li>
                                        <li><a class="dropdown-item" href="#"
                                                data-filter="understanding">Understanding</a></li>
                                        <li><a class="dropdown-item" href="#" data-filter="applying">Applying</a>
                                        </li>
                                        <li><a class="dropdown-item" href="#" data-filter="analyzing">Analyzing</a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item" href="#" data-filter="correct">Correct
                                                Only</a></li>
                                        <li><a class="dropdown-item" href="#" data-filter="incorrect">Incorrect
                                                Only</a></li>
                                    </ul>
                                </div>
                            </div>


                        </div>
                        <div class="card-body p-0">
                            <div id="history-list" class="history-list">
                                <div class="text-center py-4 text-muted" id="no-history-message">
                                    <i class="fas fa-history fa-2x mb-2"></i>
                                    <p>No answer history for this topic yet.</p>
                                </div>

                                <div id="chat-messages">
                                    <!-- History items will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay" style="display:none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Processing...</p>
    </div>
@endsection

@section('vendor-javascript')
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

            // Application state
            let currentQuestionGuid = null;
            let selectedLanguage = "indonesia";
            let isSubmitting = false;
            let questionsGroupedByLevel = {};
            let answeredQuestions = new Set();

            // Bloom's taxonomy tracking
            let currentLevel = "remembering";
            const taxonomyLevels = ['remembering', 'understanding', 'applying', 'analyzing'];

            tinymce.init({
                selector: '#user-input',
                height: 300,
                menubar: false,
                plugins: 'lists link image table code help wordcount',
                toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | table | code',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 16px; }',
                placeholder: 'Type your answer here...',
                setup: function(editor) {
                    editor.on('change', function() {
                        // Enable/disable submit button based on content
                        const content = editor.getContent().trim();
                        $("#submit-answer").prop("disabled", content === '');
                    });
                }
            });

            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: "toast-top-right",
                timeOut: 5000
            };

            // Start by checking for existing progress
            checkForExistingProgress();

            function showLoading() {
                $("#loading-overlay").fadeIn(300);
            }

            function hideLoading() {
                $("#loading-overlay").fadeOut(300);
            }

            function fetchQuestions() {
                const language = selectedLanguage || "indonesia";
                return $.ajax({
                    type: "GET",
                    url: `${apiUrl}/api/v1/question/show/${topicGuid}/${language}`,
                    data: {
                        user_id: userId
                    },
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    }
                });
            }

            function checkForExistingProgress() {
                showLoading();
                $.ajax({
                    type: "GET",
                    url: `${apiUrl}/api/v1/assignment/all-answers/${userId}/${topicGuid}`,
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(response) {
                        if (response.success && response.data && response.data.length > 0) {
                            selectedLanguage = response.data[0].language || "indonesia";

                            // FIXED: Determine current level based on completed question categories
                            currentLevel = determineUserCurrentLevel(response.data);

                            console.log("Restored user level:", currentLevel);
                            updateBloomLevelUI();

                            // Check if user has completed all levels by checking analyzing category completion
                            const hasCompletedAllLevels = checkIfAllLevelsCompleted(response.data);

                            if (hasCompletedAllLevels) {
                                // User has completed all levels, show history-only view
                                startAssignment();
                                setTimeout(() => {
                                    showOnlyHistoryView();
                                }, 1000);
                            } else {
                                // Continue normal flow
                                startAssignment();
                            }
                        } else {
                            // No history, start fresh
                            selectedLanguage = "indonesia"; // Default language
                            currentLevel = "remembering"; // Default level
                            startAssignment();
                        }
                        hideLoading();
                    },
                    error: function(error) {
                        console.error("Error checking history:", error);
                        hideLoading();
                        toastr.error("Failed to check progress history.");
                        // Default fallback
                        selectedLanguage = "indonesia";
                        currentLevel = "remembering";
                        startAssignment();
                    }
                });
            }

            /**
             * NEW FUNCTION: Determine user's current level based on completed question categories
             */
            function determineUserCurrentLevel(answers) {
                const levels = ['remembering', 'understanding', 'applying', 'analyzing'];
                const levelRanking = {
                    'remembering': 0,
                    'understanding': 1,
                    'applying': 2,
                    'analyzing': 3
                };

                // Get all correct answers
                const correctAnswers = answers.filter(answer => answer.is_correct === 1);

                if (correctAnswers.length === 0) {
                    return "remembering"; // No correct answers, start from beginning
                }

                // Find which categories/levels have been completed
                const completedCategories = new Set();
                correctAnswers.forEach(answer => {
                    if (answer.category) {
                        completedCategories.add(answer.category);
                    }
                });

                // Find the next level that hasn't been completed
                for (let level of levels) {
                    if (!completedCategories.has(level)) {
                        return level;
                    }
                }

                // If all levels have at least one correct answer, return analyzing
                return "analyzing";
            }

            /**
             * NEW FUNCTION: Check if user has completed all levels (specifically analyzing)
             */
            function checkIfAllLevelsCompleted(answers) {
                // For now, we'll consider "all levels completed" if user has correct answers in analyzing category
                // In a more sophisticated approach, you might want to check if ALL analyzing questions are completed
                const correctAnswers = answers.filter(answer => answer.is_correct === 1);
                const hasAnalyzingCorrect = correctAnswers.some(answer => answer.category === 'analyzing');

                return hasAnalyzingCorrect;
            }


            function handleMaxLevelCompletion() {
                $("#bloom-level-display")
                    .removeClass('remembering understanding applying analyzing')
                    .addClass('analyzing completed')
                    .text('Level: Analyzing (Completed)');

                $("#bloom-progress").css('width', '100%');
                toastr.success('Congratulations! You have completed all learning levels for this topic.');
                showCompletionCelebration();
            }

            // Start assignment with questions - FIXED to maintain correct level and questions
            function startAssignment() {
                showLoading();
                $("#history-section").show();

                fetchQuestions()
                    .then(response => {
                        if (response.data && response.data.length > 0) {
                            // Initialize question groups by level
                            questionsGroupedByLevel = {
                                'remembering': [],
                                'understanding': [],
                                'applying': [],
                                'analyzing': []
                            };

                            // Reset answered questions
                            answeredQuestions = new Set();

                            // Process questions from response
                            response.data.forEach(question => {
                                const level = question.category || 'remembering';

                                if (questionsGroupedByLevel[level]) {
                                    // We're only interested in tracking correctly answered questions
                                    if (question.user_answer && question.user_answer.length > 0) {
                                        const wasCorrect = question.user_answer[0].is_correct === 1;
                                        if (wasCorrect) {
                                            answeredQuestions.add(question.guid);
                                        } else {
                                            // If incorrect, can be asked again
                                            questionsGroupedByLevel[level].push(question);
                                        }
                                    } else {
                                        // If never answered, add to appropriate level
                                        questionsGroupedByLevel[level].push(question);
                                    }
                                }
                            });

                            console.log("Questions grouped by level:", Object.keys(questionsGroupedByLevel).map(
                                level =>
                                `${level}: ${questionsGroupedByLevel[level].length} questions`));
                            console.log("Current level set to:", currentLevel);

                            // Update UI to show current level
                            updateBloomLevelUI();

                            // Display question and answer boxes
                            $("#question-box").show();

                            // Get a question for the current level
                            askQuestion();
                        } else {
                            toastr.error("No questions available for the selected language.");
                        }
                        hideLoading();
                    })
                    .catch(error => {
                        console.error("Error fetching questions:", error);
                        toastr.error("Failed to fetch questions.");
                        hideLoading();
                    });

                // Fetch history data
                fetchHistoryData(true);
            }

            // Display a random question for the current level that hasn't been correctly answered yet
            // FIXED to maintain consistent question until answered correctly
            function askQuestion() {
                // Check if there's a last incorrect attempt that should be repeated
                if (currentQuestionGuid && !answeredQuestions.has(currentQuestionGuid)) {
                    // Keep the current question - it was incorrect
                    const currentQuestion = findQuestionByGuid(currentQuestionGuid);
                    if (currentQuestion) {
                        // Display the same question again
                        console.log("Showing same question again (incorrect last time):", currentQuestionGuid);
                        $("#question-text").html(currentQuestion.question_fix);
                        $("#question-category").text(capitalizeFirstLetter(currentLevel));
                        $("#question-page").text(`Page ${currentQuestion.page || 'N/A'}`);

                        // Clear previous answer
                        tinymce.get("user-input").setContent('');
                        $("#evaluation-results").hide();
                        $("#submit-answer").prop("disabled", true);
                        return;
                    }
                }

                // Find questions for current level
                const questionsForLevel = questionsGroupedByLevel[currentLevel] || [];

                if (questionsForLevel.length === 0) {
                    toastr.error(`No questions available for ${currentLevel} level.`);
                    return;
                }

                // Select a random question from the current level
                const randomIndex = Math.floor(Math.random() * questionsForLevel.length);
                const question = questionsForLevel[randomIndex];

                currentQuestionGuid = question.guid;
                console.log("Selected new question:", currentQuestionGuid, "for level:", currentLevel);

                // Display the question
                $("#question-text").html(question.question_fix);
                $("#question-category").text(capitalizeFirstLetter(currentLevel));
                $("#question-page").text(`Page ${question.page || 'N/A'}`);

                // Clear previous answer
                tinymce.get("user-input").setContent('');
                $("#evaluation-results").hide();
                $("#submit-answer").prop("disabled", true);
            }

            // Helper function to find a question by GUID across all levels
            function findQuestionByGuid(guid) {
                for (const level in questionsGroupedByLevel) {
                    const question = questionsGroupedByLevel[level].find(q => q.guid === guid);
                    if (question) return question;
                }
                return null;
            }

            // Helper to capitalize first letter
            function capitalizeFirstLetter(string) {
                return string.charAt(0).toUpperCase() + string.slice(1);
            }

            // Update the Bloom's level UI elements
            function updateBloomLevelUI() {
                // Update level display with proper capitalization
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

                // Update progress bar
                const currentLevelIndex = taxonomyLevels.indexOf(currentLevel);
                const progressPercent = (currentLevelIndex / (taxonomyLevels.length - 1)) * 100;
                $("#bloom-progress").css('width', `${progressPercent}%`);

                console.log("Updated UI: Level =", currentLevel, "Progress =", progressPercent + "%");
            }

            function showCompletionCelebration() {
                // Create a completion overlay
                const completionOverlay = $(`
        <div id="completion-overlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center" 
             style="background-color: rgba(255,255,255,0.9); z-index: 9999; animation: fadeIn 0.5s ease;">
            <div class="text-center p-5 bg-white rounded-lg shadow-lg" style="max-width: 600px;">
                <div class="mb-4">
                    <i class="fas fa-trophy fa-4x text-warning mb-3"></i>
                    <h2 class="fw-bold">Congratulations!</h2>
                    <p class="lead">You've completed all learning levels for this topic!</p>
                </div>
                <div class="achievement-summary mb-4">
                    <h5>Your Achievement Summary</h5>
                    <div class="d-flex justify-content-center gap-3 mb-3">
                        <div class="px-3 py-2 bg-light rounded">
                            <i class="fas fa-check-circle text-success"></i>
                            <span id="correct-count">0</span> Correct
                        </div>
                        <div class="px-3 py-2 bg-light rounded">
                            <i class="fas fa-times-circle text-danger"></i>
                            <span id="incorrect-count">0</span> Attempts
                        </div>
                        <div class="px-3 py-2 bg-light rounded">
                            <i class="fas fa-percentage text-primary"></i>
                            <span id="success-rate">0%</span> Success
                        </div>
                    </div>
                </div>
                <div class="buttons mt-4">
                    <button id="view-history-btn" class="btn btn-primary me-2">
                        <i class="fas fa-history me-2"></i>View Answer History
                    </button>
                   
                </div>
            </div>
        </div>
    `);

                // Append to body
                $('body').append(completionOverlay);

                // Fetch statistics to show in the completion screen
                $.ajax({
                    type: "GET",
                    url: `${apiUrl}/api/v1/assignment/all-answers/${userId}/${topicGuid}`,
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            const totalAnswers = response.data.length;
                            const correctAnswers = response.data.filter(answer => answer.is_correct ===
                                1).length;
                            const successRate = totalAnswers > 0 ? Math.round((correctAnswers /
                                totalAnswers) * 100) : 0;

                            $('#correct-count').text(correctAnswers);
                            $('#incorrect-count').text(totalAnswers - correctAnswers);
                            $('#success-rate').text(`${successRate}%`);
                        }
                    }
                });

                // Add button handlers
                $('#view-history-btn').on('click', function() {
                    $('#completion-overlay').fadeOut(300, function() {
                        $(this).remove();
                    });
                    showOnlyHistoryView();
                });


            }

            function showOnlyHistoryView() {
                // Hide question and assignment containers
                $("#assignment-container").hide();
                $("#evaluation-results").hide();

                // Modify the layout to emphasize history
                $(".col-lg-8").removeClass("col-lg-8").addClass("col-lg-5");
                $(".col-lg-4").removeClass("col-lg-4").addClass("col-lg-7");

                // Show completion state banner
                const completionBanner = $(`
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-check-circle text-success fa-2x"></i>
                    </div>
                    <div>
                        <h5 class="mb-1">Topic Completed</h5>
                        <p class="mb-0">You have successfully completed all levels of this topic. Review your answer history below</p>
                    </div>
                    
                </div>
            </div>
        </div>
    `);

                // Add the banner to the top
                $(".col-lg-5").prepend(completionBanner);

                // Ensure history section is visible and fully loaded
                $("#history-section").show();
                refreshHistory();

                // Add a page title change
                document.title = "Topic Completed - Review History";
            }


            // Submit user's answer
            $("#submit-answer").on("click", function() {
                if (isSubmitting) return;

                const userAnswer = tinymce.get("user-input").getContent();
                if (!userAnswer.trim()) {
                    toastr.error("Answer cannot be empty!");
                    return;
                }

                isSubmitting = true;
                $("#submit-answer").prop("disabled", true).html(
                    '<i class="fas fa-spinner fa-spin me-2"></i>Evaluating...');
                showLoading();

                // Send answer for evaluation
                $.ajax({
                    type: "POST",
                    url: `${apiUrl}/api/v1/assignment/submit`,
                    data: JSON.stringify({
                        user_id: userId,
                        topic_guid: topicGuid,
                        question_guid: currentQuestionGuid,
                        answer: userAnswer,
                        current_level: currentLevel,
                    }),
                    contentType: "application/json",
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(response) {
                        console.log("Evaluation response:", response);

                        // Display evaluation results
                        $("#evaluation-results").show();
                        $("#evaluation-feedback").html(response.feedback || "");

                        const combinedScore = Math.round((response.evaluation.combined_score ||
                            0) * 100);
                        $("#score").text(`Score: ${combinedScore}%`);

                        if (response.status === 'success') {
                        console.log(response);
                            if (response.is_correct) {
                                $("#is-correct").removeClass("bg-danger").addClass("bg-success")
                                    .text("Correct");

                                // Add to answered questions
                                answeredQuestions.add(currentQuestionGuid);

                                if (response.has_completed_all_levels) {
                                    // Check plagiarism first, then handle completion
                                    checkPlagiarism(userId, topicGuid, currentQuestionGuid,
                                        response.data.user_answer_guid, userAnswer,
                                        function() {
                                            handleMaxLevelCompletion();
                                        });
                                    refreshHistory();
                                } else if (response.new_level && response.new_level !==
                                    currentLevel) {
                                    currentLevel = response.new_level;
                                    console.log(currentLevel);
                                    updateBloomLevelUI();
                                    toastr.info(
                                        `Congratulations! You've advanced to the ${capitalizeFirstLetter(currentLevel)} level.`
                                    );

                                    // Check plagiarism, then continue
                                    checkPlagiarism(userId, topicGuid, currentQuestionGuid,
                                        response.data.user_answer_guid, userAnswer,
                                        function() {
                                            toastr.success("Correct answer! Well done.");
                                            refreshHistory();
                                            setTimeout(() => {
                                                askQuestion();
                                            }, 3000);
                                        });
                                } else {
                                    // Check plagiarism for regular correct answer
                                    checkPlagiarism(userId, topicGuid, currentQuestionGuid,
                                        response.data.user_answer_guid, userAnswer,
                                        function() {
                                            toastr.success("Correct answer! Well done.");
                                            refreshHistory();
                                            setTimeout(() => {
                                                askQuestion();
                                            }, 3000);
                                        });
                                }
                            } else {
                                $("#is-correct").removeClass("bg-success").addClass("bg-danger")
                                    .text("Incorrect");

                                // Check plagiarism for incorrect answer too
                                checkPlagiarism(userId, topicGuid, currentQuestionGuid,
                                    response.data.user_answer_guid, userAnswer,
                                    function() {
                                        // Show reference pages for improvement
                                        if (response.reference_pages) {
                                            showReferencePages(response.reference_pages);

                                            let pagesText = "Reference pages: ";
                                            if (Array.isArray(response.reference_pages)) {
                                                pagesText += response.reference_pages.join(
                                                    ", ");
                                            } else if (typeof response.reference_pages ===
                                                'object') {
                                                pagesText += Object.values(response
                                                    .reference_pages).join(", ");
                                            }

                                            toastr.warning(
                                                `Your answer needs improvement. ${pagesText}`
                                            );
                                        } else {
                                            toastr.warning(
                                                "Your answer needs improvement. Please review the material."
                                            );
                                            $("#reference-pages-section").hide();
                                        }

                                        // Reset UI for next attempt
                                        $("#submit-answer").html(
                                                '<i class="fas fa-paper-plane me-2"></i>Submit Answer'
                                            )
                                            .prop("disabled", false);
                                    });
                            }
                        } else {
                            hideLoading();
                            isSubmitting = false;
                            $("#submit-answer").html(
                                    '<i class="fas fa-paper-plane me-2"></i>Submit Answer')
                                .prop("disabled", false);
                            toastr.error(response.message || "Error submitting answer.");
                        }
                    },
                    error: function(xhr) {
                        isSubmitting = false;
                        hideLoading();
                        $("#submit-answer").html(
                                '<i class="fas fa-paper-plane me-2"></i>Submit Answer')
                            .prop("disabled", false);
                        toastr.error("Failed to submit answer. Please try again.");
                        console.error("Error submitting answer:", xhr);
                    }
                });
            });


            $('.dropdown-item').on('click', function(e) {
                e.preventDefault();

                // Update active state in dropdown
                $('.dropdown-item').removeClass('active');
                $(this).addClass('active');

                const filter = $(this).data('filter');
                let filterLabel = $(this).text();

                // Update filter label
                $('#current-filter-label').text(filterLabel);

                // Apply filter
                applyFilter(filter);
            });

            function applyFilter(filter) {
                let foundItems = 0;

                // Hide all no results message first
                $('#no-filter-results').remove();

                // Show all items initially
                $('.chat-message').each(function() {
                    $(this).hide(); // Hide all first

                    // Get category and correctness
                    const category = $(this).find('.message-header .badge').first().text().toLowerCase();
                    const isCorrect = $(this).find('.message-header .badge').last().text() === 'Correct';

                    // Apply filters
                    if (filter === 'all' ||
                        (filter === category) ||
                        (filter === 'correct' && isCorrect) ||
                        (filter === 'incorrect' && !isCorrect)) {
                        $(this).show();
                        foundItems++;
                    }
                });

                // Show no results message if needed
                if (foundItems === 0) {
                    $('#chat-messages').append(`
                <div id="no-filter-results" class="fade-in">
                    <i class="fas fa-filter fa-2x mb-2 text-muted"></i>
                    <p>No items match the selected filter.</p>
                </div>
            `);
                }
            }

            // Check plagiarism after submitting a correct answer - FIXED to maintain loading state
            function checkPlagiarism(userId, topicGuid, questionGuid, userAnswerGuid, userAnswer, callback) {
                console.log("Starting plagiarism check for answer:", userAnswerGuid);

                // Keep loading state visible
                showLoading();
                $("#loading-overlay p").text("Checking for plagiarism...");

                $.ajax({
                    type: "POST",
                    url: `${apiUrl}/api/v1/plagiarism/check`,
                    data: JSON.stringify({
                        user_id: userId,
                        topic_guid: topicGuid,
                        question_guid: questionGuid,
                        user_answer_guid: userAnswerGuid,
                        answer: userAnswer
                    }),
                    contentType: "application/json",
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(plagiarismResponse) {
                        console.log("Plagiarism check completed:", plagiarismResponse);
                        hideLoading();
                        isSubmitting = false;
                        $("#loading-overlay p").text("Processing..."); // Reset text for next time

                        // Show plagiarism warning if detected
                        if (plagiarismResponse.weighted_score > 0.7) { // Adjust threshold as needed
                            toastr.warning(
                                "Your answer shows similarities to various AI sources. Please ensure your work is original."
                            );
                        }

                        // Execute callback if provided
                        if (typeof callback === "function") {
                            callback();
                        }
                    },
                    error: function(xhr) {
                        console.error("Error checking plagiarism:", xhr);
                        hideLoading();
                        isSubmitting = false;
                        $("#loading-overlay p").text("Processing..."); // Reset text for next time

                        // Don't show error toast for plagiarism check failure
                        // Just continue with the callback
                        console.warn("Plagiarism check failed, continuing without it");

                        if (typeof callback === "function") {
                            callback();
                        }
                    }
                });
            }
            // Display reference pages for incorrect answers
            function showReferencePages(referencePages) {
                const referenceList = $("#reference-pages-list");
                referenceList.empty();

                // Convert to array if it's an object
                const pagesArray = Array.isArray(referencePages) ? referencePages : Object.values(referencePages);

                if (pagesArray.length > 0) {
                    const pagesList = $("<ul></ul>").addClass("mb-0");

                    pagesArray.forEach(page => {
                        pagesList.append(`<li>Page ${page}</li>`);
                    });

                    referenceList.append(pagesList);
                    $("#reference-pages-section").show();
                } else {
                    $("#reference-pages-section").hide();
                }
            }

            // FIXED history display to show all answers (correct and incorrect) for completed questions
            function fetchHistoryData(initialLoad = false) {
                $.ajax({
                    type: "GET",
                    url: `${apiUrl}/api/v1/assignment/history/${userId}/${topicGuid}`,
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(response) {
                        if (response.success && response.data && response.data.length > 0) {
                            // We have history data, now fetch all answers including incorrect ones
                            $.ajax({
                                type: "GET",
                                url: `${apiUrl}/api/v1/assignment/all-answers/${userId}/${topicGuid}`,
                                beforeSend: function(request) {
                                    request.setRequestHeader("Authorization",
                                        `Bearer ${token}`);
                                },
                                success: function(allAnswersResponse) {
                                    if (allAnswersResponse.success && allAnswersResponse
                                        .data) {
                                        renderHistoryItems(response.data, allAnswersResponse
                                            .data);
                                        $("#no-history-message").hide();
                                    } else {
                                        // If we can't get all answers, still show the correct ones
                                        renderHistoryItems(response.data, []);
                                        $("#no-history-message").hide();
                                        $("#history-section").show();
                                    }
                                },
                                error: function() {
                                    // Fallback to just showing correct answers
                                    renderHistoryItems(response.data, []);
                                    $("#no-history-message").hide();
                                }
                            });
                        } else {
                            $("#no-history-message").show();

                        }
                    },
                    error: function(error) {
                        console.error("Error fetching history:", error);
                        if (!initialLoad) {
                            toastr.error("Failed to fetch answer history.");
                        }
                    }
                });
            }

            // FIXED to show ALL answers for questions that have at least one correct answer
            function renderHistoryItems(correctAnswersData, allAnswersData) {
                const historyListElement = $("#history-list");
                const chatMessagesEl = $("#chat-messages");

                // Clear previous content
                chatMessagesEl.empty();

                // First, identify which questions have been correctly answered
                const correctlyAnsweredQuestionGuids = new Set();
                correctAnswersData.forEach(item => {
                    correctlyAnsweredQuestionGuids.add(item.question_guid);
                });

                // If we don't have any correctly answered questions, show a message
                if (correctlyAnsweredQuestionGuids.size === 0) {
                    $("#no-history-message").show();
                    return;
                } else {
                    $("#no-history-message").hide();
                }

                // Create a data structure that includes all answers (correct and incorrect) 
                // for questions that have been correctly answered at least once
                const questionGroups = {};

                // First, add all the correct answers
                correctAnswersData.forEach(item => {
                    if (!questionGroups[item.question_guid]) {
                        questionGroups[item.question_guid] = {
                            questionText: item.question,
                            questionGuid: item.question_guid,
                            category: item.category,
                            page: item.page,
                            attempts: []
                        };
                    }

                    questionGroups[item.question_guid].attempts.push({
                        guid: item.guid,
                        answer: item.answer,
                        isCorrect: 1,
                        category: item.category,
                        created_at: item.created_at,
                        evaluation_scores: item.evaluation_scores || 0
                    });
                });

                // Add incorrect answers from allAnswersData if available
                if (allAnswersData && allAnswersData.length > 0) {
                    allAnswersData.forEach(item => {
                        // Only add if this question has at least one correct answer
                        if (correctlyAnsweredQuestionGuids.has(item.question_guid)) {
                            if (!questionGroups[item.question_guid]) {
                                questionGroups[item.question_guid] = {
                                    questionText: item.question,
                                    questionGuid: item.question_guid,
                                    category: item.category || "unknown",
                                    page: item.page,
                                    attempts: []
                                };
                            }

                            // Add if not already added (avoid duplication with correct answers)
                            const alreadyAdded = questionGroups[item.question_guid].attempts.some(
                                attempt => attempt.guid === item.guid
                            );

                            if (!alreadyAdded) {
                                questionGroups[item.question_guid].attempts.push({
                                    guid: item.guid,
                                    answer: item.answer,
                                    isCorrect: item.is_correct,
                                    category: item.category,
                                    created_at: item.created_at,
                                    evaluation_scores: item.evaluation_scores || 0
                                });
                            }
                        }
                    });
                }

                // Sort questions by most recent correct answer
                const sortedQuestions = Object.values(questionGroups).sort((a, b) => {
                    // Find most recent correct attempt for each
                    const aCorrectAttempts = a.attempts.filter(attempt => attempt.isCorrect === 1);
                    const bCorrectAttempts = b.attempts.filter(attempt => attempt.isCorrect === 1);

                    if (aCorrectAttempts.length === 0) return 1;
                    if (bCorrectAttempts.length === 0) return -1;

                    const aNewestCorrect = new Date(
                        Math.max(...aCorrectAttempts.map(attempt => new Date(attempt.created_at)
                            .getTime()))
                    );
                    const bNewestCorrect = new Date(
                        Math.max(...bCorrectAttempts.map(attempt => new Date(attempt.created_at)
                            .getTime()))
                    );

                    return aNewestCorrect - bNewestCorrect;
                });

                // For each question group, display all attempts
                sortedQuestions.forEach((questionData, questionIndex) => {
                    // Sort attempts by date (oldest first)
                    const sortedAttempts = questionData.attempts.sort((a, b) =>
                        new Date(a.created_at) - new Date(b.created_at)
                    );

                    // Calculate statistics for this question
                    const totalAttempts = sortedAttempts.length;
                    const correctAttempts = sortedAttempts.filter(attempt => attempt.isCorrect === 1)
                        .length;
                    const incorrectAttempts = totalAttempts - correctAttempts;
                    const successRate = Math.round((correctAttempts / totalAttempts) * 100);

                    // Add each attempt
                    sortedAttempts.forEach((attempt, index) => {
                        const formattedDate = new Date(attempt.created_at).toLocaleString('id-ID', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        // Determine badge color based on correctness
                        const correctnessClass = attempt.isCorrect === 1 ? 'bg-success' :
                            'bg-danger';
                        const correctnessText = attempt.isCorrect === 1 ? 'Correct' : 'Incorrect';

                        // Determine badge color based on category/level
                        let levelClass = '';
                        switch (attempt.category) {
                            case 'remembering':
                                levelClass = 'bg-info';
                                break;
                            case 'understanding':
                                levelClass = 'bg-success';
                                break;
                            case 'applying':
                                levelClass = 'bg-warning';
                                break;
                            case 'analyzing':
                                levelClass = 'bg-danger';
                                break;
                            default:
                                levelClass = 'bg-secondary';
                        }

                        // Create attempt item with animation delay based on index
                        chatMessagesEl.append(`
                <div class="chat-message fade-in" 
                    data-question-guid="${questionData.questionGuid}" 
                    data-answer-guid="${attempt.guid}"
                    data-category="${attempt.category || 'unknown'}"
                    data-correctness="${attempt.isCorrect === 1 ? 'correct' : 'incorrect'}"
                    style="cursor: pointer; animation-delay: ${index * 0.1}s">
                    <div class="message-header">
                            <span class="message-timestamp">${formattedDate}</span>
                            <div>
                                <span class="badge ${levelClass} me-1">${attempt.category || 'General'}</span>
                                <span class="badge ${correctnessClass}">${correctnessText}</span>
                            </div>
                        </div>

                    <div class="message-answer">
                        <div class="answer-bubble">
                            ${questionData.questionText}
                        </div>
                    </div>
                    <div class="message-footer d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-light text-dark">Attempt ${index + 1}/${totalAttempts}</span>
                            ${index === sortedAttempts.length - 1 ? 
                                `<span class="badge bg-light text-dark ms-1">Success rate: ${successRate}%</span>` : ''}
                        </div>
                        <span class="score-badge">
                            <span class="badge bg-primary">Score: ${Math.round(attempt.evaluation_scores * 100)}%</span>
                        </span>
                    </div>
                </div>
            `);
                    });
                });

                // Add event listener for clicking history items
                $(".chat-message").on("click", function() {
                    const questionGuid = $(this).data("question-guid");
                    const answerGuid = $(this).data("answer-guid");

                    // Add visual feedback when clicked
                    $(this).addClass('pulse');
                    setTimeout(() => {
                        navigateToEvaluation(questionGuid, answerGuid);
                    }, 300);
                });

                // Helper function to capitalize first letter
                function capitalizeFirstLetter(string) {
                    return string.charAt(0).toUpperCase() + string.slice(1);
                }
            }

            function showHistorySection() {
                $("#history-section").show();
                fetchHistoryData();
            }

            // Refresh history data
            function refreshHistory() {
                fetchHistoryData();
            }

            // Navigate to evaluation page for a specific answer
            function navigateToEvaluation(questionGuid, answerGuid) {
                window.location.href = `/evaluation/${questionGuid}/${answerGuid}`;
            }
        });
    </script>
@endsection
