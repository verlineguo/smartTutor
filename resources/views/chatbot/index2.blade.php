@extends('layouts.template')
@section('vendor-css')
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-checkboxes-jquery/datatables.checkboxes.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('./assets/dashboard/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
@endsection

@section('add-css')
    <style>
        .evaluation-container {
            border: 1px solid #ccc;
            border-radius: 8px;
            display: none;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        
        .question-box {
            margin-bottom: 20px;
        }
        .btn-group {
            display: flex;
            justify-content: center;
            margin-top: 10px;
            gap: 20px;
        }
        .btn-group .btn {
            width: auto; /* Mengubah lebar tombol */
            border-radius: 5px; /* Mengatur sudut tombol */
            padding: 5px 15px; /* Mengatur padding */
            font-size: 16px; /* Mengubah ukuran font */
            background: none; /* Warna latar belakang tombol */
            border: 1px solid #00000025; /* Warna border tombol */
            color: #000000be; /* Warna teks tombol */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        

        .btn-group .btn:hover, .btn-group .btn.active {
            background: #f0eeee; /* Warna latar belakang saat hover atau aktif */
        }
        .content-section {
            display: none;
            margin-top: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }
        .input-group {
            display: flex;
            flex-direction: column;
            /* Kolom untuk textarea dan tombol */
            align-items: stretch;
            /* Semua elemen memenuhi lebar */
            gap: 10px;
            width: 100%;
            /* Lebar penuh */
        }

        .input-group textarea {
            width: 100%;
            /* Lebar penuh */
            min-height: 50px;
            max-height: 120px;
            resize: none;
            border: 1px solid #ddd;
            padding: 10px;
            font-size: 14px;
            line-height: 1.5;
            outline: none;
            overflow-y: auto;
            font-family: 'Arial', sans-serif;
            border-radius: 20px;
            background-color: #f5f5f5;
        }

        .chatbot-container {
            margin: 20px auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            height: 500px;
            max-height: 700px;
            overflow-y: auto;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .bot-message, .user-message {
            padding: 10px 15px;
            border-radius: 15px;
            max-width: 80%;
            word-wrap: break-word;
        }

        .bot-message {
            background-color: #f1f1f1;
            align-self: flex-start;
        }

        .user-message {
            background-color: #00796b;
            color: white;
            align-self: flex-end;
        }


        /* Tombol Kirim (Send) */
        .input-group button {
            background-color: #00796b;
            color: #fff;
            border: none;
            font-size: 14px;
            cursor: pointer;
            padding: 8px 15px;
            /* Padding lebih kecil untuk tombol */
            border-radius: 20px 0 0 20px;
            /* Round kiri tombol */
            width: auto;
            /* Tombol hanya sebesar kontennya */
            max-width: 200px;
            /* Batasi lebar maksimal tombol */
            margin-left: auto;
            /* Memindahkan tombol ke kanan */
            transition: background-color 0.3s ease;
        }

        .input-group button:hover {
            background-color: #004d40;
        }

        @media (max-width: 768px) {
            .chatbot-container {
                height: 350px;
                padding: 15px;
            }

            .input-group textarea {
                min-height: 50px;
                max-height: 150px;
                /* Tinggi maksimum textarea lebih besar pada mobile */
            }

            .input-group button {
                padding: 8px 15px;
                /* Ukuran padding tombol lebih kecil */
                max-width: 100px;
                /* Lebar tombol dibatasi di perangkat kecil */
            }
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
        <button id="start-test" class="btn btn-primary mt-2" disabled>Start Test</button>
    </div>

    <div class="evaluation-container" id="evaluation-container"> 
        <div class="question-box"> 
            <h6>Pertanyaan:</h6> 
            <p id="question-text"></p> 
        </div>
        <div class="mb-3 input-group">
            <label for="user-input" class="form-label"><h6>Jawaban Anda:</h6></label>
            <textarea id="user-input" class="form-control" rows="1" placeholder="Type your answer here..." disabled></textarea>
        </div>
        <button id="send-button" class="btn">Send</button>

    </div>

@endsection
@section('vendor-javascript')
    <script src="https://cdn.tiny.cloud/1/lvz6goxyxn405p74zr5vcn0xmwy7mmff6jf5wjqki5abvi3g/tinymce/7/tinymce.min.js"
        referrerpolicy="origin"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
@endsection
@section('custom-javascript')
    <script type="text/javascript">
        tinymce.init({
            selector: '#user-input'
        });
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
                    $("#start-test").prop("disabled", !selectedLanguage);
                });
            });
            $("#send-button").prop("disabled", true);
            // tinymce.get("user-input").mode.set("readonly");
            // Cek history saat halaman dimuat
            loadChatHistory();

            // Start quiz when the button is clicked
            $("#start-test").on("click", function() {
                $(".language-selection").hide();
                $(".evaluation-container").show();
                $("#send-button").prop("disabled", false);
                tinymce.get("user-input").mode.set("design");
                fetchQuestions().then(response => {
                    console.log(response);
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
                            selectedLanguage = response.language;
                            // Sembunyikan pilihan bahasa
                            $(".language-selection").hide();
                            $(".chatbot-container").show();
                            $(".evaluation-container").show();
                            $("#send-button").prop("disabled", true);
                            fetchQuestions().then(response => {
                                console.log(response);
                                questionsGroupedByPage = response.data.reduce((acc,
                                    question) => {
                                    const page = parseInt(question.page, 10) || 1;
                                    if (!acc[page]) acc[page] = [];
                                    acc[page].push(question);
                                    return acc;
                                }, {});

                                highestPage = Math.max(...Object.keys(questionsGroupedByPage)
                                    .map(page =>
                                        parseInt(page,
                                            10)));
                            });
                            response.data.forEach(message => {
                                const messageClass = (message.sender === 'bot' || message
                                        .sender === 'cosine' || message
                                        .sender === 'openai') ? 'bot-message' :
                                    'user-message';
                                $("#chatbot-container").append(
                                    `<div class="${messageClass}">${message.message}</div>`
                                );
                                if (message.sender === 'bot') {
                                    currentPage = message.page
                                    console.log(currentPage);

                                }
                            });

                            scrollToBottom();

                            // Cek kondisi akhir (cosine similarity di halaman terakhir)
                            const lastMessage = response.data[response.data.length - 1];
                            if (
                                response.regenerate ===
                                'yes' // Halaman saat ini adalah halaman terakhir
                            ) {
                                showRegenerateConfirmation();
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
                                    $("#send-button").prop("disabled", false);
                                    tinymce.get("user-input").mode.set("design");
                                } else if (currentPage >= highestPage) {
                                    isReadOnly = true;
                                    // $("#user-input").prop("disabled", true);
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
                    error: function(xhr, status, error) {
                        var errorMessage = xhr.status + ': ' + xhr.statusText;
                        toastr.options.closeButton = true;
                        toastr.error("Error load chat history: " + errorMessage, "Error");
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
                    `<p class="question-text"><strong>Question:</strong> ${question.question_fix}</p>
            <p class="question-meta">
                <strong>Page:</strong> <span class="page-number">${currentPage}</span> | 
                <strong>Threshold:</strong> <span class="threshold-value">${question.threshold || "N/A"}</span>
            </p>`;

                const lastBotMessage = $("#chatbot-container").find(".bot-message").last().text();
                if (lastBotMessage === formattedQuestion) {
                    console.log("Duplicate question detected.");
                    return;
                }

                saveMessageToHistory(formattedQuestion, "bot", currentPage, currentQuestionGuid);
                $("#chatbot-container").append(`<div class="bot-message">${formattedQuestion}</div>`);
                scrollToBottom();
                tinymce.get("user-input").getBody().setAttribute('contenteditable', true);
                $("#send-button").prop("disabled", false);
                tinymce.get("user-input").mode.set("design");
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
                tinymce.get("user-input").mode.set("readonly");
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

                        // if (sender === "user") {
                        //     saveSimilarityResults(response.results, response.guid);
                        // }

                    },
                    error: function(xhr, status, error) {
                        var errorMessage = xhr.status + ': ' + xhr.statusText;
                        toastr.options.closeButton = true;
                        toastr.error("Error saving message: " + errorMessage, "Error");
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
                const answer = tinymce.get("user-input").getContent()
                    .trim(); // Ambil konten HTML dari TinyMCE
                if (answer) {
                    // Tampilkan jawaban pengguna di chatbox
                    $("#chatbot-container").append(`<div class="user-message">${answer}</div>`);
                    scrollToBottom(); // Gulir ke bawah chatbox
                    tinymce.get("user-input").setContent("");
                    tinymce.get("user-input").mode.set("readonly");
                    $("#send-button").prop("disabled", true);
                    submitAnswer(answer); // Kirim jawaban ke server
                }
            });

            // Sesuaikan tinggi textarea saat pengguna mengetik
            // $("#user-input").on("input", function() {
            //     this.style.height = "auto"; // Reset tinggi agar bisa dihitung ulang
            //     this.style.height = `${this.scrollHeight}px`; // Atur tinggi sesuai konten
            // });

            // function saveSimilarityResults(results, userAnswerGuid) {
            //     const llmTypes = ['openai', 'gemini', 'deepseek'];
            //     const algorithms = ['cosine', 'jaccard', 'bert'];

            //     llmTypes.forEach(llmType => {
            //         algorithms.forEach(algorithm => {
            //             const similarityScore = results[`${llmType}_${algorithm}_similarity`];

            //             if (similarityScore !== null) {
            //                 $.ajax({
            //                     type: "POST",
            //                     url: "{{ env('URL_API') }}/api/v1/similarity/save",
            //                     data: {
            //                         user_answer_guid: userAnswerGuid,
            //                         llm_type: llmType,
            //                         algorithm: algorithm,
            //                         similarity_score: similarityScore
            //                     },
            //                     beforeSend: function(request) {
            //                         request.setRequestHeader("Authorization", `Bearer ${token}`);
            //                     },
            //                     success: function(response) {
            //                         console.log("Saved to similarity table:", response);
            //                     },
            //                     error: function(xhr, status, error) {
            //                         var errorMessage = xhr.status + ': ' + xhr.statusText;
            //                         toastr.options.closeButton = true;
            //                         toastr.error("Error saving to similarity: " + errorMessage, "Error");
            //                     }
            //                 });
            //             }
            //         });
            //     });
            // }

            function submitAnswer(answer) {
                if (isSubmitting) return;
                isSubmitting = true;

                // $("#user-input").prop("disabled", true);

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

                        // const results = response.results; 
                        // const userAnswerGuid = response.guid;
                        // $("#chatbot-container").append(
                        //     `<div class="bot-message">${response.answer_openai}</div>`
                        //     `<div class="bot-message">${similarityMessage}</div>`,
                        //     `<div class="bot-message">Similarity Score: ${similarityScore}%</div>`,
                        //     `<div class="bot-message"><a href="${detailsLink}" target="_blank">See Details</a></div>`,  
                        // );
                        // Simpan hasil ke tabel similarity
                        // saveSimilarityResults(results, userAnswerGuid);

                        // // Tampilkan jawaban dari LLM
                        // displayLLMResults(results);
                        const similarityMessage = response.similarityMessage;
                        console.log(similarityMessage);
                        $("#chatbot-container").append(
                            `<div class="bot-message">${similarityMessage}</div>`,
                            `<div class="bot-message">${response.answer_ai}</div>`
                        );

                        scrollToBottom();
                        if (response.status === 'success') {
                            saveMessageToHistory(response.similarityMessage, "cosine", currentPage,
                                currentQuestionGuid);

                            setTimeout(function() {
                                saveMessageToHistory(response.answer_openai, "openai", currentPage,
                                    currentQuestionGuid);
                                currentPage = response.nextPage;
                                askQuestion(questionsGroupedByPage);
                            }, 1000);
                        } else if (response.status === 'retry') {
                            $("#chatbot-container").append(response.nextQuestion);
                            scrollToBottom();
                            $("#send-button").prop("disabled", false);
                            tinymce.get("user-input").mode.set("design");
                            currentQuestionGuid = response.nextQuestionGuid;
                        } else if (response.status === 'no_questions_left') {
                            // Jika tidak ada pertanyaan tersisa, tampilkan konfirmasi untuk regenerasi
                            showRegenerateConfirmation();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("XHR Response:", xhr.responseText);
                        var errorMessage = xhr.status + ': ' + xhr.statusText;
                        toastr.options.closeButton = true;
                        toastr.error("Error saving message: " + errorMessage, "Error");

                        // Additional Debugging:
                        console.error("Status:", status);
                        console.error("Error:", error);
                    }

                });
            }

            // function displayLLMResults(results) {
            //     const llmTypes = ['openai', 'gemini', 'deepseek'];
            //     llmTypes.forEach(llmType => {
            //         const answer = results[`${llmType}_answer`]; // Ambil jawaban dari LLM
            //         const similarityScore = results[`${llmType}_similarity`]; // Ambil skor kesamaan

            //         $("#chatbot-container").append(
            //             `<div class="bot-message">${answer}</div>
            //             <div class="bot-message">Similarity Score: ${similarityScore}%</div>
            //             <div class="bot-message"><a href="/comparison/${currentQuestionGuid}/${llmType}" class="see-details">See Details</a></div>`
            //         );
            //     });
            // }

            function showRegenerateConfirmation() {
                // Tampilkan pesan konfirmasi kepada pengguna
                const confirmationMessage = `
                    <div class="bot-message" id="confirmation-message">
                        All questions for page ${currentPage} have been asked, and you have not yet reached the threshold. Would you like to regenerate with GPT?
                        <br><br>
                        <button id="regenerate-yes" class="btn btn-success">Yes</button>
                        <button id="regenerate-no" class="btn btn-danger">No</button>
                    </div>
    `;
                $("#chatbot-container").append(confirmationMessage);
                scrollToBottom();

                // Tangani pilihan pengguna
                $("#regenerate-yes").on("click", function() {
                    handleRegenerateResponse(true);
                    $("#confirmation-message").remove();
                });

                $("#regenerate-no").on("click", function() {
                    moveToNextPage();
                    $("#confirmation-message").remove();
                });
            }

            function handleRegenerateResponse(isRegenerate) {
                console.log(currentPage);
                $.ajax({
                    type: "POST",
                    url: "{{ env('URL_API') }}/api/v1/chatbot/regenerate", // API endpoint untuk handle regenerasi
                    data: {
                        user_id: userId,
                        topic_guid: topicGuid,
                        page: currentPage,
                        regenerate: isRegenerate, // true jika pengguna ingin regenerasi, false jika tidak
                    },
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(response) {
                        if (response.status != "no_attempts_left") {
                            console.log(response);
                            $("#chatbot-container").append(response.newQuestion.message);
                            scrollToBottom();
                            $("#send-button").prop("disabled", false);
                            tinymce.get("user-input").mode.set("design");
                        } else {
                            moveToNextPage();
                        }

                    },
                    error: function(xhr, status, error) {
                        var errorMessage = xhr.status + ': ' + xhr.statusText;
                        toastr.options.closeButton = true;
                        toastr.error("Error sending regenerate response: " + errorMessage, "Error");
                    }
                });
            }

        });
    </script>
@endsection
