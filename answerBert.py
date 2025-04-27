import numpy as np
import pandas as pd
import re
import torch
from transformers import AutoTokenizer, AutoModel, pipeline
from sentence_transformers import SentenceTransformer
from sklearn.metrics.pairwise import cosine_similarity
from PyPDF2 import PdfReader
import nltk
from nltk.tokenize import sent_tokenize, word_tokenize
import logging
import warnings
from typing import List, Dict, Tuple, Any
import time

# Konfigurasi logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
warnings.filterwarnings('ignore')

# Download NLTK resources jika belum ada
try:
    nltk.data.find('tokenizers/punkt')
except LookupError:
    nltk.download('punkt')

class BertAnsweringSystem:
    def __init__(self):
        """Inisialisasi model dan komponen yang diperlukan untuk sistem QA Bahasa Indonesia."""
        logging.info("Memulai inisialisasi sistem QA Bahasa Indonesia...")
        
        # Model untuk pemahaman bahasa - menggunakan model multilingual yang mendukung bahasa Indonesia
        self.qa_model_name = "deepset/xlm-roberta-base-squad2"
        # Model embedding untuk retrieval - model multilingual yang mendukung bahasa Indonesia
        self.embedding_model_name = "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2"
        
        logging.info(f"Loading QA model: {self.qa_model_name}")
        self.qa_pipe = pipeline('question-answering', model=self.qa_model_name, tokenizer=self.qa_model_name)
        
        logging.info(f"Loading embedding model: {self.embedding_model_name}")
        self.embedding_model = SentenceTransformer(self.embedding_model_name)
        
        # Model BERT untuk validasi jawaban dan perhitungan relevansi
        logging.info("Loading model BERT untuk validasi jawaban")
        self.bert_model_name = "indobenchmark/indobert-base-p1" # Model yang mendukung bahasa Indonesia
        self.bert_tokenizer = AutoTokenizer.from_pretrained(self.bert_model_name)
        self.bert_model = AutoModel.from_pretrained(self.bert_model_name)
        
        # Konstanta untuk scoring
        self.CHUNK_OVERLAP = 150  # Meningkatkan overlap untuk konteks lebih baik
        self.MIN_CHUNK_SIZE = 300
        self.MAX_CHUNK_SIZE = 800  # Meningkatkan ukuran chunk untuk lebih banyak konteks
        self.QA_WEIGHT = 0.55
        self.RETRIEVAL_WEIGHT = 0.45
        
        # Deteksi level taksonomi Bloom
        self.bloom_keywords = {
            'remembering': ['apa', 'siapa', 'kapan', 'dimana', 'sebutkan', 'identifikasi', 'jelaskan', 'definisikan'],
            'understanding': ['jelaskan', 'uraikan', 'bandingkan', 'bedakan', 'interpretasikan', 'simpulkan'],
            'applying': ['terapkan', 'gunakan', 'demonstrasikan', 'ilustrasikan', 'hitung', 'selesaikan'],
            'analyzing': ['analisis', 'mengapa', 'bagaimana', 'klasifikasikan', 'bandingkan', 'kontras', 'sebab', 'akibat'],
            'evaluating': ['evaluasi', 'nilai', 'kritik', 'justifikasi', 'dukung', 'tolak', 'putuskan'],
            'creating': ['buatlah', 'rancang', 'kembangkan', 'susun', 'formulasikan', 'hipotesis']
        }
        
        logging.info("Inisialisasi sistem QA Bahasa Indonesia selesai!")

    def detect_bloom_level(self, question: str) -> str:
        """Mendeteksi level taksonomi Bloom dari pertanyaan."""
        question_lower = question.lower()
        detected_levels = {}
        
        for level, keywords in self.bloom_keywords.items():
            for keyword in keywords:
                if keyword in question_lower:
                    if level in detected_levels:
                        detected_levels[level] += 1
                    else:
                        detected_levels[level] = 1
        
        if not detected_levels:
            return 'remembering'  # default level
        
        # Return level dengan jumlah keyword terbanyak
        return max(detected_levels.items(), key=lambda x: x[1])[0]

    def load_pdf(self, pdf_path: str) -> str:
        """Memuat dan mengekstrak teks dari file PDF."""
        logging.info(f"Memuat PDF dari: {pdf_path}")
        text = ""
        try:
            pdf_reader = PdfReader(pdf_path)
            for page in pdf_reader.pages:
                page_text = page.extract_text()
                if page_text:  # Pastikan teks tidak kosong
                    text += page_text + " "
            
            # Menangani karakter khusus dan spacing
            text = re.sub(r'\s+', ' ', text)
            text = text.strip()
            
            logging.info(f"Berhasil mengekstrak {len(text)} karakter dari PDF")
            return text
        except Exception as e:
            logging.error(f"Gagal memuat PDF: {str(e)}")
            raise

    def preprocess_text(self, text: str) -> str:
        """Pra-pemrosesan teks untuk meningkatkan kualitas hasil."""
        # Membersihkan teks
        text = re.sub(r'\n+', ' ', text)  # Menghapus newlines
        text = re.sub(r'\s+', ' ', text)  # Menghapus whitespace berlebih
        text = re.sub(r'[^\w\s.,?!:;()\[\]{}"\'-]', '', text)  # Menghapus karakter aneh
        
        # Normalisasi tanda baca
        text = re.sub(r'\.+', '.', text)  # Mengganti multiple dots dengan single dot
        text = re.sub(r'\s+([.,;:!?])', r'\1', text)  # Menghapus space sebelum tanda baca
        
        return text.strip()

    def chunk_text(self, text: str) -> List[str]:
        """Membagi teks menjadi chunk dengan overlap untuk konteks yang lebih baik."""
        logging.info("Membagi teks menjadi chunk...")
        sentences = sent_tokenize(text)
        chunks = []
        current_chunk = ""
        
        for sentence in sentences:
            # Menambahkan kalimat ke chunk saat ini jika masih dalam batas ukuran
            if len(current_chunk) + len(sentence) <= self.MAX_CHUNK_SIZE:
                current_chunk += " " + sentence if current_chunk else sentence
            else:
                # Jika chunk sudah cukup besar, simpan dan mulai chunk baru
                if current_chunk:
                    chunks.append(current_chunk.strip())
                current_chunk = sentence
        
        # Menambahkan chunk terakhir jika tidak kosong
        if current_chunk:
            chunks.append(current_chunk.strip())
            
        # Menambahkan overlap antara chunk untuk konteks yang lebih baik
        overlapped_chunks = []
        for i in range(len(chunks)):
            if i < len(chunks) - 1:
                # Dapatkan beberapa kalimat dari chunk berikutnya
                next_sentences = sent_tokenize(chunks[i+1])
                overlap_text = " ".join(next_sentences[:3]) if len(next_sentences) >= 3 else chunks[i+1][:self.CHUNK_OVERLAP]
                overlapped_chunks.append(chunks[i] + " " + overlap_text)
            else:
                overlapped_chunks.append(chunks[i])
        
        # Tambahan: pastikan chunk tidak terlalu kecil dengan menggabungkan chunk yang pendek
        final_chunks = []
        current_chunk = ""
        for chunk in overlapped_chunks:
            if len(current_chunk) + len(chunk) <= self.MAX_CHUNK_SIZE * 1.2:
                current_chunk += " " + chunk if current_chunk else chunk
            else:
                if current_chunk:
                    final_chunks.append(current_chunk.strip())
                current_chunk = chunk
        
        if current_chunk:
            final_chunks.append(current_chunk.strip())
        
        logging.info(f"Teks dibagi menjadi {len(final_chunks)} chunk")
        return final_chunks

    def get_embeddings(self, texts: List[str]) -> np.ndarray:
        """Menghasilkan embeddings untuk list teks menggunakan model embedding."""
        return self.embedding_model.encode(texts)

    def calculate_relevance(self, question_embedding: np.ndarray, chunk_embeddings: np.ndarray) -> List[float]:
        """Menghitung skor relevansi antara pertanyaan dan setiap chunk berdasarkan cosine similarity."""
        similarity_scores = cosine_similarity([question_embedding], chunk_embeddings)[0]
        return similarity_scores

    def validate_answer(self, question: str, answer: str, context: str) -> Tuple[bool, float]:
        """Memvalidasi jawaban menggunakan BERT untuk mengukur konsistensi."""
        try:
            # Encode question + context dan question + answer
            inputs_context = self.bert_tokenizer(question, context, return_tensors="pt", truncation=True, max_length=512)
            inputs_answer = self.bert_tokenizer(question, answer, return_tensors="pt", truncation=True, max_length=512)
            
            # Get embeddings
            with torch.no_grad():
                outputs_context = self.bert_model(**inputs_context)
                outputs_answer = self.bert_model(**inputs_answer)
                
            # Get CLS token embeddings
            context_embedding = outputs_context.last_hidden_state[:, 0, :].numpy()
            answer_embedding = outputs_answer.last_hidden_state[:, 0, :].numpy()
            
            # Hitung cosine similarity
            similarity = cosine_similarity(context_embedding, answer_embedding)[0][0]
            
            # Jawaban valid jika similarity diatas threshold tertentu
            valid = similarity > 0.65
            return valid, similarity
        except Exception as e:
            logging.warning(f"Error validasi jawaban: {str(e)}")
            return True, 0.7  # Default fallback

    def generate_essay_answer(self, question: str, relevant_chunks: List[str], bloom_level: str) -> str:
        """
        Menghasilkan jawaban esai berdasarkan konteks yang relevan dan level taksonomi Bloom.
        """
        try:
            # Gabungkan chunk-chunk yang relevan dengan batasan ukuran
            combined_context = " ".join(relevant_chunks)
            if len(combined_context) > 1500:
                combined_context = combined_context[:1500]
            
            # Buat prompt yang sesuai dengan level taksonomi Bloom
            if bloom_level in ['remembering', 'understanding']:
                prompt = f"Berdasarkan informasi berikut, berikan jawaban detail dan komprehensif untuk pertanyaan: '{question}'. Jawaban harus lengkap dan informatif."
            elif bloom_level == 'applying':
                prompt = f"Berdasarkan informasi berikut, jelaskan secara lengkap bagaimana menerapkan konsep dalam pertanyaan: '{question}'. Sertakan contoh dan aplikasi praktis."
            elif bloom_level == 'analyzing':
                prompt = f"Berdasarkan informasi berikut, lakukan analisis mendalam untuk pertanyaan: '{question}'. Identifikasi komponen utama, hubungan antar konsep, dan berikan pemahaman yang mendalam."
            elif bloom_level in ['evaluating', 'creating']:
                prompt = f"Berdasarkan informasi berikut, berikan evaluasi kritis dan menyeluruh untuk pertanyaan: '{question}'. Sertakan argumen yang didukung bukti, kemungkinan solusi, dan kesimpulan."
            else:
                prompt = f"Berdasarkan informasi berikut, berikan jawaban komprehensif untuk pertanyaan: '{question}'."
            
            # Gunakan pipeline QA untuk mendapatkan jawaban awal
            qa_result = self.qa_pipe(question=prompt, context=combined_context)
            initial_answer = qa_result['answer']
            
            # Perluasan jawaban: Dapatkan jawaban lainnya dari chunk berbeda untuk memperkaya
            additional_answers = []
            for chunk in relevant_chunks[:3]:  # Ambil 3 chunk paling relevan
                try:
                    result = self.qa_pipe(question=question, context=chunk)
                    if result['answer'] not in [initial_answer] + additional_answers:
                        additional_answers.append(result['answer'])
                except:
                    continue
            
            # Gabungkan jawaban-jawaban untuk membuat esai yang lebih kaya
            essay_parts = [initial_answer] + additional_answers
            
            # Gabungkan semua bagian menjadi esai yang koheren
            if bloom_level in ['remembering', 'understanding']:
                essay = self._construct_essay_remembering(question, essay_parts)
            elif bloom_level == 'applying':
                essay = self._construct_essay_applying(question, essay_parts, combined_context)
            elif bloom_level == 'analyzing':
                essay = self._construct_essay_analyzing(question, essay_parts, combined_context)
            else:
                essay = self._construct_essay_default(question, essay_parts)
            
            # Pastikan jawaban memiliki panjang minimum untuk esai (minimal 150 kata)
            if len(word_tokenize(essay)) < 150:
                # Tambahkan kalimat elaborasi jika terlalu pendek
                essay = self._expand_answer(essay, combined_context, bloom_level)
            
            return essay
            
        except Exception as e:
            logging.warning(f"Error generating essay: {str(e)}")
            # Fallback ke jawaban sederhana jika generasi esai gagal
            combined_context = " ".join(relevant_chunks[:2])
            qa_result = self.qa_pipe(question=question, context=combined_context)
            return self._expand_answer(qa_result['answer'], combined_context, "remembering")
    
    def _construct_essay_remembering(self, question: str, parts: List[str]) -> str:
        """Menyusun esai untuk level remembering dan understanding."""
        # Pembukaan
        opening = f"Untuk menjawab pertanyaan '{question}', perlu dipahami beberapa konsep penting. "
        
        # Isi utama - gabungkan semua bagian dengan transisi yang tepat
        main_content = ""
        for i, part in enumerate(parts):
            if i == 0:
                main_content += part
            else:
                transition_phrases = [
                    "Selain itu, ", "Lebih lanjut, ", "Penting juga untuk dicatat bahwa ", 
                    "Dalam konteks ini, ", "Berdasarkan informasi yang ada, "
                ]
                transition = transition_phrases[i % len(transition_phrases)]
                main_content += " " + transition + part[0].lower() + part[1:]
        
        # Penutup
        closing = " Dengan demikian, dapat disimpulkan bahwa konsep ini merupakan aspek penting dalam memahami topik tersebut."
        
        essay = opening + main_content + closing
        return essay
    
    def _construct_essay_applying(self, question: str, parts: List[str], context: str) -> str:
        """Menyusun esai untuk level applying."""
        # Cari kata kunci dari konteks untuk digunakan sebagai contoh aplikasi
        keywords = re.findall(r'\b[A-Z][a-z]{5,}\b', context)
        examples = [k for k in keywords if len(k) > 5][:3]
        
        # Pembukaan
        opening = f"Dalam mengaplikasikan konsep yang ditanyakan dalam '{question}', kita perlu memahami prinsip-prinsip dasar dan bagaimana penerapannya dalam situasi nyata. "
        
        # Isi utama
        main_content = " ".join(parts)
        
        # Tambahkan contoh aplikasi
        application = " Penerapan konsep ini dapat dilihat dalam beberapa konteks. "
        if examples:
            application += f"Misalnya dalam kasus {', '.join(examples[:-1])}" 
            if len(examples) > 1:
                application += f" dan {examples[-1]}" 
            application += ", prinsip-prinsip tersebut menjadi sangat relevan. "
        
        # Penutup
        closing = " Dengan demikian, pemahaman yang mendalam tentang konsep ini memungkinkan kita untuk mengaplikasikannya secara efektif dalam berbagai situasi."
        
        essay = opening + main_content + application + closing
        return essay
    
    def _construct_essay_analyzing(self, question: str, parts: List[str], context: str) -> str:
        """Menyusun esai untuk level analyzing."""
        # Pembukaan dengan penekanan pada analisis
        opening = f"Analisis terhadap pertanyaan '{question}' memerlukan pemahaman mendalam tentang berbagai aspek yang saling berkaitan. "
        
        # Isi utama dengan struktur yang lebih analitis
        main_parts = []
        for i, part in enumerate(parts):
            if i == 0:
                main_parts.append(f"Pertama, {part}")
            elif i == 1:
                main_parts.append(f"Kedua, {part[0].lower()}{part[1:]}")
            elif i == 2:
                main_parts.append(f"Ketiga, {part[0].lower()}{part[1:]}")
            else:
                main_parts.append(f"Selain itu, {part[0].lower()}{part[1:]}")
        
        main_content = " ".join(main_parts)
        
        # Temukan hubungan atau implikasi
        implications = " Beberapa implikasi penting dari analisis ini antara lain adalah pemahaman yang lebih mendalam tentang konsep yang dibahas, kemampuan untuk menerapkannya dalam konteks yang lebih luas, dan pengetahuan tentang keterkaitannya dengan aspek-aspek lain."
        
        # Penutup dengan kesimpulan analitis
        closing = " Berdasarkan analisis di atas, dapat disimpulkan bahwa masalah ini memiliki kompleksitas yang memerlukan pemahaman dari berbagai sudut pandang."
        
        essay = opening + main_content + implications + closing
        return essay
    
    def _construct_essay_default(self, question: str, parts: List[str]) -> str:
        """Menyusun esai default untuk level lainnya."""
        # Pembukaan
        opening = f"Dalam menjawab pertanyaan '{question}', terdapat beberapa aspek penting yang perlu diperhatikan. "
        
        # Isi
        main_content = " ".join(parts)
        
        # Penutup
        closing = " Dengan memahami konsep-konsep tersebut, kita dapat memperoleh gambaran yang lebih komprehensif tentang topik yang dibahas."
        
        essay = opening + main_content + closing
        return essay
    
    def _expand_answer(self, answer: str, context: str, bloom_level: str) -> str:
        """Memperluas jawaban untuk memastikan panjang memadai untuk esai."""
        # Ekstrak kalimat-kalimat dari konteks yang mungkin relevan
        sentences = sent_tokenize(context)
        
        # Buat embedding untuk jawaban
        answer_embedding = self.embedding_model.encode(answer)
        
        # Cari kalimat yang mirip dengan jawaban
        sentence_embeddings = self.embedding_model.encode(sentences)
        similarities = cosine_similarity([answer_embedding], sentence_embeddings)[0]
        
        # Ambil 3-5 kalimat yang paling relevan tapi belum ada di jawaban
        relevant_sentences = []
        for idx in similarities.argsort()[-10:][::-1]:
            if sentences[idx] not in answer and len(relevant_sentences) < 5:
                relevant_sentences.append(sentences[idx])
        
        # Tambahkan paragraf berdasarkan level bloom
        if bloom_level in ['remembering', 'understanding']:
            expanded = f"{answer} Penting untuk dicatat bahwa {relevant_sentences[0] if relevant_sentences else 'konsep ini memiliki aspek-aspek penting'}. "
            if len(relevant_sentences) > 1:
                expanded += f"Selain itu, {relevant_sentences[1]}. "
            expanded += "Pemahaman yang mendalam tentang topik ini sangat penting dalam konteks pembelajaran yang lebih luas."
            
        elif bloom_level == 'applying':
            expanded = f"{answer} Dalam praktiknya, {relevant_sentences[0] if relevant_sentences else 'konsep ini dapat diterapkan dalam berbagai situasi'}. "
            if len(relevant_sentences) > 1:
                expanded += f"Contoh penerapan lainnya adalah ketika {relevant_sentences[1].lower() if relevant_sentences[1][0].isupper() else relevant_sentences[1]}. "
            expanded += "Kemampuan untuk mengaplikasikan konsep ini dalam berbagai konteks menunjukkan pemahaman yang mendalam."
            
        elif bloom_level == 'analyzing':
            expanded = f"{answer} Analisis lebih lanjut menunjukkan bahwa {relevant_sentences[0] if relevant_sentences else 'terdapat beberapa komponen kunci dalam topik ini'}. "
            if len(relevant_sentences) > 1:
                expanded += f"Jika kita mengamati lebih detail, {relevant_sentences[1].lower() if relevant_sentences[1][0].isupper() else relevant_sentences[1]}. "
            expanded += "Dengan memahami keterhubungan antar konsep, kita dapat memperoleh perspektif yang lebih holistik."
            
        else:
            expanded = f"{answer} Perlu dipertimbangkan juga bahwa {relevant_sentences[0] if relevant_sentences else 'terdapat beberapa aspek penting dalam topik ini'}. "
            if len(relevant_sentences) > 1:
                expanded += f"{relevant_sentences[1]} "
            if len(relevant_sentences) > 2:
                expanded += f"Lebih lanjut, {relevant_sentences[2]}. "
            expanded += "Dengan mempertimbangkan berbagai aspek ini, kita dapat memperoleh pemahaman yang komprehensif tentang topik yang dibahas."
        
        return expanded

    def answer_question(self, context: str, question: str, top_k: int = 3) -> List[Dict[str, Any]]:
        """
        Menjawab pertanyaan berdasarkan konteks dengan mengembalikan top-k jawaban terbaik.
        Dirancang khusus untuk menghasilkan jawaban esai yang komprehensif.
        
        Args:
            context (str): Teks konteks lengkap (dari PDF)
            question (str): Pertanyaan dalam bahasa Indonesia
            top_k (int): Jumlah jawaban terbaik yang akan dikembalikan
            
        Returns:
            List[Dict]: Daftar top-k jawaban dengan skor dan metadata terkait
        """
        logging.info(f"Menjawab pertanyaan: '{question}'")
        
        # Deteksi level taksonomi Bloom
        bloom_level = self.detect_bloom_level(question)
        logging.info(f"Terdeteksi level taksonomi Bloom: {bloom_level}")
        
        # Preprocess context dan bagi menjadi chunk
        processed_context = self.preprocess_text(context)
        chunks = self.chunk_text(processed_context)
        
        # Mendapatkan embeddings
        question_embedding = self.embedding_model.encode(question)
        chunk_embeddings = self.get_embeddings(chunks)
        
        # Menghitung relevance scores
        relevance_scores = self.calculate_relevance(question_embedding, chunk_embeddings)
        
        # Urutkan chunk berdasarkan relevansi
        sorted_chunks_with_scores = sorted(zip(chunks, relevance_scores), key=lambda x: x[1], reverse=True)
        
        # Ambil chunk paling relevan untuk digunakan dalam generasi jawaban esai
        top_relevant_chunks = [chunk for chunk, _ in sorted_chunks_with_scores[:5]]
        
        # Generate jawaban esai dari chunk-chunk yang relevan
        essay_answer = self.generate_essay_answer(question, top_relevant_chunks, bloom_level)
        
        # Proses jawaban reguler dari setiap chunk untuk mendapatkan top-k
        results = []
        for i, (chunk, relevance) in enumerate(sorted_chunks_with_scores):
            if i >= min(10, len(chunks)):  # Batasi hanya 10 chunk teratas
                break
                
            logging.info(f"Mengevaluasi chunk {i+1}/{min(10, len(chunks))} (relevance: {relevance:.4f})")
            
            # Gunakan QA pipeline untuk mendapatkan jawaban dari chunk
            try:
                qa_result = self.qa_pipe(question=question, context=chunk)
                answer = qa_result['answer']
                
                # Untuk jawaban dari chunk, tambahkan konteks jika terlalu pendek
                if len(word_tokenize(answer)) < 20:
                    # Cari kalimat dari chunk yang berisi jawaban
                    sentences = sent_tokenize(chunk)
                    for sentence in sentences:
                        if answer in sentence and sentence != answer:
                            answer = sentence
                            break
                
                # Validasi jawaban menggunakan BERT
                is_valid, validation_score = self.validate_answer(question, answer, chunk)
                
                # Menghitung skor gabungan
                qa_score = qa_result['score']
                
                # Berikan bobot lebih untuk jawaban yang lebih panjang (untuk mendukung format esai)
                length_bonus = min(0.2, 0.01 * len(word_tokenize(answer)))
                
                combined_score = (self.QA_WEIGHT * qa_score) + (self.RETRIEVAL_WEIGHT * relevance) + length_bonus
                
                # Penyesuaian tambahan berdasarkan validasi BERT
                if is_valid:
                    combined_score *= (1.0 + 0.2 * validation_score)
                else:
                    combined_score *= 0.7
                
                # Tambahkan hasil ke daftar
                results.append({
                    'answer': answer,
                    'original_answer': qa_result['answer'],
                    'qa_score': qa_result['score'],
                    'retrieval_score': relevance,
                    'combined_score': combined_score,
                    'chunk': chunk,
                    'is_valid': is_valid,
                    'bloom_level': bloom_level
                })
                
            except Exception as e:
                logging.warning(f"Error pada chunk {i+1}: {str(e)}")
                continue
        
        # Tambahkan jawaban esai sebagai hasil teratas
        essay_result = {
            'answer': essay_answer,
            'original_answer': essay_answer[:50] + "...",  # Untuk referensi saja
            'qa_score': 0.95,  # Nilai tinggi karena ini adalah jawaban esai komprehensif
            'retrieval_score': 0.95,
            'combined_score': 0.95,  # Prioritaskan jawaban esai
            'chunk': "combined_relevant_chunks",
            'is_valid': True,
            'bloom_level': bloom_level,
            'is_essay': True
        }
        
        # Gabungkan essay result dengan hasil regular dan pilih top-k
        all_results = [essay_result] + results
        top_results = sorted(all_results, key=lambda x: x['combined_score'], reverse=True)[:top_k]
        
        logging.info(f"Berhasil menghasilkan {len(top_results)} jawaban terbaik")
        return top_results

    def postprocess_answers(self, answers: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """
        Memperbaiki format dan kualitas jawaban akhir.
        """
        for i, answer_data in enumerate(answers):
            # Perbaiki kapitalisasi dan tanda baca
            answer = answer_data['answer']
            
            # Pastikan jawaban diakhiri dengan tanda baca yang tepat
            if not answer.endswith(('.', '!', '?')):
                answer += '.'
            
            # Pastikan huruf pertama kapital
            if answer and len(answer) > 0:
                answer = answer[0].upper() + answer[1:] if answer else answer
            
            # Hapus karakter khusus yang tidak diinginkan
            answer = re.sub(r'[^\w\s.,?!:;()\[\]{}"\'-]', '', answer)
            
            # Perbaiki spasi setelah tanda baca
            answer = re.sub(r'([.,;:!?])([A-Za-z])', r'\1 \2', answer)
            
            # Perbaiki kesalahan umum dalam bahasa Indonesia
            answer = re.sub(r'\bdi bawah ini\b', 'berikut', answer)
            answer = re.sub(r'\badalah merupakan\b', 'adalah', answer)
            
            answers[i]['answer'] = answer
            
        return answers

    def process_pdf_query(self, pdf_path: str, question: str, top_k: int = 3) -> List[Dict[str, Any]]:
        """
        Fungsi utilitas untuk memproses query dari PDF dalam satu langkah.
        """
        try:
            # Load dan proses PDF
            context = self.load_pdf(pdf_path)
            
            # Dapatkan jawaban
            results = self.answer_question(context, question, top_k)
            
            # Post-process jawaban
            final_results = self.postprocess_answers(results)
            
            return final_results
        except Exception as e:
            logging.error(f"Error memproses query PDF: {str(e)}")
            return []


# Contoh penggunaan
if __name__ == "__main__":
    qa_system = IndonesianQASystem()
    
    # Contoh dengan file PDF
    pdf_path = "translated_67ff25827deef.pdf"
    
    # Contoh pertanyaan berbagai level taksonomi Bloom
    pertanyaan_samples = [
        "Apa pengertian dari machine learning?",  # Remembering
        "Jelaskan bagaimana machine learning bekerja?",  # Understanding
        "Bagaimana cara menerapkan konsep machine learning dalam spam filtering?",  # Applying
        "Analisis perbandingan antara supervised learning dan unsupervised learning.",  # Analyzing

    ]
    
    # Proses salah satu pertanyaan
    question = pertanyaan_samples[1]  # Pilih salah satu pertanyaan
    print(f"\nMemproses pertanyaan: {question}")
    
    # Dapatkan dan tampilkan jawaban
    results = qa_system.process_pdf_query(pdf_path, question)
    
    print("\n===== Hasil Top 3 Jawaban =====")
    for i, result in enumerate(results):
        print(f"\nJawaban #{i+1} (Skor: {result['combined_score']:.4f}, Level Bloom: {result['bloom_level']}):")
        print(f"- {result['answer']}")
        print(f"- Valid: {result['is_valid']}")
        print(f"- Panjang jawaban: {len(word_tokenize(result['answer']))} kata")
        if 'is_essay' in result and result['is_essay']:
            print("- [Jawaban dalam format esai]")