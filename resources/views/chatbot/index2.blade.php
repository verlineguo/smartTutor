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
        /* Chatbot container */
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
        }

        /* User and bot messages */
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

        /* Input area */
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

    <div class="input-group mt-2">
        <input type="text" id="user-input" class="form-control" placeholder="Type your answer here..." disabled>
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

            function loadChatHistory() {
                $.ajax({
                    type: "GET",
                    url: `{{ env('URL_API') }}/api/v1/chatbot/history/${topicGuid}/${userId}`,
                    beforeSend: function(request) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    },
                    success: function(response) {
                        response.data.forEach(message => {
                            const messageClass = message.sender === 'bot' ? 'bot-message' :
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

                        if (currentPage >= highestPage) {
                            isReadOnly = true;
                            $("#user-input").prop("disabled", true);
                        } else {
                            $("#user-input").prop("disabled", false);
                        }

                        scrollToBottom();

                        if (!isReadOnly && !response.data.some(m => m.sender === 'user' && m.page ===
                                currentPage)) {
                            askQuestion(questionsGroupedByPage);
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
                    url: `{{ env('URL_API') }}/api/v1/question/show/${topicGuid}`,
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
                const formattedQuestion = `Page ${currentPage}: ${question.question_fix}`;

                // Front-End Check: Avoid generating the same question if it already exists
                const lastBotMessage = $("#chatbot-container").find(".bot-message").last().text();
                if (lastBotMessage === formattedQuestion) {
                    console.log("Question already exists in chat history, skipping duplicate.");
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
                        saveMessageToHistory(similarityMessage, "bot", currentPage,
                        currentQuestionGuid);
                        $("#chatbot-container").append(
                            `<div class="bot-message">${similarityMessage}</div>`
                        );
                        scrollToBottom();

                        if (response.status === 'success') {
                            currentPage = response.nextPage;
                            askQuestion(questionsGroupedByPage);
                        } else if (response.status === 'retry') {
                            $("#chatbot-container").append(
                                `<div class="bot-message">${response.nextQuestion}</div>`
                            );
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
                    success: function() {
                        console.log("Message saved to history");
                    },
                    error: function(xhr) {
                        console.error(`Error saving message: ${xhr.statusText}`);
                    }
                });
            }

            function endChat() {
                $("#chatbot-container").append(
                    "<div class='bot-message'>Quiz completed! Thank you for your answers.</div>"
                );
                scrollToBottom();
                $("#user-input").prop("disabled", true);
            }

            function scrollToBottom() {
                const chatContainer = document.getElementById("chatbot-container");
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }

            $("#user-input").on("keypress", function(e) {
                if (e.which === 13 && !$(this).prop("disabled")) {
                    e.preventDefault();
                    const answer = $(this).val().trim();
                    if (answer) {
                        $("#chatbot-container").append(`<div class="user-message">${answer}</div>`);
                        scrollToBottom();
                        $(this).val("");
                        submitAnswer(answer);
                    }
                }
            });

            fetchQuestions().then(response => {
                questionsGroupedByPage = response.data.reduce((acc, question) => {
                    const page = parseInt(question.page, 10) || 1;
                    if (!acc[page]) acc[page] = [];
                    acc[page].push(question);
                    return acc;
                }, {});

                highestPage = Math.max(...Object.keys(questionsGroupedByPage).map(page => parseInt(page,
                    10)));
                loadChatHistory();
            });
        });
    </script>
@endsection
