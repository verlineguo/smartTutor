import openai
import pdfplumber
import json

# Load OpenAI API key from file
with open('hidden.txt') as file:
    openai.api_key = file.read()


def get_api_response_generate_questions(prompt: str) -> str | None:
    """Get response from OpenAI API for generating questions."""
    text: str | None = None
    try:
        response = openai.chat.completions.create(
            model='gpt-3.5-turbo',
            messages=[{"role": "user", "content": prompt}],
            temperature=0.9,
            top_p=1,
            frequency_penalty=0,
            presence_penalty=0.6,
            stop=['Human:', 'AI:']
        )
        choices = response.choices[0]
        text = choices.message.content
    except Exception as e:
        print('ERROR:', e)
    print(text)
    return str(text)


def generate_questions_from_text(text: str, language: str, existing_questions: list[str], attempt=1) -> list[str]:
    """Generate questions from the extracted text based on Bloom's Taxonomy, avoiding existing questions."""

    # Convert the list of existing questions to a string format for easy inclusion in the prompt
    if attempt > 3:
        return {"error": "Failed to generate valid questions after multiple attempts."}
    existing_questions_text = "\n".join(existing_questions)

    # Tentukan prompt berdasarkan bahasa yang diberikan
    if language.lower() == "indonesia":
        prompt = f"""
        Berikut adalah beberapa template pertanyaan berdasarkan Taksonomi Bloom yang dapat digunakan untuk membuat pertanyaan dari teks berikut. 
        Teks: "{text}"
        
        Berikut adalah daftar pertanyaan yang sudah ada:
        {existing_questions_text}
        
        - **Remembering (Mengingat)**: 
            1. Apa itu …?
            2. Di mana …?
            3. Bagaimana ___ terjadi?
            4. Mengapa …?
            5. Bagaimana Anda akan menunjukkan …?
            6. Yang mana …?
            7. Bagaimana …?
            8. Kapan ___ terjadi?
            9. Bagaimana Anda akan menjelaskan …?
            10. Bagaimana Anda akan menggambarkan..?
            11. Bisakah Anda mengingat …?
            12. Bisakah Anda memilih …?
            13. Bisakah Anda menyebutkan tiga …?
            14. Siapa yang …?

        - **Understanding (Memahami)**:
            1. Bagaimana Anda akan mengklasifikasikan jenis …?
            2. Bagaimana Anda akan membandingkan …? Kontraskan …?
            3. Akankah Anda menyatakan atau menafsirkan dengan kata-kata Anda sendiri …?
            4. Bagaimana Anda akan memperbaiki makna …?
            5. Fakta atau ide apa yang menunjukkan …?
            6. Apa ide utama dari …?
            7. Pernyataan mana yang mendukung …?
            8. Bisakah Anda menjelaskan apa yang sedang terjadi …?
            9. Apa yang dimaksud …?
            10. Apa yang dapat Anda katakan tentang …?
            11. Mana yang merupakan jawaban terbaik …?
            12. Bagaimana Anda akan merangkum …?

        **Buat satu pertanyaan baru berdasarkan teks di atas, pastikan tidak ada duplikasi dengan pertanyaan yang sudah ada.**

        Format output yang diinginkan:
         """ + """
        {
            "questions": [
                {
                    "question": "<Pertanyaan>",
                    "answer": "<Jawaban>",
                    "category": "<Kategori (remembering/understanding)>",
                    "question_nouns": ["<Kata benda>"]
                }
            ]
        }
        """

    elif language.lower() == "english":
        prompt = f"""
        Here are some question templates based on Bloom's Taxonomy that can be used to create questions from the following text. 
        Text: "{text}"
        
        Here is the list of existing questions:
        {existing_questions_text}
        
        - **Remembering**: 
            1. What is ...?
            2. Where ...?
            3. How did ___ happen?
            4. Why ...?
            5. How would you demonstrate ...?
            6. Which one ...?
            7. How ...?
            8. When did ___ happen?
            9. How would you explain ...?
            10. How would you describe ...?
            11. Can you recall ...?
            12. Can you select ...?
            13. Can you list three ...?
            14. Who ...?

        - **Understanding**:
            1. How would you classify the type of ...?
            2. How would you compare ...? Contrast ...?
            3. Will you state or interpret in your own words ...?
            4. How would you correct the meaning of ...?
            5. What facts or ideas indicate ...?
            6. What is the main idea of ...?
            7. Which statement supports ...?
            8. Can you explain what is happening ...?
            9. What does ... mean?
            10. What can you say about ...?
            11. Which is the best answer to ...?
            12. How would you summarize ...?

        **Create one new questions based on the text above, ensuring no duplication with the existing questions.**

        Desired output format:
         """ + """
        {
            "questions": [
                {
                    "question": "<Question>",
                    "answer": "<Answer>",
                    "category": "<Category (remembering/understanding)>",
                    "question_nouns": ["<Nouns>"]
                }
            ]
        }
        """

    elif language.lower() == "japanese":
        prompt = f"""
        以下のテキストに基づいて、質問を作成してください。質問は一つだけ作成し、既存の質問と重複しないようにしてください。

        テキスト: "{text}"

        以下は既存の質問のリストです:
        {existing_questions_text}

        - **Remembering (記憶)**: 
            1. 〜とは何ですか？
            2. 〜はどこで発生しますか？
            3. 〜が発生する仕組みは？
            4. 〜はなぜですか？
            5. どのように〜を示すことができますか？
            6. どれが〜ですか？
            7. どのように〜を説明しますか？
            8. 〜が発生するのはいつですか？
            9. 〜をどのように説明しますか？
            10. 〜についてどのように表現しますか？
            11. 〜を覚えていますか？
            12. 〜を選ぶことができますか？
            13. 〜を3つ挙げられますか？
            14. 〜は誰ですか？

        - **Understanding (理解)**:
            1. 〜の種類をどのように分類しますか？
            2. 〜と〜をどのように比較しますか？
            3. 〜を自分の言葉で説明できますか？
            4. 〜の意味をどのように解釈しますか？
            5. 〜に関してどの事実やアイデアが示されますか？
            6. 〜の主なアイデアは何ですか？
            7. 〜を支持する声明はどれですか？
            8. 何が起きているのかを説明できますか？
            9. 〜の意味を説明できますか？
            10. 〜について何を言えますか？
            11. 〜に最も適切な答えはどれですか？
            12. 〜をどのように要約しますか？

        **上記のテキストを基に新しい質問を作成し、既存の質問との重複を避けてください。**

        出力形式:
        """+"""
        {
            "questions": [
                {
                    "question": "<質問>",
                    "answer": "<回答>",
                    "category": "<Category (remembering/understanding)>",
                    "question_nouns": ["<名詞>"]
                }
            ]
        }
        """

    # Get the response from the OpenAI API to generate new questions
    response = get_api_response_generate_questions(prompt)

    # Assuming the response will be a list of questions, split by line breaks or similar logic
    if response:
        validated_response = validate_api_response(
            response, existing_questions)
        if "error" not in validated_response:
            return validated_response  # Return validated response if no errors
        else:
            print(
                f"Validation failed: {validated_response['error']}. Retrying... ({attempt + 1}/3)")
            attempt += 1
            return generate_questions_from_text(
                text, language, existing_questions, attempt)

    else:
        print(
            f"No response from API. Retrying... ({attempt + 1}/3)")
        attempt += 1
        return generate_questions_from_text(
            text, language, existing_questions, attempt)

    # If all retries fail


def validate_api_response(response: str, existing_questions: list[str]) -> dict:
    """Validate the response format and check for any duplicates."""
    try:
        # Parse the response string as JSON
        response_data = json.loads(response)

        # Check if the 'questions' key exists and is a list
        if "questions" not in response_data or not isinstance(response_data["questions"], list):
            return {"error": "Invalid format: 'questions' key missing or not a list."}

        # Validate each question in the 'questions' list
        for question_data in response_data["questions"]:
            # Validate the structure of each question
            if not all(key in question_data for key in ["question", "answer", "category", "question_nouns"]):
                return {"error": "Invalid question format: Missing keys in one of the questions."}

            # Validate the 'category' field
            if question_data["category"] not in ["remembering", "understanding"]:
                return {"error": f"Invalid category: {question_data['category']} is not valid."}

            # Validate that 'question_nouns' is a list of strings
            if not isinstance(question_data["question_nouns"], list) or not all(isinstance(noun, str) for noun in question_data["question_nouns"]):
                return {"error": "Invalid 'question_nouns': It must be a list of strings."}

            # Check if the question already exists
            if question_data["question"] in existing_questions:
                return {"error": f"Duplicate question detected: '{question_data['question']}' already exists."}

        # If everything is valid, return success
        return response_data

    except json.JSONDecodeError:
        return {"error": "Invalid JSON format in the response."}
    except Exception as e:
        return {"error": f"An error occurred: {str(e)}"}


def read_pdf(file_path: str) -> list[str]:
    """Extract text from a PDF file."""
    with pdfplumber.open(file_path) as pdf:
        return [page.extract_text() for page in pdf.pages]


def regenerate_questions(file_path: str, language: str, existing_questions: list[str], page: int) -> dict:
    """Extract text from a specific page of the PDF and generate new questions."""
    pages_text = read_pdf(file_path)

    # Ensure that the requested page is valid
    if page < 1 or page > len(pages_text):
        raise ValueError(f"Halaman {page} tidak ditemukan dalam file PDF.")

    # Only process the requested page (adjust for 0-based index)
    page_text = pages_text[page - 1]

    # Generate questions for the selected page
    page_questions = generate_questions_from_text(
        page_text, language, existing_questions)

    return {
        "page": page,
        "data": page_questions
    }