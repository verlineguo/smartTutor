@extends('layouts.template')
@section('vendor-css')
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-checkboxes-jquery/datatables.checkboxes.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <!-- Row Group CSS -->
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/css/generate.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
@endsection

@section('add-css')
    <style>
        #pdf-answers-table {
            table-layout: auto;
            /* Kolom menyesuaikan lebar konten */
            width: 100%;
            /* Pastikan tabel menggunakan lebar penuh */
        }

        #pdf-answers-table th,
        #pdf-answers-table td {
            white-space: normal;
            /* Izinkan teks membungkus */
            word-wrap: break-word;
            /* Bungkus kata jika terlalu panjang */
            text-align: left;
            /* Rata kiri untuk konten */
        }

        .pdf-answer-content {
            width: 100%;
            overflow-y: auto;
            padding: 8px;
            border: 1px solid #eee;
            border-radius: 4px;
            background-color: #f9f9f9;
        }

        .scores-container {
            font-size: 0.85rem;
        }

        .alternate-answers {
            margin-top: 8px;
        }

        #skipInfo {
            margin-left: 8px;
            vertical-align: middle;
        }

        .text-wrap {
            white-space: normal;
            word-wrap: break-word;
            min-width: 200px;
        }

        .dataTables_filter {
            float: right;
            text-align: right;
            margin-right: 10px;
        }


        .card-icon {
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            margin-right: 15px;
        }

        .card-icon i {
            font-size: 1.5rem;
            color: white;
        }

        .nav-tabs .nav-link.active {
            font-weight: bold;
            border-bottom: 3px solid #5e72e4;
        }

        .tab-content {
            padding-top: 20px;
        }

        .dropdown-menu {
            min-width: 15rem;
        }

        .dropdown-item-text {
            padding: 0.25rem 1rem;
            font-size: 0.875rem;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }

        .progress-slim {
            height: 4px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .answer-toggle {
            cursor: pointer;
            color: #5e72e4;
        }

        .answer-content {
            display: none;
            margin-top: 8px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .model-badge {
            font-size: 0.7rem;
            padding: 0.2em 0.5em;
            margin-right: 5px;
        }

        .tooltip-inner {
            max-width: 300px;
        }

        /* Custom tab styles */
        .question-tabs .nav-item .nav-link {
            padding: 0.75rem 1.25rem;
            font-weight: 500;
        }

        .question-tabs .nav-item .nav-link.active {
            color: #5e72e4;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }

        .score-pill {
            font-size: 0.7rem;
            border-radius: 20px;
            padding: 0.2rem 0.6rem;
        }

        /* Tooltip styles */
        [data-bs-toggle="tooltip"] {
            cursor: pointer;
        }

        .accordion-body ul,
        .accordion-body ol {
            padding-left: 2rem;
            margin-bottom: 1rem;
        }

        .accordion-body h1,
        .accordion-body h2,
        .accordion-body h3,
        .accordion-body h4 {
            margin-top: 1rem;
            margin-bottom: 1rem;
        }

        .accordion-body p {
            margin-bottom: 1rem;
        }
    </style>
@endsection

@section('info-page')
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
        <li class="breadcrumb-item text-sm text-dark active text-capitalize" aria-current="page">generate</li>
    </ol>
    <h5 class="font-weight-bolder mb-0 text-capitalize">Question Generator</h5>
@endsection

@section('content')
    <main class="main-content position-relative max-height-vh-100 h-100 mt-1 border-radius-lg">
        <!-- Generate Button Container -->
        <div class="container d-flex justify-content-center align-items-center">
            <div class="row d-flex align-items-center justify-content-center">
                <div class="col-md-6 order-md-1 order-2">
                    <button class="btn btn-primary w-100" id="generate">Generate Questions</button>
                </div>
                <div class="col-md-6 order-md-2 order-1">
                    <p class="text-start">Silakan tekan tombol "Generate Question" untuk membuat pertanyaan baru. Anda dapat
                        memilih bahasa, kursus, dan topik yang sesuai untuk pertanyaan yang ingin Anda buat.
                    </p>
                </div>
            </div>
        </div>

        <!-- Loading Indicators -->
        <div id="loading" style="display: none;">
            <div class="loader"></div>
            <div>Loading... <span id="progressPercentage">0%</span></div>
        </div>
        <div id="loadingScreen" style="display: none;">
            <div id="loadingMessage">Generating Questions...</div>
            <div id="progressBarContainer"
                style="background: #e0e0e0; border-radius: 5px; overflow: hidden; width: 100%; height: 25px;">
                <div id="progressBar" style="background: #007bff; width: 0%; height: 100%; transition: width 0.4s;"></div>
            </div>
            <div style="text-align: center; margin-top: 10px;">
                <span id="progressPercentage">0%</span>
            </div>
        </div>

        <!-- Questions Table Container with Tabs -->
        <div class="container-xxl flex-grow-1 container-p-y" id="table-container" style="display: none;">
            <!-- Action Buttons -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <button type="button" class="btn btn-success me-2" id="saveQuestions">
                        <i class="fas fa-save mr-1"></i> Save Questions
                    </button>
                    <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal"
                        data-bs-target="#updateBulkModal">
                        <i class="fas fa-edit mr-1"></i> Update Selected
                    </button>
                    <button type="button" class="btn btn-danger" id="deleteSelectedRows">
                        <i class="fas fa-trash mr-1"></i> Delete Selected
                    </button>
                </div>
                <div>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="filterDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-filter mr-1"></i> Filter
                        </button>
                        <div class="dropdown-menu p-3" aria-labelledby="filterDropdown">
                            <h6 class="dropdown-header">Filter by Language</h6>
                            <div id="language-filters" class="mb-3">
                                <!-- Language filters will be added dynamically -->
                            </div>
                            <h6 class="dropdown-header">Filter by Category</h6>
                            <div id="category-filters" class="mb-3">
                                <!-- Category filters will be added dynamically -->
                            </div>
                            <h6 class="dropdown-header">Filter by Similarity</h6>
                            <div class="dropdown-item-text">
                                <label for="similarity-range">Minimum Similarity: <span
                                        id="similarity-value">0</span>%</label>
                                <input type="range" class="form-range" id="similarity-range" min="0" max="100"
                                    value="0">
                            </div>
                            <div class="dropdown-divider"></div>
                            <button id="apply-filters" class="btn btn-sm btn-primary w-100">Apply Filters</button>
                            <button id="reset-filters" class="btn btn-sm btn-outline-secondary w-100 mt-2">Reset
                                Filters</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs and Table -->
            <div class="card" id="card-block">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs question-tabs" id="question-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-questions-tab" data-bs-toggle="tab"
                                data-bs-target="#all-questions" type="button" role="tab"
                                aria-controls="all-questions" aria-selected="true">All Questions</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="llm-comparison-tab" data-bs-toggle="tab"
                                data-bs-target="#llm-comparison" type="button" role="tab"
                                aria-controls="llm-comparison" aria-selected="false">LLM Comparison</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pdf-answers-tab" data-bs-toggle="tab"
                                data-bs-target="#pdf-answers" type="button" role="tab" aria-controls="pdf-answers"
                                aria-selected="false">PDF Answers & Scores</button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="questionsTabContent">
                        <!-- All Questions Tab -->
                        <div class="tab-pane fade show active" id="all-questions" role="tabpanel"
                            aria-labelledby="all-questions-tab">
                            <div class="table-responsive">
                                <table class="table" id="table-data">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="checkAll"></th>
                                            <th>No</th>
                                            <th>Language</th>
                                            <th>Question</th>
                                            <th>Category</th>
                                            <th>Page</th>
                                            <th>Cosine Similarity</th>
                                            <th>Threshold</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                        <!-- LLM Comparison Tab -->
                        <div class="tab-pane fade" id="llm-comparison" role="tabpanel"
                            aria-labelledby="llm-comparison-tab">
                            <div class="table-responsive">
                                <table class="table" id="llm-table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Question</th>
                                            <th>OpenAI Answer</th>
                                            <th>Gemini Answer</th>
                                            <th>Deepseek Answer</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                        <!-- User Answers Tab -->
                        <div class="tab-pane fade" id="user-answers" role="tabpanel" aria-labelledby="user-answers-tab">
                            <div class="table-responsive">
                                <table class="table" id="user-answers-table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Question</th>
                                            <th>User Answer</th>
                                            <th>User</th>
                                            <th>Cosine Similarity</th>
                                            <th>Timestamp</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                        <!-- PDF Answers Tab -->
                        <div class="tab-pane fade" id="pdf-answers" role="tabpanel" aria-labelledby="pdf-answers-tab">
                            <div class="table-responsive">
                                <table class="table" id="pdf-answers-table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Question</th>
                                            <th>PDF Answer</th>
                                            <th>Combined Score</th>
                                            <th>QA Score</th>
                                            <th>Retrieval Score</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generate Question Modal -->
        <div class="modal fade" id="generateQuestionModal" tabindex="-1" aria-labelledby="generateQuestionModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="generateQuestionModalLabel">
                            Generate Question
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="generateQuestionForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- Course Selection -->
                                    <div class="mb-3">
                                        <label for="courseInput" class="form-label">
                                            <i class="fas fa-book mr-1"></i> Course
                                        </label>
                                        <select class="form-select" id="courseInput" required>
                                            <option value="" selected>Choose Course</option>
                                        </select>
                                    </div>

                                    <!-- Topic Selection -->
                                    <div class="mb-3" id="topic">
                                        <label for="topicInput" class="form-label">
                                            <i class="fas fa-bookmark mr-1"></i> Topic
                                        </label>
                                        <select class="form-select" id="topicInput" required>
                                            <option value="" selected>Choose Topic</option>
                                        </select>
                                        <div id="filePathDisplay"
                                            style="display: flex; align-items: center; margin-top: 10px;">
                                            <span id="filePath" style="cursor: pointer;"></span>

                                        </div>
                                        <button type="button" class="btn btn-danger btn-sm mt-2" id="deleteFilePath"
                                            style="margin-left: 10px;">
                                            <i class="fas fa-trash-alt"></i> Delete FIle
                                        </button>
                                    </div>

                                    <!-- PDF Upload -->
                                    <div class="mb-3" id="pdfUpload" style="display: none;">
                                        <label for="pdfInput" class="form-label">
                                            <i class="fas fa-file-pdf mr-1"></i> Upload PDF
                                        </label>
                                        <input type="file" class="form-control" id="pdfInput" name="pdfInput"
                                            accept=".pdf">
                                    </div>

                                    <!-- File Language -->
                                    <div class="mb-3" id="fileLanguageContainer" style="display: none;">
                                        <label for="fileLanguage" class="form-label">
                                            <i class="fas fa-language mr-1"></i> File Language
                                        </label>
                                        <select class="form-select" id="fileLanguage" required>
                                            <option value="english" selected>English</option>
                                            <option value="indonesia">Indonesia</option>
                                            <option value="japanese">Japanese</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <!-- Language Checkboxes -->
                                    <div class="mb-3">
                                        <label for="languageCheckboxGroup" class="form-label">
                                            <i class="fas fa-globe mr-1"></i> Output Languages
                                        </label>
                                        <div id="languageCheckboxGroup" class="border p-3 rounded">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="english"
                                                    id="languageEnglish" name="languages[]">
                                                <label class="form-check-label" for="languageEnglish">English</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="indonesia"
                                                    id="languageIndonesia" name="languages[]">
                                                <label class="form-check-label" for="languageIndonesia">Indonesia</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="japanese"
                                                    id="languageJapanese" name="languages[]">
                                                <label class="form-check-label" for="languageJapanese">Japanese</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Advanced Options -->
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-sliders-h mr-1"></i> Advanced Options
                                        </label>
                                        <div class="border p-3 rounded">
                                            <div class="mb-2">
                                                <label for="threshold-input" class="form-label small">Default Threshold
                                                    (%)</label>
                                                <input type="number" class="form-control" id="threshold-input"
                                                    min="0" max="100" value="70">
                                                <div class="d-flex justify-content-between">
                                                    <small>0%</small>
                                                    <small id="threshold-value">70%</small>
                                                    <small>100%</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-magic mr-1"></i> Generate
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Update Modal -->
        <div class="modal fade" id="updateBulkModal" tabindex="-1" aria-labelledby="updateBulkModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateBulkModalLabel">Update Selected Questions</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="bulkThreshold" class="form-label">Threshold (%)</label>
                            <input type="number" id="bulkThreshold" class="form-control" placeholder="Enter Threshold"
                                min="0" max="100">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="updateSelectedRows" class="btn btn-primary">Update Selected</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview LLM Answers Modal -->
        <div class="modal fade" id="previewAnswersModal" tabindex="-1" aria-labelledby="previewAnswersModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="previewAnswersModalLabel">LLM Answers Comparison</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <h6>Question:</h6>
                            <p id="previewQuestionText" class="p-2 bg-light rounded"></p>
                        </div>
                        <div class="mb-3">
                            <div class="accordion" id="llmAnswersAccordion">
                                <!-- LLM Answers will be added here dynamically -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="loading-overlay" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div id="loading-message" class="mt-2">Processing your request...</div>
    </div>
@endsection



@section('vendor-javascript')
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="{{ asset('./assets/dashboard/datatables/jquery.dataTables.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-responsive/datatables.responsive.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-responsive-bs5/responsive.bootstrap5.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-checkboxes-jquery/datatables.checkboxes.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-buttons/datatables-buttons.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-buttons-bs5/buttons.bootstrap5.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-buttons/buttons.html5.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-buttons/buttons.print.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.blockUI/2.70/jquery.blockUI.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
@endsection

@section('custom-javascript')
    <script>
        $(document).ready(function() {
            $('#table-container').hide();
            $('#topic').hide();
            $('#filePathDisplay').hide();
            $('#deleteFilePath').hide();

            // Fungsi untuk handle submit form
            let completedRequests = 0; // Jumlah operasi yang sudah selesai
            let totalRequests = 0; // Total operasi yang akan dijalankan
            initializeDataTables();

            $('#threshold-input').on('input', function() {
                $('#threshold-value').text($(this).val() + '%');
            });

            // Set up similarity slider in filter dropdown
            $('#similarity-range').on('input', function() {
                $('#similarity-value').text($(this).val());
            });

            $('#generateQuestionForm').submit(function(event) {
                event.preventDefault();
                $('#table-container').hide();
                $('#generateQuestionModal').modal('hide');

                const pdfFile = $('#pdfInput')[0].files[0];
                const topic = $('#topicInput').val();
                const defaultThreshold = $('#threshold-input').val() || 70;

                // Reset counter sebelum memulai loop baru
                completedRequests = 0;
                totalRequests = 0;

                // Ambil bahasa yang dipilih untuk translate
                const selectedLanguages = [];
                $('#languageCheckboxGroup input:checked').each(function() {
                    selectedLanguages.push($(this).val());
                });

                if (selectedLanguages.length === 0) {
                    toastr.options.closeButton = true;
                    toastr.options.timeOut = 3000;
                    toastr.error('Please select at least one language for translation.');
                    return;
                }

                // Set jumlah total operasi berdasarkan bahasa yang dipilih
                totalRequests = selectedLanguages.length;

                if (cachedFilePath) {
                    // Jika menggunakan file yang sudah dicache
                    selectedLanguages.forEach(language => {
                        sendToTranslateDocument(language, topic, defaultThreshold);
                    });
                } else if (pdfFile) {
                    // Jika ada file PDF yang diupload
                    uploadFile(pdfFile, $('#fileLanguage').val(), topic, selectedLanguages,
                        defaultThreshold);
                } else {
                    toastr.options.closeButton = true;
                    toastr.options.timeOut = 3000;
                    toastr.error('Please upload a PDF file or select a topic with an existing file.');
                }
            });

            $('#saveQuestions').on('click', function() {
                const table = $('#table-data').DataTable();
                const questionsData = table.rows().data().toArray();
                const topicGuid = $('#topicInput').val();
                const code = $('#courseInput').val();

                const dataToSave = questionsData.map(question => {
                    return {
                        guid: question.guid,
                        question: question.question,
                        category: question.category,
                        language: question.language,
                        threshold: question.threshold || 70,
                        page_number: question.page_number,
                        'cosine_q&d': question['cosine_q&d'],
                        question_nouns: question.question_nouns,
                        // Include PDF answers
                        pdf_answer: question.pdf_answer,
                        all_pdf_answers: question.all_pdf_answers,
                        combined_score: question.combined_score,
                        qa_score: question.qa_score,
                        retrieval_score: question.retrieval_score,
                        // Include LLM answers
                        answer_openai: question.answer_openai,
                        answer_openai_guid: question.answer_openai_guid,
                        answer_gemini: question.answer_gemini,
                        answer_gemini_guid: question.answer_gemini_guid
                    };
                });


                $.ajax({
                    type: "POST",
                    url: "{{ env('URL_API') }}/api/v1/question/save",
                    data: {
                        topic_guid: topicGuid,
                        questions: questionsData
                    },
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization",
                            "Bearer {{ $token }}");
                    },
                    success: function(response) {
                        toastr.options.closeButton = true;
                        toastr.options.timeOut = 3000;
                        toastr.success("Questions saved successfully.");

                        // Redirect ke route question setelah sukses
                        window.location.href = "{{ url('/question') }}/" + code + "/" +
                            topicGuid;
                    },
                    error: function(xhr) {
                        toastr.options.closeButton = true;
                        toastr.options.timeOut = 3000;
                        toastr.error("Failed to save questions: " + xhr.responseText);
                    }
                });
            });

            function showLoading(message = "Processing your request...") {
                console.log("Loading started: " + message);
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
                });

                // Update or create the message element
                if ($("#loading-message").length === 0) {
                    $("#loading-overlay").append('<div id="loading-message" class="mt-2"></div>');
                }

                $("#loading-message").text(message);
                $("#loading-overlay").fadeIn(300);
            }

            function hideLoading() {
                console.log("Loading ended"); // Debug log
                $("#loading-overlay").fadeOut(300);
            }


            function uploadFile(file, fileLanguage, topic, languages, defaultThreshold) {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('language', fileLanguage); // Kirimkan bahasa yang dipilih untuk file
                formData.append('topic_guid', topic);

                console.log("File object:", file);
                console.log("File in FormData:", formData.get('file'));
                showLoading("Uploading file...");


                $.ajax({
                    url: "{{ env('URL_API') }}/api/v1/topic/upload-file",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", "Bearer {{ $token }}");
                    },
                    success: function() {
                        hideLoading();
                        // alert('File uploaded successfully.');

                        // Lakukan translate untuk setiap bahasa setelah upload berhasil
                        languages.forEach(language => {
                            sendToTranslateDocument(language, topic, defaultThreshold);
                        });
                    },
                    error: function() {
                        hideLoading();
                        toastr.options.closeButton = true;
                        toastr.options.timeOut = 3000;
                        toastr.error('Failed to upload file.');
                    }

                });
            }


            function sendToTranslateDocument(language, topic, defaultThreshold) {
                showLoading("Translating document to " + language + "...");
                $.ajax({
                    type: "POST",
                    url: "{{ env('URL_API') }}/api/v1/question/translate",
                    data: {
                        'language': language,
                        'topic_guid': topic
                    },
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", "Bearer {{ $token }}");
                    },
                    success: function() {
                        hideLoading();
                        sendToTfidfDocument(language, topic, defaultThreshold);
                    },
                    error: function() {
                        hideLoading();
                        alert(`Failed to translate document to ${language}.`);
                    }
                });
            }

            function sendToTfidfDocument(language, topic, defaultThreshold) {
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
                        sendToGenerateData(language, topic, defaultThreshold);
                    },
                    error: function() {
                        hideLoading();
                        alert(`Failed to calculate TF-IDF for ${language}.`);
                    }
                });
            }

            function sendToGenerateData(language, topic, defaultThreshold) {
                showLoading("Generating questions in " + language + "...");


                $.ajax({
                    type: "POST",
                    url: "{{ env('URL_API') }}/api/v1/question/generate",
                    data: {
                        'language': language,
                        'topic_guid': topic
                    },
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", "Bearer {{ $token }}");
                    },
                    success: function(response) {

                        // Tambahkan data ke tabel
                        loadDataToTable(response.data, language, defaultThreshold);
                        processGeneratedData(response.data, language, defaultThreshold);

                        getBertAnswers(response.data, topic, language)
                            .then(questionsWithAnswers => {
                                // Update tabel dengan jawaban yang dihasilkan
                                updatePdfAnswersTable(questionsWithAnswers);


                                toastr.success('Questions and answers generated successfully.');
                                showLoading("generate answer with LLM")
                            })
                            .catch(error => {
                                console.error("Error generating answers:", error);

                                toastr.error('Failed to generate answers.');
                            })

                        processQuestionsWithLLM(response.data);


                        // Tingkatkan jumlah permintaan yang selesai
                        checkCompletion();

                    },
                    error: function() {
                        alert(`Failed to generate questions in ${language}.`);
                        // Tetap tingkatkan jumlah permintaan yang selesai meskipun gagal
                        checkCompletion();
                    }
                });
            }

            function updatePdfAnswersTable(questionsWithAnswers) {
                if (!$.fn.DataTable.isDataTable('#pdf-answers-table')) {
                    $('#pdf-answers-table').DataTable({
                        columns: [{
                                data: 'index',
                                title: 'No'
                            },
                            {
                                data: 'question',
                                title: 'Question',
                                className: 'text-wrap',
                            },
                            {
                                data: 'pdf_answer',
                                title: 'PDF Answer',
                                className: 'text-wrap'
                            },
                            {
                                data: 'combined_score',
                                title: 'Combined Score',
                                render: function(data) {
                                    return data ? parseFloat(data).toFixed(2) : '0.00';
                                }
                            },
                            {
                                data: 'qa_score',
                                title: 'QA Score',
                                render: function(data) {
                                    return data ? parseFloat(data).toFixed(2) : '0.00';
                                }
                            },
                            {
                                data: 'retrieval_score',
                                title: 'Retrieval Score',
                                render: function(data) {
                                    return data ? parseFloat(data).toFixed(2) : '0.00';
                                }
                            }
                        ],
                        searching: true,
                        paging: true,
                        info: true
                    });
                }

                // Filter questions to only those with PDF answers
                const answeredQuestions = questionsWithAnswers.filter(q => q.pdf_answer);

                if (answeredQuestions.length > 0) {
                    const pdfTable = $('#pdf-answers-table').DataTable();
                    pdfTable.clear().rows.add(answeredQuestions).draw();
                }
            }

            function processGeneratedData(data, language, defaultThreshold) {
                // Append necessary fields to make data compatible with all tables
                data.forEach((item, i) => {
                    // Add essential fields
                    item.index = i + 1;
                    item.language = language;
                    item.checkbox = false;
                    item.threshold = item.threshold || defaultThreshold;

                });

                populateOtherTables(data);
            }

            function processQuestionsWithLLM(questions) {
                // Create a counter to track when all requests are done
                let completedLlmRequests = 0;
                let totalLlmRequests = questions.length * 2; // 2 models per question (OpenAI and Gemini)

                questions.forEach(question => {
                    // Process with OpenAI
                    $.ajax({
                        type: "POST",
                        url: "{{ env('URL_API') }}/api/v1/llm-answer",
                        data: {
                            model: 'openai',
                            prompt: question.question,
                            question_guid: question.guid
                        },
                        beforeSend: function(request) {
                            request.setRequestHeader("Authorization",
                                "Bearer {{ $token }}");
                        },
                        success: function(response) {
                            if (response.data && response.data.response) {
                                // Update main table
                                const table = $('#table-data').DataTable();
                                table.rows().every(function() {
                                    const data = this.data();
                                    if (data.guid === question.guid) {
                                        data.answer_openai = response.data.response;
                                        data.answer_openai_guid = response.data.guid;
                                        this.data(data);
                                    }
                                });
                            }

                            // Check if all requests completed
                            completedLlmRequests++;
                            checkLlmCompletion();
                        },
                        error: function() {
                            completedLlmRequests++;
                            checkLlmCompletion();
                        }
                    });

                    // Process with Gemini
                    $.ajax({
                        type: "POST",
                        url: "{{ env('URL_API') }}/api/v1/llm-answer",
                        data: {
                            model: 'gemini',
                            prompt: question.question,
                            question_guid: question.guid
                        },
                        beforeSend: function(request) {
                            request.setRequestHeader("Authorization",
                                "Bearer {{ $token }}");
                        },
                        success: function(response) {
                            if (response.data && response.data.response) {
                                // Update main table
                                const table = $('#table-data').DataTable();
                                table.rows().every(function() {
                                    const data = this.data();
                                    if (data.guid === question.guid) {
                                        data.answer_gemini_guid = response.data.guid;
                                        data.answer_gemini = response.data.response;
                                        this.data(data);
                                    }
                                });
                            }

                            // Check if all requests completed
                            completedLlmRequests++;
                            checkLlmCompletion();
                        },
                        error: function() {
                            completedLlmRequests++;
                            checkLlmCompletion();
                        }
                    });
                });

                function checkLlmCompletion() {
                    if (completedLlmRequests >= totalLlmRequests) {
                        // All LLM requests are done, update tables
                        const table = $('#table-data').DataTable();
                        table.draw();
                        hideLoading();
                        // Sync data to the LLM comparison table
                        syncLlmAnswersToComparisonTable();

                        toastr.success('LLM answers generated successfully.');
                    }
                }
            }

            function getLLMAnswer(model, prompt, questionGuid) {
                $.ajax({
                    type: "POST",
                    url: "{{ env('URL_API') }}/api/v1/llm-answer", // Laravel endpoint
                    data: {
                        model: model,
                        prompt: prompt,
                        question_guid: questionGuid
                    },
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", "Bearer {{ $token }}");
                    },
                    success: function(response) {
                        if (response.data && response.data.response) {
                            console.log(`Response from ${model}:`, response.data.response);

                            // Update tabel dengan jawaban
                            const table = $('#table-data').DataTable();
                            table.rows().every(function() {
                                const data = this.data();
                                if (data.guid === questionGuid) {
                                    data[`answer_${model}`] = response
                                        .response; // Simpan jawaban di kolom yang sesuai
                                    this.data(data).draw(false);
                                }
                            });
                            if ($.fn.DataTable.isDataTable('#llm-table')) {
                                const llmTable = $('#llm-table').DataTable();
                                llmTable.rows().every(function() {
                                    const llmData = this.data();
                                    if (llmData.question === prompt) {
                                        llmData[`answer_${model}`] = response.data.response;
                                        this.data(llmData).draw(false);
                                    }
                                });
                            }
                            toastr.success(`Response from ${model} received successfully.`);

                        } else {
                            toastr.error(`No response from ${model}.`);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(`Failed to get response from ${model}: ${xhr.responseText}`);
                    }
                });
            }


            function syncLlmAnswersToComparisonTable() {
                // Only proceed if both tables exist
                if (!$.fn.DataTable.isDataTable('#table-data') || !$.fn.DataTable.isDataTable('#llm-table')) {
                    return;
                }

                const mainTable = $('#table-data').DataTable();
                const llmTable = $('#llm-table').DataTable();

                // Clear the LLM table first
                llmTable.clear();

                // Add data from main table to LLM table
                mainTable.rows().every(function() {
                    const data = this.data();

                    // Create a new row for the LLM table
                    const llmData = {
                        index: data.index,
                        question: data.question,
                        answer_openai: data.answer_openai || '',
                        answer_gemini: data.answer_gemini || '',
                        other_models: '' // Add other models if needed
                    };

                    // Add to LLM table
                    llmTable.row.add(llmData);
                });

                // Draw the updated table
                llmTable.draw();
            }

            function updatePdfAnswersTable(questions) {
                const table = $('#pdf-answers-table');
                table.DataTable().clear().destroy();

                const rows = questions.map((question, index) => {
                    // Default answer display (best answer)
                    let answerDisplay =
                        `<div class="pdf-answer-content">${question.pdf_answer || 'No answer available'}</div>`;
                    let scoreDisplay = `
            <div>Combined: ${question.combined_score ? question.combined_score.toFixed(4) : 'N/A'}</div>
            <div>QA: ${question.qa_score ? question.qa_score.toFixed(4) : 'N/A'}</div>
            <div>Retrieval: ${question.retrieval_score ? question.retrieval_score.toFixed(4) : 'N/A'}</div>
        `;

                    // If we have multiple answers, add a dropdown
                    if (question.all_pdf_answers && question.all_pdf_answers.length > 1) {
                        const selectId = `answer-select-${index}`;

                        // Create dropdown with all answers
                        let selectOptions = '<option value="-1">Select alternate answer...</option>';
                        question.all_pdf_answers.forEach((answer, answerIndex) => {
                            selectOptions +=
                                `<option value="${answerIndex}">Answer ${answerIndex + 1} (Score: ${answer.combined_score.toFixed(4)})</option>`;
                        });

                        // Add dropdown to the display
                        answerDisplay = `
                <div class="pdf-answer-content mb-2">${question.pdf_answer || 'No answer available'}</div>
                <div class="alternate-answers">
                    <select class="form-select answer-selector" id="${selectId}" data-question-index="${index}">
                        ${selectOptions}
                    </select>
                </div>
            `;
                    }

                    return [
                        index + 1,
                        question.question,
                        answerDisplay,
                        `<div class="scores-container">${scoreDisplay}</div>`,
                        question.qa_score ? question.qa_score.toFixed(4) : 'N/A',
                        question.retrieval_score ? question.retrieval_score.toFixed(4) : 'N/A'
                    ];
                });

                // Initialize DataTable with the new rows
                const dataTable = table.DataTable({
                    data: rows,
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [
                        [5, 10, 25, 50, -1],
                        [5, 10, 25, 50, "All"]
                    ]
                });

                // Add event listeners for answer selection
                $('.answer-selector').on('change', function() {
                    const questionIndex = $(this).data('question-index');
                    const answerIndex = parseInt($(this).val());

                    if (answerIndex >= 0) {
                        // Get the selected answer data
                        const question = questions[questionIndex];
                        const selectedAnswer = question.all_pdf_answers[answerIndex];

                        // Update the displayed answer and scores
                        const answerContainer = $(this).closest('tr').find('.pdf-answer-content');
                        const scoresContainer = $(this).closest('tr').find('.scores-container');

                        answerContainer.html(selectedAnswer.answer);
                        scoresContainer.html(`
                <div>Combined: ${selectedAnswer.combined_score.toFixed(4)}</div>
                <div>QA: ${selectedAnswer.qa_score.toFixed(4)}</div>
                <div>Retrieval: ${selectedAnswer.retrieval_score.toFixed(4)}</div>
            `);

                        // Reset dropdown
                        $(this).val(-1);
                    }
                });
            }


            function getBertAnswers(questions, topic, language) {
                const promises = questions.map(question => {
                    return new Promise((resolve, reject) => {
                        $.ajax({
                            type: "POST",
                            url: "{{ env('URL_API') }}/api/v1/answerpdf/bert-qa",
                            data: {
                                'topic_guid': topic,
                                'language': language,
                                'question': question.question
                            },
                            beforeSend: function(request) {
                                showLoading("Generating BERT answers...");
                                request.setRequestHeader("Authorization",
                                    "Bearer {{ $token }}");
                            },
                            success: function(response) {
                                // Find the best answer based on combined score
                                if (response.data && response.data.answers && response
                                    .data.answers.length > 0) {
                                    question.all_pdf_answers = response.data.answers;
                                    // Sort answers by combined_score
                                    const sortedAnswers = [...response.data.answers]
                                        .sort((a, b) =>
                                            b.combined_score - a.combined_score
                                        );

                                    // Get the best answer
                                    const bestAnswer = sortedAnswers[0];

                                    // Add answer to the question object
                                    question.pdf_answer = bestAnswer.answer;
                                    question.combined_score = bestAnswer.combined_score;
                                    question.qa_score = bestAnswer.qa_score;
                                    question.retrieval_score = bestAnswer
                                        .retrieval_score;
                                    question.context = bestAnswer.context;
                                }
                                resolve(question);

                            },
                            error: function(xhr) {
                                console.error(
                                    `Failed to get BERT answer: ${xhr.responseText}`
                                );
                                // Resolve anyway to continue with other questions
                                resolve(question);
                            }
                        });
                    });
                });

                return Promise.all(promises)
                    .then(updatedQuestions => {
                        updatePdfAnswersTable(updatedQuestions);
                        return updatedQuestions;
                    })


            }

            // Fungsi untuk mengecek apakah semua permintaan selesai
            function checkCompletion() {
                completedRequests++;
                console.log(completedRequests);
                console.log(totalRequests);
                if (completedRequests === totalRequests) {
                    // Jika semua permintaan selesai, hentikan loading
                }
            }



            let lastIndex = 0; // Variabel untuk menyimpan nomor terakhir

            function loadDataToTable(data, language, defaultThreshold) {


                // Tampilkan container tabel
                $('#table-container').show();

                // Periksa apakah DataTable sudah diinisialisasi
                if (!$.fn.DataTable.isDataTable('#table-data')) {
                    // Tambahkan properti yang diperlukan ke setiap item data
                    data.forEach((item, i) => {
                        item.index = i + 1; // Nomor urut
                        item.language = language; // Bahasa
                        item.checkbox = false; // Default checkbox tidak dicentang
                        item.threshold = item.threshold || defaultThreshold; // Threshold default
                    });
                    $('#table-data').DataTable({
                        data: data,
                        columns: [{
                                data: 'checkbox',
                                title: '<input type="checkbox" id="checkAll">',
                                orderable: false,
                                searchable: false,
                                render: function(data, type, row) {
                                    return `<input type="checkbox" class="row-checkbox" data-id="${row.guid}" ${data ? 'checked' : ''}>`;
                                }
                            },
                            {
                                data: 'index',
                                title: 'No'
                            },
                            {
                                data: 'language',
                                title: 'Language'
                            },
                            {
                                data: 'question',
                                title: 'Question',
                                className: 'text-wrap',
                                render: function(data, type, row) {
                                    if (type === 'display' && data.length > 100) {
                                        return `<span title="${data}">${data.substr(0, 100)}...</span>`;
                                    }
                                    return data;
                                }
                            },
                            {
                                data: 'category',
                                title: 'Category'
                            },
                            {
                                data: 'page_number',
                                title: 'Page'
                            },
                            {
                                data: 'cosine_q&d',
                                title: 'Cosine Similarity',
                                render: function(data) {
                                    const value = parseFloat(data);
                                    let color = '';
                                    if (value > 0.7) color = 'text-success';
                                    else if (value > 0.4) color = 'text-warning';
                                    else color = 'text-danger';

                                    return `<span class="${color}">${value.toFixed(2)}</span>`;
                                }
                            },
                            {
                                data: 'threshold',
                                title: 'Threshold',
                                render: function(data) {
                                    return `<input type="number" class="form-control threshold-input" value="${data || 0}" min="0" max="100">`;
                                }
                            },
                            {
                                data: null,
                                title: 'Actions',
                                orderable: false,
                                render: function(data, type, row) {
                                    return `
                            <div class="btn-group">
                                <button class="btn btn-sm btn-info preview-llm-btn" data-id="${row.guid}">
                                    <i class="fas fa-eye"></i> LLM
                                </button>
                                
                            </div>
                        `;
                                }
                            }
                        ],
                        destroy: true,
                        scrollX: true,
                    });
                } else {
                    // Tambahkan data ke tabel yang sudah ada
                    const table = $('#table-data').DataTable();
                    let maxIndex = 0;

                    // Find the current maximum index
                    table.data().each(function(item) {
                        if (item.index > maxIndex) {
                            maxIndex = item.index;
                        }
                    });

                    // Set indices for new data continuing from max index
                    data.forEach((item, i) => {
                        item.index = maxIndex + i + 1;
                        item.language = language;
                        item.checkbox = false;
                        item.threshold = item.threshold || defaultThreshold;
                    });

                    // Add rows to existing table
                    table.rows.add(data).draw();
                }

                // Update filter options
                updateFilterOptions();
            }

            function populateOtherTables(data) {
                // Populate LLM Comparison table
                if ($.fn.DataTable.isDataTable('#llm-table')) {
                    const llmTable = $('#llm-table').DataTable();

                    // Transform data for LLM table
                    const llmData = data.map(item => ({
                        index: item.index,
                        question: item.question,
                        answer_openai: item.answer_openai || '',
                        answer_gemini: item.answer_gemini || '',
                        other_models: '' // Could be populated if you have other model data
                    }));

                    llmTable.rows.add(llmData).draw();
                }

                // Populate PDF Answers table
                if ($.fn.DataTable.isDataTable('#pdf-answers-table')) {
                    const pdfTable = $('#pdf-answers-table').DataTable();

                    // Transform data for PDF table
                    const pdfData = data.map(item => ({
                        index: item.index,
                        question: item.question,
                        pdf_answer: item.pdf_answer || '',
                        combined_score: item.combined_score || 0,
                        qa_score: item.qa_score || 0,
                        retrieval_score: item.retrieval_score || 0
                    }));

                    pdfTable.rows.add(pdfData).draw();
                }
            }

            function initializeDataTables() {
                // Initialize the LLM comparison table
                $('#llm-table').DataTable({
                    columns: [{
                            data: 'index',
                            title: 'No'
                        },
                        {
                            data: 'question',
                            title: 'Question',
                            className: 'text-wrap',
                        },
                        {
                            data: 'answer_openai',
                            title: 'OpenAI Answer',
                            className: 'text-wrap'
                        },
                        {
                            data: 'answer_gemini',
                            title: 'Gemini Answer',
                            className: 'text-wrap'
                        },
                        {
                            data: 'other_models',
                            title: 'Other Models',
                            className: 'text-wrap'
                        }
                    ],
                    scrollX: true,
                    searching: true,
                    paging: true,
                    info: true
                });

                // Initialize the PDF answers table
                $('#pdf-answers-table').DataTable({
                    columns: [{
                            data: 'index',
                            title: 'No'
                        },
                        {
                            data: 'question',
                            title: 'Question',
                            className: 'text-wrap',
                        },
                        {
                            data: 'pdf_answer',
                            title: 'PDF Answer',
                            className: 'text-wrap'
                        },
                        {
                            data: 'combined_score',
                            title: 'Combined Score',
                            render: function(data) {
                                return parseFloat(data).toFixed(2);
                            }
                        },
                        {
                            data: 'qa_score',
                            title: 'QA Score',
                            render: function(data) {
                                return parseFloat(data).toFixed(2);
                            }
                        },
                        {
                            data: 'retrieval_score',
                            title: 'Retrieval Score',
                            render: function(data) {
                                return parseFloat(data).toFixed(2);
                            }
                        }
                    ],
                    scrollX: true,
                    searching: true,
                    paging: true,
                    info: true
                });
            }

            function updateFilterOptions() {
                // Clear current options
                $('#language-filters').empty();
                $('#category-filters').empty();

                // Get unique languages and categories
                const languages = new Set();
                const categories = new Set();

                $('#table-data').DataTable().data().each(function(item) {
                    if (item.language) languages.add(item.language);
                    if (item.category) categories.add(item.category);
                });

                // Add language filters
                languages.forEach(lang => {
                    $('#language-filters').append(`
                <div class="form-check">
                    <input class="form-check-input filter-language" type="checkbox" value="${lang}" id="lang-${lang}">
                    <label class="form-check-label" for="lang-${lang}">${lang}</label>
                </div>
            `);
                });

                // Add category filters
                categories.forEach(cat => {
                    $('#category-filters').append(`
                <div class="form-check">
                    <input class="form-check-input filter-category" type="checkbox" value="${cat}" id="cat-${cat}">
                    <label class="form-check-label" for="cat-${cat}">${cat}</label>
                </div>
            `);
                });
            }

            function applyTableFilters() {
                const table = $('#table-data').DataTable();

                // Get selected languages
                const selectedLanguages = [];
                $('.filter-language:checked').each(function() {
                    selectedLanguages.push($(this).val());
                });

                // Get selected categories
                const selectedCategories = [];
                $('.filter-category:checked').each(function() {
                    selectedCategories.push($(this).val());
                });

                // Get minimum similarity
                const minSimilarity = parseFloat($('#similarity-range').val()) / 100;

                // Apply custom filtering
                $.fn.dataTable.ext.search.push(
                    function(settings, data, dataIndex) {
                        // Skip tables other than main table
                        if (settings.nTable.id !== 'table-data') return true;

                        const rowData = table.row(dataIndex).data();

                        // Filter by language
                        if (selectedLanguages.length > 0 && !selectedLanguages.includes(rowData.language)) {
                            return false;
                        }

                        // Filter by category
                        if (selectedCategories.length > 0 && !selectedCategories.includes(rowData.category)) {
                            return false;
                        }

                        // Filter by similarity
                        if (parseFloat(rowData.cosine_similarity) < minSimilarity) {
                            return false;
                        }

                        return true;
                    }
                );

                // Redraw table with filters
                table.draw();

                // Remove the custom filter function
                $.fn.dataTable.ext.search.pop();
            }

            function resetTableFilters() {
                // Uncheck all filter checkboxes
                $('.filter-language, .filter-category').prop('checked', false);

                // Reset similarity slider
                $('#similarity-range').val(0);
                $('#similarity-value').text('0');

                // Redraw table without filters
                $('#table-data').DataTable().draw();
            }

            function refreshTabTable(tabId) {
                // Redraw the table in the active tab to ensure proper rendering
                switch (tabId) {
                    case '#llm-comparison':
                        if ($.fn.DataTable.isDataTable('#llm-table')) {
                            $('#llm-table').DataTable().columns.adjust().draw();
                        }
                        break;
                    case '#pdf-answers':
                        if ($.fn.DataTable.isDataTable('#pdf-answers-table')) {
                            $('#pdf-answers-table').DataTable().columns.adjust().draw();
                        }
                        break;
                    default:
                        if ($.fn.DataTable.isDataTable('#table-data')) {
                            $('#table-data').DataTable().columns.adjust().draw();
                        }
                }
            }

            function showLlmAnswersModal(rowData) {
                // Populate the modal with data
                $('#previewQuestionText').text(rowData.question);

                // Clear previous answers
                $('#llmAnswersAccordion').empty();

                // Add OpenAI answer
                if (rowData.answer_openai) {
                    appendLlmAnswer('openai', 'OpenAI', rowData.answer_openai);
                }

                // Add Gemini answer
                if (rowData.answer_gemini) {
                    appendLlmAnswer('gemini', 'Gemini', rowData.answer_gemini);
                }

                // Add answers from other models if available
                if (rowData.llm_answers && Array.isArray(rowData.llm_answers)) {
                    rowData.llm_answers.forEach(llm => {
                        // Skip if already added
                        if (llm.model_name.toLowerCase() !== 'openai' && llm.model_name.toLowerCase() !==
                            'gemini') {
                            appendLlmAnswer(llm.model_name.toLowerCase(), llm.model_name, llm.answer);
                        }
                    });
                }

                // Show the modal
                $('#previewAnswersModal').modal('show');
            }

            function appendLlmAnswer(id, name, answer) {
                $('#llmAnswersAccordion').append(`
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading-${id}">
                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapse-${id}" aria-expanded="true" aria-controls="collapse-${id}">
                    ${name} Answer
                </button>
            </h2>
            <div id="collapse-${id}" class="accordion-collapse collapse show"
                aria-labelledby="heading-${id}" data-bs-parent="#llmAnswersAccordion">
                <div class="accordion-body">
                    ${marked.parse(answer)}
                </div>
            </div>
        </div>
    `);
            }
            $(document).on('click', '#checkAll', function() {
                const isChecked = $(this).is(':checked');
                toggleAllCheckboxes(isChecked);
            });

            function toggleAllCheckboxes(isChecked) {
                // Cari semua checkbox di dalam tabel
                $('#table-data .row-checkbox').each(function() {
                    $(this).prop('checked', isChecked); // Set status checkbox sesuai dengan isChecked
                    const table = $('#table-data').DataTable();
                    const row = $(this).closest('tr');
                    const rowIndex = table.row(row).index();
                    const data = table.row(rowIndex).data();

                    // Perbarui data checkbox di DataTable
                    data.checkbox = isChecked;
                    table.row(rowIndex).data(data).draw(false); // Render ulang tabel tanpa reload
                });
            }

            $('#table-data').on('change', '.row-checkbox', function() {
                const table = $('#table-data').DataTable();
                const row = $(this).closest('tr');
                const rowIndex = table.row(row).index();
                const data = table.row(rowIndex).data();

                // Perbarui state checkbox di data
                data.checkbox = $(this).is(':checked');
                table.row(rowIndex).data(data).draw();

                // Perbarui status checkbox global
                const allCheckboxes = table.rows({
                    search: 'applied'
                }).data().toArray();
                const allChecked = allCheckboxes.every(row => row.checkbox);

                $('#checkAll').prop('checked', allChecked);
            });

            $('#apply-filters').on('click', function() {
                applyTableFilters();
            });
            $('#reset-filters').on('click', function() {
                resetTableFilters();
            });

            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                const targetTabId = $(e.target).attr('href');
                refreshTabTable(targetTabId);
            });

            // Preview LLM answers button handler
            $('#table-data').on('click', '.preview-llm-btn', function() {
                const rowData = $('#table-data').DataTable().row($(this).closest('tr')).data();
                showLlmAnswersModal(rowData);
            });


            // Event listener untuk tombol update di dalam modal
            // Event listener untuk tombol update di dalam modal
            $('#updateSelectedRows').on('click', function() {
                const bulkThreshold = $('#bulkThreshold').val();

                if (bulkThreshold === '') {
                    toastr.options.closeButton = true;
                    toastr.options.timeOut = 3000;
                    toastr.error('Please enter a value for threshold.');
                    return;
                }

                const table = $('#table-data').DataTable();

                // Iterasi melalui semua data di DataTable
                table.rows().every(function() {
                    const data = this.data();
                    // Periksa apakah baris ini dicentang
                    if (data.checkbox) {
                        data.threshold = bulkThreshold; // Update nilai threshold
                        this.data(data); // Simpan perubahan ke DataTable
                        $(this.node()).find('.threshold-input').val(threshold);

                    }
                });

                // Redraw tabel untuk memperbarui tampilan
                table.draw(false);

                $('#updateBulkModal').modal('hide');
                toastr.options.closeButton = true;
                toastr.options.timeOut = 3000;
                toastr.success('Selected rows updated successfully.');
            });


            $('#deleteSelectedRows').on('click', function() {
                const table = $('#table-data').DataTable();

                // Ambil semua checkbox yang dicentang
                const selectedRows = $('.row-checkbox:checked');

                if (selectedRows.length === 0) {
                    toastr.options.closeButton = true;
                    toastr.options.timeOut = 3000;
                    toastr.error('No rows selected.');
                    return;
                }

                if (!confirm('Are you sure you want to delete the selected rows?')) {
                    return;
                }

                // Loop melalui setiap checkbox yang dipilih dan hapus baris dari DataTable
                selectedRows.each(function() {
                    const row = $(this).closest('tr'); // Temukan baris terkait checkbox
                    table.row(row).remove().draw(
                        false); // Hapus baris dari DataTable tanpa submit ke backend
                });

                // Update nomor urut setelah penghapusan
                updateRowNumbers(table);

                toastr.options.closeButton = true;
                toastr.options.timeOut = 3000;
                toastr.success('Selected rows deleted successfully.');
            });

            // Fungsi untuk memperbarui nomor urut setelah penghapusan
            function updateRowNumbers(table) {
                table.rows().every(function(rowIdx, tableLoop, rowLoop) {
                    const data = this.data();
                    data.index = rowIdx + 1; // Update nomor urut berdasarkan posisi baris
                    this.data(data); // Update data di DataTable
                });
                table.draw(false); // Redraw tabel untuk menampilkan nomor urut yang diperbarui
            }


            $('#generate').click(function() {
                $('#generateQuestionModal').modal('show');
            });

            $('#questionInput').on('input', function() {
                if ($(this).val() !== '') {
                    $('#pdfUpload').hide();
                    // $('#skipButton').hide();
                } else {
                    $('#pdfUpload').show();
                    // $('#skipButton').show();
                }
            });

            $('#pdfInput').on('change', function() {
                if ($(this).prop('files').length > 0) {
                    // $('#nounInput').hide();
                    // $('#skipButton').show();
                } else {
                    // $('#nounInput').show();
                }
            });

            $('#skipInfo').tooltip();

            $.ajax({
                type: "GET",
                url: "{{ env('URL_API') }}/api/v1/user-course/user/{{ $id }}",
                beforeSend: function(request) {
                    request.setRequestHeader("Authorization", "Bearer {{ $token }}");
                },
                success: function(response) {
                    if (response.data && response.data.length > 0) {
                        response.data.forEach(element => {
                            $('#courseInput').append(
                                $("<option />").val(element.code).text(element.code + '-' +
                                    element.name)
                            );
                        });
                    } else {
                        console.log("No courses available.");
                    }
                },
                error: function(xhr) {
                    // Display error message using toastr
                    toastr.options.closeButton = true;
                    toastr.error("Error loading courses: " + xhr.responseText, "Error");
                }
            });

            $('#courseInput').on('change', function() {
                const course = $('#courseInput').val();
                $('#pdfUpload').hide();
                $('#filePathDisplay').hide();
                $('#fileLanguageContainer').hide();
                if (course) {
                    $('#topic').show();

                    $.ajax({
                        type: "POST",
                        url: "{{ env('URL_API') }}/api/v1/topic/filter/course",
                        data: {
                            'code': course
                        },
                        beforeSend: function(request) {
                            request.setRequestHeader("Authorization",
                                "Bearer {{ $token }}");
                        },
                        success: function(response) {
                            $('#topicInput').empty().append(
                                '<option value="" selected>Choose Topic</option>');
                            response.data.forEach(element => {
                                $('#topicInput').append($("<option />").val(element
                                    .guid).text(element.name));
                            });
                        },
                        error: function(xhr) {
                            // Display error message using toastr
                            toastr.options.closeButton = true;
                            toastr.error("Error loading topics: " + xhr.responseText, "Error");

                            // Log error to console for debugging purposes
                            console.error("Error loading topics:", xhr.responseText);
                        }
                    });
                } else {
                    $('#topic').hide();
                }
            });

            let cachedFilePath = null;
            $('#topicInput').on('change', function() {
                const topic = $(this).val();
                if (topic) {
                    $.ajax({
                        type: "GET",
                        url: "{{ env('URL_API') }}/api/v1/topic/" + topic,
                        beforeSend: function(request) {
                            request.setRequestHeader("Authorization",
                                "Bearer {{ $token }}");
                        },
                        success: function(response) {
                            cachedFilePath = response['data']['file_path'];
                            const fileName = cachedFilePath ? cachedFilePath.split('/').pop()
                                .replace(/^\w+_/, '') : '';

                            if (cachedFilePath) {
                                $('#filePath').html(
                                    `<a href="{{ env('URL_API') }}/storage/${cachedFilePath}" target="_blank">${fileName}</a>`
                                );
                                $('#filePathDisplay').show();
                                $('#deleteFilePath').show();
                                $('#pdfUpload').hide();
                                // $('#nounInput').hide();
                                // $('#skipButton').show();
                                $('#fileLanguageContainer')
                                    .hide(); // Hide "File Language" if file exists
                            } else {
                                $('#filePathDisplay').hide();
                                $('#deleteFilePath').hide();
                                $('#pdfUpload').show();
                                // $('#nounInput').show();
                                $('#fileLanguageContainer')
                                    .show(); // Show "File Language" if no file
                            }
                        },
                        error: function(xhr) {
                            // Display error message using toastr
                            toastr.options.closeButton = true;
                            toastr.error("Error checking topic file path: " + xhr.responseText,
                                "Error");

                            // Log error to console for debugging purposes
                            console.error("Error checking topic file path:", xhr.responseText);
                        }
                    });
                } else {
                    cachedFilePath = null;
                    $('#filePathDisplay').hide();
                    $('#deleteFilePath').hide();
                    $('#pdfUpload').hide();
                    // $('#nounInput').show();
                    $('#fileLanguageContainer').show(); // Show "File Language" if no topic selected
                }
            });


            $('#deleteFilePath').click(function() {
                const topicGuid = $('#topicInput').val();
                $.ajax({
                    url: "{{ env('URL_API') }}/api/v1/topic/delete-file",
                    type: "POST",
                    data: {
                        topic_guid: topicGuid
                    },
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization",
                            "Bearer {{ $token }}");
                    },
                    success: function() {
                        $('#filePathDisplay').hide();
                        $('#deleteFilePath').hide();
                        $('#pdfUpload').show();
                        $('#fileLanguageContainer').show();
                        cachedFilePath = null;

                        // Display success message using toastr
                        toastr.options.closeButton = true;
                        toastr.success("File has been successfully deleted", "Success");
                    },

                    error: function(xhr) {
                        // Display error message using toastr
                        toastr.options.closeButton = true;
                        toastr.error("Failed to delete file: " + xhr.statusText, "Error");
                    }

                });
            });

        });
    </script>
@endsection
