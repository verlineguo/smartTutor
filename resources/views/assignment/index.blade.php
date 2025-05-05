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
            border: 1px solid #dee2e6;
            border-radius: 8px;
            display: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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

        .streak-counter {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }

        .chat-message {
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }

        .message-header {
            background-color: #f8f9fa;
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #e0e0e0;
        }

        .message-question {
            padding: 10px 15px;
        }

        .question-bubble {
            background-color: #e8f4f8;
            padding: 10px;
            border-radius: 6px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {

            .history-header,
            .message-header {
                flex-direction: column;
                align-items: flex-start;
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
                            <span id="streak-counter" class="badge bg-info">Correct streak: 0/4</span>
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
                <div class="question-box" id="question-box">
                    <h6 class="mb-3">Question:</h6>
                    <div id="question-text"></div>
                    <div class="question-meta mt-2">
                        <span id="question-category" class="badge bg-secondary"></span>
                        <span id="question-page" class="badge bg-info ms-2"></span>
                    </div>
                </div>

                <!-- Assignment Container -->
                <div class="assignment-container" id="assignment-container">
                    <h6 class="mb-3">Your Answer:</h6>
                    <div class="mb-3">
                        <textarea id="user-input" class="form-control" rows="6" placeholder="Type your answer here..."></textarea>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <button class="btn btn-primary" id="submit-answer" disabled>
                            <i class="fas fa-paper-plane me-2"></i>Submit Answer
                        </button>
                    </div>
                </div>

                <!-- Evaluation Results (shown after submission) -->
                <div class="card mt-4" id="evaluation-results" style="display: none;">
                    <div class="card-header">
                        <h6 class="mb-0">Evaluation Results</h6>
                    </div>
                    <div class="card-body">
                        <div id="evaluation-feedback"></div>
                        <div class="mt-3">
                            <span id="is-correct" class="badge"></span>
                            <span id="score" class="ms-2"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="history-section" id="history-section">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Answer History</h6>
                        </div>
                        <div class="card-body">
                            <div id="history-list" class="history-list">
                                <div class="text-center py-4 text-muted" id="no-history-message">
                                    <i class="fas fa-history fa-2x mb-2"></i>
                                    <p>No answer history for this topic yet.</p>
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
            let correctStreak = 0;

            // Level progression logic
            const taxonomyLevels = ['remembering', 'understanding', 'applying', 'analyzing'];
            const streakThreshold = 4; // Number of correct answers needed to progress

            // Initialize TinyMCE editor
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

            // Initialize toastr notification settings
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: "toast-top-right",
                timeOut: 5000
            };

            // Start the assignment on page load
            checkForExistingProgress();

            // Loading indicator functions
            function showLoading() {
                $("#loading-overlay").fadeIn(300);
            }

            function hideLoading() {
                $("#loading-overlay").fadeOut(300);
            }

            // Fetch questions from the API based on topic and language
            function fetchQuestions() {
                const language = selectedLanguage || "indonesia";
                return $.ajax({
                    type: "GET",
                    url: `${apiUrl}/api/v1/question/show/${topicGuid}/${language}`,
                    data: {
                        user_id: userId
                    }, // Pass user_id to get user's answer history
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    }
                });



            }

            // Check for existing user progress
            function checkForExistingProgress() {
                showLoading();
                $.ajax({
                    type: "GET",
                    url: `${apiUrl}/api/v1/assignment/history/${userId}/${topicGuid}`,
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(response) {
                        if (response.success && response.data && response.data.length > 0) {
                            const latestAnswer = response.data[0];
                            // User has history, get the language and level from the first item
                            selectedLanguage = latestAnswer.language || "indonesia";

                            // Set current level from history
                            currentLevel = latestAnswer.category || "remembering";

                            // Set streak from history
                            correctStreak = latestAnswer.streak || 0;

                            // Update UI based on retrieved state
                            updateBloomLevelUI();
                            showHistorySection();
                            startAssignment();
                        } else {
                            // No history, start fresh
                            selectedLanguage = "indonesia"; // Default language
                            currentLevel = "remembering"; // Default level
                            correctStreak = 0; // Default streak
                            startAssignment();
                        }
                        hideLoading();
                    },
                    error: function(error) {
                        console.error("Error checking history:", error);
                        hideLoading();
                        toastr.error("Failed to check progress history.");
                    }
                });
            }

            // Start assignment with questions
            function startAssignment() {
                showLoading();
                if (!selectedLanguage) {
                    selectedLanguage = "indonesia"; // Default fallback
                }

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

                                // Check if question was previously answered
                                if (question.user_answer && question.user_answer.length > 0) {
                                    const wasCorrect = question.user_answer[0].is_correct === 1;

                                    // If the answer was correct, add to answered questions set
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
                            });

                            // Display question and answer boxes
                            $("#question-box").show();
                            $("#assignment-container").show();

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

                showHistorySection();
            }

            // Display a random question for the current level
            function askQuestion() {
                // Find questions for current level
                const questionsForLevel = questionsGroupedByLevel[currentLevel] || [];

                // Filter out already answered questions
                const availableQuestions = questionsForLevel.filter(q => !answeredQuestions.has(q.guid));

                if (availableQuestions.length === 0) {
                    // If no unanswered questions, use all questions for this level
                    if (questionsForLevel.length > 0) {
                        toastr.info("You've seen all questions for this level. Some questions will be repeated.");
                    } else {
                        toastr.error(`No questions available for ${currentLevel} level.`);
                        return;
                    }
                }

                // Select a random question (either from available or all if none available)
                const questionPool = availableQuestions.length > 0 ? availableQuestions : questionsForLevel;

                if (!questionPool || questionPool.length === 0) {
                    toastr.error(`No questions available for ${currentLevel} level.`);
                    return;
                }

                const randomIndex = Math.floor(Math.random() * questionPool.length);
                const question = questionPool[randomIndex];

                if (!question) {
                    toastr.error(`No questions available for ${currentLevel} level.`);
                    return;
                }

                currentQuestionGuid = question.guid;

                // Display the question
                $("#question-text").html(question.question_fix);
                $("#question-category").text(currentLevel);
                $("#question-page").text(`Page ${question.page || 'N/A'}`);

                // Clear previous answer
                tinymce.get("user-input").setContent('');
                $("#evaluation-results").hide();
                $("#submit-answer").prop("disabled", true);
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
                const progressPercent = (correctStreak / streakThreshold) * 100;
                $("#bloom-progress").css('width', `${progressPercent}%`);
                $("#streak-counter").text(`Correct streak: ${correctStreak}/${streakThreshold}`);
            }

            // Handle progression between Bloom's taxonomy levels
            function handleLevelProgression(isCorrect) {
                if (isCorrect) {
                    correctStreak++;

                    // Check if we should advance to next level
                    if (correctStreak >= streakThreshold) {
                        const currentLevelIndex = taxonomyLevels.indexOf(currentLevel);

                        // If not at max level, advance to next level
                        if (currentLevelIndex < taxonomyLevels.length - 1) {
                            currentLevel = taxonomyLevels[currentLevelIndex + 1];
                            correctStreak = 0; // Reset streak for new level
                            toastr.success(`Advanced to ${currentLevel} level!`);
                        } else {
                            // Max level reached
                            toastr.success("You've reached the highest level. Keep practicing!");
                            correctStreak = streakThreshold; // Cap at max streak
                        }
                    }
                } else {
                    // Reset streak on incorrect answer
                    correctStreak = 0;
                }

                updateBloomLevelUI();
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
                        correct_streak: correctStreak
                    }),
                    contentType: "application/json",
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(response) {
                        console.log("Evaluation response:", response);
                        const evaluation = response.evaluation || {};
                        displayEvaluationResults(evaluation);
                        isSubmitting = false;
                        hideLoading();

                        if (response.status === 'success') {
                            // Update level and streak from response or handle locally
                            if (response.new_level) {
                                currentLevel = response.new_level;
                            }

                            if (response.new_streak !== undefined) {
                                correctStreak = response.new_streak;
                            } else {
                                // Handle progression locally if not provided by server
                                handleLevelProgression(response.is_correct);
                            }

                            // If answer was correct, add to answered questions set
                            if (response.is_correct) {
                                answeredQuestions.add(currentQuestionGuid);
                            }

                            updateBloomLevelUI();

                            // Start plagiarism check
                            checkPlagiarism(userId, topicGuid, currentQuestionGuid, response
                                .data.user_answer_guid, userAnswer, response);

                            // Display evaluation results
                            $("#evaluation-results").show();
                            $("#evaluation-feedback").html(response.evaluation.feedback || "");

                            if (response.is_correct) {
                                $("#is-correct").removeClass("bg-danger").addClass("bg-success")
                                    .text("Correct");
                                toastr.success("Correct answer! Well done.");
                            } else {
                                $("#is-correct").removeClass("bg-success").addClass("bg-danger")
                                    .text("Incorrect");
                                toastr.warning("Your answer needs improvement.");
                            }

                            $("#score").text(`Score: ${response.evaluation.score || 0}%`);

                            // Show next question button after delay
                            setTimeout(() => {
                                askQuestion();
                            }, 3000);

                            // Refresh history section
                            refreshHistory();
                        } else {
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
                            '<i class="fas fa-paper-plane me-2"></i>Submit Answer').prop(
                            "disabled", false);
                        toastr.error("Failed to submit answer. Please try again.");
                        console.error("Error submitting answer:", xhr);
                    }
                });
            });

            // Show history section and load history data
            function showHistorySection() {
                $("#history-section").show();
                fetchHistoryData();
            }

            // Fetch history data from API
            function fetchHistoryData() {
                $.ajax({
                    type: "GET",
                    url: `${apiUrl}/api/v1/assignment/history/${userId}/${topicGuid}`,
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(response) {
                        if (response.success && response.data && response.data.length > 0) {
                            renderHistoryItems(response.data);
                            $("#no-history-message").hide();
                        } else {
                            $("#history-list").html(`
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-history fa-2x mb-2"></i>
                            <p>No answer history for this topic yet.</p>
                        </div>
                    `);
                        }
                    },
                    error: function(error) {
                        console.error("Error fetching history:", error);
                        toastr.error("Failed to fetch answer history.");
                    }
                });
            }

            // Render history items in chat-like format
            function renderHistoryItems(historyData) {
                const historyListElement = $("#history-list");
                historyListElement.empty();

                // Create a chat-like container
                historyListElement.append(`
            <div class="chat-history-container">
                <div class="chat-messages" id="chat-messages"></div>
            </div>
        `);

                const chatMessagesEl = $("#chat-messages");

                historyData.forEach((item, index) => {
                    const formattedDate = new Date(item.created_at).toLocaleString('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    // Determine badge color based on category/level
                    let levelClass = '';
                    switch (item.category) {
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

                    // Create clickable history item
                    chatMessagesEl.append(`
                <div class="chat-message" 
                    data-question-guid="${item.question_guid}" 
                    data-answer-guid="${item.guid}"
                    style="cursor: pointer;">
                    <div class="message-header">
                        <span class="message-timestamp">${formattedDate}</span>
                        <span class="badge ${levelClass}">${item.category || 'General'}</span>
                    </div>
                    <div class="message-question">
                        <div class="question-bubble">
                            ${item.question}
                        </div>
                    </div>
                </div>
            `);
                });

                // Add event listener for clicking history items
                $(".chat-message").on("click", function() {
                    const questionGuid = $(this).data("question-guid");
                    const answerGuid = $(this).data("answer-guid");
                    navigateToEvaluation(questionGuid, answerGuid);
                });
            }

            function checkPlagiarism(userId, topicGuid, questionGuid, userAnswerGuid, userAnswer,
            originalResponse) {
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
                        isSubmitting = false;
                        hideLoading();
                        $("#submit-answer").html(
                        '<i class="fas fa-paper-plane me-2"></i>Submit Answer');
                        $("#submit-answer").prop("disabled", false);

                        // if (plagiarismResponse.success) {
                        //     window.location.href = `/evaluation/${questionGuid}/${userAnswerGuid}`;
                        // } else {
                        //     toastr.warning(plagiarismResponse.message || 
                        //         "There was a problem checking for plagiarism, but the evaluation is still available.");

                        //     setTimeout(() => {
                        //         window.location.href = `/evaluation/${questionGuid}/${userAnswerGuid}`;
                        //     }, 2000);
                        // }
                        refreshHistory();
                    },
                    error: function(xhr) {
                        console.error("Error checking plagiarism:", xhr);

                        // Even if plagiarism check fails, continue with the flow
                        isSubmitting = false;
                        hideLoading();
                        $("#submit-answer").html(
                        '<i class="fas fa-paper-plane me-2"></i>Submit Answer');
                        $("#submit-answer").prop("disabled", false);

                        toastr.warning(
                            "Failed to check plagiarism, but the evaluation is still available.");
                        
                    }
                });
            }

            // Refresh history data
            function refreshHistory() {
                fetchHistoryData();
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

            
            // Navigate to evaluation page
            function navigateToEvaluation(questionGuid, answerGuid) {
                window.location.href = `/evaluation/${questionGuid}/${answerGuid}`;
            }
        });
    </script>
@endsection
