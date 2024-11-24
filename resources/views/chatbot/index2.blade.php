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

        .input-group textarea {
            resize: none;
            /* Disable manual resizing */
            overflow-y: auto;
            /* Sembunyikan scrollbar vertikal */
            max-height: 150px;
            /* Batasi tinggi maksimum */
            min-height: 50px;
            /* Tetapkan tinggi minimum */
            border: none;
            /* border-radius: 20px; */
            padding: 10px;
            font-size: 14px;
            line-height: 1.5;
        }

        * {
            box-sizing: border-box;
            /* Pastikan padding dan border diperhitungkan */
        }

        .input-group {
            display: flex;
            /* Gunakan flexbox */
            align-items: stretch;
            /* Semua elemen akan memenuhi tinggi yang sama */
            border: 1px solid #ddd;
            border-radius: 20px;
            overflow: hidden;
            /* Hilangkan elemen yang keluar */
            background-color: #fff;
        }

        .input-group textarea {
            resize: none;
            /* Nonaktifkan resize manual */
            overflow-y: auto;
            /* Aktifkan scrollbar vertikal jika diperlukan */
            height: auto;
            /* Sesuaikan tinggi berdasarkan konten */
            min-height: 50px;
            /* Tetapkan tinggi minimum */
            max-height: 150px;
            /* Tetapkan tinggi maksimum */
            border: none;
            padding: 10px;
            font-size: 14px;
            line-height: 1.5;
            flex-grow: 1;
            /* Biarkan textarea mengambil ruang yang tersedia */
        }

        .input-group button {
            background-color: #00796b;
            /* Warna tombol */
            color: #fff;
            border: none;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            /* Pusatkan konten secara vertikal */
            justify-content: center;
            /* Pusatkan konten secara horizontal */
            padding: 0 20px;
            /* Tambahkan jarak horizontal */
            height: auto;
            /* Sesuaikan tinggi tombol dengan textarea */
            min-height: 50px;
            /* Tinggi minimum */
            border-left: 1px solid #ddd;
            /* Tambahkan pembatas di sebelah kiri */
            transition: background-color 0.3s ease;
            /* Efek hover */
        }

        .input-group button:hover {
            background-color: #004d40;
            /* Warna tombol saat di-hover */
        }

        .input-group textarea,
        .input-group button {
            margin: 0;
            /* Hapus margin tambahan */
            outline: none;
            /* Hapus outline bawaan */
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
    <div class="language-selection">
        <label for="language">Pilih Bahasa:</label>
        <select id="language" class="form-control">
            <option value="">Select Language</option>
        </select>
        <button id="start-quiz" class="btn btn-primary mt-2" disabled>Start Quiz</button>
    </div>

    <div class="chatbot-container" id="chatbot-container">
        <!-- Chatbot history will load here -->
    </div>

    <div class="input-group">
        <textarea id="user-input" class="form-control" rows="1" placeholder="Type your answer here..." disabled></textarea>
        <button id="send-button" class="btn">Send</button>
    </div>
@endsection

@section('custom-javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            const userId = "{{ $id }}";
            const topicGuid = "{{ $guid }}";
            const token = "{{ $token }}";
            let currentPage = 1;
            let currentQuestionGuid = null;
            let questionsGroupedByPage = {};
            let highestPage = 0;
            let isReadOnly = false;
            let isSubmitting = false;
            let selectedLanguage = null;

            // Fetch languages dynamically from API
            function fetchLanguages() {
                return $.ajax({
                    type: "GET",
                    url: `{{ env('URL_API') }}/api/v1/chatbot/languages/${topicGuid}`,
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    }
                });
            }


            // Populate languages dropdown
            fetchLanguages().then(response => {
                const languageDropdown = $("#language");
                response.data.forEach(language => {
                    languageDropdown.append(
                        `<option value="${language}">${language}</option>`
                    );
                });
                $("#language").on("change", function() {
                    selectedLanguage = $(this).val();
                    console.log(selectedLanguage);
                    $("#start-quiz").prop("disabled", !selectedLanguage);
                });
            });
            // Cek history saat halaman dimuat
            loadChatHistory();

            // Start quiz when the button is clicked
            $("#start-quiz").on("click", function() {
                $(".language-selection").hide();
                $(".chatbot-container").show();
                fetchQuestions().then(response => {
                    questionsGroupedByPage = response.data.reduce((acc, question) => {
                        const page = parseInt(question.page, 10) || 1;
                        if (!acc[page]) acc[page] = [];
                        acc[page].push(question);
                        return acc;
                    }, {});

                    highestPage = Math.max(...Object.keys(questionsGroupedByPage).map(page =>
                        parseInt(page,
                            10)));
                    askQuestion(questionsGroupedByPage)
                });
            });

            function loadChatHistory() {
                $.ajax({
                    type: "GET",
                    url: `{{ env('URL_API') }}/api/v1/chatbot/history/${topicGuid}/${userId}`,
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(response) {
                        if (response.data.length > 0) {
                            // Sembunyikan pilihan bahasa
                            $(".language-selection").hide();
                            $(".chatbot-container").show();

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

                            // Cek kondisi akhir (cosine similarity di halaman terakhir)
                            const lastMessage = response.data[response.data.length - 1];
                            if (
                                lastMessage.sender === 'cosine' &&
                                // Pesan terakhir adalah cosine similarity
                                currentPage === highestPage // Halaman saat ini adalah halaman terakhir
                            ) {
                                endChat(); // Langsung akhiri chat
                            } else {
                                // Lanjutkan ke logika lainnya jika kondisi tidak terpenuhi
                                const lastPage = currentPage; // Halaman terakhir dari history
                                const hasAnswer = response.data.some(
                                    m => m.sender === 'user' && m.page === lastPage
                                );

                                if (lastPage === highestPage && hasAnswer) {
                                    endChat();
                                } else if (lastMessage.sender === 'bot' && (!lastMessage
                                        .cosine_similarity || lastMessage.cosine_similarity === null)) {
                                    currentQuestionGuid = lastMessage.question_guid || null;
                                    $("#user-input").prop("disabled", false).focus();
                                } else if (currentPage >= highestPage) {
                                    isReadOnly = true;
                                    $("#user-input").prop("disabled", true);
                                } else {
                                    if (!isReadOnly && !response.data.some(m => m.sender === 'user' && m
                                            .page === currentPage)) {
                                        askQuestion(questionsGroupedByPage);
                                    }
                                }
                            }
                        } else {
                            // Jika tidak ada history, tampilkan pilihan bahasa
                            $(".language-selection").show();
                        }
                    },
                    error: function(xhr) {
                        console.error(`Error loading chat history: ${xhr.statusText}`);
                    }
                });
            }


            function fetchQuestions() {
                return $.ajax({
                    type: "GET",
                    url: `{{ env('URL_API') }}/api/v1/question/show/${topicGuid}/${selectedLanguage}`,
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    }
                });
            }

            function askQuestion(questionsGroupedByPage) {
                if (isReadOnly) return;

                const questionsOnPage = questionsGroupedByPage[currentPage];
                if (!questionsOnPage || questionsOnPage.length === 0) {
                    moveToNextPage();
                    return;
                }

                const question = questionsOnPage[Math.floor(Math.random() * questionsOnPage.length)];
                currentQuestionGuid = question.guid;

                // Tambahkan threshold ke dalam formattedQuestion
                const formattedQuestion =
                    `Page ${currentPage}: ${question.question_fix} (Threshold: ${question.threshold})`;

                const lastBotMessage = $("#chatbot-container").find(".bot-message").last().text();
                if (lastBotMessage === formattedQuestion) {
                    console.log("Duplicate question detected.");
                    return;
                }

                saveMessageToHistory(formattedQuestion, "bot", currentPage, currentQuestionGuid);
                $("#chatbot-container").append(`<div class="bot-message">${formattedQuestion}</div>`);
                scrollToBottom();
                $("#user-input").prop("disabled", false).focus();
            }


            function moveToNextPage() {
                currentPage++;
                if (currentPage > highestPage) {
                    endChat();
                } else {
                    askQuestion(questionsGroupedByPage);
                }
            }

            function endChat() {
                $("#chatbot-container").append(
                    "<div class='bot-message'>Quiz completed! Thank you for your answers.</div>"
                );
                scrollToBottom();
                $("#user-input").prop("disabled", true);
            }

            function saveMessageToHistory(message, sender, page, questionGuid) {
                $.ajax({
                    type: "POST",
                    url: "{{ env('URL_API') }}/api/v1/chatbot/save",
                    data: {
                        user_id: userId,
                        topic_guid: topicGuid,
                        message: message,
                        sender: sender,
                        page: page,
                        question_guid: questionGuid
                    },
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(response) {
                        console.log(response);
                    },
                    error: function(xhr) {
                        console.error(`Error saving message: ${xhr.statusText}`);
                    }
                });
            }

            function scrollToBottom() {
                const chatContainer = document.getElementById("chatbot-container");
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }


            $("#user-input").on("keypress", function(e) {
                if (e.which === 13) { // Deteksi tombol Enter
                    if (!e.shiftKey) { // Jika Shift tidak ditekan
                        e.preventDefault(); // Cegah default behavior (menambah baris)
                        $("#send-button").trigger("click"); // Panggil klik tombol Send
                    }
                    // Jika Shift ditekan, biarkan default behavior untuk menambah baris
                }
            });


            // Fungsi untuk menangani klik tombol Send
            $("#send-button").on("click", function() {
                const answer = $("#user-input").val().trim(); // Ambil nilai dari textarea
                if (answer) {
                    // Tampilkan jawaban pengguna di chatbox
                    $("#chatbot-container").append(`<div class="user-message">${answer}</div>`);
                    scrollToBottom(); // Gulir ke bawah chatbox
                    $("#user-input").val(""); // Kosongkan textarea
                    $("#user-input").css("height", "50px"); // Reset tinggi textarea
                    submitAnswer(answer); // Kirim jawaban ke server
                }
            });

            // Sesuaikan tinggi textarea saat pengguna mengetik
            $("#user-input").on("input", function() {
                this.style.height = "auto"; // Reset tinggi agar bisa dihitung ulang
                this.style.height = `${this.scrollHeight}px`; // Atur tinggi sesuai konten
            });



            function submitAnswer(answer) {
                if (isSubmitting) return;
                isSubmitting = true;

                $("#user-input").prop("disabled", true);

                $.ajax({
                    type: "POST",
                    url: "{{ env('URL_API') }}/api/v1/chatbot/save",
                    data: {
                        user_id: userId,
                        topic_guid: topicGuid,
                        question_guid: currentQuestionGuid,
                        message: answer,
                        sender: "user",
                        page: currentPage
                    },
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(response) {
                        isSubmitting = false;

                        const similarityMessage = response.similarityMessage;

                        $("#chatbot-container").append(
                            `<div class="bot-message">${similarityMessage}</div>`
                        );
                        scrollToBottom();

                        if (response.status === 'success') {
                            currentPage = response.nextPage;
                            saveMessageToHistory(similarityMessage, "cosine", currentPage,
                                currentQuestionGuid);
                            askQuestion(questionsGroupedByPage);
                        } else if (response.status === 'retry') {
                            const retryMessage = `
        <div class="bot-message">
            Retry required! <br>
            <strong>Page:</strong> ${currentPage} <br>
            <strong>Threshold:</strong> ${response.threshold || "N/A"} <br>
            <strong>Message:</strong> ${response.nextQuestion}
        </div>`;

                            $("#chatbot-container").append(retryMessage);
                            scrollToBottom();
                            $("#user-input").prop("disabled", false).focus();
                            currentQuestionGuid = response.nextQuestionGuid;
                        }

                    },
                    error: function(xhr) {
                        isSubmitting = false;
                        console.error(`Error saving message: ${xhr.statusText}`);
                    }
                });
            }
        });
    </script>
@endsection
