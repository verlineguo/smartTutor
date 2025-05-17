@extends('layouts.template')
@section('vendor-css')
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-checkboxes-jquery/datatables.checkboxes.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <!-- Row Group CSS -->
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Student Progress & Grades</h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item btn-export" data-type="copy" href="javascript:void(0);">Copy to
                                    clipboard</a></li>
                            <li><a class="dropdown-item btn-export" data-type="csv" href="javascript:void(0);">Export as
                                    CSV</a></li>
                            <li><a class="dropdown-item btn-export" data-type="print" href="javascript:void(0);">Print</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card-datatable table-responsive pt-0">
                    <table class="table table-hover" id="table-data">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Progress</th>
                                <th>Score</th>
                                <th>Current Level</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via DataTables -->
                        </tbody>
                    </table>
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
                        url: "{{ env('URL_API') }}/api/v1/chatbot/reset-histories",
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
                            // Reload the table without refreshing the page
                            $('#table-data').DataTable().ajax.reload();
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

        $(document).ready(function() {
            // Initialize DataTable
            const dataTable = $('#table-data').DataTable({
                "processing": true,
                "serverSide": false,
                "scrollX": true,
                "ajax": {
                    "url": "{{ env('URL_API') }}/api/v1/grade/topic/{{ $code }}/{{ $guid }}",
                    "type": "GET",
                    'beforeSend': function(request) {
                        request.setRequestHeader("Authorization", "Bearer {{ $token }}");
                    },
                    "dataSrc": "data"
                },
                "columns": [{
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
                                            percentage === 100 ? '#28a745' : percentage >= 75 ? '#4caf50' : 
                                            percentage >= 50 ? '#ffc107' : percentage >= 25 ? '#ff9800' : '#dc3545'
                                        }; border-radius: 5px;"></div>
                                    </div>
                                    <span>${data} (${percentage}%)</span>
                                </div>`;
                            }
                            return '<span class="badge bg-danger">No Progress</span>';
                        }
                    },
                    
                    {
                        data: 'average_score',
                        title: "Avg Score",
                        render: function(data, type, full, meta) {
                            if (data !== null && data !== undefined) {
                                // Determine color based on score
                                let color = '#dc3545'; // Red for low scores
                                if (data >= 80) {
                                    color = '#28a745'; // Green for high scores
                                } else if (data >= 70) {
                                    color = '#4caf50'; // Light green
                                } else if (data >= 60) {
                                    color = '#8bc34a'; // Lime green
                                } else if (data >= 50) {
                                    color = '#ffc107'; // Yellow for medium scores
                                } else if (data >= 40) {
                                    color = '#ff9800'; // Orange
                                }

                                return `<span style="color: ${color}; font-weight: bold;">${data.toFixed(1)}</span>`;
                            }
                            return '<span>-</span>';
                        }
                    },
                    {
                        data: 'current_level',
                        title: "Current Level",
                        render: function(data, type, full, meta) {
                            if (data) {
                                let levelTitle = data.charAt(0).toUpperCase() + data.slice(1);
                                let badgeColor;
                                switch (data) {
                                    case 'remembering':
                                        badgeColor = 'bg-info';
                                        break;
                                    case 'understanding':
                                        badgeColor = 'bg-success';
                                        break;
                                    case 'applying':
                                        badgeColor = 'bg-warning';
                                        break;
                                    case 'analyzing':
                                        badgeColor = 'bg-danger';
                                        break;
                                    default:
                                        badgeColor = 'bg-secondary';
                                }

                                return `<span class="badge ${badgeColor}">${levelTitle}</span>`;
                            }
                            return '<span>No data</span>';
                        }
                    },
                    {
                        data: null,
                        title: "Actions",
                        render: function(data, type, row) {
                            return `
                                <div class="d-flex align-items-center">
                                    <a href="/grade/detail/{{ $code }}/{{ $guid }}/` + row[
                                'user_id'] + `" 
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
                                </div>
                            `;
                        },
                        "orderable": false,
                        "searchable": false
                    }
                ],
                "language": {
                    "emptyTable": "No student data available",
                    "info": "Showing _START_ to _END_ of _TOTAL_ students",
                    "infoEmpty": "Showing 0 to 0 of 0 students",
                    "lengthMenu": "Show _MENU_ students",
                    "loadingRecords": "Loading student data...",
                    "processing": "Processing...",
                    "zeroRecords": "No matching students found",
                    "paginate": {
                        "first": "<i class='fa-solid fa-angles-left'></i>",
                        "last": "<i class='fa-solid fa-angles-right'></i>",
                        "next": "<i class='fa-solid fa-angle-right'></i>",
                        "previous": "<i class='fa-solid fa-angle-left'></i>"
                    },
                    "aria": {
                        "sortAscending": ": activate to sort column ascending",
                        "sortDescending": ": activate to sort column descending"
                    }
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-end"f>>' +
     't' +
     '<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                "displayLength": 10,
                "lengthMenu": [10, 25, 50, 100],
                "order": [
                    [3, "desc"]
                ], // Order by score by default
                "responsive": true,
                // Create hidden buttons that will be triggered by our dropdown
                buttons: [{
                        extend: 'copy',
                        className: 'buttons-copy d-none' // Hide this button
                    },
                    {
                        extend: 'csv',
                        className: 'buttons-csv d-none' // Hide this button
                    },
                    {
                        extend: 'print',
                        className: 'buttons-print d-none' // Hide this button
                    }
                ]
            });

            // Export buttons click event
            $('.btn-export').on('click', function() {
                const exportType = $(this).data('type');
                if (exportType === 'copy') {
                    dataTable.button('.buttons-copy').trigger();
                } else if (exportType === 'csv') {
                    dataTable.button('.buttons-csv').trigger();
                } else if (exportType === 'print') {
                    dataTable.button('.buttons-print').trigger();
                }
            });

            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>
@endsection
