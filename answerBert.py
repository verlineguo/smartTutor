import numpy as np
import re
import fitz
from transformers import pipeline
from sentence_transformers import SentenceTransformer
from sklearn.metrics.pairwise import cosine_similarity
import nltk
from nltk.tokenize import sent_tokenize, word_tokenize
import openai
import requests
from typing import List, Dict, Tuple, Any, Optional

try:
    nltk.data.find('tokenizers/punkt')
except LookupError:
    nltk.download('punkt')

with open('hidden.txt') as file:
    openai.api_key = file.read()
    
class BertAnsweringSystem:
    def __init__(self, use_openai_for_formatting: bool = True, openai_api_key: Optional[str] = openai.api_key):
        self.qa_model_name = "deepset/xlm-roberta-large-squad2"
        self.embedding_model_name = "sentence-transformers/paraphrase-multilingual-mpnet-base-v2"
        self.qa_pipe = pipeline('question-answering', model=self.qa_model_name, tokenizer=self.qa_model_name)
        self.embedding_model = SentenceTransformer(self.embedding_model_name)
        self.use_openai_for_formatting = use_openai_for_formatting
        self.openai_api_key = openai_api_key
        
        if use_openai_for_formatting and not openai_api_key:
            self.use_openai_for_formatting = False
            
        self.CHUNK_OVERLAP = 200
        self.MIN_CHUNK_SIZE = 300
        self.MAX_CHUNK_SIZE = 1000
        self.QA_WEIGHT = 0.4
        self.RETRIEVAL_WEIGHT = 0.6
        
        self.bloom_keywords = {
            'remembering': ['apa', 'siapa', 'kapan', 'dimana', 'sebutkan', 'identifikasi', 'jelaskan', 'definisikan'],
            'understanding': ['jelaskan', 'uraikan', 'bandingkan', 'bedakan', 'interpretasikan', 'simpulkan'],
            'applying': ['terapkan', 'gunakan', 'demonstrasikan', 'ilustrasikan', 'hitung', 'selesaikan'],
            'analyzing': ['analisis', 'mengapa', 'bagaimana', 'klasifikasikan', 'bandingkan', 'kontras', 'sebab', 'akibat'],
        }

    def detect_bloom_level(self, question: str) -> str:
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
            return 'remembering'
        
        return max(detected_levels.items(), key=lambda x: x[1])[0]

    def load_pdf(self, pdf_path: str) -> Tuple[str, List[Dict]]:
        full_text = ""
        metadata = []
        page_text_data = []
        
        doc = fitz.open(pdf_path)
        
        for page_num, page in enumerate(doc):
            page_text = page.get_text("text")
            cleaned_page_text = self.preprocess_text(page_text)
            
            page_text_data.append({
                "text": cleaned_page_text,
                "page": page_num + 1
            })
            
            full_text += cleaned_page_text + " "
        
        full_text = self.preprocess_text(full_text)
        
        metadata.append({
            "type": "page_text_data",
            "data": page_text_data
        })
        
        sentence_page_mapping = []
        for page_data in page_text_data:
            page_sentences = sent_tokenize(page_data["text"])
            page_num = page_data["page"]
            
            for sentence in page_sentences:
                if sentence.strip():
                    sentence_page_mapping.append({
                        "text": sentence.strip(),
                        "page": page_num
                    })
        
        metadata.append({
            "type": "sentence_page_mapping",
            "mapping": sentence_page_mapping
        })
        
        return full_text, metadata

    def preprocess_text(self, text: str) -> str:
        text = re.sub(r'\n+', ' ', text)
        text = re.sub(r'\s+', ' ', text)
        text = re.sub(r'[^\w\s.,?!:;()\[\]{}"\'\-]', '', text)
        text = re.sub(r'\.+', '.', text)
        text = re.sub(r'\s+([.,;:!?])', r'\1', text)
        
        return text.strip()

    def chunk_text_with_page_info(self, text: str, metadata: List[Dict]) -> List[Dict]:
        sentence_page_mapping = None
        for item in metadata:
            if item['type'] == 'sentence_page_mapping' and 'mapping' in item:
                sentence_page_mapping = item['mapping']
                break
        
        if not sentence_page_mapping:
            chunks = self.chunk_text(text)
            return [{"text": chunk, "pages": [1]} for chunk in chunks]
        
        all_sentences = [(item["text"], item["page"]) for item in sentence_page_mapping]
        
        chunks_with_pages = []
        current_chunk_text = ""
        current_chunk_pages = set()
        
        for sentence, page in all_sentences:
            if len(current_chunk_text) + len(sentence) <= self.MAX_CHUNK_SIZE:
                current_chunk_text += " " + sentence if current_chunk_text else sentence
                current_chunk_pages.add(page)
            else:
                if current_chunk_text and len(current_chunk_text) >= self.MIN_CHUNK_SIZE:
                    chunks_with_pages.append({
                        "text": current_chunk_text.strip(),
                        "pages": sorted(list(current_chunk_pages))
                    })
                current_chunk_text = sentence
                current_chunk_pages = {page}
        
        if current_chunk_text and len(current_chunk_text) >= self.MIN_CHUNK_SIZE:
            chunks_with_pages.append({
                "text": current_chunk_text.strip(),
                "pages": sorted(list(current_chunk_pages))
            })
        
        overlapped_chunks = []
        for i in range(len(chunks_with_pages)):
            if i < len(chunks_with_pages) - 1:
                current_chunk = chunks_with_pages[i]["text"]
                current_pages = set(chunks_with_pages[i]["pages"])
                
                next_chunk = chunks_with_pages[i+1]["text"]
                next_pages = set(chunks_with_pages[i+1]["pages"])
                
                next_sentences = sent_tokenize(next_chunk)
                overlap_text = " ".join(next_sentences[:3]) if len(next_sentences) >= 3 else next_chunk[:self.CHUNK_OVERLAP]
                
                overlapped_text = current_chunk + " " + overlap_text
                combined_pages = sorted(list(current_pages.union(next_pages)))
                
                overlapped_chunks.append({
                    "text": overlapped_text,
                    "pages": combined_pages
                })
            else:
                overlapped_chunks.append(chunks_with_pages[i])
        
        final_chunks = []
        current_text = ""
        current_pages = set()
        
        for chunk in overlapped_chunks:
            if len(current_text) + len(chunk["text"]) <= self.MAX_CHUNK_SIZE * 1.2:
                current_text += " " + chunk["text"] if current_text else chunk["text"]
                current_pages.update(chunk["pages"])
            else:
                if current_text:
                    final_chunks.append({
                        "text": current_text.strip(),
                        "pages": sorted(list(current_pages))
                    })
                current_text = chunk["text"]
                current_pages = set(chunk["pages"])
        
        if current_text:
            final_chunks.append({
                "text": current_text.strip(),
                "pages": sorted(list(current_pages))
            })
        
        return final_chunks

    def chunk_text(self, text: str) -> List[str]:
        sentences = sent_tokenize(text)
        chunks = []
        current_chunk = ""
        
        for sentence in sentences:
            if len(current_chunk) + len(sentence) <= self.MAX_CHUNK_SIZE:
                current_chunk += " " + sentence if current_chunk else sentence
            else:
                if current_chunk and len(current_chunk) >= self.MIN_CHUNK_SIZE:
                    chunks.append(current_chunk.strip())
                current_chunk = sentence
        
        if current_chunk and len(current_chunk) >= self.MIN_CHUNK_SIZE:
            chunks.append(current_chunk.strip())
            
        overlapped_chunks = []
        for i in range(len(chunks)):
            if i < len(chunks) - 1:
                next_sentences = sent_tokenize(chunks[i+1])
                overlap_text = " ".join(next_sentences[:3]) if len(next_sentences) >= 3 else chunks[i+1][:self.CHUNK_OVERLAP]
                overlapped_chunks.append(chunks[i] + " " + overlap_text)
            else:
                overlapped_chunks.append(chunks[i])
        
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
        
        return final_chunks

    def get_embeddings(self, texts: List[str]) -> np.ndarray:
        return self.embedding_model.encode(texts)

    def calculate_relevance(self, question_embedding: np.ndarray, chunk_embeddings: np.ndarray) -> List[float]:
        similarity_scores = cosine_similarity([question_embedding], chunk_embeddings)[0]
        return similarity_scores

  
    def format_answer_with_openai(self, answer: str, question: str) -> str:
        if not self.use_openai_for_formatting or not self.openai_api_key:
            return answer
            
        try:
            prompt = f"""
            Berikut adalah pertanyaan dalam Bahasa Indonesia dan jawaban yang akan diformat ulang.
            
            Pertanyaan: {question}
            
            Jawaban mentah: {answer}
                        
            Tolong format ulang jawaban tersebut agar lebih mudah dibaca dan dipahami. 
            Pastikan untuk:
            1. Memperbaiki tata bahasa dan ejaan
            2. Menjaga konten asli tetap utuh
            3. Memperbaiki format rumus matematika jika ada
            4. Pastikan jawaban lengkap dan tidak dipotong
            5. Tambahkan struktur yang jelas jika diperlukan (paragraf, dll)
            
            Jawaban yang sudah diformat:
            """
            
            headers = {
                "Content-Type": "application/json",
                "Authorization": f"Bearer {self.openai_api_key}"
            }
            
            data = {
                "model": "gpt-3.5-turbo",
                "messages": [
                    {"role": "user", "content": prompt}
                ],
                "temperature": 0.3,
                "max_tokens": 1000
            }
            
            response = requests.post(
                "https://api.openai.com/v1/chat/completions",
                headers=headers,
                json=data
            )
            
            if response.status_code == 200:
                result = response.json()
                formatted_answer = result["choices"][0]["message"]["content"].strip()
                return formatted_answer
            else:
                return answer
                
        except Exception as e:
            return answer

    def find_page_references(self, answer: str, metadata: List[Dict]) -> List[int]:
        page_refs = []
        
        sentence_page_mapping = None
        for item in metadata:
            if item['type'] == 'sentence_page_mapping' and 'mapping' in item:
                sentence_page_mapping = item['mapping']
                break
                
        if not sentence_page_mapping:
            return page_refs
        
        answer_sentences = sent_tokenize(answer)
        
        for ans_sentence in answer_sentences:
            ans_sentence = ans_sentence.strip()
            if len(ans_sentence) < 10:
                continue
                
            ans_words = set(word.lower() for word in word_tokenize(ans_sentence) if len(word) > 3)
            if not ans_words:
                continue
                
            best_match_score = 0
            best_match_page = None
            
            for mapping in sentence_page_mapping:
                page_sentence = mapping['text'].strip()
                page_num = mapping['page']
                
                if len(page_sentence) < 10:
                    continue
                    
                if ans_sentence in page_sentence or page_sentence in ans_sentence:
                    page_refs.append(page_num)
                    break
                
                page_words = set(word.lower() for word in word_tokenize(page_sentence) if len(word) > 3)
                if not page_words:
                    continue
                    
                common_words = ans_words.intersection(page_words)
                if not common_words:
                    continue
                    
                total_words = len(ans_words.union(page_words))
                similarity = len(common_words) / total_words if total_words > 0 else 0
                
                if similarity > best_match_score and similarity >= 0.4:
                    best_match_score = similarity
                    best_match_page = page_num
            
            if best_match_page is not None:
                page_refs.append(best_match_page)
        
        if not page_refs:
            for item in metadata:
                if item['type'] == 'page_text_data' and 'data' in item:
                    page_text_data = item['data']
                    
                    for page_data in page_text_data:
                        page_text = page_data['text'].lower()
                        page_num = page_data['page']
                        
                        for ans_sentence in answer_sentences:
                            if len(ans_sentence) > 15:
                                ans_words = word_tokenize(ans_sentence.lower())
                                if len(ans_words) >= 3:
                                    for i in range(len(ans_words) - 2):
                                        phrase = " ".join(ans_words[i:i+3])
                                        if len(phrase) >= 10 and phrase in page_text:
                                            page_refs.append(page_num)
                                            break
        
        if not page_refs:
            answer_embedding = self.embedding_model.encode(answer)
            
            page_texts = []
            page_nums = []
            
            for item in metadata:
                if item['type'] == 'page_text_data' and 'data' in item:
                    for page_data in item['data']:
                        page_texts.append(page_data['text'])
                        page_nums.append(page_data['page'])
            
            if page_texts and page_nums:
                page_embeddings = self.embedding_model.encode(page_texts)
                similarities = cosine_similarity([answer_embedding], page_embeddings)[0]
                
                top_indices = similarities.argsort()[-3:][::-1]
                for idx in top_indices:
                    if similarities[idx] > 0.5:
                        page_refs.append(page_nums[idx])
        
        return sorted(list(set(page_refs)))

 
    def _enhance_answer_content(self, answer: str, context: str) -> str:
        sentences = sent_tokenize(context)
        
        answer_embedding = self.embedding_model.encode(answer)
        
        sentence_embeddings = self.embedding_model.encode(sentences)
        similarities = cosine_similarity([answer_embedding], sentence_embeddings)[0]
        
        relevant_sentences = []
        for idx in similarities.argsort()[-10:][::-1]:
            if sentences[idx] not in answer and len(relevant_sentences) < 5:
                relevant_sentences.append(sentences[idx])
        
        enhanced = answer
        for sent in relevant_sentences[:3]:
            if sent not in enhanced:
                enhanced += " " + sent
                
        return enhanced
    
   
    def answer_question(self, context: str, question: str, metadata: List[Dict] = None, top_k: int = 3) -> List[Dict[str, Any]]:
        bloom_level = self.detect_bloom_level(question)
        
        processed_context = self.preprocess_text(context)
        chunks = self.chunk_text(processed_context)
        
        question_embedding = self.embedding_model.encode(question)
        chunk_embeddings = self.get_embeddings(chunks)
        
        relevance_scores = self.calculate_relevance(question_embedding, chunk_embeddings)
        
        sorted_chunks_with_scores = sorted(zip(chunks, relevance_scores), key=lambda x: x[1], reverse=True)
        
                
        results = []
        for i, (chunk, relevance) in enumerate(sorted_chunks_with_scores):
            if i >= min(10, len(chunks)):
                break
                
            try:
                qa_result = self.qa_pipe(question=question, context=chunk)
                answer = qa_result['answer']
                
                if len(word_tokenize(answer)) < 20:
                    sentences = sent_tokenize(chunk)
                    for sentence in sentences:
                        if answer in sentence and sentence != answer:
                            answer = sentence
                            break
                
                
                page_refs = self.find_page_references(answer, metadata)
                
                qa_score = qa_result['score']
                
                length_bonus = min(0.15, 0.008 * len(word_tokenize(answer)))
                
                combined_score = (self.QA_WEIGHT * qa_score) + (self.RETRIEVAL_WEIGHT * relevance) + length_bonus
                
                combined_score = max(0.0, min(combined_score, 1.0))
                
                
                results.append({
                    'answer': answer,
                    'qa_score': qa_result['score'],
                    'retrieval_score': relevance,
                    'combined_score': combined_score,
                    'chunk': chunk,
                    'bloom_level': bloom_level,
                    'page_references': page_refs
                })
                
            except Exception as e:
                continue
            
        
        top_results = sorted(results, key=lambda x: x['combined_score'], reverse=True)[:top_k]
        
        return top_results

    def postprocess_answers(self, answers: List[Dict[str, Any]], question: str) -> List[Dict[str, Any]]:
        for i, answer_data in enumerate(answers):
            clean_answer = answer_data['answer']
            
            if self.use_openai_for_formatting:
                formatted_answer = self.format_answer_with_openai(clean_answer, question)
                answers[i]['answer'] = formatted_answer
            else:
                answers[i]['answer'] = clean_answer
            
        return answers

    def process_pdf_query(self, pdf_path: str, question: str, top_k: int = 3) -> List[Dict[str, Any]]:
        try:
            context, metadata = self.load_pdf(pdf_path)
            
            results = self.answer_question(context, question, metadata, top_k)
            
            final_results = self.postprocess_answers(results, question)
            
            return final_results
        except Exception as e:
            return []
        
    