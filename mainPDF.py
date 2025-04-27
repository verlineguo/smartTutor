# !pip install openai
# !pip install pdfplumber
# !pip install sklearn
# import google.generativeai as genai
import json
import collections
from typing import List, Dict
from concurrent.futures import ThreadPoolExecutor, as_completed
from tqdm import tqdm
import pdfplumber
import openai
from sklearn.metrics.pairwise import cosine_similarity

with open('hidden.txt') as file:
    openai.api_key = file.read()


# with open('hidden2.txt') as file:
    # genai_api_key = file.read()


# Setup API Client
# genai.configure(api_key=GENAI_API_KEY)
# deepseek_client = openai.OpenAI(api_key=DEEPSEEK_API_KEY)
# genai.configure(api_key=genai_api_key)


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

# # Validasi struktur JSON yang dihasilkan


def validate_json(data):
    required_keys = {"question", "category", "question_nouns"}
    valid_categories = {"remembering", "understanding", "applying", "analyzing"}
    
    if not isinstance(data, dict) or "questions" not in data:
        return False
        
    questions = data["questions"]
    if not isinstance(questions, list) or len(questions) != 8:
        return False
    
    category_counts = collections.defaultdict(int)
    for q in questions:
        if not all(k in q for k in required_keys):
            return False
        if q["category"] not in valid_categories:
            return False
        category_counts[q["category"]] += 1
    
    return all(count == 2 for count in category_counts.values())

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
            
            #### Applying (Menerapkan):
            1. Bagaimana Anda akan menggunakan ... dalam situasi nyata?
            2. Teknik apa yang akan Anda gunakan untuk menyelesaikan ...?
            3. Apa contoh nyata dari konsep ... dalam kehidupan sehari-hari?
            4. Bagaimana konsep ... dapat diterapkan di bidang ...?
            5. Dalam situasi seperti ..., bagaimana Anda akan menerapkan ...?
            6. Alat atau metode apa yang cocok untuk menerapkan ...?
            7 Berikan skenario di mana ... dapat digunakan secara efektif.
            8 Apa langkah-langkah praktis untuk menerapkan ...?
            9. Bagaimana Anda akan menyusun solusi menggunakan prinsip ...?
            10. Bagaimana Anda mengadaptasi ... untuk digunakan di lingkungan yang berbeda?
            11. Apa saja tantangan yang mungkin dihadapi saat menerapkan ...?
            12. Jika Anda diberikan masalah ..., bagaimana Anda akan menyelesaikannya menggunakan ...?
                        
            ### Analyzing (Menganalisis):
            1. Apa asumsi yang mendasari konsep ...?
            2. Apa perbedaan antara ... dan ... dalam konteks ...?
            3. Apa struktur logis dari penjelasan tentang ...?
            4. Bagaimana bagian-bagian dari ... saling berhubungan?
            5. Apa penyebab dan akibat dari ...?
            6. Bagaimana Anda mengelompokkan informasi tentang ...?
            7. Apa bukti yang mendukung atau melemahkan ...?
            8. Bandingkan pendekatan A dan B dalam menyelesaikan ..., mana yang lebih efektif dan mengapa?
            9. Apa pola yang dapat diidentifikasi dalam ...?
            10. Apa kelemahan dalam argumen mengenai ...?
            11. Bagaimana Anda mengevaluasi keakuratan informasi tentang ...?
            12. Dalam struktur sistem ..., apa peran masing-masing komponen dan bagaimana mereka saling memengaruhi?

        Dari masing-masing kategori di atas, buat 2 pertanyaan remembering, 2 pertanyaan understanding, 2 pertanyaan applying, dan 2 pertanyaan analyzing SEHINGGA TOTAL SELURUH PERTANYAAN ADALAH 8 SOAL !!. JANGAN bertanya tentang sejarah yang hanya menyangkut waktu dan juga JANGAN MENGGUNAKAN TEMPLATE PERTANYAAN YANG SAMA LEBIH DARI 2X, tetapi Perluas/Perdalam materi berdasarkan kata-kata kunci yang dimasukkan pengguna.
        setelah Mengenerate pertanyaan. ekstrak semua nouns pada pertanyaan tersebut dan masukkan ke "question_nouns".
        FORMAT RESPONSE HARUS DALAM BENTUK JSON yang dapat di DECODE !!!  BERIKUT MERUPAKAN CONTOHNYA !!! :

         "questions":[
                      {
                      "question": "What is supervised learning method?",
                      "category": "remembering",
                      "question_nouns": ["learning method", "approach", "machine learning", "model", "data", "input", "output"]
                      },
                      {
                      "question": "How do you choose an appropriate model in machine learning?",
                      "category": "remembering",
                      "question_nouns": ["model", "machine learning", "data", "objectives", "performance", "complexity", "generalization", "adaptation"]
                      },
                      {
                      "question": "Why is cross-validation important in machine learning?",
                      "category": "understanding",
                      "question_nouns": ["cross-validation", "model", "performance", "data", "subsets"]
                      }
                  ]

        """
    return template

MAX_RETRY = 3  # Batas percobaan untuk regenerasi pertanyaan

# Fungsi utama untuk memproses halaman PDF dan menghasilkan JSON


def process_page(page_num, text, content, nouns, tfidf_embeddings):
    MAX_ATTEMPTS = 3
    questions = []
    
    for attempt in range(MAX_ATTEMPTS):
        try:
            prompt = generate_prompt(content, nouns)
            response = generate_questions(prompt, text[:3000])  # Limit input size
            
            # Basic validation before JSON parse
            if not response.strip().startswith("{"):
                response = "{" + response.split("{", 1)[-1]  # Fix common formatting issue
            
            data = json.loads(response)
            
            if validate_json(data):
                # Process all 8 questions (not just 2)
                page_questions = []
                for q in data['questions']:
                    q['page_number'] = page_num + 1
                    q['cosine_q&d'] = calculate_similarity(q, tfidf_embeddings)
                    page_questions.append(q)
                
                # Ensure we got 2 per category
                categories = collections.defaultdict(int)
                for q in page_questions:
                    categories[q['category']] += 1
                
                if all(count >= 2 for count in categories.values()):
                    questions.extend(page_questions)
                    break
                
        except Exception as e:
            print(f"Attempt {attempt+1} failed: {str(e)}")
            if attempt == MAX_ATTEMPTS - 1:
                print(f"Failed after {MAX_ATTEMPTS} attempts for page {page_num}")
    
    return questions

def calculate_similarity(question, tfidf_embeddings):
    # Simplified similarity calculation
    noun_embeddings = [get_embedding(noun) for noun in question['question_nouns']]
    if not noun_embeddings or not tfidf_embeddings:
        return 0
    
    similarities = []
    for noun_emb in noun_embeddings:
        for tfidf_emb in tfidf_embeddings:
            similarities.append(cosine_sim(noun_emb, tfidf_emb))
    
    return sum(similarities) / len(similarities) if similarities else 0


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
    with ThreadPoolExecutor(max_workers=3) as executor:
        futures = []

        # Buat future untuk setiap halaman PDF yang akan diproses
        for page_num, page_text in enumerate(texts):
            nouns = [term['N-gram'] for term in 
                   sorted(tfidf_data['uni'] + tfidf_data['bi'] + tfidf_data['tri'],
                   key=lambda x: x['Cosine Similarity'], reverse=True)[:top_n]]
            
            futures.append(executor.submit(
                process_page, page_num, page_text, content, nouns, tfidf_embeddings
            ))
        
        for future in tqdm(as_completed(futures), total=len(futures)):
            all_questions.extend(future.result())

        unique_questions = []
        seen = set()
        for q in all_questions:
            ident = (q['question'], q['category'])
            if ident not in seen:
                seen.add(ident)
                unique_questions.append(q)
        
        return json.dumps(unique_questions, ensure_ascii=False, indent=2)