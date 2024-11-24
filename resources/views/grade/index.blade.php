@extends('layouts.template')
@section('vendor-css')
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-checkboxes-jquery/datatables.checkboxes.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <!-- Row Group CSS -->
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}">
@endsection
@section('info-page')
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
        <li class="breadcrumb-item text-sm text-dark active text-capitalize" aria-current="page">
            Grade/{{ $name }}</li>
    </ol>
    <h5 class="font-weight-bolder mb-0 text-capitalize">Grade/{{ $name }}</h5>
@endsection
@section('content')
    <main class="main-content position-relative max-height-vh-100 h-100 mt-1 border-radius-lg ">
        <div class="container-xxl flex-grow-1 container-p-y">
            <!-- DataTable with Buttons -->
            <div class="card" id="card-block">
                <div class="card-datatable table-responsive pt-0">
                    <table class="table" id="table-data">
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>Name</th>
                                <th>status</th>
                                <th>Grade</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                    <!-- Modal Edit-->
                    {{-- <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Topic</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="edit-form">
                                        <div class="mb-3">
                                            <label for="id" class="form-label">Id</label>
                                            <input type="text" class="form-control" id="id" name="id"
                                                required readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit-grade" class="form-label">Grade</label>
                                            <input type="text" class="form-control" id="edit-grade" name="edit-grade"
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
@endsection
@section('custom-javascript')
    <script type="text/javascript">
        function resetHistories(userId, topicGuid) {
            if (confirm("Are you sure you want to reset this user's chat histories for this topic?")) {
                $.ajax({
                    url: "{{ env('URL_API') }}/api/v1/chatbot/reset-histories", // Endpoint backend untuk reset histories
                    type: "POST",
                    data: {
                        user_id: userId,
                        topic_guid: topicGuid,
                    },
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization",
                            "Bearer {{ $token }}");
                    },
                    success: function(response) {
                        alert("Chat histories have been successfully reset!");
                        // Refresh data table atau halaman jika perlu
                        $('#dataTable').DataTable().ajax.reload();
                    },
                    error: function(xhr) {
                        alert("Failed to reset chat histories. Please try again.");
                        console.error(xhr.responseText);
                    }
                });
            }
        }
        $(document).ready(function() {

            $('#table-data').DataTable({
                "destroy": true,
                "processing": true,
                "scrollX": true,
                "ajax": {
                    "url": "{{ env('URL_API') }}/api/v1/grade/topic/{{ $code }}/{{ $guid }}",
                    "type": "GET",
                    'beforeSend': function(request) {
                        request.setRequestHeader("Authorization",
                            "Bearer {{ $token }}");
                    },
                    "data": {},

                },
                "columns": [{
                        data: 'user_id',
                        title: "User ID",
                        render: function(data, type, full, meta) {
                            return data ? data : '-';
                        }
                    },
                    {
                        data: 'name',
                        title: "Name",
                        render: function(data, type, full, meta) {
                            return data ? data : '-';
                        }
                    },
                    {
                        data: 'progress',
                        title: "Progress",
                        render: function(data, type, full, meta) {
                            if (data) {
                                const [completed, total] = data.split('/');
                                const percentage = Math.round((completed / total) * 100);
                                return `<div style="display: flex; align-items: center;">
                            <div style="width: 100px; height: 10px; background-color: #e9ecef; margin-right: 10px; border-radius: 5px;">
                                <div style="width: ${percentage}%; height: 100%; background-color: ${
                                    percentage === 100 ? '#28a745' : '#ffc107'
                                }; border-radius: 5px;"></div>
                            </div>
                            <span>${data}</span>
                        </div>`;
                            }
                            return '<span class="badge bg-danger">No Progress</span>';
                        }
                    },
                    {
                        data: 'grade',
                        title: "Grade",
                        render: function(data, type, full, meta) {
                            return data !== null ? data : '<span>-</span>';
                        }
                    },
                    {
                        data: null,
                        title: "Actions",
                        render: function(data, type, row) {
                            return `
            <a href="/answer/detail/{{ $code }}/{{ $guid }}/` + row['user_id'] + `" 
               role="button" 
               class="edit-btn" 
               style="text-decoration: none; margin-right: 10px;" 
               data-bs-toggle="tooltip" 
               data-bs-placement="top" 
               title="View details of user's answers">
                <i class="fa-solid fa-circle-info" style="font-size: 15px; color: blue;"></i>
            </a>
            <a href="javascript:void(0);" 
               onclick="resetHistories('${row['user_id']}', '{{ $guid }}')" 
               role="button" 
               class="reset-btn" 
               style="text-decoration: none;" 
               data-bs-toggle="tooltip" 
               data-bs-placement="top" 
               title="Reset chat histories for this user">
                <i class="fa-solid fa-rotate-left" style="font-size: 15px; color: red;"></i>
            </a>
        `;
                        },
                        "orderable": false,
                        "searchable": false
                    }


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
                        extend: 'copy',
                        exportOptions: {
                            columns: ':visible:not(.not-export-column)'
                        },
                        text: 'Copy',
                        className: 'btn btn-primary d-none',
                    },
                    {
                        extend: 'csv',
                        exportOptions: {
                            columns: ':visible:not(.not-export-column)'
                        },
                        text: 'CSV',
                        className: 'btn btn-primary d-none',
                        enabled: false
                    }, // Set enabled to false to disable the CSV button
                    {
                        extend: 'print',
                        exportOptions: {
                            columns: ':visible:not(.not-export-column)'
                        },
                        text: 'Print',
                        className: 'btn btn-primary d-none',
                        enabled: false
                    }
                ],
            }), $("div.head-label").html('<h5 class="card-title mb-0">Grade Data</h5>');





            $(document).on("click", ".open-edit-dialog", function() {
                var id = $(this).data('user-id');
                $('#id').val(id);
                $.ajax({
                    type: "GET",
                    url: "{{ env('URL_API') }}/api/v1/grade",
                    data: {
                        topic_guid: "{{ $guid }}",
                        user_id: id
                    },
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization",
                            "Bearer {{ $token }}");
                    },
                    success: function(result) {
                        if (result['data']) {
                            $('#edit-grade').val(result['data']['grade']);
                        }
                        $('#modalEdit').modal('show');

                    },
                    error: function(xhr, status, error) {
                        var errorMessage = xhr.status + ': ' + xhr.statusText;
                        alert('Terjadi kesalahan: ' + errorMessage);
                    }
                });

            });

            $('#edit-form').on('submit', function(e) {
                e.preventDefault();

                var id = $('#id').val();
                var grade = $('#edit-grade').val();


                $.ajax({
                    type: "PUT",
                    url: "{{ env('URL_API') }}/api/v1/grade",
                    data: {
                        "user_id": id,
                        "grade": grade,
                        "topic_guid": "{{ $guid }}",
                    },
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization",
                            "Bearer {{ $token }}");
                    },
                    success: function(result) {
                        $('#modalEdit').modal('hide');
                        window.location.href =
                            "{{ route('grade', ['code' => $code, 'guid' => $guid]) }}";
                    },
                    error: function(xhr, status, error) {
                        var errorMessage = xhr.status + ': ' + xhr.statusText;
                        alert('Terjadi kesalahan: ' + errorMessage);
                    }
                });
            });
        });
    </script>
@endsection
