@extends('layouts.template')
@section('vendor-css')
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
@endsection
@section('add-css')
    <style>
        .profile-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #3b71ca;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .status-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            background-color: #3b71ca;
        }

        .level-card {
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .level-card-header {
            border-radius: 10px 10px 0 0;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .level-card-header h6 {
            margin: 0;
            color: white;
        }

        .level-card-body {
            padding: 15px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .progress {
            height: 10px;
            margin-bottom: 15px;
        }

        .learning-path-levels {
            display: flex;
            position: relative;
            padding: 20px 0;
        }

        .path-level {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .path-level-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            background-color: #ced4da;
        }

        .path-level-icon.active {
            background-color: #3b71ca;
        }

        .path-level-icon.completed {
            background-color: #14a44d;
        }

        .path-line {
            position: absolute;
            height: 4px;
            top: 50px;
            left: 10%;
            right: 10%;
            background-color: #ced4da;
            z-index: 1;
        }

        .path-line-progress {
            position: absolute;
            height: 4px;
            top: 50px;
            left: 10%;
            background-color: #14a44d;
            z-index: 1;
            transition: width 0.5s;
        }

        .suggestion-card {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #3b71ca;
            background-color: #f8f9fa;
        }

        .question-status {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .question-status.correct {
            background-color: #14a44d;
        }

        .question-status.incorrect {
            background-color: #dc4c64;
        }

        .question-status.not-attempted {
            background-color: #e4a11b;
        }

        .plagiarism-flag {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            color: white;
        }

        .plagiarism-flag.high {
            background-color: #dc4c64;
        }

        .plagiarism-flag.medium {
            background-color: #e4a11b;
        }

        .plagiarism-flag.low {
            background-color: #54b4d3;
        }

        .insight-category {
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
@endsection
@section('info-page')
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark"
                href="/grade/{{ $code }}/{{ $guid }}">Grade/{{ $name }}</a></li>
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark"
                href="/grade/{{ $code }}/{{ $guid }}/{{ $userId }}">Student Details</a></li>
        <li class="breadcrumb-item text-sm text-dark active text-capitalize" aria-current="page">Learning Evaluation</li>
    </ol>
    <h5 class="font-weight-bolder mb-0 text-capitalize">Student Learning Evaluation</h5>
@endsection

@section('content')
    <main class="main-content position-relative max-height-vh-100 h-100 mt-1 border-radius-lg">
        <div class="container-xxl flex-grow-1 container-p-y">
            <!-- Student Profile Card -->
            <div class="card profile-card mb-4" id="profile-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="profile-info">
                            <div class="d-flex align-items-center">
                                <div class="profile-avatar" id="profile-avatar">
                                    <!-- Initial will be populated by JS -->
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-1" id="student-name">Loading...</h4>
                                    <p class="mb-0" id="student-email">Loading...</p>
                                    <p class="mb-0 text-muted" id="student-id">Loading...</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <a href="/grade/{{ $code }}/{{ $guid }}/{{ $userId }}"
                                class="btn btn-secondary">
                                <i class="fa-solid fa-arrow-left"></i> Back to Answers
                            </a>
                            <button type="button" class="btn btn-primary" id="download-report-btn">
                                <i class="fa-solid fa-file-export"></i> Download Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Learning Path Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0">Learning Path Status</h6>
                </div>
                <div class="card-body">
                    <div class="learning-path-progress">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="learning-path-levels" id="learning-path-levels">
                                    <!-- Will be populated dynamically -->
                                    <div class="d-flex justify-content-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="learning-path-status-card">
                                    <h6 class="text-muted mb-3">Current Status</h6>
                                    <div class="current-status" id="current-status">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="status-icon">
                                                <i class="fa-solid fa-graduation-cap"></i>
                                            </div>
                                            <div class="ms-3">
                                                <p class="mb-0 text-muted">Learning Path Status</p>
                                                <h5 class="mb-0" id="learning-path-status">Loading...</h5>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div class="status-icon bg-success">
                                                <i class="fa-solid fa-award"></i>
                                            </div>
                                            <div class="ms-3">
                                                <p class="mb-0 text-muted">Highest Level Achieved</p>
                                                <h5 class="mb-0" id="highest-level">Loading...</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overall Progress Summary -->
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <h6>Overall Learning Progress</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="chart-container" style="height: 300px;" id="overall-progress-chart">
                                <!-- Chart will be rendered here -->
                                <div class="d-flex justify-content-center align-items-center h-100">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <h6 class="mb-3">Learning Statistics</h6>
                                <div class="stat-item mb-3">
                                    <div class="stat-icon bg-primary">
                                        <i class="fa-solid fa-check-double"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 text-muted">Overall Completion</p>
                                        <h5 class="mb-0" id="overall-completion">Loading...</h5>
                                    </div>
                                </div>
                                <div class="stat-item mb-3">
                                    <div class="stat-icon bg-success">
                                        <i class="fa-solid fa-chart-line"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 text-muted">Average Score</p>
                                        <h5 class="mb-0" id="average-score">Loading...</h5>
                                    </div>
                                </div>
                                <div class="stat-item mb-3">
                                    <div class="stat-icon bg-warning">
                                        <i class="fa-solid fa-redo"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 text-muted">Average Attempts</p>
                                        <h5 class="mb-0" id="average-attempts">Loading...</h5>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-icon bg-info">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 text-muted">Persistence Score</p>
                                        <h5 class="mb-0" id="persistence-score">Loading...</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Level Based Progress -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Level-Based Analysis</h6>
                    <ul class="nav nav-pills" id="levelTabsPills" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="progress-tab" data-bs-toggle="pill"
                                data-bs-target="#progress-content" type="button" role="tab"
                                aria-controls="progress-content" aria-selected="true">Progress</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="performance-tab" data-bs-toggle="pill"
                                data-bs-target="#performance-content" type="button" role="tab"
                                aria-controls="performance-content" aria-selected="false">Performance</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="questions-tab" data-bs-toggle="pill"
                                data-bs-target="#questions-content" type="button" role="tab"
                                aria-controls="questions-content" aria-selected="false">Questions</button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="levelTabsPillsContent">
                        <!-- Progress Tab -->
                        <div class="tab-pane fade show active" id="progress-content" role="tabpanel"
                            aria-labelledby="progress-tab">
                            <div class="row" id="level-cards-container">
                                <!-- Level cards will be populated dynamically -->
                                <div class="d-flex justify-content-center w-100">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Performance Tab -->
                        <div class="tab-pane fade" id="performance-content" role="tabpanel"
                            aria-labelledby="performance-tab">
                            <div class="chart-container" style="height: 300px;" id="performance-chart">
                                <!-- Performance chart will be rendered here -->
                                <div class="d-flex justify-content-center align-items-center h-100">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="chart-container" style="height: 300px;" id="score-comparison-chart">
                                        <!-- AI vs Lecturer Score Chart -->
                                        <div class="d-flex justify-content-center align-items-center h-100">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="chart-container" style="height: 300px;" id="attempts-chart">
                                        <!-- Attempts Chart -->
                                        <div class="d-flex justify-content-center align-items-center h-100">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Questions Tab -->
                        <div class="tab-pane fade" id="questions-content" role="tabpanel"
                            aria-labelledby="questions-tab">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <select class="form-select" id="level-filter">
                                        <option value="all">All Levels</option>
                                        <option value="remembering">Remembering</option>
                                        <option value="understanding">Understanding</option>
                                        <option value="applying">Applying</option>
                                        <option value="analyzing">Analyzing</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" id="status-filter">
                                        <option value="all">All Statuses</option>
                                        <option value="correct">Correct</option>
                                        <option value="incorrect">Incorrect</option>
                                        <option value="not-attempted">Not Attempted</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Search questions..."
                                            id="question-search">
                                        <button class="btn btn-outline-secondary" type="button" id="search-btn">
                                            <i class="fa-solid fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover" id="questions-table">
                                    <thead>
                                        <tr>
                                            <th>Question</th>
                                            <th>Level</th>
                                            <th>Attempts</th>
                                            <th>Best Score</th>
                                            <th>Status</th>
                                            <th>Plagiarism</th>
                                        </tr>
                                    </thead>
                                    <tbody id="questions-table-body">
                                        <!-- Questions will be populated dynamically -->
                                        <tr>
                                            <td colspan="6" class="text-center">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Temporal Progression -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6>Progress Over Time</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;" id="temporal-chart">
                        <!-- Temporal chart will be rendered here -->
                        <div class="d-flex justify-content-center align-items-center h-100">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic Integrity Analysis -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6>Academic Integrity Analysis</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="plagiarism-summary" id="plagiarism-summary">
                                <!-- Will be populated dynamically -->
                                <div class="d-flex justify-content-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container" style="height: 250px;" id="plagiarism-chart">
                                <!-- Plagiarism chart will be rendered here -->
                                <div class="d-flex justify-content-center align-items-center h-100">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

           
        </div>
    </main>
@endsection



@section('custom-javascript')
    <script>
        // Global variables to store chart objects
        let overallProgressChart, performanceChart, scoreComparisonChart, attemptsChart, temporalChart, plagiarismChart;
        let studentData = {};

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
            const courseCode = pathSegments[2];
            const topicGuid = pathSegments[3];
            const userId = pathSegments[4];

            // Fetch evaluation data
            $.ajax({
                url: `{{ env('URL_API') }}/api/v1/grade/evaluation-stats/${courseCode}/${topicGuid}/${userId}`,
                type: "GET",
                beforeSend: function(request) {
                    request.setRequestHeader("Authorization", "Bearer {{ $token }}");
                },
                success: function(response) {
                    // Store student data globally
                    studentData = response;

                    // Display student profile
                    displayStudentProfile(response.profile);

                    // Initialize all displays
                    displayLearningPathStatus(response.overall_progress);
                    displayOverallStats(response.overall_progress, response.temporal_progression);
                    displayLevelCards(response.levels);
                    displayQuestionsTable(response.levels);
                    displayPlagiarismSummary(response.plagiarism_summary);

                    // Initialize charts
                    initCharts(response);

                    // Display insights and recommendations
                    generateInsights(response.recommendations);
                },
                error: function(xhr) {
                    toastr.error('Failed to load evaluation data. Please try again.');
                    console.error(xhr.responseText);
                }
            });

            // Handle download report button
            $('#download-report-btn').on('click', function() {
                generatePDF();
            });

            // Handle filters for questions table
            $('#level-filter, #status-filter').on('change', function() {
                filterQuestionsTable();
            });

            $('#question-search').on('keyup', function() {
                filterQuestionsTable();
            });

            $('#search-btn').on('click', function() {
                filterQuestionsTable();
            });
        });

        function displayStudentProfile(profile) {
            $('#student-name').text(profile.name);
            $('#student-email').text(profile.email);
            $('#student-id').text(`ID: ${profile.user_id}`);

            // Create avatar with initials
            const initials = profile.name.split(' ').map(name => name[0]).join('').toUpperCase();
            $('#profile-avatar').text(initials);
        }

        function displayLearningPathStatus(overallProgress) {
            const learningPathStatus = overallProgress.learning_path_status;
            $('#learning-path-status').text(learningPathStatus);
            $('#highest-level').text(overallProgress.highest_level);

            // Create learning path visualization
            const levels = ['Not Started', 'Foundation Level', 'Intermediate Level', 'Advanced Level', 'Expert Level'];
            const currentLevelIndex = levels.indexOf(learningPathStatus);
            let pathHTML = '<div class="path-line"></div>';

            // Calculate progress percentage for the path line
            const progressWidth = currentLevelIndex >= 0 ?
                ((currentLevelIndex) / (levels.length - 1)) * 100 : 0;
            pathHTML += `<div class="path-line-progress" style="width: ${progressWidth}%"></div>`;

            levels.forEach((level, index) => {
                const isActive = index === currentLevelIndex;
                const isCompleted = index < currentLevelIndex;
                const iconClass = isCompleted ? 'completed' : (isActive ? 'active' : '');
                const icon = isCompleted ? 'fa-check' : (isActive ? 'fa-star' : 'fa-circle');

                pathHTML += `
                <div class="path-level">
                    <div class="path-level-icon ${iconClass}">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="path-level-name">${level}</div>
                </div>
            `;
            });

            $('#learning-path-levels').html(pathHTML);
        }

        function displayOverallStats(overallProgress, temporalData) {
            $('#overall-completion').text(`${overallProgress.progress_percentage}%`);

            // Calculate average score across all levels
            let totalScore = 0;
            let scoreCount = 0;

            for (const level in studentData.levels) {
                if (studentData.levels[level].avg_score) {
                    totalScore += studentData.levels[level].avg_score;
                    scoreCount++;
                }
            }
            const avgScore = scoreCount > 0 ? (totalScore / scoreCount).toFixed(1) : 'N/A';
            $('#average-score').text(avgScore);

            // Calculate average attempts across all levels
            let totalAttempts = 0;
            let levelCount = 0;

            for (const level in studentData.levels) {
                if (studentData.levels[level].avg_attempts) {
                    totalAttempts += studentData.levels[level].avg_attempts;
                    levelCount++;
                }
            }
            const avgAttempts = levelCount > 0 ? (totalAttempts / levelCount).toFixed(1) : 'N/A';
            $('#average-attempts').text(avgAttempts);

            // Display persistence score if available
            if (temporalData && temporalData.persistence_metrics) {
                $('#persistence-score').text(`${temporalData.persistence_metrics.persistence_score}/100`);
            } else {
                $('#persistence-score').text('N/A');
            }
        }

        function displayLevelCards(levelsData) {
            const levelColors = {
                'remembering': {
                    bg: '#3b71ca',
                    text: 'white'
                },
                'understanding': {
                    bg: '#54b4d3',
                    text: 'white'
                },
                'applying': {
                    bg: '#14a44d',
                    text: 'white'
                },
                'analyzing': {
                    bg: '#e4a11b',
                    text: 'white'
                }
            };

            const levelOrder = ['remembering', 'understanding', 'applying', 'analyzing'];
            let cardsHTML = '';

            levelOrder.forEach(level => {
                if (levelsData[level]) {
                    const levelData = levelsData[level];
                    const color = levelColors[level] || {
                        bg: '#6c757d',
                        text: 'white'
                    };

                    cardsHTML += `
                    <div class="col-md-6 mb-4">
                        <div class="level-card">
                            <div class="level-card-header" style="background-color: ${color.bg}; color: ${color.text}">
                                <h6>${levelData.level_name}</h6>
                                <span class="badge bg-light text-dark">${levelData.passed_questions}/${levelData.total_questions} Completed</span>
                            </div>
                            <div class="level-card-body">
                                <p class="text-muted small">${levelData.level_description}</p>
                                <div class="progress-label">
                                    <span>Progress</span>
                                    <span>${levelData.progress_percentage}%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: ${levelData.progress_percentage}%; background-color: ${color.bg}" 
                                        aria-valuenow="${levelData.progress_percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col">
                                        <p class="mb-1 small text-muted">Average Score</p>
                                        <h6>${levelData.avg_score !== null ? levelData.avg_score : 'N/A'}</h6>
                                    </div>
                                    <div class="col">
                                        <p class="mb-1 small text-muted">Average Attempts</p>
                                        <h6>${levelData.avg_attempts}</h6>
                                    </div>
                                    <div class="col">
                                        <p class="mb-1 small text-muted">Lecturer Score</p>
                                        <h6>${levelData.avg_lecturer_score !== null ? levelData.avg_lecturer_score : 'N/A'}</h6>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <h6 class="text-muted small">Improvement Suggestions:</h6>
                                    <ul class="small">
                                        ${levelData.improvement_suggestions.map(suggestion => `<li>${suggestion}</li>`).join('')}
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                }
            });

            $('#level-cards-container').html(cardsHTML);
        }

    
        
        function displayPlagiarismSummary(plagiarismData) {
            const totalDetected = plagiarismData.total_detected;

            let summaryHTML = `
            <h6 class="mb-3">Plagiarism Overview</h6>
            <div class="mb-4">
                <p class="mb-2"><strong>Total Instances Detected:</strong> ${totalDetected}</p>
                <p class="mb-0">Distribution by Learning Level:</p>
                <ul class="mb-3">
        `;

            // Add distribution by level
            for (const level in plagiarismData.detected_by_level) {
                const count = plagiarismData.detected_by_level[level];
                summaryHTML += `<li>${level.charAt(0).toUpperCase() + level.slice(1)}: ${count} instance(s)</li>`;
            }

            summaryHTML += `</ul><p class="mb-2">Most Common Detection Strategies:</p><ul>`;

            // Add common strategies
            let strategiesAdded = 0;
            for (const strategy in plagiarismData.most_common_strategies) {
                if (strategiesAdded < 3) {
                    const count = plagiarismData.most_common_strategies[strategy];
                    summaryHTML += `<li>${formatStrategyName(strategy)}: ${count} instance(s)</li>`;
                    strategiesAdded++;
                } else {
                    break;
                }
            }

            summaryHTML += `</ul></div>`;

            $('#plagiarism-summary').html(summaryHTML);
        }

        function formatStrategyName(strategy) {
            // Format camelCase or snake_case to readable text
            const formatted = strategy.replace(/_/g, ' ').replace(/([A-Z])/g, ' $1');
            return formatted.charAt(0).toUpperCase() + formatted.slice(1);
        }

     
        
        // Function to generate PDF report
        function generatePDF() {
            // Show loading message
            Swal.fire({
                title: 'Generating PDF...',
                html: 'Please wait while we generate your report',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Use jsPDF
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');

            // Get the container element to capture
            const content = document.querySelector('.container-xxl');

            // Use html2canvas to capture the content
            html2canvas(content, {
                scale: 1,
                useCORS: true,
                allowTaint: true,
                onrendered: function(canvas) {
                    // Canvas has been created
                }
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const imgWidth = 210; // A4 width in mm
                const pageHeight = 297; // A4 height in mm
                const imgHeight = canvas.height * imgWidth / canvas.width;
                let heightLeft = imgHeight;
                let position = 0;

                doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;

                // Add additional pages if the content is larger than one page
                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight;
                    doc.addPage();
                    doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }

                // Generate student name for filename
                const studentName = studentData?.profile?.name || 'student';
                const fileName = `${studentName.replace(/\s+/g, '_')}_learning_evaluation.pdf`;

                // Save the PDF
                doc.save(fileName);

                // Close loading dialog
                Swal.close();

                // Show success message
                toastr.success('Report generated successfully!');
            }).catch(error => {
                console.error('Error generating PDF:', error);
                Swal.close();
                toastr.error('Failed to generate report. Please try again.');
            });
        }
    </script>
@endsection
