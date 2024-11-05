@extends('layouts.template')
@section('vendor-css')
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-checkboxes-jquery/datatables.checkboxes.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <!-- Row Group CSS -->
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/tagify/tagify.css') }}" />
@endsection
@section('info-page')
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
        <li class="breadcrumb-item text-sm text-dark active text-capitalize" aria-current="page">
            Student/Answer/{{ $name }}</li>
    </ol>
    <h5 class="font-weight-bolder mb-0 text-capitalize">Student/Answer/{{ $name }}</h5>
@endsection
@section('content')
    <main class="main-content position-relative max-height-vh-100 h-100 mt-1 border-radius-lg ">
        <div class="container-xxl flex-grow-1 container-p-y">
            <!-- DataTable with Buttons -->
            <div class="card" id="card-block">
                <div id="loading-indicator" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div class="card-datatable table-responsive pt-0">
                    <table class="table" id="table-data">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>guid</th>
                                <th>Question</th>
                                <th>Answer</th>
                                <th>Student Answer</th>
                                <th>Cossine Similarity</th>
                                <th>Weight</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                    </table>
                    {{-- <div class="modal fade" id="modalAdd" tabindex="-1" aria-hidden="true">

                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Grade Student</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="add-form">
                                        <div class="mb-3">
                                            <label for="add-grade" class="form-label">Grade</label>
                                            <input type="text" class="form-control" id="add-grade" name="add-grade"
                                                required>
                                        </div>
                                        <!-- Add other input fields as needed -->
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div> --}}
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
    <script src="{{ asset('./assets/dashboard/tagify/tagify.js') }}"></script>
    <script src="{{ asset('./assets/dashboard/block-ui/block-ui.js') }}"></script>
    <script src="{{ asset('./assets/js/blockui.js') }}"></script>
@endsection
@section('custom-javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $('#table-data').DataTable({
                "destroy": true,
                "processing": true,
                "serverSide": false,
                "ajax": {
                    "url": "{{ env('URL_API') }}/api/v1/answer/{{ $guid }}/{{ $id }}",
                    "type": "GET",
                    'beforeSend': function(request) {
                        request.setRequestHeader("Authorization",
                            "Bearer {{ $token }}");
                    },
                    "data": {},
                },
                "columns": [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'user_answer',
                        render: function(data, type, row) {
                            if (data[0] && data[0]['guid'] != null) {
                                return "<div class='text-wrap' style='text-align: justify;'>" +
                                    data[0]['guid'] + "</div>"
                            } else {
                                return "<div class='text-wrap' style='text-align: justify;'></div>"
                            }

                        },
                        visible: false
                    },
                    {
                        data: 'question_fix',
                        render: function(data, type, row) {
                            return "<div class='text-wrap' style='text-align: justify;'>" +
                                data + "</div>"
                        }
                    },
                    {
                        data: 'answer_fix',
                        render: function(data, type, row) {
                            return "<div class='text-wrap' style='text-align: justify;'>" +
                                data + "</div>"
                        }
                    },
                    {
                        data: 'user_answer',
                        render: function(data, type, row) {
                            if (data[0] && data[0]['answer'] != null) {
                                return "<div class='text-wrap' style='text-align: justify;'>" +
                                    data[0]['answer'] + "</div>"
                            } else {
                                return "<div class='text-wrap' style='text-align: justify;'></div>"
                            }

                        }
                    },
                    {
                        data: 'user_answer',
                        render: function(data, type, row) {
                            if (data[0] && data[0]['cossine_similarity'] != null) {
                                return "<div class='text-wrap' style='text-align: justify;'>" +
                                    data[0]['cossine_similarity'] + "</div>"
                            } else {
                                return "<div class='text-wrap' style='text-align: justify;'> 0 </div>"
                            }

                        }
                    },
                    {
                        data: 'weight',
                        render: function(data, type, row) {
                            return "<div class='text-wrap' style='text-align: justify;'>" +
                                data + "</div>"
                        }
                    },
                    {
                        data: 'user_answer',
                        render: function(data, type, row) {
                            if (data[0] && data[0]['grade'] != null) {

                                return "<div class='text-wrap' style='text-align: justify;'>" +
                                    "<input type='number' class='form-control' name='grade' value='" +
                                    parseFloat(data[0]['grade']) * 100 / row
                                    .weight + "' data-weight='" + row
                                    .weight +
                                    "' data-guid='" + row.guid + "' min='0' max='100'/>" +
                                    "</div>";


                            } else {
                                return "<div class='text-wrap' style='text-align: justify;'>" +
                                    "<input type='number' class='form-control' name='grade' value='0' data-weight='" +
                                    row
                                    .weight +
                                    "' data-guid='" + row.guid + "' min='0' max='100'/>" +
                                    "</div>";
                            }

                        }
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
                scrollX: true,
                buttons: [{
                        text: '<i class="fa-solid fa-plus me-sm-1"></i> <span class="d-none d-sm-inline-block">Save</span>',
                        className: "create-new btn btn-primary",
                        action: function(e, dt, node, config) {
                            // $('#modalAdd').modal('show');
                            window.location.href =
                                "{{ route('grade', ['code' => $code, 'guid' => $guid]) }}";
                        }
                    },
                    {
                        text: '<i class="fas fa-check-circle me-sm-1"></i> <span class="d-none d-sm-inline-block">Check Cossine</span>',
                        className: "create-new btn btn-success",
                        action: function(e, dt, node, config) {
                            var table = $('#table-data').DataTable();
                            var rowsData = table.rows().data();
                            var totalRows = rowsData.length;
                            $('#loading-indicator').show();
                            rowsData.each(function(rowData, index) {
                                if (rowData.user_answer[0]) {
                                    var guid = rowData.user_answer[0].guid;
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
                                            "question": rowData.answer_fix,
                                            "answer": rowData.user_answer[0].answer
                                        },
                                        success: function(similarity) {
                                            $.ajax({
                                                type: "PUT",
                                                url: "{{ env('URL_API') }}/api/v1/answer",
                                                beforeSend: function(
                                                    request) {
                                                    request
                                                        .setRequestHeader(
                                                            "Authorization",
                                                            "Bearer {{ $token }}"
                                                        );
                                                },
                                                data: {
                                                    "answer_guid": guid,
                                                    "cossine_similarity": similarity
                                                },
                                                success: function(
                                                    similarity) {},
                                                error: function(xhr,
                                                    status,
                                                    error) {
                                                    console.error(
                                                        "Error:",
                                                        error);
                                                }
                                            });
                                        },
                                        error: function(xhr, status,
                                            error) {
                                            console.error("Error:",
                                                error);
                                        }
                                    });
                                }
                                if (index === totalRows - 1) {
                                    alert(
                                        "Cosine similarity checked successfully for all rows!"
                                    );
                                    $('#loading-indicator').hide();
                                    location.reload();
                                }
                            })
                        }
                    }
                ],
                dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                displayLength: 10,
                lengthMenu: [7, 10, 25, 50],
               
            }), $("div.head-label").html('<h5 class="card-title mb-0">Student Answer Data</h5>');


            // $('#add-form').on('submit', function(e) {
            //     e.preventDefault();
            //     e.preventDefault();

            //     var grade = $('#add-grade').val();

            //     $.ajax({
            //         type: "PUT",
            //         url: "{{ env('URL_API') }}/api/v1/grade",
            //         data: {
            //             "user_id": "{{ $id }}",
            //             "grade": grade,
            //             "topic_guid": "{{ $guid }}",
            //         },
            //         beforeSend: function(request) {
            //             request.setRequestHeader("Authorization",
            //                 "Bearer {{ $token }}");
            //         },
            //         success: function(result) {
            //             $('#modalEdit').modal('hide');
            //             window.location.href =
            //                 "{{ route('grade', ['code' => $code, 'guid' => $guid]) }}";
            //         },
            //         error: function(xhr, status, error) {
            //             var errorMessage = xhr.status + ': ' + xhr.statusText;
            //             alert('Terjadi kesalahan: ' + errorMessage);
            //         }
            //     });
            // });

            $(document).on('change', '.form-control[name="grade"]', function() {
                var questionId = $(this).data('guid');
                var weight = $(this).data('weight');
                var grade = $(this).val() * weight / 100;

                $.ajax({
                    type: "POST",
                    url: "{{ env('URL_API') }}/api/v1/answer/grade",
                    data: {
                        question_guid: questionId,
                        grade: grade,
                        user_id: "{{ $id }}",
                        topic_guid: "{{ $guid }}"
                    },
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization",
                            "Bearer {{ $token }}");

                    },
                    success: function(result) {},
                    error: function(xhr, status, error) {
                        var errorMessage = xhr.status + ': ' + xhr.statusText;
                        alert('Terjadi kesalahan: ' + errorMessage);
                    }
                });
            });
        });
    </script>
@endsection
