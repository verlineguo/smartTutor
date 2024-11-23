@extends('layouts.template')
@section('vendor-css')
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-checkboxes-jquery/datatables.checkboxes.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}">
@endsection

@section('add-css')
    <style>
        .chatbot-container {
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 20px;
            height: 400px;
            max-height: 500px;
            overflow-y: auto;
            background-color: #f9f9f9;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            font-family: 'Arial', sans-serif;
            display: flex;
            flex-direction: column;
            display: none;
        }

        .bot-message,
        .user-message {
            margin-bottom: 15px;
            max-width: 80%;
            padding: 10px;
            border-radius: 15px;
            font-size: 14px;
            line-height: 1.4;
        }

        .bot-message {
            background-color: #e0f7fa;
            color: #00796b;
            align-self: flex-start;
            margin-right: auto;
        }

        .user-message {
            background-color: #fff3e0;
            color: #ff5722;
            align-self: flex-end;
            margin-left: auto;
        }

        .input-group {
            display: flex;
            align-items: stretch;
            margin-top: 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            overflow: hidden;
            height: 50px;
        }

        .input-group input {
            flex-grow: 1;
            padding: 0 12px;
            border: none;
            font-size: 14px;
            height: 100%;
            line-height: 50px;
            box-sizing: border-box;
        }

        .input-group input:focus {
            outline: none;
        }

        .input-group button {
            background-color: #00796b;
            color: #fff;
            padding: 0 20px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            height: 100%;
            line-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }

        .input-group button:hover {
            background-color: #004d40;
        }

        .language-selection {
            margin-bottom: 20px;
        }
    </style>
@endsection

@section('info-page')
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
        <li class="breadcrumb-item text-sm text-dark active text-capitalize" aria-current="page">Chatbot</li>
    </ol>
    <h5 class="font-weight-bolder mb-0 text-capitalize">Chatbot</h5>
@endsection

@section('content')
    <div class="chatbot-container" id="chatbot-container">
        <!-- Chatbot history will load here -->
    </div>
@endsection

@section('custom-javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $(".chatbot-container").show();
            loadChatHistory();

            function loadChatHistory() {
                $.ajax({
                    type: "GET",
                    url: `{{ env('URL_API') }}/api/v1/chatbot/history/{{ $guid }}/{{ $id }}`,
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer {{ $token }}`);
                    },
                    success: function(response) {
                        response.data.forEach(message => {
                            const messageClass = (message.sender === 'bot' || message
                                    .sender === 'cosine') ? 'bot-message' :
                                'user-message';
                            $("#chatbot-container").append(
                                `<div class="${messageClass}">${message.message}</div>`
                            );
                            if (message.sender === 'bot') {
                                const pageMatch = message.message.match(/Page (\d+):/);
                                if (pageMatch) {
                                    currentPage = parseInt(pageMatch[1], 10);
                                }
                            }
                        });

                        scrollToBottom();



                    },
                    error: function(xhr) {
                        console.error(`Error loading chat history: ${xhr.statusText}`);
                    }
                });
            }

            function scrollToBottom() {
                const chatContainer = document.getElementById("chatbot-container");
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        });
    </script>
@endsection
