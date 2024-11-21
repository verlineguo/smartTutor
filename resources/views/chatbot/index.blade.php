@extends('layouts.template')
@section('vendor-css')
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-checkboxes-jquery/datatables.checkboxes.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <!-- Row Group CSS -->
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}">
@endsection
@section('add-css')
    <style>
        /* Styling untuk chat box */
        #chat-container {
            width: 100%;
            /* Sesuaikan lebar chat container */
            /* max-width: 800px; */
            /* Lebar maksimum untuk layar besar */
            margin: 20px auto;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        #chat-log {
            height: 400px;
            /* Perbesar tinggi area chat */
            overflow-y: scroll;
            padding: 20px;
            background-color: #f5f5f5;
            border-bottom: 1px solid #ddd;
        }

        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            max-width: 75%;
        }

        .message.user {
            background-color: #d1e7dd;
            align-self: flex-end;
            margin-left: auto;
        }

        .message.bot {
            background-color: #f1f1f1;
            align-self: flex-start;
            margin-right: auto;
        }

        #chat-input {
            display: flex;
            padding: 10px;
            background-color: #fff;
            border-top: 1px solid #ddd;
        }

        #user-input {
            flex: 1;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            margin-right: 10px;
        }

        #send-button {
            background-color: #4CAF50;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        #send-button:hover {
            background-color: #45a049;
        }
    </style>
@endsection
@section('info-page')
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
        <li class="breadcrumb-item text-sm text-dark active text-capitalize" aria-current="page">
            Chatbot</li>
    </ol>
    <h5 class="font-weight-bolder mb-0 text-capitalize">Chatbot</h5>
@endsection
@section('content')
    <div id="chat-container">
        <div id="chat-log" class="d-flex flex-column"></div>
        <div id="chat-input">
            <input type="text" id="user-input" placeholder="Ketik jawaban Anda..." />
            <button id="send-button" onclick="sendAnswer()">Kirim</button>
        </div>
    </div>
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
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
@endsection
@section('custom-javascript')
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            loadQuestion();
        });

        function loadQuestion() {
            fetch("{{ url('/api/chatbot/question') }}", {
                    headers: {
                        "Authorization": "Bearer {{ $token }}"
                    }
                })
                .then(response => response.json())
                .then(data => {
                    const chatLog = document.getElementById("chat-log");
                    const questionMessage = document.createElement("div");
                    questionMessage.classList.add("message", "bot");
                    questionMessage.textContent = `Pertanyaan: ${data.question}`;
                    chatLog.appendChild(questionMessage);
                    document.getElementById("user-input").dataset.questionId = data.id;
                });
        }

        function sendAnswer() {
            const answer = document.getElementById("user-input").value;
            const questionId = document.getElementById("user-input").dataset.questionId;

            // Tampilkan jawaban pengguna di chat log
            const chatLog = document.getElementById("chat-log");
            const userMessage = document.createElement("div");
            userMessage.classList.add("message", "user");
            userMessage.textContent = answer;
            chatLog.appendChild(userMessage);

            // Kosongkan input setelah jawaban dikirim
            document.getElementById("user-input").value = "";

            fetch("{{ url('/api/chatbot/answer') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Authorization": "Bearer {{ $token }}"
                    },
                    body: JSON.stringify({
                        question_id: questionId,
                        answer: answer
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.score) {
                        // Tampilkan skor di chat log sebagai respons bot
                        const botResponse = document.createElement("div");
                        botResponse.classList.add("message", "bot");
                        botResponse.textContent = `Nilai: ${data.score}`;
                        chatLog.appendChild(botResponse);

                        // Muat pertanyaan berikutnya
                        loadQuestion();
                    } else {
                        alert('Gagal menilai jawaban');
                    }
                });

            // Scroll ke bawah agar pesan terbaru terlihat
            chatLog.scrollTop = chatLog.scrollHeight;
        }
    </script>
@endsection
