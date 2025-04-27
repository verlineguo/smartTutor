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

@section('info-page')
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
        <li class="breadcrumb-item text-sm text-dark active text-capitalize" aria-current="page">Assignment</li>
    </ol>
    <h5 class="font-weight-bolder mb-0 text-capitalize">Assignment</h5>
@endsection

@section('content')
    <!-- Language Selection -->

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-8 d-flex flex-column">
                <div class="question-box" id="question-box">
                    <h6 class="mb-3">Pertanyaan:</h6>
                    <div id="question-text"></div>
                    <div class="question-meta mt-2">
                        <span id="question-category" class="badge bg-secondary"></span>
                        <span id="question-page" class="badge bg-info ms-2"></span>
                    </div>
                </div>

                <!-- Assignment Container -->
                <div class="assignment-container" id="assignment-container">
                    <h6 class="mb-3">Jawaban Anda:</h6>
                    <div class="mb-3">
                        <textarea id="user-input" class="form-control" rows="6" placeholder="Ketik jawaban Anda di sini..." disabled></textarea>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <button class="btn btn-primary" id="submit-answer" disabled>
                            <i class="fas fa-paper-plane me-2"></i>Kirim Jawaban
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="history-section" id="history-section">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Riwayat Jawaban</h6>
                        </div>
                        <div class="card-body">
                            <div id="history-list" class="history-list">
                                <div class="text-center py-4 text-muted" id="no-history-message">
                                    <i class="fas fa-history fa-2x mb-2"></i>
                                    <p>Belum ada riwayat jawaban untuk topik ini.</p>
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
        <p class="mt-2">Sedang memproses...</p>
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
            let selectedLanguage = null;
            let currentThreshold = 0;
            let isSubmitting = false;
         

            let currentLevel = "remembering";
            let correctStreak = 0;
            let currentQuestion = null;
            let evaluationResult = null;



            // Init TinyMCE editor
            tinymce.init({
                selector: '#user-input',
                height: 300,
                menubar: false,
                plugins: 'lists link image table code help wordcount',
                toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | table | code',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 16px; }',
                placeholder: 'Ketik jawaban Anda di sini...',
                setup: function(editor) {
                    editor.on('change', function() {
                        // Enable/disable send button based on content
                        const content = editor.getContent().trim();
                        $("#send-button").prop("disabled", content === '');
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

            startAssignment();

            function startAssignment() {
                showLoading();
                fetchQuestions()
                    .then(response => {
                        if (response.data && response.data.length > 0) {
                            // Group questions by page number
                            questionsGroupedByPage = response.data.reduce((acc, question) => {
                                const page = parseInt(question.page, 10) || 1;
                                if (!acc[page]) acc[page] = [];
                                acc[page].push(question);
                                return acc;
                            }, {});

                            // Find highest page number
                            highestPage = Math.max(...Object.keys(questionsGroupedByPage).map(page =>
                                parseInt(page, 10)));

                            // Display the first question
                            $("#question-box").show();
                            $("#assignment-container").show();
                            tinymce.get("user-input").mode.set("design");

                            askQuestion(questionsGroupedByPage);
                        } else {
                            toastr.error("No questions available for the selected language.");
                            $("#language-selection").show();
                        }
                        hideLoading();
                    })
                    .catch(error => {
                        console.error("Error fetching questions:", error);
                        toastr.error("Failed to fetch questions.");
                        $("#language-selection").show();
                        hideLoading();
                    });
            }


            // Loading indicator functions
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



            
            


            // Fetch questions from the API
            function fetchQuestions() {
                console.log(currentPage);
                return $.ajax({
                    type: "GET",
                    url: `${apiUrl}/api/v1/question/show/${topicGuid}/${selectedLanguage}`,
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
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



            // Display a random question from the current page
            function askQuestion(questionsGroupedByPage) {
                const questionsOnPage = questionsGroupedByPage[currentPage];
                if (!questionsOnPage || questionsOnPage.length === 0) {
                    moveToNextPage();
                    return;
                }

                // Select a random question from the current page
                const question = questionsOnPage[Math.floor(Math.random() * questionsOnPage.length)];
                currentQuestionGuid = question.guid;
                currentThreshold = question.threshold || 70;

                // Display the question
                $("#question-text").html(question.question_fix);
                
                // Clear previous answer
                tinymce.get("user-input").setContent('');
                $("#send-button").prop("disabled", true);
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


            // Submit user's answer
            $("#send-button").on("click", function() {
                if (isSubmitting) return;

                // Get content from TinyMCE
                const userAnswer = tinymce.get("user-input").getContent();
                if (!userAnswer.trim()) {
                    toastr.error("Jawaban tidak boleh kosong!");
                    return;
                }

                isSubmitting = true;

                // Show loading state
                $("#send-button").html('<i class="fas fa-spinner fa-spin me-2"></i>Mengirim...');
                $("#send-button").prop("disabled", true);
                showLoading();

                // Send answer to the API
                $.ajax({
                    type: "POST",
                    url: `${apiUrl}/api/v1/assignment/submit`,
                    data: JSON.stringify({
                        user_id: userId,
                        topic_guid: topicGuid,
                        question_guid: currentQuestionGuid,
                        answer: userAnswer,
                        page: currentPage,

                    }),
                    contentType: "application/json",

                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(response) {
                        if (response.status) {
                            currentPage = response.nextPage;

                            checkPlagiarism(userId, topicGuid, currentQuestionGuid, response
                                .data
                                .user_answer_guid, userAnswer, response);

                        } else {
                            isSubmitting = false;
                            hideLoading();
                            $("#send-button").html(
                                '<i class="fas fa-paper-plane me-2"></i>Kirim Jawaban');
                            $("#send-button").prop("disabled", false);
                            toastr.error(response.message ||
                                "Terjadi kesalahan saat mengirim jawaban.");
                        }

                    },
                    error: function(xhr) {
                        isSubmitting = false;
                        hideLoading();
                        $("#send-button").html(
                            '<i class="fas fa-paper-plane me-2"></i>Kirim Jawaban');
                        $("#send-button").prop("disabled", false);

                        toastr.error("Gagal mengirim jawaban. Silakan coba lagi.");
                        console.error("Error submitting answer:", xhr);

                    }
                });
            });

            // Right after document.ready, add this logic:
            function checkForExistingProgress() {
                showLoading();

                // Check if user has history for this topic
                $.ajax({
                    type: "GET",
                    url: `${apiUrl}/api/v1/assignment/history/${userId}/${topicGuid}`,
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(response) {
                        if (response.success && response.data && response.data.length > 0) {
                            // User has history, get the language from the first item
                            const userLanguage = response.data[0]
                                .language; // Assuming language is stored

                            // Select that language in dropdown
                            $("#language").val(userLanguage);
                            selectedLanguage = userLanguage;

                            // Automatically start the test
                            $(".language-selection").hide();
                            showHistorySection();
                            fetchQuestions()
                                .then(response => {
                                    if (response.data && response.data.length > 0) {
                                        // Group questions by page number
                                        questionsGroupedByPage = response.data.reduce((acc,
                                            question) => {
                                            const page = parseInt(question.page, 10) || 1;
                                            if (!acc[page]) acc[page] = [];
                                            acc[page].push(question);
                                            return acc;
                                        }, {});

                                        // Find highest page number
                                        highestPage = Math.max(...Object.keys(
                                            questionsGroupedByPage).map(page =>
                                            parseInt(page, 10)));

                                        // Display the first question
                                        $("#question-box").show();
                                        $("#assignment-container").show();
                                        tinymce.get("user-input").mode.set("design");

                                        askQuestion(questionsGroupedByPage);
                                    } else {
                                        toastr.error(
                                            "No questions available for the selected language.");
                                        $("#language-selection").show();
                                    }
                                    hideLoading();
                                })
                                .catch(error => {
                                    console.error("Error fetching questions:", error);
                                    toastr.error("Failed to fetch questions.");
                                    $("#language-selection").show();
                                    hideLoading();
                                });
                        }

                        hideLoading();
                    },
                    error: function(error) {
                        console.error("Error checking history:", error);
                        hideLoading();
                    }
                });
            }

            // Call this function on page load
            checkForExistingProgress();

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
                        $("#send-button").html('<i class="fas fa-paper-plane me-2"></i>Kirim Jawaban');
                        $("#send-button").prop("disabled", false);
                        console.log(plagiarismResponse.data);
                        if (plagiarismResponse.success) {
                            window.location.href =
                                `/evaluation/${ questionGuid }/${userAnswerGuid}`;
                        } else {
                            toastr.warning(plagiarismResponse.message ||
                                "Terjadi masalah saat memeriksa plagiarisme, tetapi evaluasi tetap tersedia."
                            );

                            setTimeout(() => {
                                window.location.href =
                                    `/evaluation/${questionGuid}/${userAnswerGuid}`;
                            }, 2000);
                        }
                        refreshHistory();





                    },
                    error: function(xhr) {
                        console.error("Error checking plagiarism:", xhr);

                        // Even if plagiarism check fails, continue with the flow
                        isSubmitting = false;
                        hideLoading();
                        $("#send-button").html('<i class="fas fa-paper-plane me-2"></i>Kirim Jawaban');
                        $("#send-button").prop("disabled", false);

                        toastr.warning("Gagal memeriksa plagiarisme, tetapi evaluasi tetap tersedia.");
                        setTimeout(() => {
                            window.location.href =
                                `/evaluation/${questionGuid}/${userAnswerGuid}`;
                        }, 2000);
                    }
                });
            }


            function refreshHistory() {
                fetchHistoryData();
            }


            function showHistorySection() {
                $("#history-section").show();
                fetchHistoryData();
            }

            // Fetch history data from API
            function fetchHistoryData() {
                showLoading();
                $.ajax({
                    type: "GET",
                    url: `${apiUrl}/api/v1/assignment/history/${userId}/${topicGuid}`,
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(response) {
                        hideLoading();
                        if (response.success && response.data && response.data.length > 0) {
                            renderHistoryItems(response.data);
                            $("#no-history-message").hide();
                        } else {
                            $("#history-list").html(`
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-history fa-2x mb-2"></i>
                        <p>Belum ada riwayat jawaban untuk topik ini.</p>
                    </div>
                `);
                        }
                    },
                    error: function(error) {
                        hideLoading();
                        console.error("Error fetching history:", error);
                        toastr.error("Gagal mengambil data riwayat jawaban.");
                    }
                });
            }

            // Modify the renderHistoryItems function to make the entire chat message clickable instead of using a button
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



                    chatMessagesEl.append(`
            <div class="chat-message" 
                 data-question-guid="${item.question_guid}" 
                 data-answer-guid="${item.guid}"
                 style="cursor: pointer;">
                <div class="message-header">
                    <span class="message-timestamp">${formattedDate}</span>
                    <span class="message-category"> ${item.category || 'Umum'}</span>
                </div>
                <div class="message-question">
                    <div class="question-bubble">
                        ${item.question}
                    </div>
                </div>
                
            </div>
        `);
                });

                // <div class="message-footer">
                //     <div class="message-meta">
                //         <span class="badge ${scoreClass}">Similarity: ${percentageSimilarity}%</span>
                //     </div>
                // </div>

                // Add event listener for the entire chat message
                $(".chat-message").on("click", function() {
                    const questionGuid = $(this).data("question-guid");
                    const answerGuid = $(this).data("answer-guid");
                    navigateToEvaluation(questionGuid, answerGuid);
                });
            }

            // Also update the addNewHistoryItem function to match this behavior
            function addNewHistoryItem(item) {
                const historyListElement = $("#history-list");
                const index = $(".history-card").length + 1;

                const formattedDate = new Date(item.created_at).toLocaleString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });


                // Create new chat message item that's clickable
                const newHistoryItem = $(`
        <div class="chat-message" 
             data-question-guid="${item.question_guid}" 
             data-answer-guid="${item.guid}"
             style="cursor: pointer; display:none;">
            <div class="message-header">
                <span class="message-timestamp">${formattedDate}</span>
                <span class="message-category">Category: ${item.category || 'Umum'}</span>
            </div>
            <div class="message-question">
                <div class="question-bubble">
                    ${item.question}
                </div>
            </div>
            <div class="message-footer">
                <div class="message-meta">
                    <span class="badge ${scoreClass}">Similarity: ${percentageSimilarity}%</span>
                </div>
            </div>
        </div>
    `);

                // Hide "no history" message if present
                $("#no-history-message").hide();

                // Make sure chat-messages container exists
                if ($("#chat-messages").length === 0) {
                    historyListElement.html(`
            <div class="chat-history-container">
                <div class="chat-messages" id="chat-messages"></div>
            </div>
        `);
                }

                // Prepend and show with animation
                $("#chat-messages").prepend(newHistoryItem);
                newHistoryItem.fadeIn(500);

                // Add event listener for clicking the message
                newHistoryItem.on("click", function() {
                    const qGuid = $(this).data("question-guid");
                    const aGuid = $(this).data("answer-guid");
                    navigateToEvaluation(qGuid, aGuid);
                });
            }



            // Navigate to evaluation page
            function navigateToEvaluation(questionGuid, answerGuid) {
                window.location.href = `/evaluation/${questionGuid}/${answerGuid}`;
            }


        });
    </script>
@endsection
