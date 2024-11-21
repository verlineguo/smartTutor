# !pip install openai
# !pip install pdfplumber
# !pip install sklearn

import json
from concurrent.futures import ThreadPoolExecutor, as_completed
from tqdm import tqdm
import pdfplumber
import openai
from sklearn.metrics.pairwise import cosine_similarity

with open('hidden.txt') as file:
    openai.api_key = file.read()

# Fungsi untuk ekstraksi teks per halaman dari PDF


def extract_text(file):
    with pdfplumber.open(file) as pdf:
        return [page.extract_text() for page in pdf.pages]

# Fungsi untuk mendapatkan embedding teks dari OpenAI API


def get_embedding(text, model="text-embedding-ada-002"):
    if not isinstance(text, str):
        text = str(text)
    text = text.replace("\n", " ")
    response = openai.embeddings.create(
        input=[text],
        model=model
    )
    return response.data[0].embedding

# Hitung kesamaan kosinus antara dua embedding


def cosine_sim(emb1, emb2):
    return cosine_similarity([emb1], [emb2])[0][0]

# Fungsi untuk mendapatkan respons API OpenAI untuk pertanyaan


def generate_questions(prompt, page_text, temp=0.7, top_p=0.7, freq_penalty=0.5, pres_penalty=0.5):
    try:
        response: dict = openai.chat.completions.create(
            model='gpt-3.5-turbo',
            messages=[
                {"role": "system", "content": prompt},
                {"role": "user", "content": page_text}
            ],
            temperature=temp,
            # max_tokens=300,
            response_format={"type": "json_object"},
            top_p=top_p,
            frequency_penalty=freq_penalty,
            presence_penalty=pres_penalty,
            stop=['Human:', 'AI:']
        )
        choices: dict = response.choices[0]
        text = choices.message.content
        token = response.usage.total_tokens
    except Exception as e:
        return ('ERROR:', e)

    return text

# Validasi struktur JSON yang dihasilkan


def validate_json(data):
    required_keys = {"question", "answer", "category", "question_nouns"}
    valid_categories = {"remembering", "understanding"}

    if "questions" not in data or not isinstance(data["questions"], list):
        return False

    for item in data["questions"]:
        if not required_keys.issubset(item.keys()) or item["category"] not in valid_categories:
            return False
    return True

# Template untuk pembuatan pertanyaan


def generate_prompt(content, noun_list=None):
    template = content
    if noun_list:
        template += f"Gunakan list berikut sebagai acuan untuk membuat pertanyaan: {', '.join(noun_list)}.\n"

    template += """

            ### Template Pertanyaan:
                Berikut adalah template pertanyaan yang dapat digunakan:

                #### Remembering (Mengingat):
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

                #### Understanding (Memahami):
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

        Dari masing-masing kategori di atas, buat 2 pertanyaan understanding dan 2 pertanyaan remembering SEHINGGA TOTAL SELURUH PERTANYAAN ADALAH 4 SOAL !!. JANGAN bertanya tentang sejarah yang hanya menyangkut waktu dan juga JANGAN MENGGUNAKAN TEMPLATE PERTANYAAN YANG SAMA LEBIH DARI 2X, tetapi Perluas/Perdalam materi berdasarkan kata-kata kunci yang dimasukkan pengguna.
        setelah Mengenerate pertanyaan. ekstrak semua nouns pada pertanyaan tersebut dan masukkan ke "question_nouns".
        FORMAT RESPONSE HARUS DALAM BENTUK JSON yang dapat di DECODE !!!  BERIKUT MERUPAKAN CONTOHNYA !!! :
        
         CONTOH JIKA BUKAN BAHASA JEPANG :

         "questions":[
                      {
                      "question": "What is supervised learning method?",
                      "answer": "Supervised learning method is an approach in machine learning where the model learns from labeled data, which means the model learns by mapping input to desired output.",
                      "category": "remembering",
                      "question_nouns": ["learning method", "approach", "machine learning", "model", "data", "input", "output"]
                      },
                      {
                      "question": "How do you choose an appropriate model in machine learning?",
                      "answer": "Choosing a model in machine learning involves understanding the characteristics of the data, prediction objectives, and model performance. This includes considering model complexity, generalization, and adaptation to the data type.",
                      "category": "remembering",
                      "question_nouns": ["model", "machine learning", "data", "objectives", "performance", "complexity", "generalization", "adaptation"]
                      },
                      {
                      "question": "Why is cross-validation important in machine learning?",
                      "answer": "Cross-validation is used to evaluate the performance of a model by dividing the data into training and testing subsets. It helps measure how well the model can generalize to unseen data.",
                      "category": "understanding",
                      "question_nouns": ["cross-validation", "model", "performance", "data", "subsets"]
                      },
                      {
                      "question": "How can we evaluate the performance of a model in machine learning?",
                      "answer": "The performance evaluation of a model in machine learning can be done using metrics such as accuracy, precision, recall, F1-score, and ROC-AUC curve. This helps to understand how well the model predicts unseen data.",
                      "category": "understanding",
                      "question_nouns": ["performance", "model", "machine learning", "metrics", "accuracy", "precision", "recall", "F1-score", "ROC-AUC curve", "data"]
                      }
                  ]

        CONTOH JIKA BAHASA JEPANG

        "questions": [
                        {
                            "question": "教師あり学習手法とは何ですか？",
                            "answer": "教師あり学習手法は、モデルがラベル付きデータから学習する機械学習のアプローチであり、モデルが入力を期待する出力にマッピングすることを意味します。",
                            "category": "remembering",
                            "question_nouns": ["学習手法", "アプローチ", "機械学習", "モデル", "データ", "入力", "出力"]
                        },
                        {
                            "question": "機械学習において適切なモデルをどのように選択しますか？",
                            "answer": "機械学習におけるモデルの選択は、データの特性、予測目標、モデルの性能を理解することに関わります。これには、モデルの複雑さ、一般化、データ型への適応を考慮することが含まれます。",
                            "category": "remembering",
                            "question_nouns": ["モデル", "機械学習", "データ", "目標", "性能", "複雑さ", "一般化", "適応"]
                        },
                        {
                            "question": "機械学習において交差検証が重要なのはなぜですか？",
                            "answer": "交差検証は、データをトレーニングとテストのサブセットに分割することでモデルの性能を評価するために使用されます。これにより、モデルが未知のデータにどの程度一般化できるかを測定できます。",
                            "category": "understanding",
                            "question_nouns": ["交差検証", "モデル", "性能", "データ", "サブセット"]
                        },
                        {
                            "question": "機械学習においてモデルの性能をどのように評価できますか？",
                            "answer": "機械学習のモデル性能の評価は、精度、適合率、再現率、F1スコア、ROC-AUCカーブなどの指標を使用して行うことができます。これにより、モデルが未知のデータをどの程度予測できるかを理解することができます。",
                            "category": "understanding",
                            "question_nouns": ["性能", "モデル", "機械学習", "指標", "精度", "適合率", "再現率", "F1スコア", "ROC-AUCカーブ", "データ"]
                        }
                    ]

        """
    return template

# Fungsi utama untuk memproses halaman PDF dan menghasilkan JSON


MAX_RETRY = 3  # Batas percobaan untuk regenerasi pertanyaan

# Fungsi utama untuk memproses halaman PDF dan menghasilkan JSON


def process_page(page_num, text, content, nouns, tfidf_terms, tfidf_embeddings, retry_count=0):
    # Buat prompt dan dapatkan respons pertanyaan
    prompt = generate_prompt(content, nouns)
    response = generate_questions(prompt, text)

    # # Proses tfidf_terms untuk mendapatkan embedding n-gram
    # combined_terms = tfidf_terms['uni'] + \
    #     tfidf_terms['bi'] + tfidf_terms['tri']
    # ngrams = [term['N-gram']
    #           for term in combined_terms if isinstance(term, dict) and 'N-gram' in term]
    # tfidf_embeddings = [get_embedding(ngram) for ngram in ngrams]

    try:
        # Parsing response JSON
        data = json.loads(response)

        # Validasi struktur JSON; jika gagal, lakukan regenerasi
        if not validate_json(data):
            raise ValueError(f"Invalid JSON structure for page {page_num}")

        questions_data = []
        question_noun_embeddings = {}

        # Buat embedding dari setiap question_noun satu kali
        for question in data['questions']:
            for noun in question['question_nouns']:
                if noun not in question_noun_embeddings:
                    question_noun_embeddings[noun] = get_embedding(noun)

        # Proses setiap pertanyaan
        for question in data['questions']:
            question_text = question['question']
            answer_text = question['answer']
            question_embedding = get_embedding(question_text)
            answer_embedding = get_embedding(answer_text)
            question['cosine_q&a'] = cosine_sim(
                question_embedding, answer_embedding)

            # Hitung avg_similarity menggunakan embedding yang sudah dihitung
            avg_similarity = sum(
                cosine_sim(question_noun_embeddings[n], t_embedding)
                for n in question['question_nouns'] for t_embedding in tfidf_embeddings
            ) / (len(question['question_nouns']) * len(tfidf_embeddings))

            question['cosine_q&d'] = avg_similarity
            question['page_number'] = page_num + 1

            questions_data.append(question)

        data['questions'] = questions_data
        return data

    except (json.JSONDecodeError, ValueError) as e:
        print(f"Error processing page {page_num}: {e}")
        if retry_count < MAX_RETRY:
            print(
                f"Retrying page {page_num} (Attempt {retry_count + 1}) due to validation failure.")
            return process_page(page_num, text, content, nouns, tfidf_terms, tfidf_embeddings, retry_count + 1)
        else:
            print(
                f"Failed to process page {page_num} after {MAX_RETRY} attempts due to validation failure.")
            return None


# Fungsi utama yang memproses seluruh halaman dalam PDF dan mengembalikan JSON
def generate_questions_from_pdf(pdf_file, tfidf_data, top_n=10, language="Indonesian"):
    if (language == "japanese"):
        language = "JEPANG"
    content = f"""
        Buat pertanyaan untuk menguji pengetahuan mahasiswa berdasarkan teks PDF.
        Gunakan Taksonomi Bloom untuk menghasilkan pertanyaan yang sesuai.
        Terjemahkan semua PERTANYAAN dan JAWABAN ke dalam BAHASA {language} dan pastikan output dalam BAHASA {language}.
    """
    print(language)
    # Ekstrak teks per halaman
    texts = extract_text(pdf_file)
    print("test")
    all_questions = []
    # Proses tfidf_terms untuk mendapatkan embedding n-gram
    combined_terms = tfidf_data['uni'] + \
        tfidf_data['bi'] + tfidf_data['tri']
    # print(combined_terms)
    ngrams = [term['N-gram']
              for term in combined_terms if isinstance(term, dict) and 'N-gram' in term]
    tfidf_embeddings = [get_embedding(ngram) for ngram in ngrams]
    # Inisialisasi ThreadPoolExecutor untuk memproses halaman secara paralel
    with ThreadPoolExecutor() as executor:
        futures = []

        # Buat future untuk setiap halaman PDF yang akan diproses
        for page_num, page_text in enumerate(texts):
            # Urutkan berdasarkan Cosine Similarity, lalu ambil top 10
            combined_terms = tfidf_data['uni'] + \
                tfidf_data['bi'] + tfidf_data['tri']

            # Urutkan berdasarkan Cosine Similarity dan ambil top N
            top_tfidf_terms = sorted(
                combined_terms, key=lambda x: x['Cosine Similarity'], reverse=True)[:top_n]
            # print(top_tfidf_terms)
            # Konversi kolom 'N-gram' menjadi list
            nouns = [term['N-gram'] for term in top_tfidf_terms]

            # Submit setiap halaman untuk diproses secara paralel
            futures.append(executor.submit(process_page, page_num,
                           page_text, content, nouns, tfidf_data, tfidf_embeddings))

        # Tampilkan progress bar saat setiap future selesai diproses
        with tqdm(total=len(futures), desc="Generating Questions", leave=False) as pbar:
            for future in as_completed(futures):
                result = future.result()
                if result:
                    all_questions.extend(result['questions'])
                pbar.update(1)

    return json.dumps(all_questions, ensure_ascii=False, indent=4)
