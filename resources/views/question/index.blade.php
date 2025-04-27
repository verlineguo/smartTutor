@extends('layouts.template')
@section('vendor-css')
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-checkboxes-jquery/datatables.checkboxes.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <!-- Row Group CSS -->
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
@endsection

@section('info-page')
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
        <li class="breadcrumb-item text-sm text-dark active text-capitalize" aria-current="page">
            List Question/{{ $name }}</li>
    </ol>
    <h5 class="font-weight-bolder mb-0 text-capitalize">List Question/{{ $name }}</h5>
@endsection

@section('content')
    <main class="main-content position-relative max-height-vh-100 h-100 mt-1 border-radius-lg ">
        <div class="container-xxl flex-grow-1 container-p-y">
            <!-- DataTable with Buttons -->
            <div class="card" id="card-block">

                <div class="card-body">
                    <div class="tab-content" id="questionsTabContent">
                        <!-- All Questions Tab -->
                        <div class="tab-pane fade show active" id="all-questions" role="tabpanel"
                            aria-labelledby="all-questions-tab">
                            <div class="action-buttons mb-3">
                                <button id="bulk-update-btn" class="btn btn-warning">
                                    <i class="fa-solid fa-wrench me-sm-1"></i> Bulk Update Threshold
                                </button>
                                <button id="bulk-delete-btn" class="btn btn-danger">
                                    <i class="fa-solid fa-trash me-sm-1"></i> Bulk Delete
                                </button>
                                <button id="add-question-btn" class="btn btn-primary">
                                    <i class="fa-solid fa-plus me-sm-1"></i> Add Question
                                </button>
                                <button id="export-excel-btn" class="btn btn-success">
                                    <i class="fa-solid fa-download me-sm-1"></i> Download Excel
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table" id="table-data">
                                    <thead>
                                        <tr>
                                            <th class="text-center"><input type="checkbox" id="select-all"></th>
                                            <th class="text-center">No</th>
                                            <th class="text-center">Question Fix</th>
                                            <th class="text-center">Answer Fix</th>
                                            <th class="text-center">Category</th>
                                            <th class="text-center">Page</th>
                                            <th class="text-center">Cosine Similarity</th>
                                            <th class="text-center">Threshold</th>
                                            <th class="text-center">Language</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Modal Add Question -->
            <div class="modal fade" id="modalAdd" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Question</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="add-form">
                                <div class="mb-3">
                                    <label for="add-question" class="form-label">Question</label>
                                    <textarea class="form-control" id="add-question" name="add-question" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="add-answer" class="form-label">Answer</label>
                                    <textarea class="form-control" id="add-answer" name="add-answer" rows="3" required></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="add-category" class="form-label">Category</label>
                                        <select class="form-select" id="add-category" name="add-category" required>
                                            <option value="">Select Category</option>
                                            <option value="remembering">Remembering</option>
                                            <option value="understanding">Understanding</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="add-threshold" class="form-label">Threshold</label>
                                        <input type="number" class="form-control" id="add-threshold"
                                            name="add-threshold" min="0" max="100" required>
                                        <div id="add-threshold-warning" class="text-danger"></div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="add-language" class="form-label">Language</label>
                                        <select class="form-select" id="add-language" name="add-language" required>
                                            <option value="">Select Language</option>
                                            <option value="English">English</option>
                                            <option value="Indonesian">Indonesian</option>
                                            <option value="Japanese">Japanese</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="add-page" class="form-label">Page</label>
                                        <input type="number" class="form-control" id="add-page" name="add-page"
                                            min="0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="add-weight" class="form-label">Weight</label>
                                        <input type="number" class="form-control" id="add-weight" name="add-weight"
                                            min="0" max="100" value="1">
                                    </div>
                                </div>
                                <button type="submit" id="submit-button-add" class="btn btn-primary">Submit</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Bulk Update Threshold -->
            <div class="modal fade" id="modalBulkUpdate" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Bulk Update Threshold</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="bulk-update-form">
                                <div class="mb-3">
                                    <label for="bulk-threshold" class="form-label">Threshold Value</label>
                                    <input type="number" class="form-control" id="bulk-threshold" name="bulk-threshold"
                                        min="0" max="100" required>
                                    <div id="bulk-threshold-warning" class="text-danger"></div>
                                </div>
                                <button type="submit" class="btn btn-primary">Update</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Delete -->
            <div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalCenterTitle">Delete Data</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col mb-3">
                                    <p>Are you sure you want to delete this data?</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <form id="delete-form">
                                <input id="delete-id" class="d-none" />
                                <button type="button" class="btn btn-label-secondary"
                                    data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" data-bs-dismiss="modal">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Edit -->
            <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Question</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="edit-form">
                                <input type="hidden" id="guid" name="guid">
                                <ul class="nav nav-tabs mb-3" id="edit-tabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="edit-question-tab" data-bs-toggle="tab"
                                            data-bs-target="#edit-question-content" type="button" role="tab"
                                            aria-controls="edit-question-content" aria-selected="true">Question
                                            Details</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="edit-pdf-tab" data-bs-toggle="tab"
                                            data-bs-target="#edit-pdf-content" type="button" role="tab"
                                            aria-controls="edit-pdf-content" aria-selected="false">PDF Answers</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="edit-llm-tab" data-bs-toggle="tab"
                                            data-bs-target="#edit-llm-content" type="button" role="tab"
                                            aria-controls="edit-llm-content" aria-selected="false">LLM Answers</button>
                                    </li>

                                </ul>
                                <div class="tab-content" id="editTabsContent">
                                    <!-- Question Details Tab -->
                                    <div class="tab-pane fade show active" id="edit-question-content" role="tabpanel"
                                        aria-labelledby="edit-question-tab">
                                        <div class="mb-3">
                                            <label for="edit-question-original" class="form-label">Original
                                                Question</label>
                                            <textarea class="form-control" id="edit-question-original" name="question" rows="2" readonly></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit-question" class="form-label">Question Fix</label>
                                            <textarea class="form-control" id="edit-question" name="question_fix" rows="2" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit-answer" class="form-label">Answer Fix</label>
                                            <textarea class="form-control" id="edit-answer" name="answer_fix" rows="3" required></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="edit-category" class="form-label">Category</label>
                                                <select class="form-select" id="edit-category" name="category" required>
                                                    <option value="">Select Category</option>
                                                    <option value="remembering">Remembering</option>
                                                    <option value="understanding">Understanding</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="edit-threshold" class="form-label">Threshold</label>
                                                <input type="number" class="form-control" id="edit-threshold"
                                                    name="threshold" min="0" max="100" required>
                                                <div id="edit-threshold-warning" class="text-danger"></div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="edit-language" class="form-label">Language</label>
                                                <select class="form-select" id="edit-language" name="language" required>
                                                    <option value="">Select Language</option>
                                                    <option value="english">English</option>
                                                    <option value="indonesian">Indonesian</option>
                                                    <option value="japanese">Japanese</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="edit-page" class="form-label">Page</label>
                                                <input type="number" class="form-control" id="edit-page" name="page"
                                                    min="0">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="edit-weight" class="form-label">Weight</label>
                                                <input type="number" class="form-control" id="edit-weight"
                                                    name="weight" min="0" max="100">
                                            </div>
                                        </div>
                                    </div>
                                    <!-- LLM Answers Tab -->
                                    <div class="tab-pane fade" id="edit-llm-content" role="tabpanel"
                                        aria-labelledby="edit-llm-tab">
                                        <div class="mb-3">
                                            <label for="answer-openai" class="form-label">OpenAI Answer</label>
                                            <textarea class="form-control" id="answer-openai" name="answer_openai" rows="4" readonly></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="answer-gemini" class="form-label">Gemini Answer</label>
                                            <textarea class="form-control" id="answer-gemini" name="answer_gemini" rows="4" readonly></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="answer-llama" class="form-label">llama Answer</label>
                                            <textarea class="form-control" id="answer-llama" name="answer_llama" rows="4" readonly></textarea>
                                        </div>
                                    </div>
                                    <!-- PDF Answers Tab -->
                                    <div class="tab-pane fade" id="edit-pdf-content" role="tabpanel"
                                        aria-labelledby="edit-pdf-tab">
                                        <div class="mb-3">
                                            <div id="pdf-answers-container">
                                                <!-- PDF answers will be loaded here dynamically -->
                                                <div class="text-center py-3">
                                                    <div class="spinner-border text-primary" role="status"></div>
                                                    <p>Loading PDF answers...</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="selected-pdf-answer" class="form-label">Selected PDF
                                                Answer</label>
                                            <textarea class="form-control" id="selected-pdf-answer" name="selected_pdf_answer" rows="4"></textarea>
                                        </div>
                                        <button type="button" id="use-selected-answer" class="btn btn-secondary">Use
                                            Selected Answer</button>
                                    </div>
                                </div>
                                <button type="submit" id="submit-button-edit" class="btn btn-primary mt-3">Save
                                    Changes</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@section('vendor-javascript')
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>


    <script src="{{ asset('./assets/dashboard/datatables/jquery.dataTables.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-responsive/datatables.responsive.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-responsive-bs5/responsive.bootstrap5.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-checkboxes-jquery/datatables.checkboxes.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="{{ asset('./assets/dashboard/datatables-buttons/datatables-buttons.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-buttons-bs5/buttons.bootstrap5.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-buttons/buttons.html5.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-buttons/buttons.print.js') }}"></script>
    <!-- Row Group JS -->
    <script src="{{ asset('./assets/dashboard/datatables-rowgroup/datatables.rowgroup.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-rowgroup-bs5/rowgroup.bootstrap5.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.blockUI/2.70/jquery.blockUI.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
@endsection

@section('custom-javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $('#table-data').on('draw.dt', function() {
    $('[data-bs-toggle="tooltip"]').tooltip();
});
            // Helper functions for rendering data
            function wrapTextWithTooltip(data) {
    const maxLength = 50; // Panjang maksimum teks sebelum dipotong
    if (data && data.length > maxLength) {
        return `
            <span data-bs-toggle="tooltip" title="${data}">
                ${data.substring(0, maxLength)}...
            </span>
        `;
    }
    return `<span>${data}</span>`;
}

            function wrapText(data) {
                return data ? `<div class='text-wrap' style='text-align: justify;'>${data}</div>` : '-';
            }

            function wrapNullable(data) {
                return `<div class='text-wrap'>${data ? data : '-'}</div>`;
            }

            function isMobile() {
                return window.innerWidth < 768;
            }

            var questionsTable = $('#table-data').DataTable({
                "processing": true,
                "ajax": {
                    "url": "{{ env('URL_API') }}/api/v1/question/show/{{ $guid }}",
                    "type": "GET",
                    "beforeSend": function(request) {
                        request.setRequestHeader("Authorization", "Bearer {{ $token }}");
                    }
                },
                "columns": [{
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center dt-control',
                        render: function(data, type, row) {
                            return `<input type="checkbox" class="row-checkbox" value="${row.guid}">`;
                        },
                        responsivePriority: 1
                    },
                    {
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        responsivePriority: 2
                    },
                    {
                        data: 'question_fix',
                        render: wrapText,
                        responsivePriority: 1
                    },
                    {
                        data: 'answer_fix',
                        render: wrapTextWithTooltip,
                        responsivePriority: 3
                    },
                    {
                        data: 'category',
                        render: wrapText,
                        className: 'text-center',
                        responsivePriority: 4
                    },
                    {
                        data: 'page',
                        render: wrapNullable,
                        className: 'text-center',
                        responsivePriority: 6
                    },
                    {
                        data: 'cossine_similarity',
                        render: wrapNullable,
                        className: 'text-center',
                        responsivePriority: 7
                    },
                    {
                        data: 'threshold',
                        render: wrapText,
                        className: 'text-center',
                        responsivePriority: 5
                    },
                    {
                        data: 'language',
                        render: wrapText,
                        className: 'text-center',
                        responsivePriority: 6
                    },
                    {
                        data: null,
                        render: function(data) {
                            return `
                        <a role="button" class="edit-btn open-edit-dialog" data-guid="${data.guid}">
                            <i class="fa-solid fa-pen-to-square" style="font-size: 15px; color: yellow;"></i>
                        </a>
                        <a role="button" class="delete-btn open-delete-dialog" data-bs-toggle="modal" data-bs-target="#modalDelete" data-guid="${data.guid}">
                            <i class="fa-solid fa-trash" style="font-size: 15px; color: red;"></i>
                        </a>`;
                        },
                        orderable: false,
                        searchable: false,
                        width: '80px',
                        className: 'text-center',
                        responsivePriority: 1
                    },
                ],
                "scrollX": true,
                "responsive": {
                    details: {
                        type: 'column',
                        renderer: function(api, rowIdx, columns) {
                            var data = $.map(columns, function(col, i) {
                                if (col.hidden) {
                                    return '<div class="row mb-2">' +
                                        '<div class="col-4 fw-bold">' + col.title + '</div>' +
                                        '<div class="col-8">' + col.data + '</div>' +
                                        '</div>';
                                }
                                return '';
                            }).join('');

                            return data ?
                                $('<div class="card card-body border-0 p-3"/>')
                                .append($('<div class="details-container"/>').append(data)) :
                                false;
                        }
                    }
                },
                "language": {
                    "emptyTable": "No data available",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoEmpty": "No entries found",
                    "lengthMenu": "Show _MENU_ entries",
                    "loadingRecords": "Loading...",
                    "processing": "Processing...",
                    "zeroRecords": "No matching records found",
                    "paginate": {
                        "first": "<i class='fa-solid fa-angles-left'></i>",
                        "last": "<i class='fa-solid fa-angles-right'></i>",
                        "next": "<i class='fa-solid fa-angle-right'></i>",
                        "previous": "<i class='fa-solid fa-angle-left'></i>",
                    },
                },
                "lengthMenu": [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"],
                ],
            });

            $(window).on('resize', function() {
                questionsTable.responsive.recalc();
                questionsTable.columns.adjust();
            });

            if (isMobile()) {
                // Create floating action button for mobile
                $('body').append(`
            <div class="position-fixed bottom-0 end-0 m-3" style="z-index: 1050;">
                <div class="dropup">
                    <button class="btn btn-primary btn-lg rounded-circle shadow" type="button" id="floatingActionBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="floatingActionBtn">
                        <li><a class="dropdown-item" href="#" id="mobile-add-btn">
                            <i class="fa-solid fa-plus me-2"></i>Add Question
                        </a></li>
                        <li><a class="dropdown-item" href="#" id="mobile-bulk-update-btn">
                            <i class="fa-solid fa-wrench me-2"></i>Bulk Update
                        </a></li>
                        <li><a class="dropdown-item" href="#" id="mobile-bulk-delete-btn">
                            <i class="fa-solid fa-trash me-2"></i>Bulk Delete
                        </a></li>
                        <li><a class="dropdown-item" href="#" id="mobile-export-btn">
                            <i class="fa-solid fa-download me-2"></i>Export Excel
                        </a></li>
                    </ul>
                </div>
            </div>
        `);

                // Link mobile action buttons to their desktop counterparts
                $('#mobile-add-btn').on('click', function() {
                    $('#add-question-btn').click();
                });

                $('#mobile-bulk-update-btn').on('click', function() {
                    $('#bulk-update-btn').click();
                });

                $('#mobile-bulk-delete-btn').on('click', function() {
                    $('#bulk-delete-btn').click();
                });

                $('#mobile-export-btn').on('click', function() {
                    $('#export-excel-btn').click();
                });
            }



            // Handle individual checkboxes
            $('#table-data tbody').on('change', 'input.row-checkbox', function() {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                } else {
                    var allChecked = true;
                    $('#table-data tbody input.row-checkbox').each(function() {
                        if (!this.checked) {
                            allChecked = false;
                            return false;
                        }
                    });
                    $('#select-all').prop('checked', allChecked);
                }
            });

            // Get selected rows
            function getSelectedRows() {
                var selected = [];
                questionsTable.rows().every(function() {
                    var row = $(this.node());
                    if ($('input.row-checkbox', row).prop('checked')) {
                        selected.push(this.data().guid);
                    }
                });
                return selected;
            }

            // Toggle tables based on tabs
            $('.question-tabs .nav-link').on('click', function() {
                const tabId = $(this).attr('data-bs-target');

                // Adjust DataTables when they become visible
                if (tabId === '#all-questions') {
                    setTimeout(() => questionsTable.columns.adjust(), 10);
                }
            });

            // Handle Bulk Update Threshold
            $('#bulk-update-btn').on('click', function() {
                var selectedRows = getSelectedRows();
                if (selectedRows.length === 0) {
                    toastr.warning('No questions selected. Please select at least one question.',
                    'Warning');
                    return;
                }
                $('#modalBulkUpdate').modal('show');
            });

            $('#bulk-update-form').on('submit', function(e) {
                e.preventDefault();
                var selectedRows = getSelectedRows();
                var newThreshold = parseFloat($('#bulk-threshold').val());

                if (!newThreshold || isNaN(newThreshold) || newThreshold < 0 || newThreshold > 100) {
                    $('#bulk-threshold-warning').text(
                        'Please enter a valid threshold value between 0 and 100.');
                    return;
                } else {
                    $('#bulk-threshold-warning').text('');
                }

                $.ajax({
                    url: "{{ env('URL_API') }}/api/v1/question/bulk-update-threshold",
                    type: "POST",
                    contentType: "application/json",
                    data: JSON.stringify({
                        guids: selectedRows,
                        threshold: newThreshold,
                    }),
                    beforeSend: function(request) {
                        $.blockUI({
                            message: '<div class="spinner-border text-primary" role="status"></div><span class="ms-2">Updating...</span>',
                            css: {
                                border: 'none',
                                backgroundColor: 'transparent'
                            }
                        });
                        request.setRequestHeader("Authorization",
                        "Bearer {{ $token }}");
                    },
                    success: function(response) {
                        $.unblockUI();
                        toastr.options.closeButton = true;
                        toastr.options.timeOut = 2000;
                        toastr.success('Threshold updated successfully for ' + selectedRows
                            .length + ' questions.', "Success");
                        $('#modalBulkUpdate').modal('hide');
                        questionsTable.ajax.reload();
                    },
                    error: function(xhr) {
                        $.unblockUI();
                        var errorMessage = 'An error occurred while updating thresholds.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.options.closeButton = true;
                        toastr.error(errorMessage, "Error");
                    }
                });
            });

            // Handle Bulk Delete
            $('#bulk-delete-btn').on('click', function() {
                var selectedRows = getSelectedRows();
                if (selectedRows.length === 0) {
                    toastr.warning('No questions selected. Please select at least one question.',
                    'Warning');
                    return;
                }

                if (confirm('Are you sure you want to delete ' + selectedRows.length +
                        ' selected questions? This action cannot be undone.')) {
                    $.ajax({
                        url: "{{ env('URL_API') }}/api/v1/question/bulk-delete",
                        type: "POST",
                        contentType: "application/json",
                        data: JSON.stringify({
                            guids: selectedRows,
                        }),
                        beforeSend: function(request) {
                            $.blockUI({
                                message: '<div class="spinner-border text-primary" role="status"></div><span class="ms-2">Deleting...</span>',
                                css: {
                                    border: 'none',
                                    backgroundColor: 'transparent'
                                }
                            });
                            request.setRequestHeader("Authorization",
                                "Bearer {{ $token }}");
                        },
                        success: function(response) {
                            $.unblockUI();
                            toastr.options.closeButton = true;
                            toastr.options.timeOut = 2000;
                            toastr.success('Successfully deleted ' + selectedRows.length +
                                ' questions.', "Success");
                            questionsTable.ajax.reload();
                        },
                        error: function(xhr) {
                            $.unblockUI();
                            var errorMessage = 'An error occurred while deleting questions.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            toastr.options.closeButton = true;
                            toastr.error(errorMessage, "Error");
                        }
                    });
                }
            });

            // Handle Add Question button
            $('#add-question-btn').on('click', function() {
                // Reset form fields
                $('#add-form')[0].reset();
                $('#add-threshold-warning').text('');
                $('#modalAdd').modal('show');
            });

            // Handle Add Question form submission
            $('#add-form').on('submit', function(e) {
                e.preventDefault();

                // Validate threshold
                var threshold = parseFloat($('#add-threshold').val());
                if (!threshold || isNaN(threshold) || threshold < 0 || threshold > 100) {
                    $('#add-threshold-warning').text(
                        'Please enter a valid threshold value between 0 and 100.');
                    return;
                } else {
                    $('#add-threshold-warning').text('');
                }

                // Get form values
                var question = $('#add-question').val();
                var answer = $('#add-answer').val();
                var category = $('#add-category').val();
                var language = $('#add-language').val();
                var page = $('#add-page').val() || null;
                var weight = $('#add-weight').val() || 1;

                $.ajax({
                    type: "POST",
                    url: "{{ env('URL_API') }}/api/v1/question",
                    data: {
                        question: question,
                        question_fix: question,
                        answer_fix: answer,
                        category: category,
                        threshold: threshold,
                        language: language,
                        page: page,
                        weight: weight,
                        topic_id: "{{ $guid }}"
                    },
                    beforeSend: function(request) {
                        $.blockUI({
                            message: '<div class="spinner-border text-primary" role="status"></div><span class="ms-2">Adding question...</span>',
                            css: {
                                border: 'none',
                                backgroundColor: 'transparent'
                            }
                        });
                        request.setRequestHeader("Authorization",
                        "Bearer {{ $token }}");
                    },
                    success: function(result) {
                        $.unblockUI();
                        toastr.options.closeButton = true;
                        toastr.options.timeOut = 2000;
                        toastr.success("Question added successfully", "Success");
                        $('#modalAdd').modal('hide');
                        questionsTable.ajax.reload();
                    },
                    error: function(xhr) {
                        $.unblockUI();
                        var errorMessage = 'An error occurred while adding the question.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.options.closeButton = true;
                        toastr.error(errorMessage, "Error");
                    }
                });
            });

            // Handle Export to Excel
            $('#export-excel-btn').on('click', function() {
                window.location.href =
                    "{{ env('URL_API') }}/api/v1/question/export/{{ $guid }}?token={{ $token }}";
            });

            // Handle Delete button click
            $(document).on('click', '.delete-btn', function() {
                var guid = $(this).data('guid');
                $('#delete-id').val(guid);
                $('#modalDelete').modal('show');
            });

            // Handle Delete form submission
            $('#delete-form').on('submit', function(e) {
                e.preventDefault();
                var guid = $('#delete-id').val();

                $.ajax({
                    type: "DELETE",
                    url: "{{ env('URL_API') }}/api/v1/question/" + guid,
                    beforeSend: function(request) {
                        $.blockUI({
                            message: '<div class="spinner-border text-primary" role="status"></div><span class="ms-2">Deleting...</span>',
                            css: {
                                border: 'none',
                                backgroundColor: 'transparent'
                            }
                        });
                        request.setRequestHeader("Authorization",
                        "Bearer {{ $token }}");
                    },
                    success: function(result) {
                        $.unblockUI();
                        toastr.options.closeButton = true;
                        toastr.options.timeOut = 2000;
                        toastr.success("Question deleted successfully", "Success");
                        $('#modalDelete').modal('hide');
                        questionsTable.ajax.reload();
                    },
                    error: function(xhr) {
                        $.unblockUI();
                        var errorMessage = 'An error occurred while deleting the question.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.options.closeButton = true;
                        toastr.error(errorMessage, "Error");
                    }
                });
            });

            // When editing a question, fetch PDF answers
            $(document).on('click', '.edit-btn', function() {
                var guid = $(this).data('guid');
                $('#guid').val(guid);
                $('#edit-threshold-warning').text('');

                $.ajax({
                    type: "GET",
                    url: "{{ env('URL_API') }}/api/v1/question/" + guid,
                    beforeSend: function(request) {
                        $.blockUI({
                            message: '<div class="spinner-border text-primary" role="status"></div><span class="ms-2">Loading question data...</span>',
                            css: {
                                border: 'none',
                                backgroundColor: 'transparent'
                            }
                        });
                        request.setRequestHeader("Authorization",
                        "Bearer {{ $token }}");
                    },
                    success: function(result) {
                        $.unblockUI();
                        var data = result.data;

                        // Fill form fields with question data
                        $('#edit-question-original').val(data.question);
                        $('#edit-question').val(data.question_fix);
                        $('#edit-answer').val(data.answer_fix);
                        $('#edit-category').val(data.category);
                        $('#edit-threshold').val(data.threshold);
                        $('#edit-language').val(data.language);
                        $('#edit-page').val(data.page);
                        $('#edit-weight').val(data.weight || 1);

                        // Get LLM answers
                        $.ajax({
                            type: "GET",
                            url: "{{ env('URL_API') }}/api/v1/question/llm-answers-by-question/" +
                                guid,
                            beforeSend: function(request) {
                                request.setRequestHeader("Authorization",
                                    "Bearer {{ $token }}");
                            },
                            success: function(llmResult) {
                                // Process LLM answers
                                // Replace the LLM answers part of the success function
                                if (llmResult.data) {
                                    var openai = llmResult.data.find(a => a
                                        .source === 'openai');
                                    var gemini = llmResult.data.find(a => a
                                        .source === 'gemini');
                                    var llama = llmResult.data.find(a => a
                                        .source === 'llama');

                                    // Replace textareas with formatted div elements
                                    $('#edit-llm-content').html(`
        <div class="mb-3">
            <label class="form-label">OpenAI Answer</label>
            <div class="form-control markdown-content" style="height: auto; min-height: 150px; overflow-y: auto;">
                ${openai ? marked.parse(openai.answer) : 'No answer available'}
            </div>
            <input type="hidden" id="answer-openai-raw" value="${openai ? encodeURIComponent(openai.answer) : ''}">
        </div>
        <div class="mb-3">
            <label class="form-label">Gemini Answer</label>
            <div class="form-control markdown-content" style="height: auto; min-height: 150px; overflow-y: auto;">
                ${gemini ? marked.parse(gemini.answer) : 'No answer available'}
            </div>
            <input type="hidden" id="answer-gemini-raw" value="${gemini ? encodeURIComponent(gemini.answer) : ''}">
        </div>
        <div class="mb-3">
            <label class="form-label">LLama Answer</label>
            <div class="form-control markdown-content" style="height: auto; min-height: 150px; overflow-y: auto;">
                ${llama ? marked.parse(llama.answer) : 'No answer available'}
            </div>
            <input type="hidden" id="answer-llama-raw" value="${llama ? encodeURIComponent(llama.answer) : ''}">
        </div>
    `);


                                } else {
                                    $('#answer-openai').val('No answer available');
                                    $('#answer-gemini').val('No answer available');
                                    $('#answer-llama').val(
                                    'No answer available');
                                }


                                // Get PDF answers
                                $.ajax({
                                    type: "GET",
                                    url: "{{ env('URL_API') }}/api/v1/question/pdf-answers-by-question/" +
                                        guid,
                                    beforeSend: function(request) {
                                        request.setRequestHeader(
                                            "Authorization",
                                            "Bearer {{ $token }}"
                                            );
                                    },
                                    success: function(pdfResult) {
                                        // Process PDF answers
                                        var pdfContainer = $(
                                            '#pdf-answers-container'
                                            );
                                        pdfContainer.empty();

                                        if (pdfResult.data && pdfResult
                                            .data.length > 0) {
                                            var pdfAnswersHtml =
                                                '<div class="pdf-answers-list">';

                                            pdfResult.data.forEach(
                                                function(item,
                                                    index) {
                                                    pdfAnswersHtml
                                                        += `
                                    <div class="card mb-3 pdf-answer-card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>Combined Score:</strong> ${item.combined_score || 'N/A'}</span>
                                                <span class="ms-3"><strong>QA Score:</strong> ${item.qa_score || 'N/A'}</span>
                                                <span class="ms-3"><strong>Retrieval Score:</strong> ${item.retrieval_score || 'N/A'}</span>

                                            </div>
                                            <div>
                                                <button type="button" class="btn btn-sm btn-primary select-pdf-btn" 
                                                    data-answer="${encodeURIComponent(item.answer)}">
                                                    Select
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">${item.answer}</p>
                                        </div>
                                    </div>`;
                                                });

                                            pdfAnswersHtml += '</div>';
                                            pdfContainer.html(
                                                pdfAnswersHtml);

                                            // Handle PDF answer selection
                                            $('.select-pdf-btn').on(
                                                'click',
                                                function() {
                                                    var selectedAnswer =
                                                        decodeURIComponent(
                                                            $(this)
                                                            .data(
                                                                'answer'
                                                                ));
                                                    $('#selected-pdf-answer')
                                                        .val(
                                                            selectedAnswer
                                                            );

                                                    // Highlight selected card
                                                    $('.pdf-answer-card')
                                                        .removeClass(
                                                            'border-primary'
                                                            );
                                                    $(this).closest(
                                                            '.pdf-answer-card'
                                                            )
                                                        .addClass(
                                                            'border-primary'
                                                            );
                                                });
                                        } else {
                                            pdfContainer.html(
                                                '<div class="alert alert-info">No PDF answers available for this question.</div>'
                                                );
                                        }

                                        // Show modal after all data is loaded
                                        $('#modalEdit').modal('show');
                                    },
                                    error: function() {
                                        $('#pdf-answers-container')
                                            .html(
                                                '<div class="alert alert-danger">Error loading PDF answers.</div>'
                                                );
                                        $('#modalEdit').modal('show');
                                    }
                                });
                            },
                            error: function() {
                                // Show modal even if LLM answers couldn't be fetched
                                $('#answer-openai').val('Error loading answer');
                                $('#answer-gemini').val('Error loading answer');
                                $('#answer-llama').val('Error loading answer');

                                // Still try to load PDF answers
                                $.ajax({
                                    type: "GET",
                                    url: "{{ env('URL_API') }}/api/v1/question/pdf-answers-by-question/" +
                                        guid,
                                    beforeSend: function(request) {
                                        request.setRequestHeader(
                                            "Authorization",
                                            "Bearer {{ $token }}"
                                            );
                                    },
                                    success: function(pdfResult) {
                                        // Process PDF answers (same code as above)
                                        // ...
                                        $('#modalEdit').modal('show');
                                    },
                                    error: function() {
                                        $('#pdf-answers-container')
                                            .html(
                                                '<div class="alert alert-danger">Error loading PDF answers.</div>'
                                                );
                                        $('#modalEdit').modal('show');
                                        $('#edit-llm-content').html(
                                            '<div class="alert alert-danger">Error loading LLM answers.</div>'
                                            );

                                    }
                                });
                            }
                        });
                    },
                    error: function(xhr) {
                        $.unblockUI();
                        var errorMessage = 'An error occurred while loading question data.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.options.closeButton = true;
                        toastr.error(errorMessage, "Error");
                    }
                });
            });


            // Handle "Use Selected Answer" button
            $('#use-selected-answer').on('click', function() {
                var selectedAnswer = $('#selected-pdf-answer').val();
                if (selectedAnswer.trim() !== '') {
                    $('#edit-answer').val(selectedAnswer);
                    // Switch to the question details tab
                    $('#edit-question-tab').tab('show');
                    toastr.info('PDF answer applied to the answer field', 'Info');
                } else {
                    toastr.warning('No PDF answer selected', 'Warning');
                }
            });

            // Handle Edit form submission
            $('#edit-form').on('submit', function(e) {
                e.preventDefault();

                // Validate threshold
                var threshold = parseFloat($('#edit-threshold').val());
                if (!threshold || isNaN(threshold) || threshold < 0 || threshold > 100) {
                    $('#edit-threshold-warning').text(
                        'Please enter a valid threshold value between 0 and 100.');
                    return;
                } else {
                    $('#edit-threshold-warning').text('');
                }



                // Get form values
                var guid = $('#guid').val();
                var question_fix = $('#edit-question').val();
                var answer_fix = $('#edit-answer').val();
                var category = $('#edit-category').val();
                var language = $('#edit-language').val();
                var page = $('#edit-page').val() || null;
                var weight = $('#edit-weight').val() || 1;

                $.ajax({
                    type: "PUT",
                    url: "{{ env('URL_API') }}/api/v1/question",
                    contentType: "application/json",
                    data: JSON.stringify({
                        guid: guid,
                        question_fix: question_fix,
                        answer_fix: answer_fix,
                        category: category,
                        threshold: threshold,
                        language: language,
                        page: page,
                        weight: weight,
                        topic_id: "{{ $guid }}"
                    }),
                    beforeSend: function(request) {
                        $.blockUI({
                            message: '<div class="spinner-border text-primary" role="status"></div><span class="ms-2">Updating question...</span>',
                            css: {
                                border: 'none',
                                backgroundColor: 'transparent'
                            }
                        });
                        request.setRequestHeader("Authorization",
                        "Bearer {{ $token }}");
                    },
                    success: function(result) {
                        $.unblockUI();
                        toastr.options.closeButton = true;
                        toastr.options.timeOut = 2000;
                        toastr.success("Question updated successfully", "Success");
                        $('#modalEdit').modal('hide');
                        questionsTable.ajax.reload();
                        llmTable.ajax.reload();
                    },
                    error: function(xhr) {
                        $.unblockUI();
                        var errorMessage = 'An error occurred while updating the question.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.options.closeButton = true;
                        toastr.error(errorMessage, "Error");
                    }
                });
            });



            // Threshold input validation
            $('#add-threshold, #edit-threshold, #bulk-threshold').on('input', function() {
                var value = parseFloat($(this).val());
                var warningId = '#' + $(this).attr('id') + '-warning';

                if (!value || isNaN(value) || value < 0 || value > 100) {
                    $(warningId).text('Please enter a valid threshold value between 0 and 100.');
                } else {
                    $(warningId).text('');
                }
            });
        });
    </script>
@endsection
