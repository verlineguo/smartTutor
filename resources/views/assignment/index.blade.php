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
    <div class="language-selection">
        <label for="language">Pilih Bahasa:</label>
        <select id="language" class="form-control">
            <option value="">Select Language</option>
        </select>
        <button id="start-test" class="btn btn-primary mt-2" disabled>Start Test</button>
    </div>
    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-8 d-flex flex-column">
                <div class="question-box" id="question-box">
                    <h6 class="mb-3">Pertanyaan:</h6>
                    <div id="question-text"></div>
                    <div class="question-meta" id="question-meta"></div>
                </div>

                <!-- Assignment Container -->
                <div class="assignment-container" id="assignment-container">
                    <h6 class="mb-3">Jawaban Anda:</h6>
                    <div class="mb-3">
                        <textarea id="user-input" class="form-control" rows="6" placeholder="Ketik jawaban Anda di sini..." disabled></textarea>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <button class="btn btn-primary" id="send-button" disabled>
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
            let currentPage = 1;
            let currentQuestionGuid = null;
            let questionsGroupedByPage = {};
            let highestPage = 0;
            let selectedLanguage = null;
            let currentThreshold = 0;
            let isSubmitting = false;
            let availableRegenerationAttempts = 0;



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



            // Fetch available languages for the topic
            function fetchLanguages() {
                showLoading();
                return $.ajax({
                    type: "GET",
                    url: `${apiUrl}/api/v1/assignment/languages/${topicGuid}`,
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    complete: function() {
                        hideLoading();
                    }
                });
            }

            // Initialize language dropdown
            fetchLanguages()
                .then(response => {
                    const languageDropdown = $("#language");
                    if (response.data && response.data.length > 0) {
                        response.data.forEach(language => {
                            languageDropdown.append(
                                `<option value="${language}">${language}</option>`
                            );
                        });

                        // Enable language selection change event
                        $("#language").on("change", function() {
                            selectedLanguage = $(this).val();
                            $("#start-test").prop("disabled", !selectedLanguage);
                        });
                    } else {
                        languageDropdown.append(
                            `<option value="">No languages available</option>`
                        );
                        toastr.warning("No languages available for this topic.");
                    }
                })
                .catch(error => {
                    console.error("Error fetching languages:", error);
                    toastr.error("Failed to fetch available languages.");
                });

            // Start the assignment test
            $("#start-test").on("click", function() {
                if (!selectedLanguage) {
                    toastr.warning("Please select a language first.");
                    return;
                }
                $(".language-selection").hide();
                showHistorySection();
                showLoading();

                // Fetch questions for the selected language
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
            });

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
                $("#question-meta").html(`
                    <strong>Page: </strong> ${currentPage}/${highestPage} | 
                    <strong>Category:</strong> ${question.category || 'Umum'} | 
                    <strong>Threshold:</strong> ${currentThreshold}
                `);

                // Clear previous answer
                tinymce.get("user-input").setContent('');
                $("#send-button").prop("disabled", true);
            }

            // Move to the next page of questions
            function moveToNextPage() {
                currentPage++;
                if (currentPage > highestPage) {
                    endAssignment();
                } else {
                    askQuestion(questionsGroupedByPage);
                }
            }

            // Handle end of assignment
            function endAssignment() {
                $("#question-box").hide();
                $("#assignment-container").hide();

                Swal.fire({
                    title: 'Selamat!',
                    text: 'Anda telah menyelesaikan semua pertanyaan dalam assignment ini.',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'Lihat Riwayat',
                    cancelButtonText: 'Tutup'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Redirect to history page or show history
                        window.location.href = `/assignment/history/${topicGuid}`;
                    }
                });

            }

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
                        console.log(response);
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
                                `http://127.0.0.1:8003/evaluation/${ questionGuid }/${userAnswerGuid}`;
                        } else {
                            toastr.error(plagiarismResponse.message ||
                                "Terjadi kesalahan saat memeriksa plagiarisme.");
                        }




                    },
                    error: function(xhr) {
                        console.error("Error checking plagiarism:", xhr);

                        // Even if plagiarism check fails, continue with the flow
                        isSubmitting = false;
                        hideLoading();
                        $("#send-button").html('<i class="fas fa-paper-plane me-2"></i>Kirim Jawaban');
                        $("#send-button").prop("disabled", false);

                    }
                });
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

            // Render history items
            function renderHistoryItems(historyData) {
                const historyListElement = $("#history-list");
                historyListElement.empty();

                historyData.forEach((item, index) => {
                    const formattedDate = new Date(item.created_at).toLocaleString('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    const scoreClass = getSimilarityScoreClass(item.cosine_similarity);
                    const percentageSimilarity = (item.cosine_similarity * 100).toFixed(2);

                    historyListElement.append(`
            <div class="history-card mb-3">
                <div class="history-header">
                    <span class="fw-bold">Pertanyaan ${index + 1} (Page ${item.page})</span>
                    <span class="history-date text-muted">${formattedDate}</span>
                </div>
                <div class="history-content">
                    <div class="history-question mb-3">
                        <strong>Pertanyaan:</strong>
                        <div class="mt-2">${item.question}</div>
                    </div>
                    <div class="history-answer">
                        <strong>Jawaban Anda:</strong>
                        <div class="mt-2">${truncateAnswer(item.answer)}</div>
                    </div>
                </div>
                <div class="history-footer">
                    <div>
                        <span class="badge ${scoreClass}">Similarity: ${percentageSimilarity}%</span>
                        <span class="badge bg-secondary ms-1">Category: ${item.category || 'Umum'}</span>
                    </div>
                    <button class="btn btn-sm btn-primary view-evaluation" 
                            data-question-guid="${item.question_guid}" 
                            data-answer-guid="${item.guid}">
                        <i class="fas fa-eye me-1"></i>Lihat Evaluasi
                    </button>
                </div>
            </div>
        `);
                });

                // Add event listener for view evaluation buttons
                $(".view-evaluation").on("click", function() {
                    const questionGuid = $(this).data("question-guid");
                    const answerGuid = $(this).data("answer-guid");
                    navigateToEvaluation(questionGuid, answerGuid);
                });
            }

            // Get appropriate CSS class based on similarity score
            function getSimilarityScoreClass(score) {
                const percentage = score * 100;
                if (percentage >= 80) return "bg-success";
                if (percentage >= 60) return "bg-primary";
                if (percentage >= 40) return "bg-warning";
                return "bg-danger";
            }

            // Truncate long answers for display
            function truncateAnswer(answer, maxLength = 200) {
                // Remove HTML tags for display in the history card
                const plainText = answer.replace(/<[^>]*>/g, ' ');
                if (plainText.length <= maxLength) return answer;
                return plainText.substring(0, maxLength) +
                    '... <span class="text-muted">(klik Lihat Evaluasi untuk detail)</span>';
            }

            // Navigate to evaluation page
            function navigateToEvaluation(questionGuid, answerGuid) {
                window.location.href = `/evaluation/${questionGuid}/${answerGuid}`;
            }


        });
    </script>
@endsection
