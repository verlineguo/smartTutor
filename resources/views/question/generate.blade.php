@extends('layouts.template')
@section('vendor-css')
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-checkboxes-jquery/datatables.checkboxes.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <!-- Row Group CSS -->
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/css/generate.css') }}" />
@endsection
@section('info-page')
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
        <li class="breadcrumb-item text-sm text-dark active text-capitalize" aria-current="page">
            generate</li>
    </ol>
    <h5 class="font-weight-bolder mb-0 text-capitalize">generate</h5>
@endsection

@section('content')
    <main class="main-content position-relative max-height-vh-100 h-100 mt-1 border-radius-lg ">
        {{-- <div class="col-md-3"><a class="btn btn-primary w-100" role="button" id="generate">Generate
                Question<i class="" style="text-decoration: none; margin-left: 10px;"></i></a>
        </div> --}}
        <div class="container d-flex justify-content-center align-items-center">
            <div class="row d-flex align-items-center justify-content-center">
                <!-- Bagian untuk tombol generate -->
                <div class="col-md-6 order-md-1 order-2">
                    <a class="btn btn-primary w-100" role="button" id="generate">Generate Question<i class=""
                            style="text-decoration: none; margin-left: 10px;"></i></a>
                </div>
                <!-- Bagian untuk keterangan -->
                <div class="col-md-6 order-md-2 order-1">
                    <p class="text-start">Silakan tekan tombol "Generate Question" untuk membuat pertanyaan baru. Anda dapat
                        memilih bahasa, kursus, dan topik yang sesuai untuk pertanyaan yang ingin Anda buat.</p>
                </div>
            </div>
        </div>
        <div id="loading" style="display: none;">
            <div class="loader"></div>
            <div>Loading... <span id="progressPercentage">0%</span></div>
        </div>
        <div id="loadingScreen" style="display: none;">
            <div id="loadingMessage">Loading...</div>
            <div id="progressBarContainer">
                <div id="progressBar"></div>
            </div>
        </div>
        <div class="container-xxl flex-grow-1 container-p-y" id="table-container">
            <!-- DataTable with Buttons -->
            <div class="card" id="card-block">
                <div class="card-datatable table-responsive pt-0">
                    <table class="table" id="table-data">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Question</th>
                                <th>Answer</th>
                                <th>Category</th>
                                <th>Page</th>
                                <th>Cossine Similarity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal fade" id="generateQuestionModal" tabindex="-1" aria-labelledby="generateQuestionModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="generateQuestionModalLabel">Generate Question</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="generateQuestionForm">
                            <div class="mb-3" id="text">
                                <label for="questionInput" class="form-label">Noun with ","</label>
                                <input type="text" class="form-control" id="questionInput">
                            </div>
                            <div class="mb-3" id="pdf">
                                <label for="pdfInput" class="form-label">Upload PDF</label>
                                <input type="file" class="form-control" id="pdfInput" name="pdfInput" accept=".pdf">
                                <button type="button" class="btn btn-danger mt-2" id="cancelPdf">Cancel</button>
                            </div>
                            <div class="mb-3">
                                <label for="language" class="form-label">Language</label>
                                <select class="form-select" aria-label="Default select example" id="language">
                                    <option value="english" selected>English</option>
                                    <option value="indonesia" selected>Indonesia</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="courseInput" class="form-label">Course</label>
                                <select class="form-select" aria-label="Default select example" id="courseInput">
                                    <option value="" selected>Choose Course</option>
                                </select>
                            </div>
                            <div class="mb-3" id="topic">
                                <label for="topicInput" class="form-label">Topic</label>
                                <select class="form-select" aria-label="Default select example" id="topicInput" required>
                                    <option value="" selected>Choose Topic</option>
                                </select>
                            </div>
                            <div class="mb-3 form-check" id="skipButton">
                                <input type="checkbox" class="form-check-input" id="skipPagesCheckbox">
                                <label class="form-check-label" for="skipPagesCheckbox">Skip Unprocessable Pages</label>
                            </div>
                            <!-- Add other input fields as needed -->
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
@section('vendor-javascript')
    <script src="{{ asset('./assets/dashboard/datatables/jquery.dataTables.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-responsive/datatables.responsive.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-responsive-bs5/responsive.bootstrap5.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-checkboxes-jquery/datatables.checkboxes.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-buttons/datatables-buttons.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-buttons-bs5/buttons.bootstrap5.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-buttons/buttons.html5.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-buttons/buttons.print.js') }}"></script>
    <!-- Row Group JS -->
    <script src="{{ asset('./assets/dashboard/datatables-rowgroup/datatables.rowgroup.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/datatables-rowgroup-bs5/rowgroup.bootstrap5.js') }}"></script>
@endsection
@section('custom-javascript')
    <script type="text/javascript">
        $('#table-container').hide();
        $('#topic').hide();

        $('#cancelPdf').click(function() {
            $('#pdfInput').val('');
            $('#text').show();
        });

        $('#questionInput').on('input', function() {
            if ($(this).val() !== '') {
                $('#pdf').hide();
                $('#skipButton').hide();
                $('#text').show();
            } else {
                $('#pdf').show();
                $('#skipButton').show();
            }
        });

        $('#pdfInput').on('change', function() {
            if ($(this).prop('files').length > 0) {
                $('#text').hide();
            }
        });

        $(document).ready(function() {
            $('#generate').click(function() {
                $('#generateQuestionModal').modal('show');
            });


            $(document).on('click', '.delete-btn', deleteRow);

            $.ajax({
                type: "GET",
                url: "{{ env('URL_API') }}/api/v1/user-course/user/{{ $id }}",
                data: {},
                beforeSend: function(request) {
                    request.setRequestHeader("Authorization",
                        "Bearer {{ $token }}");
                },
                success: function(response) {
                    response['data'].forEach(element => {
                        $('#courseInput').append($("<option />").val(element[
                            'course_code']).text(element[
                            'course_code'] + '-' + element['course'][
                            'name'
                        ]));
                    });
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    errorCount++;
                }
            });

            $('#courseInput').on('change', function() {
                var course = $('#courseInput').val();
                if (course != "") {
                    $('#topic').show();
                } else {
                    $('#topic').hide();
                }
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

                        $('#topicInput').empty();
                        response['data'].forEach(element => {
                            $('#topicInput').append($("<option />").val(element[
                                'guid']).text(element[
                                'name']));
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                        errorCount++;
                    }
                });

            });


            $('#generateQuestionForm').submit(function(event) {
                $('#generateQuestionModal').modal(
                    'hide');
                event.preventDefault();

                var question = $('#questionInput').val();
                var language = $('#language').val();
                var pdfFile = $('#pdfInput')[0].files[0];
                if (!question && !pdfFile) {
                    alert('Please insert noun or upload PDF.');
                    return;
                }
                var formData = new FormData();
                formData.append('pdf', pdfFile);
                formData.append('language', language);

                // generate use pdf
                if (pdfFile) {
                    var isChecked = $('#skipPagesCheckbox').is(':checked');
                    $('#loading').show();
                    $.ajax({
                        type: "POST",
                        url: "{{ env('URL_API') }}/api/v1/question/upload-file",
                        beforeSend: function(request) {
                            request.setRequestHeader("Authorization",
                                "Bearer {{ $token }}");
                        },
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: function(response) {
                            var path = response['data']['path'];
                            var name = response['data']['name'];
                            var page = response['data']['page'];
                            console.log(page);
                            var allResponses = [];
                            var completedRequests = 0;
                            var processedPages = 0;

                            function updateProgressBar() {

                                var progressPercentage = (processedPages / page) * 100;
                                document.getElementById("progressPercentage").innerText =
                                    progressPercentage.toFixed(2) + "%";

                            }


                            function makeDatatable(response, cossineSimilarity, questionTotal) {
                                $('#loading').hide();
                                $('#table-container').show();
                                cossineSimilarity = cossineSimilarity / questionTotal;
                                $('#table-data').DataTable({
                                    "dom": "lrt",
                                    "bFilter": false,
                                    "searching": false,
                                    "keys": true,
                                    "destroy": true,
                                    "processing": true,
                                    "serverSide": false,
                                    "ajax": {
                                        "url": "{{ env('URL_API') }}/api/v1/question/convert/datatable",
                                        "type": "POST",
                                        'beforeSend': function(
                                            request) {
                                            request
                                                .setRequestHeader(
                                                    "Authorization",
                                                    "Bearer {{ $token }}"
                                                );
                                        },
                                        "data": {
                                            "data": allResponses,
                                            "path": path,
                                            "name": name,
                                        },
                                    },
                                    "columns": [{
                                            data: 'DT_RowIndex',
                                            orderable: false,
                                            searchable: false
                                        },
                                        {
                                            data: 'question',
                                            render: function(
                                                data,
                                                type,
                                                row
                                            ) {
                                                return "<div class='text-wrap' contenteditable style='text-align: justify;'>" +
                                                    data +
                                                    "</div>"
                                            }
                                        },
                                        {
                                            data: 'answer',
                                            render: function(
                                                data,
                                                type,
                                                row
                                            ) {
                                                return "<div class='text-wrap' contenteditable style='text-align: justify;'>" +
                                                    data +
                                                    "</div>"
                                            }
                                        },
                                        {
                                            data: 'category',
                                            render: function(
                                                data,
                                                type,
                                                row
                                            ) {
                                                return "<div class='text-wrap' contenteditable>" +
                                                    data +
                                                    "</div>"
                                            }
                                        },
                                        {
                                            data: 'page',
                                            render: function(data, type, row) {
                                                if (data) {
                                                    return "<div class='text-wrap' contenteditable>" +
                                                        data + "</div>";
                                                } else {
                                                    return "<div class='text-wrap'>-</div>";
                                                }
                                            }
                                        },
                                        {
                                            data: 'cossine_similarity',
                                            render: function(
                                                data,
                                                type,
                                                row
                                            ) {
                                                return "<div class='text-wrap' contenteditable>" +
                                                    data +
                                                    "</div>"
                                            }
                                        },
                                        {
                                            data: null,
                                            title: "Actions",
                                            render: function(
                                                data,
                                                type,
                                                row
                                            ) {
                                                return '<a role="button" id="delete" class="delete-btn" style="text-decoration: none;"><i class="fa-solid fa-trash" style="font-size: 15px; color: red;"></i></a>';
                                            },
                                            "orderable": false,
                                            "searchable": false

                                        },
                                    ],
                                    "language": {
                                        "emptyTable": "No data available in table",
                                        "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                                        "infoEmpty": "Showing 0 to 0 of 0 entries",
                                        "lengthMenu": "Show _MENU_ entries",
                                        "loadingRecords": "Loading...",
                                        "processing": "Processing...",
                                        "zeroRecords": "No matching records found",
                                        "paginate": {
                                            "first": "<i class='fa-solid fa-angle-double-left'></i>",
                                            "last": "<i class='fa-solid fa-angle-double-right'></i>",
                                            "next": "<i class='fa-solid fa-angle-right'></i>",
                                            "previous": "<i class='fa-solid fa-angle-left'></i>"
                                        },
                                        "aria": {
                                            "sortAscending": ": activate to sort column ascending",
                                            "sortDescending": ": activate to sort column descending"
                                        }
                                    },
                                    dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                                    displayLength: 10,
                                    lengthMenu: [7, 10, 25,
                                        50
                                    ],
                                    buttons: [{
                                        text: '<span class="d-none d-sm-inline-block" id="save-btn">Save</span>',
                                        className: "create-new btn btn-success",
                                        action: function(
                                            e,
                                            dt,
                                            node,
                                            config
                                        ) {
                                            saveData
                                                ();
                                        }
                                    }],
                                    responsive: {
                                        details: {
                                            display: $.fn
                                                .dataTable
                                                .Responsive
                                                .display
                                                .modal({
                                                    header: function(
                                                        e
                                                    ) {
                                                        return "Details of " +
                                                            e
                                                            .data()
                                                            .full_name
                                                    }
                                                }),
                                            type: "column",
                                            renderer: function(
                                                e, t, a
                                            ) {
                                                a = $
                                                    .map(
                                                        a,
                                                        function(
                                                            e,
                                                            t
                                                        ) {
                                                            return "" !==
                                                                e
                                                                .title ?
                                                                '<tr data-dt-row="' +
                                                                e
                                                                .rowIndex +
                                                                '" data-dt-column="' +
                                                                e
                                                                .columnIndex +
                                                                '"><td>' +
                                                                e
                                                                .title +
                                                                ":</td> <td>" +
                                                                e
                                                                .data +
                                                                "</td></tr>" :
                                                                ""
                                                        }
                                                    )
                                                    .join(
                                                        ""
                                                    );
                                                return !
                                                    !
                                                    a &&
                                                    $(
                                                        '<table class="table"/><tbody />'
                                                    )
                                                    .append(
                                                        a
                                                    )
                                            }
                                        }
                                    },

                                }), $("div.head-label").html(
                                    '<h5 class="card-title mb-0">Generate Question - Average Cosine Similarity: ' +
                                    cossineSimilarity.toFixed(2) + '</h5>');

                            }

                            let error = 0;
                            var errorPages = [];
                            let cossineSimilarity = 0;
                            let questionTotal = 0;

                            function makeAjaxRequest(i) {
                                $.ajax({
                                    type: "GET",
                                    url: "{{ env('URL_API') }}/api/v1/question/generate",
                                    beforeSend: function(request) {
                                        request.setRequestHeader("Authorization",
                                            "Bearer {{ $token }}");
                                    },
                                    data: {
                                        "path": path,
                                        "name": name,
                                        "language": language,
                                        "page": i
                                    },
                                    success: function(response) {
                                        if (response && Array.isArray(response)) {
                                            var allRequestsCompleted =
                                                false;
                                            let index = 0;

                                            function processItem(item, index) {
                                                item.page = i + 1;

                                                function makeAjaxRequest(item,
                                                    index) {
                                                    $.ajax({
                                                        type: "GET",
                                                        url: "{{ env('URL_API') }}/api/v1/question/check-cossine",
                                                        beforeSend: function(
                                                            request) {
                                                            request
                                                                .setRequestHeader(
                                                                    "Authorization",
                                                                    "Bearer {{ $token }}"
                                                                );
                                                        },
                                                        data: {
                                                            "question": item
                                                                .question,
                                                            "answer": item
                                                                .answer
                                                        },
                                                        success: function(
                                                            response) {
                                                            let parsedResponse =
                                                                parseFloat(
                                                                    response
                                                                );
                                                            if (!isNaN(
                                                                    parsedResponse
                                                                )) {
                                                                item.cossine_similarity =
                                                                    parsedResponse;
                                                                cossineSimilarity
                                                                    +=
                                                                    parsedResponse;
                                                                questionTotal
                                                                    +=
                                                                    1;
                                                                index =
                                                                    index +
                                                                    1;
                                                                allResponses.push(item);
                                                                if (index ===
                                                                    response
                                                                    .length -
                                                                    1) {
                                                                    allRequestsCompleted
                                                                        =
                                                                        true;
                                                                }
                                                            } else {
                                                                console
                                                                    .error(
                                                                        "Error: Response cannot be parsed to float"
                                                                    );
                                                                makeAjaxRequest
                                                                    (item,
                                                                        index
                                                                    );
                                                            }
                                                        },

                                                        error: function(xhr,
                                                            status,
                                                            error) {
                                                            console
                                                                .error(
                                                                    "Error: ",
                                                                    error
                                                                );
                                                            makeAjaxRequest
                                                                (item,
                                                                    index
                                                                );
                                                        }
                                                    });
                                                }


                                                makeAjaxRequest(item, index);
                                            }

                                            response.forEach(function(item, index) {
                                                processItem(item, index);
                                            });


                                            var checkCompletionInterval =
                                                setInterval(function() {
                                                        if (allRequestsCompleted) {
                                                            clearInterval(
                                                                checkCompletionInterval
                                                            );
                                                            completedRequests++;
                                                            processedPages++;
                                                            updateProgressBar();
                                                            console
                                                                .log(
                                                                    response
                                                                );

                                                            if (completedRequests ===
                                                                page) {
                                                                makeDatatable(
                                                                    allResponses,
                                                                    cossineSimilarity,
                                                                    questionTotal
                                                                );
                                                                if (errorPages
                                                                    .length > 0) {
                                                                    alert("Pertanyaan berhasil digenerate. Halaman yang mengalami kesalahan: " +
                                                                        errorPages
                                                                        .join(
                                                                            ", "
                                                                        ));
                                                                } else {
                                                                    alert(
                                                                        "Pertanyaan berhasil digenerate tanpa ada halaman yang mengalami kesalahan."
                                                                    );
                                                                }
                                                            } else {
                                                                makeAjaxRequest(i +
                                                                    1);
                                                            }
                                                        }
                                                    },
                                                    100
                                                );


                                        } else {
                                            console.log(response);
                                            error += 1;
                                            if (error > 1) {
                                                error = 0;
                                                if (!isChecked) {
                                                    var proceed = confirm(
                                                        'Terjadi kesalahan saat memproses halaman ' +
                                                        i + 1 +
                                                        '. Apakah Anda ingin melewati halaman ?'
                                                    );
                                                    if (!proceed) {
                                                        makeDatatable(allResponses,
                                                            cossineSimilarity,
                                                            questionTotal);
                                                        if (errorPages.length > 0) {
                                                            alert("Pertanyaan berhasil digenerate. Halaman yang mengalami kesalahan: " +
                                                                errorPages.join(
                                                                    ", "));
                                                        } else {
                                                            alert(
                                                                "Pertanyaan berhasil digenerate tanpa ada halaman yang mengalami kesalahan."
                                                            );
                                                        }
                                                        return
                                                    }
                                                }

                                                completedRequests++;
                                                processedPages++;
                                                updateProgressBar();
                                                errorPages.push(i);
                                                console.log(errorPages);
                                                makeAjaxRequest(i + 1);
                                            } else {
                                                makeAjaxRequest(i);
                                            }
                                        }

                                    },
                                    error: function(xhr, status, error) {
                                        if (error > 1) {
                                            error = 0;
                                            if (!isChecked) {
                                                var proceed = confirm(
                                                    'Terjadi kesalahan saat memproses halaman ' +
                                                    (i + 1) +
                                                    '. Apakah Anda ingin melewati halaman ?'
                                                );
                                                if (!proceed) {
                                                    makeDatatable(allResponses,
                                                        cossineSimilarity,
                                                        questionTotal);
                                                    if (errorPages.length > 0) {
                                                        alert("Pertanyaan berhasil digenerate. Halaman yang mengalami kesalahan: " +
                                                            errorPages.join(
                                                                ", "));
                                                    } else {
                                                        alert(
                                                            "Pertanyaan berhasil digenerate tanpa ada halaman yang mengalami kesalahan."
                                                        );
                                                    }
                                                    return
                                                }
                                            }

                                            completedRequests++;
                                            processedPages++;
                                            updateProgressBar();
                                            errorPages.push(i);
                                            console.log(errorPages);
                                            makeAjaxRequest(i + 1);
                                        } else {
                                            makeAjaxRequest(i);
                                        }
                                    }
                                });
                            }
                            makeAjaxRequest(0);
                        },
                        error: function(xhr, status, error) {
                            console.error(xhr.responseText);
                            var errorMessage = "Terjadi kesalahan saat menjalankan URL. ";
                            if (xhr.status === 0) {
                                errorMessage += "Koneksi jaringan gagal.";
                            } else {
                                errorMessage += "Error: " + xhr.status + " " + error;
                            }
                            alert(errorMessage);
                        }
                    });
                } else {
                    $.ajax({
                        type: "GET",
                        url: "{{ env('URL_API') }}/api/v1/question/generate",
                        beforeSend: function(request) {
                            request.setRequestHeader("Authorization",
                                "Bearer {{ $token }}");
                        },
                        data: {
                            "noun": question,
                            "language": language
                        },
                        success: function(response) {
                            var data = response['data'];
                            var promises = [];
                            let cossineSimilarity = 0;
                            let questionTotal = 0;
                            data.forEach(function(row) {
                                var promise = new Promise(function(resolve, reject) {
                                    $.ajax({
                                        type: "GET",
                                        url: "{{ env('URL_API') }}/api/v1/question/check-cossine",
                                        beforeSend: function(request) {
                                            request
                                                .setRequestHeader(
                                                    "Authorization",
                                                    "Bearer {{ $token }}"
                                                );
                                        },
                                        data: {
                                            "question": row.question,
                                            "answer": row.answer
                                        },
                                        success: function(similarity) {
                                            row.cossine_similarity =
                                                similarity;
                                            cossineSimilarity
                                                +=
                                                parseFloat(
                                                    similarity
                                                );
                                            questionTotal
                                                +=
                                                1;
                                            resolve
                                                ();
                                        },
                                        error: function(xhr, status,
                                            error) {
                                            console.error("Error:",
                                                error);
                                            reject(
                                                error
                                            );
                                        }
                                    });
                                });
                                promises.push(promise);
                            });

                            // Menjalankan semua promises
                            Promise.all(promises)
                                .then(function() {
                                    $('#table-container').show();
                                    cossineSimilarity = cossineSimilarity / questionTotal;
                                    $('#table-data').DataTable({
                                        "dom": "lrt",
                                        "bFilter": false,
                                        "searching": false,
                                        "keys": true,
                                        "destroy": true,
                                        "processing": true,
                                        "serverSide": false,
                                        "data": data,
                                        "columns": [{
                                                data: 'DT_RowIndex',
                                                orderable: false,
                                                searchable: false
                                            }, {
                                                data: 'question',
                                                render: function(data, type,
                                                    row) {
                                                    return "<div class='text-wrap' style='text-align: justify;'>" +
                                                        data + "</div>"
                                                }
                                            },
                                            {
                                                data: 'answer',
                                                render: function(data, type,
                                                    row) {
                                                    return "<div class='text-wrap' style='text-align: justify;'>" +
                                                        data + "</div>"
                                                }
                                            },
                                            {
                                                data: 'category',
                                                render: function(data, type,
                                                    row) {
                                                    return "<div class='text-wrap' contenteditable>" +
                                                        data +
                                                        "</div>"
                                                }
                                            },
                                            {
                                                data: 'page',
                                                render: function(data, type,
                                                    row) {
                                                    return "<div class='text-wrap'>-</div>";
                                                }
                                            },
                                            {
                                                data: 'cossine_similarity',
                                                render: function(data, type,
                                                    row) {
                                                    return "<div class='text-wrap'>" +
                                                        data +
                                                        "</div>"
                                                }
                                            },
                                            {
                                                data: null,
                                                title: "Actions",
                                                render: function(data, type,
                                                    row) {
                                                    return '<a role="button" id="delete" class="delete-btn" style="text-decoration: none;"><i class="fa-solid fa-trash" style="font-size: 15px; color: red;"></i></a>';
                                                },
                                                "orderable": false,
                                                "searchable": false

                                            },
                                        ],
                                        "language": {
                                            "emptyTable": "No data available in table",
                                            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                                            "infoEmpty": "Showing 0 to 0 of 0 entries",
                                            "lengthMenu": "Show _MENU_ entries",
                                            "loadingRecords": "Loading...",
                                            "processing": "Processing...",
                                            "zeroRecords": "No matching records found",
                                            "paginate": {
                                                "first": "<i class='fa-solid fa-angle-double-left'></i>",
                                                "last": "<i class='fa-solid fa-angle-double-right'></i>",
                                                "next": "<i class='fa-solid fa-angle-right'></i>",
                                                "previous": "<i class='fa-solid fa-angle-left'></i>"
                                            },
                                            "aria": {
                                                "sortAscending": ": activate to sort column ascending",
                                                "sortDescending": ": activate to sort column descending"
                                            }
                                        },
                                        dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                                        displayLength: 10,
                                        lengthMenu: [7, 10, 25, 50],
                                        buttons: [{
                                            text: '<span class="d-none d-sm-inline-block" id="save-btn">Save</span>',
                                            className: "create-new btn btn-success",
                                            action: function(e, dt, node,
                                                config) {
                                                saveData();
                                            }

                                        }],
                                        responsive: {
                                            details: {
                                                display: $.fn.dataTable.Responsive
                                                    .display
                                                    .modal({
                                                        header: function(e) {
                                                            return "Details of " +
                                                                e
                                                                .data()
                                                                .full_name
                                                        }
                                                    }),
                                                type: "column",
                                                renderer: function(e, t, a) {
                                                    a = $.map(a, function(e,
                                                        t) {
                                                        return "" !== e
                                                            .title ?
                                                            '<tr data-dt-row="' +
                                                            e
                                                            .rowIndex +
                                                            '" data-dt-column="' +
                                                            e
                                                            .columnIndex +
                                                            '"><td>' + e
                                                            .title +
                                                            ":</td> <td>" +
                                                            e.data +
                                                            "</td></tr>" :
                                                            ""
                                                    }).join("");
                                                    return !!a && $(
                                                        '<table class="table"/><tbody />'
                                                    ).append(a)
                                                }
                                            }
                                        },

                                    }), $("div.head-label").html(
                                        '<h5 class="card-title mb-0">Generate Question - Average Cosine Similarity: ' +
                                        cossineSimilarity.toFixed(2) + '</h5>');

                                })
                                .catch(function(error) {
                                    console.error("Error:", error);
                                });


                        },
                        error: function(xhr, status, error) {
                            console.error("Error:", error);
                        }
                    });
                }
                $('#table-data').on('blur', 'tbody td div.text-wrap[contenteditable]', function() {
                    var table = $('#table-data').DataTable();
                    var cell = table.cell($(this).closest('td'));
                    var newValue = $(this).text();
                    cell.data(newValue).draw();
                });


            });

            function deleteRow() {
                var table = $('#table-data').DataTable();
                var row = $(this).closest('tr');
                table.row(row).remove().draw();
                updateRowIndex();
            };

            function updateRowIndex() {
                var table = $('#table-data').DataTable();
                table.rows().every(function(rowIdx, tableLoop, rowLoop) {
                    var rowData = this.data();
                    rowData['DT_RowIndex'] = rowIdx +
                        1;
                    this.data(rowData);
                });
                table.draw();
            }

            function saveData() {
                {
                    $('#loadingScreen').show();
                    var table = $('#table-data').DataTable();
                    var tableData = table.rows().data();
                    var numRows = tableData.length;
                    var weight = 100;
                    var successCount = 0;
                    var errorCount = 0;
                    var topic = $('#topicInput').val();
                    $.ajax({
                        type: "GET",
                        url: "{{ env('URL_API') }}/api/v1/topic/" + topic,
                        data: {},
                        beforeSend: function(request) {
                            request.setRequestHeader("Authorization",
                                "Bearer {{ $token }}");
                        },
                        success: function(response) {
                            console.log(response['data']['totalWeight']);
                            weight = (weight - response['data']['totalWeight']) / numRows;
                            console.log(weight);
                            let index = 0
                            let rowData = tableData[index];
                            sendRequest(rowData, 0);
                        },
                        error: function(xhr, status, error) {
                            console.error(xhr.responseText);
                        }

                    });

                    function updateProgressBar(percentage) {
                        $('#progressBar').css('width', percentage + '%');
                    }


                    function sendRequest(rowData, index) {
                        $.ajax({
                            type: "POST",
                            url: "{{ env('URL_API') }}/api/v1/question",
                            data: {
                                'question_ai': rowData.question,
                                'answer_ai': rowData.answer,
                                'question_fix': rowData.question,
                                'answer_fix': rowData.answer,
                                'category': rowData.category,
                                'weight': weight,
                                'topic_guid': topic,
                                'page': rowData.page,
                                'cossine_similarity': rowData.cossine_similarity,
                                ...(rowData.page && rowData.page !== '-' ? {
                                    'page': rowData.page
                                } : {})
                            },
                            beforeSend: function(request) {
                                request.setRequestHeader("Authorization",
                                    "Bearer {{ $token }}");
                            },
                            success: function(response) {
                                console.log(response);
                                successCount++;
                                var percentage = Math.floor((successCount + errorCount) /
                                    numRows * 100);
                                updateProgressBar(percentage);
                                if ((index + 1) < tableData.length) {
                                    let rowData = tableData[index];
                                    sendRequest(rowData, index + 1);
                                } else {
                                    var course = $('#courseInput').val();
                                    if (successCount + errorCount === numRows) {
                                        alert("Success to save data!");
                                        window.location = "/question/" + course + "/" + topic;
                                    } else {
                                        alert("Terjadi kesalahan saat menyimpan data.");
                                    }
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error(error);
                                errorCount++;
                                reject(xhr.responseText);
                            }
                        });
                    }



                };



            };
        });
    </script>
@endsection
