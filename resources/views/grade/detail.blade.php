@extends('layouts.template')
@section('vendor-css')
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-checkboxes-jquery/datatables.checkboxes.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        .input-underline {
            border: none;
            border-bottom: 2px solid #dee2e6;
            border-radius: 0;
            outline: none;
            transition: border-color 0.3s ease;
            margin-bottom: 10px;
        }

        .input-underline:focus {
            border-bottom: 2px solid #0d6efd;
            box-shadow: none;
        }

        .profile-card {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .profile-info {
            display: flex;
            align-items: center;
        }


        .card-header .nav-tabs {
            border-bottom: none;
        }

        .card-header .nav-link {
            border: none;
            color: #6c757d;
            padding: 0.75rem 1rem;
            font-weight: 500;
        }

        .card-header .nav-link.active {
            color: #495057;
            background-color: transparent;
            border-bottom: 3px solid #0d6efd;
        }

        .answer-card {
            margin-bottom: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 6px;
        }

        .answer-card.correct {
            border-left: 4px solid #198754;
        }

        .answer-card.incorrect {
            border-left: 4px solid #dc3545;
        }

        .plagiarism-alert {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 6px;
            color: #856404;
            padding: 1rem;
        }

        .lecturer-score-form {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }

        .scores-section {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.75rem;
        }

        .score-item {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            color: #495057;
        }

        .score-item strong {
            color: #212529;
        }

        .attempt-tag {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            background-color: #e9ecef;
            color: #495057;
            margin-left: 0.5rem;
            font-weight: 500;
        }

        .expected-answer {
            background-color: #f8f9fa;
            padding: 1rem;
            margin-top: 1rem;
            border-radius: 6px;
            border-left: 4px solid #0d6efd;
        }

        .question-text {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: #212529;
        }

        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }

        .btn-evaluation {
            margin-left: 0.5rem;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-indicator.correct {
            color: #198754;
        }

        .status-indicator.incorrect {
            color: #dc3545;
        }

        .highest-level-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .highest-level-text {
            color: #6c757d;
            font-size: 0.875rem;
            margin: 0;
        }

        .highest-level-value {
            color: #212529;
            font-weight: 600;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .profile-card {
                padding: 1rem;
            }

            .profile-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

        
            .card-header .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
            }

            .btn-evaluation {
                margin-left: 0;
                margin-top: 1rem;
                width: 100%;
            }

            .scores-section {
                flex-direction: column;
                gap: 0.5rem;
            }

            .lecturer-score-form .input-group {
                max-width: 100% !important;
            }
        }

        @media (max-width: 576px) {
            .profile-card .d-flex {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .question-text {
                font-size: 1rem;
            }

            .expected-answer {
                padding: 0.75rem;
            }
        }
    </style>
@endsection

@section('info-page')
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark"
                href="/grade/{{ $code }}/{{ $guid }}">Grade/{{ $name }}</a></li>
        <li class="breadcrumb-item text-sm text-dark active text-capitalize" aria-current="page">Answer Details</li>
    </ol>
    <h5 class="font-weight-bolder mb-0 text-capitalize">Student Answer Details</h5>
@endsection

@section('content')
    <main class="main-content position-relative max-height-vh-100 h-100 mt-1 border-radius-lg">
        <div class="container-xxl flex-grow-1 container-p-y">
            <!-- Student Profile Card -->
            <div class="card profile-card" id="profile-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="profile-info">
                  
                        <div>
                            <h4 class="mb-1" id="student-name">Loading...</h4>
                            <p class="mb-1" id="student-email">Loading...</p>
                            <p class="mb-0 text-muted" id="student-id">Loading...</p>
                            <div class="highest-level-container">
                                <span class="highest-level-text">Highest Level:</span>
                                <span class="highest-level-value" id="highest-level">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-column flex-md-row gap-2">
                        <a href="/evaluation/{{ $code }}/{{ $guid }}/{{ $userId }}"
                            class="btn btn-primary btn-evaluation">
                            <i class="fa-solid fa-chart-line me-1"></i> View Evaluation
                        </a>
                        @isRole(['admin', 'lecturer', 'assistant'])
                            <button type="button" class="btn btn-outline-danger"
                                onclick="resetHistories('{{ $userId }}', '{{ $guid }}')">
                                <i class="fa-solid fa-rotate-left me-1"></i> Reset Progress
                            </button>
                        @endisRole
                    </div>
                </div>
            </div>

            <!-- Answers by Level -->
            <div class="card" id="answers-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <ul class="nav nav-tabs flex-grow-1" id="levelTabs" role="tablist">
                            <!-- Tabs will be populated by JS -->
                        </ul>
                        <div class="btn-group" role="group" aria-label="Filter answers">
                            <button type="button" class="btn btn-sm btn-primary" id="filter-all">All Answers</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="filter-correct">Correct Only</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="filter-incorrect">Incorrect Only</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="levelTabsContent">
                        <!-- Tab panes will be populated by JS -->
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal for viewing answer details -->
    <div class="modal fade" id="answerDetailModal" tabindex="-1" aria-labelledby="answerDetailModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="answerDetailModalLabel">Answer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="answerDetailModalBody">
                    <!-- Content will be populated by JS -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('vendor-javascript')
    <script src="{{ asset('./assets/dashboard/datatables/jquery.dataTables.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('custom-javascript')
    <script type="text/javascript">
        // Function to reset student histories
        function resetHistories(userId, topicGuid) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will reset the user's progress and answers for this topic!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, reset it!',
                cancelButtonText: 'No, cancel!',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ env('URL_API') }}/api/v1/assignment/reset-histories",
                        type: "POST",
                        data: {
                            user_id: userId,
                            topic_guid: topicGuid,
                        },
                        beforeSend: function(request) {
                            request.setRequestHeader("Authorization", "Bearer {{ $token }}");
                        },
                        success: function(response) {
                            toastr.options.closeButton = true;
                            toastr.options.timeOut = 3000;
                            toastr.success('Progress has been successfully reset!');
                            setTimeout(function() {
                                window.location.reload();
                            }, 1500);
                        },
                        error: function(xhr) {
                            toastr.options.closeButton = true;
                            toastr.options.timeOut = 3000;
                            toastr.error('Failed to reset progress. Please try again.');
                            console.error(xhr.responseText);
                        }
                    });
                }
            });
        }

        // Function to update lecturer score
        function updateLecturerScore(answerGuid, formId) {
            const lecturerScore = $(`#${formId} input[name="lecturer_score"]`).val();

            if (!lecturerScore) {
                toastr.error('Please enter a score between 0 and 100');
                return;
            }

            if (isNaN(lecturerScore) || lecturerScore < 0 || lecturerScore > 100) {
                toastr.error('Score must be between 0 and 100');
                return;
            }

            $.ajax({
                url: "{{ env('URL_API') }}/api/v1/grade/lecturer-score",
                type: "POST",
                data: {
                    answer_guid: answerGuid,
                    lecturer_score: lecturerScore
                },
                beforeSend: function(request) {
                    request.setRequestHeader("Authorization", "Bearer {{ $token }}");
                },
                success: function(response) {
                    toastr.success('Score successfully updated');
                    $(`#${formId} button[type="submit"]`).prop('disabled', true).html(
                        'Saved <i class="fa-solid fa-check ms-1"></i>');
                    $(`#lecturer-score-${answerGuid}`).text(lecturerScore);
                    setTimeout(function() {
                        $(`#${formId} button[type="submit"]`).prop('disabled', false).html(
                            'Save Score');
                    }, 2000);
                },
                error: function(xhr) {
                    toastr.error('Failed to update score. Please try again.');
                    console.error(xhr.responseText);
                }
            });
        }

        // Function to show answer details in modal
        function showAnswerDetail(answerGuid, answerText, questionText, attemptNumber, isCorrect, evaluationScore,
            lecturerScore, createdAt) {
            const modalBody = $('#answerDetailModalBody');
            const correctBadge = isCorrect ?
                '<span class="badge bg-success">Correct</span>' :
                '<span class="badge bg-danger">Incorrect</span>';

            let scoreHtml = '';
            if (evaluationScore !== null) {
                scoreHtml += `<div class="score-item">Evaluation Score: <strong>${evaluationScore}</strong></div>`;
            }
            if (lecturerScore !== null) {
                scoreHtml += `<div class="score-item">Lecturer Score: <strong>${lecturerScore}</strong></div>`;
            }

            modalBody.html(`
                <div class="mb-3">
                    <h6>Question:</h6>
                    <p>${questionText}</p>
                </div>
                <div class="mb-3">
                    <h6>Answer:</h6>
                    <div class="p-3 bg-light rounded">${answerText}</div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        ${correctBadge}
                        <span class="ms-2 text-muted">Attempt #${attemptNumber}</span>
                    </div>
                    <div>
                        <small class="text-muted">${new Date(createdAt).toLocaleString()}</small>
                    </div>
                </div>
                <div class="scores-section">
                    ${scoreHtml}
                </div>
            `);

            const modal = new bootstrap.Modal(document.getElementById('answerDetailModal'));
            modal.show();
        }

        $(document).ready(function() {
            // Initialize toastr options
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: "toast-top-right",
                timeOut: 3000
            };

            // Get parameters from URL
            const urlParams = new URL(window.location.href);
            const pathSegments = urlParams.pathname.split('/');
            const courseCode = pathSegments[3];
            const topicGuid = pathSegments[4];
            const userId = pathSegments[5];

            // Add filter variables
            let currentFilter = 'all';

            // Add filter event handlers
            $('#filter-all').click(function() {
                $(this).addClass('btn-primary').removeClass('btn-outline-secondary');
                $('#filter-correct, #filter-incorrect').addClass('btn-outline-secondary').removeClass('btn-primary');
                currentFilter = 'all';
                applyFilter();
            });

            $('#filter-correct').click(function() {
                $(this).addClass('btn-primary').removeClass('btn-outline-secondary');
                $('#filter-all, #filter-incorrect').addClass('btn-outline-secondary').removeClass('btn-primary');
                currentFilter = 'correct';
                applyFilter();
            });

            $('#filter-incorrect').click(function() {
                $(this).addClass('btn-primary').removeClass('btn-outline-secondary');
                $('#filter-all, #filter-correct').addClass('btn-outline-secondary').removeClass('btn-primary');
                currentFilter = 'incorrect';
                applyFilter();
            });

            // Function to apply filter
            function applyFilter() {
                if (currentFilter === 'all') {
                    $('.answer-card').show();
                } else if (currentFilter === 'correct') {
                    $('.answer-card.correct').show();
                    $('.answer-card.incorrect').hide();
                } else if (currentFilter === 'incorrect') {
                    $('.answer-card.correct').hide();
                    $('.answer-card.incorrect').show();
                }
            }

            // Fetch student answer details
            $.ajax({
                url: `{{ env('URL_API') }}/api/v1/grade/student-details/${courseCode}/${topicGuid}/${userId}`,
                type: "GET",
                beforeSend: function(request) {
                    request.setRequestHeader("Authorization", "Bearer {{ $token }}");
                },
                success: function(response) {
                    displayStudentProfile(response.profile);
                    $('#highest-level').text(response.highest_level);
                    populateLevelTabs(response.data);
                    applyFilter();
                },
                error: function(xhr) {
                    toastr.error('Failed to load student details. Please try again.');
                    console.error(xhr.responseText);
                }
            });

            // Function to display student profile information
            function displayStudentProfile(profile) {
                const initials = profile.name.split(' ').map(n => n[0]).join('').toUpperCase();
                $('#student-name').text(profile.name);
                $('#student-email').text(profile.email);
                $('#student-id').text(`NRP: ${profile.user_id}`);
            }

            // Function to populate level tabs
            function populateLevelTabs(levelData) {
                const tabsContainer = $('#levelTabs');
                const tabContentContainer = $('#levelTabsContent');

                tabsContainer.empty();
                tabContentContainer.empty();

                if (levelData.length === 0) {
                    tabContentContainer.html(`
                        <div class="empty-state">
                            <i class="fa-solid fa-clipboard-question"></i>
                            <h5>No Answers Yet</h5>
                            <p>This student hasn't submitted any answers for this topic yet.</p>
                        </div>
                    `);
                    return;
                }

                levelData.forEach((level, index) => {
                    const isActive = index === 0 ? 'active' : '';

                    tabsContainer.append(`
                        <li class="nav-item" role="presentation">
                            <button class="nav-link ${isActive}" id="tab-${level.level}" data-bs-toggle="tab"
                                    data-bs-target="#content-${level.level}" type="button" role="tab"
                                    aria-controls="content-${level.level}" aria-selected="${index === 0}">
                                ${level.level_title}
                                <span class="badge bg-secondary rounded-pill ms-1">${level.questions.length}</span>
                            </button>
                        </li>
                    `);

                    const contentHtml = $(`
                        <div class="tab-pane fade show ${isActive}" id="content-${level.level}" role="tabpanel"
                             aria-labelledby="tab-${level.level}">
                        </div>
                    `);

                    if (level.questions.length > 0) {
                        level.questions.forEach((question) => {
                            const questionCard = $(`
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="question-text">${question.question}</h6>
                                    </div>
                                    <div class="card-body">
                                        <h6>Attempts (${question.answers.length}):</h6>
                                        <div class="answer-list">
                                            <!-- Answers will be added here -->
                                        </div>
                                        <div class="expected-answer">
                                            <h6>Expected Answer:</h6>
                                            <p>${question.expected_answer}</p>
                                        </div>
                                        ${question.plagiarism_check ? createPlagiarismAlert(question.plagiarism_check) : ''}
                                    </div>
                                </div>
                            `);

                            const answerList = questionCard.find('.answer-list');

                            question.answers.forEach((answer) => {
                                const answerClass = answer.is_correct ? 'correct' : 'incorrect';
                                const statusText = answer.is_correct ? 'Correct' : 'Incorrect';
                                const statusClass = answer.is_correct ? 'correct' : 'incorrect';

                                let scoresHtml = '';
                                if (answer.evaluation_score !== null) {
                                    scoresHtml += `<div class="score-item">Evaluation Score: <strong>${answer.evaluation_score}</strong></div>`;
                                }
                                if (answer.lecturer_score !== null) {
                                    scoresHtml += `<div class="score-item">Lecturer Score: <strong id="lecturer-score-${answer.answer_guid}">${answer.lecturer_score}</strong></div>`;
                                }

                                const answerCard = $(`
                                    <div class="card answer-card ${answerClass}">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="d-flex align-items-center">
                                                    <span class="status-indicator ${statusClass}">${statusText}</span>
                                                    <span class="attempt-tag">Attempt #${answer.attempt_number}</span>
                                                    <button class="btn btn-sm btn-outline-primary ms-2"
                                                        onclick="showAnswerDetail('${answer.answer_guid}', 
                                                    '${escapeHtml(answer.answer_text)}', 
                                                    '${escapeHtml(question.question)}', 
                                                    ${answer.attempt_number}, 
                                                    ${answer.is_correct}, 
                                                    ${answer.evaluation_score}, 
                                                    ${answer.lecturer_score}, 
                                                    '${answer.created_at}')">
                                                        View Full Answer
                                                    </button>
                                                </div>
                                                <small class="text-muted">${formatDate(answer.created_at)}</small>
                                            </div>
                                            <p class="mb-0">${truncateText(answer.answer_text, 150)}</p>
                                            <div class="scores-section">
                                                ${scoresHtml}
                                            </div>
                                            ${createLecturerScoreForm(answer.answer_guid, answer.lecturer_score)}
                                        </div>
                                    </div>
                                `);

                                answerList.append(answerCard);
                            });

                            contentHtml.append(questionCard);
                        });
                    } else {
                        contentHtml.html(`
                            <div class="empty-state">
                                <i class="fa-solid fa-clipboard-question"></i>
                                <h5>No Answers for ${level.level_title} Level</h5>
                                <p>The student hasn't submitted any answers for this level yet.</p>
                            </div>
                        `);
                    }

                    tabContentContainer.append(contentHtml);
                });
            }

            // Helper function to create plagiarism alert
            function createPlagiarismAlert(plagiarismData) {
                const weightedAverage = (
                    (plagiarismData.bert_score * 0.4) +
                    (plagiarismData.cosine_similarity * 0.15) +
                    (plagiarismData.jaccard_similarity * 0.15) +
                    (plagiarismData.levenshtein_similarity || 0) * 0.05 +
                    (plagiarismData.ngram_similarity || 0) * 0.25
                ) * 100;

                if (weightedAverage < 70) {
                    return '';
                }

                const strategies = plagiarismData.detected_strategies.join(', ');
                return `
                    <div class="alert plagiarism-alert mt-3">
                        <h6><i class="fa-solid fa-triangle-exclamation me-2"></i>Potential AI Usage Detected</h6>
                        <p class="mb-2">Our system detected potential AI usage in this answer with the following strategies:</p>
                        <p class="mb-2"><strong>Strategies:</strong> ${strategies}</p>
                        <div class="row">
                            <div class="col-md-3">
                                <p class="mb-0"><strong>BERT Score:</strong> ${(plagiarismData.bert_score * 100).toFixed(1)}%</p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-0"><strong>Cosine Similarity:</strong> ${(plagiarismData.cosine_similarity * 100).toFixed(1)}%</p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-0"><strong>Jaccard Similarity:</strong> ${(plagiarismData.jaccard_similarity * 100).toFixed(1)}%</p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-0"><strong>Overall Similarity:</strong> ${weightedAverage.toFixed(1)}%</p>
                            </div>
                        </div>
                    </div>
                `;
            }

            function createLecturerScoreForm(answerGuid, currentScore) {
                const formId = `lecturer-score-form-${answerGuid}`;
                return `
                    @isRole(['admin', 'lecturer', 'assistant'])
                    <form id="${formId}" class="lecturer-score-form" onsubmit="event.preventDefault(); updateLecturerScore('${answerGuid}', '${formId}')">
                        <div class="row g-2 align-items-end">
                            <div class="col-auto">
                                <label class="form-label small">Lecturer Score (0-100)</label>
                                <input type="number" class="form-control form-control-sm input-underline" name="lecturer_score"
                                       min="0" max="100" placeholder="0-100" value="${currentScore !== null ? currentScore : ''}" style="width: 120px;">
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-sm btn-primary" type="submit">
                                    ${currentScore !== null ? 'Update' : 'Save Score'}
                                </button>
                            </div>
                        </div>
                    </form>
                    @endisRole
                `;
            }

            // Helper function to truncate text
            function truncateText(text, maxLength) {
                if (text.length <= maxLength) return text;
                return text.substr(0, maxLength) + '...';
            }

            // Helper function to format date
            function formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleString();
            }

            // Helper function to escape HTML
            function escapeHtml(unsafe) {
                return unsafe
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        });

    </script>
@endsection