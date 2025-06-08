@extends('layouts.template')
@section('vendor-css')
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-checkboxes-jquery/datatables.checkboxes.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        .level-badge {
            margin-right: 10px;
        }

        .badge-remembering {
            background-color: #17a2b8;
        }

        .badge-understanding {
            background-color: #28a745;
        }

        .badge-applying {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-analyzing {
            background-color: #dc3545;
        }

        .input-underline {
            border: none;
            /* Hapus semua border */
            border-bottom: 2px solid #dee2e6;
            /* Tambahkan border bawah */
            border-radius: 0;
            /* Hapus border radius */
            outline: none;
            /* Hapus outline saat fokus */
            transition: border-color 0.3s ease;
            /* Animasi untuk perubahan warna border */
            margin-bottom: 10px;
            /* Tambahkan jarak bawah */
        }

        .input-underline:focus {
            border-bottom: 2px solid #dee2e6;
            /* Ubah warna border saat fokus */
            box-shadow: none;
            /* Hapus efek shadow */
        }

        .profile-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .profile-info {
            display: flex;
            align-items: center;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #6c757d;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-right: 20px;
        }

        .progress-section {
            margin-top: 20px;
        }

        .progress {
            height: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        .card-header .nav-tabs {
            border-bottom: none;
        }

        .card-header .nav-link {
            border: none;
            color: #6c757d;
            padding: 0.5rem 1rem;
        }

        .card-header .nav-link.active {
            color: #495057;
            background-color: transparent;
            border-bottom: 3px solid #007bff;
        }

        .answer-card {
            margin-bottom: 15px;
            border-left: 4px solid transparent;
        }

        .answer-card.correct {
            border-left-color: #28a745;
        }

        .answer-card.incorrect {
            border-left-color: #dc3545;
        }

        .plagiarism-alert {
            background-color: #fff3cd;
            border-color: #ffeeba;
            color: #856404;
        }

        .lecturer-score-form {
            margin-top: 15px;
            padding-top: 15px;
        }

        .scores-section {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .score-badge {
            padding: 5px 10px;
            border-radius: 5px;
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .score-badge i {
            font-size: 0.8rem;
        }

        .ai-score {
            background-color: #6f42c1;
        }

        .lecturer-score {
            background-color: #fd7e14;
        }

        .attempt-tag {
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 4px;
            background-color: #6c757d;
            color: white;
            margin-left: 10px;
        }

        .expected-answer {
            background-color: #e9ecef;
            padding: 15px;
            margin-top: 15px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }

        .question-text {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 15px;
        }

        .empty-state {
            padding: 30px;
            text-align: center;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .btn-evaluation {
            margin-left: 10px;
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
                <div class="d-flex justify-content-between align-items-center">
                    <div class="profile-info">
                        <div class="profile-avatar" id="profile-avatar">
                            <!-- Initial will be populated by JS -->
                        </div>
                        <div>
                            <h4 class="mb-1" id="student-name">Loading...</h4>
                            <p class="mb-0" id="student-email">Loading...</p>
                            <p class="mb-0 text-muted" id="student-id">Loading...</p>
                        </div>
                    </div>
                    <div>
                        <a href="/evaluation/{{ $code }}/{{ $guid }}/{{ $userId }}"
                            class="btn btn-primary btn-evaluation">
                            <i class="fa-solid fa-chart-line"></i> View Evaluation
                        </a>
                        @isRole(['admin', 'lecturer', 'assistant'])
                            <button type="button" class="btn btn-danger"
                                onclick="resetHistories('{{ $userId }}', '{{ $guid }}')">
                                <i class="fa-solid fa-rotate-left"></i> Reset Progress
                            </button>
                        @endisRole
                    </div>
                </div>
                <div class="progress-section">

                    <div class="mt-3 text-end">
                        <span id="highest-level" class="badge bg-primary">Loading...</span>
                    </div>
                </div>
            </div>

            <!-- Answers by Level -->
            <div class="card" id="answers-card">
                <div class="card-header">
                    <ul class="nav nav-tabs" id="levelTabs" role="tablist">
                        <!-- Tabs will be populated by JS -->
                    </ul>
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
            // Use SweetAlert2 for confirmation dialog
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
                            // Reload the page after a short delay
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
                        'Saved <i class="fa-solid fa-check"></i>');
                    $(`#lecturer-score-badge-${answerGuid}`).text(lecturerScore);
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
                '<span class="badge bg-success"><i class="fa-solid fa-check"></i> Correct</span>' :
                '<span class="badge bg-danger"><i class="fa-solid fa-xmark"></i> Incorrect</span>';

            let scoreHtml = '';
            if (evaluationScore !== null) {
                scoreHtml +=
                    `<div class="score-badge ai-score"><i class="fa-solid fa-robot"></i> AI Score: ${evaluationScore}</div>`;
            }
            if (lecturerScore !== null) {
                scoreHtml +=
                    `<div class="score-badge lecturer-score"><i class="fa-solid fa-user-tie"></i> Lecturer Score: ${lecturerScore}</div>`;
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
            let currentFilter = 'all'; // Default show all answers (options: 'all', 'correct', 'incorrect')

            // Add filter controls to the page
            $('#answers-card .card-header').prepend(`
        <div class="float-end mb-2">
            <div class="btn-group" role="group" aria-label="Filter answers">
                <button type="button" class="btn btn-sm btn-primary active" id="filter-all">All Answers</button>
                <button type="button" class="btn btn-sm btn-outline-success" id="filter-correct">Correct Only</button>
                <button type="button" class="btn btn-sm btn-outline-danger" id="filter-incorrect">Incorrect Only</button>
            </div>
        </div>
    `);

            // Add filter event handlers
            $('#filter-all').click(function() {
                $(this).addClass('btn-primary active').removeClass('btn-outline-primary');
                $('#filter-correct').addClass('btn-outline-success').removeClass('btn-success active');
                $('#filter-incorrect').addClass('btn-outline-danger').removeClass('btn-danger active');
                currentFilter = 'all';
                applyFilter();
            });

            $('#filter-correct').click(function() {
                $(this).addClass('btn-success active').removeClass('btn-outline-success');
                $('#filter-all').addClass('btn-outline-primary').removeClass('btn-primary active');
                $('#filter-incorrect').addClass('btn-outline-danger').removeClass('btn-danger active');
                currentFilter = 'correct';
                applyFilter();
            });

            $('#filter-incorrect').click(function() {
                $(this).addClass('btn-danger active').removeClass('btn-outline-danger');
                $('#filter-all').addClass('btn-outline-primary').removeClass('btn-primary active');
                $('#filter-correct').addClass('btn-outline-success').removeClass('btn-success active');
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
                    updateProgressBars(response.level_stats);
                    $('#highest-level').text(`Highest Level: ${response.highest_level}`);
                    populateLevelTabs(response.data);
                    // Apply initial filter
                    applyFilter();
                },
                error: function(xhr) {
                    toastr.error('Failed to load student details. Please try again.');
                    console.error(xhr.responseText);
                }
            });

            // Function to display student profile information
            function displayStudentProfile(profile) {
                // Get initials for avatar
                const initials = profile.name.split(' ').map(n => n[0]).join('').toUpperCase();
                $('#profile-avatar').text(initials);
                $('#student-name').text(profile.name);
                $('#student-email').text(profile.email);
                $('#student-id').text(`ID: ${profile.user_id}`);
            }

            // Function to update progress bars
            function updateProgressBars(levelStats) {
                $('#progress-remembering').css('width', `${levelStats.remembering.percentage}%`).attr(
                    'aria-valuenow', levelStats.remembering.percentage);
                $('#progress-understanding').css('width', `${levelStats.understanding.percentage}%`).attr(
                    'aria-valuenow', levelStats.understanding.percentage);
                $('#progress-applying').css('width', `${levelStats.applying.percentage}%`).attr('aria-valuenow',
                    levelStats.applying.percentage);
                $('#progress-analyzing').css('width', `${levelStats.analyzing.percentage}%`).attr('aria-valuenow',
                    levelStats.analyzing.percentage);
            }

            // Function to populate level tabs
            function populateLevelTabs(levelData) {
                const tabsContainer = $('#levelTabs');
                const tabContentContainer = $('#levelTabsContent');

                // Clear existing tabs
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

                // Create tabs and content for each level
                levelData.forEach((level, index) => {
                    const isActive = index === 0 ? 'active' : '';

                    // Create tab
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

                    // Create tab content
                    const contentHtml = $(`
                    <div class="tab-pane fade show ${isActive}" id="content-${level.level}" role="tabpanel"
                         aria-labelledby="tab-${level.level}">
                    </div>
                `);

                    // Add questions and answers to this level
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

                            // Add answers to the question card
                            const answerList = questionCard.find('.answer-list');

                            question.answers.forEach((answer) => {
                                const answerClass = answer.is_correct ? 'correct' :
                                    'incorrect';
                                const statusIcon = answer.is_correct ?
                                    '<i class="fa-solid fa-check text-success"></i>' :
                                    '<i class="fa-solid fa-xmark text-danger"></i>';

                                // Create scores display
                                let scoresHtml = '';
                                if (answer.evaluation_score !== null) {
                                    scoresHtml += `<div class="score-badge ai-score" id="ai-score-badge-${answer.answer_guid}">
                                    <i class="fa-solid fa-robot"></i> ${answer.evaluation_score}
                                </div>`;
                                }
                                if (answer.lecturer_score !== null) {
                                    scoresHtml += `<div class="score-badge lecturer-score" id="lecturer-score-badge-${answer.answer_guid}">
                                    <i class="fa-solid fa-user-tie"></i> ${answer.lecturer_score}
                                </div>`;
                                }

                                const answerCard = $(`
                                <div class="card answer-card ${answerClass} mb-2">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                ${statusIcon}
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
                                        <p class="mb-2">${truncateText(answer.answer_text, 150)}</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="scores-section">
                                                ${scoresHtml}
                                            </div>
                                            ${createLecturerScoreForm(answer.answer_guid, answer.lecturer_score)}
                                        </div>
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
                // Only display alert if the weighted average is above 70%
                const weightedAverage = (
                    (plagiarismData.bert_score * 0.4) +
                    (plagiarismData.cosine_similarity * 0.15) +
                    (plagiarismData.jaccard_similarity * 0.15) +
                    (plagiarismData.levenshtein_similarity || 0) * 0.05 +
                    (plagiarismData.ngram_similarity || 0) * 0.25
                ) * 100;

                // Only show alert if above 70%
                if (weightedAverage < 70) {
                    return '';
                }

                const strategies = plagiarismData.detected_strategies.join(', ');
                return `
    <div class="alert plagiarism-alert mt-3">
        <h6><i class="fa-solid fa-triangle-exclamation"></i> Potential AI Usage Detected</h6>
        <p class="mb-1">Our system detected potential AI usage in this answer with the following strategies:</p>
        <p class="mb-1"><strong>Strategies:</strong> ${strategies}</p>
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
                // Always make the form editable, regardless of whether a score exists
                return `
    @isRole(['admin', 'lecturer', 'assistant'])
<form id="${formId}" class="lecturer-score-form" onsubmit="event.preventDefault(); updateLecturerScore('${answerGuid}', '${formId}')">
        <div class="input-group" style="max-width: 200px; display: flex; flex-direction: column; gap: 10px;">
            <input type="number" class="form-control form-control-sm w-100 input-underline" name="lecturer_score"
                   min="0" max="100" placeholder="0-100" value="${currentScore !== null ? currentScore : ''}">
            <button class="btn btn-sm btn-primary" type="submit">
                ${currentScore !== null ? 'Update Score' : 'Save Score'}
            </button>
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
