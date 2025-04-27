import json
from evaluation import AnswerEvaluator
from mainPDF import extract_text, get_embedding, BLOOM_PROGRESSION, get_next_level, generate_reference_answer, generate_questions
from answerBert import BERTQuestionAnsweringSystem 


def run_single_question(pdf_file, tfidf_data, last_answer, current_level="remembering", correct_streak=0, top_n=10, language="Indonesian"):
    evaluator = AnswerEvaluator()
    # Initialize the BERT QA system
    bert_qa_system = BERTQuestionAnsweringSystem()

    if language.lower() == "japanese":
        language = "JEPANG"

    texts = extract_text(pdf_file)
    combined_terms = tfidf_data['uni'] + tfidf_data['bi'] + tfidf_data['tri']
    ngrams = [term['N-gram'] for term in combined_terms if isinstance(term, dict)]
    tfidf_embeddings = [get_embedding(ngram) for ngram in ngrams if get_embedding(ngram) is not None]

    top_terms = sorted(combined_terms, key=lambda x: x.get('Cosine Similarity', 0), reverse=True)[:top_n]
    nouns = [term['N-gram'] for term in top_terms]

    # Template langsung (jika tidak pakai get_prompt_template)
    content = f"""
    Buat pertanyaan untuk menguji pengetahuan mahasiswa berdasarkan teks PDF.
    Fokus pada level Bloom's Taxonomy: {current_level}.
    Gunakan template yang sesuai untuk level {current_level}.
    Terjemahkan semua PERTANYAAN dan JAWABAN ke dalam BAHASA {language}.
    Gunakan list berikut sebagai acuan untuk membuat pertanyaan: {', '.join(nouns)}.

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
    
    Format output harus JSON valid:
    "questions": [
        {{
            "question": "...",
            "category": "{current_level}",
            "question_nouns": ["..."]
        }}
    ]
    """

    for page_text in texts:
        if not page_text.strip():
            continue

        prompt = content + "\n\nTeks referensi:\n" + page_text[:2000]
        response = generate_questions(prompt, page_text)

        try:
            data = json.loads(response)
            if "questions" not in data or not data["questions"]:
                continue

            for question in data["questions"]:
                q_text = question["question"]
                ref_answer = generate_reference_answer(q_text)
                question["reference_answer"] = ref_answer
                context = bert_qa_system.read_pdf(pdf_file)
                # Generate BERT answer using the existing BERTQuestionAnsweringSystem
                bert_answers = bert_qa_system.answer_question(context, q_text, top_k=3)
                
                # Get the best answer (highest combined_score)
                if bert_answers:
                    # Sort answers by combined_score and take the best one
                    bert_answers.sort(key=lambda x: x.get('combined_score', 0), reverse=True)
                    best_bert_answer = bert_answers[0]['answer']
                    question["bert_answer"] = best_bert_answer
                    # Keep track of all BERT answers for reference
                    question["all_bert_answers"] = bert_answers
                else:
                    # Fallback to reference answer if no BERT answers available
                    question["bert_answer"] = ref_answer
                
                # Use BERT answer for evaluation instead of reference answer
                if last_answer:
                    # Use bert_answer instead of reference_answer for evaluation
                    eval_result = evaluator.combined_evaluation(question["bert_answer"], last_answer)
                    question['evaluation'] = eval_result
                    if eval_result["is_correct"]:
                        correct_streak += 1
                    else:
                        correct_streak = 0

                if current_level == "analyzing" and correct_streak >= BLOOM_PROGRESSION[current_level]:
                    return {
                        "message": "Selesai. Mahasiswa telah menyelesaikan seluruh level.",
                        "question": None,
                        "next_level": current_level,
                        "correct_streak": correct_streak
                    }

                next_level, updated_streak = get_next_level(current_level, correct_streak)

                return {
                    "question": q_text,
                    "reference_answer": ref_answer,
                    "bert_answer": question.get("bert_answer", ""),  # Include bert_answer in the response
                    "evaluation": question.get("evaluation", {}),
                    "next_level": next_level,
                    "correct_streak": updated_streak,
                    "save_to_db": True
                }

        except Exception as e:
            print(f"[ERROR] Failed to parse or generate question: {e}")
            continue

    return {
        "message": "Tidak berhasil membuat pertanyaan.",
        "question": None,
        "next_level": current_level,
        "correct_streak": correct_streak
    }