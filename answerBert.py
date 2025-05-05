import numpy as np
import pandas as pd
import re
import torch
import fitz  # PyMuPDF for better PDF extraction
from transformers import AutoTokenizer, AutoModel, pipeline
from sentence_transformers import SentenceTransformer
from sklearn.metrics.pairwise import cosine_similarity
import nltk
from nltk.tokenize import sent_tokenize, word_tokenize
import logging
import openai
import warnings
import requests
from typing import List, Dict, Tuple, Any, Optional, Union

# Logging setup
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
warnings.filterwarnings('ignore')

# Download NLTK resources if needed
try:
    nltk.data.find('tokenizers/punkt')
except LookupError:
    nltk.download('punkt')

with open('hidden.txt') as file:
    openai.api_key = file.read()
    

class BertAnsweringSystem:
    def __init__(self, use_openai_for_formatting: bool = True, openai_api_key: Optional[str] = openai.api_key):

        logging.info("Initializing Indonesian QA system...")
        
        # Models selection - using multilingual models with better Indonesian support
        self.qa_model_name = "deepset/xlm-roberta-large-squad2"  # Upgraded to large model
        self.embedding_model_name = "sentence-transformers/paraphrase-multilingual-mpnet-base-v2"  # More accurate model
        
        logging.info(f"Loading QA model: {self.qa_model_name}")
        self.qa_pipe = pipeline('question-answering', model=self.qa_model_name, tokenizer=self.qa_model_name)
        
        logging.info(f"Loading embedding model: {self.embedding_model_name}")
        self.embedding_model = SentenceTransformer(self.embedding_model_name)
        
        # Model for answer validation and relevance calculation
        logging.info("Loading BERT model for answer validation")
        self.bert_model_name = "indolem/indobert-base-uncased"  # Better Indonesian model
        self.bert_tokenizer = AutoTokenizer.from_pretrained(self.bert_model_name)
        self.bert_model = AutoModel.from_pretrained(self.bert_model_name)
        
        # OpenAI integration for answer formatting
        self.use_openai_for_formatting = use_openai_for_formatting
        self.openai_api_key = openai_api_key
        
        if use_openai_for_formatting and not openai_api_key:
            logging.warning("OpenAI API key not provided. Formatting will use internal methods only.")
            self.use_openai_for_formatting = False
            
        # Constants for text processing and scoring
        self.CHUNK_OVERLAP = 200  # Increased overlap for better context
        self.MIN_CHUNK_SIZE = 300
        self.MAX_CHUNK_SIZE = 1000  # Larger chunks for more context
        self.QA_WEIGHT = 0.6
        self.RETRIEVAL_WEIGHT = 0.4
        
        # Mathematical notation extraction flag
        self.has_math_notation = False
        
        # Bloom taxonomy keywords for question classification
        self.bloom_keywords = {
            'remembering': ['apa', 'siapa', 'kapan', 'dimana', 'sebutkan', 'identifikasi', 'jelaskan', 'definisikan'],
            'understanding': ['jelaskan', 'uraikan', 'bandingkan', 'bedakan', 'interpretasikan', 'simpulkan'],
            'applying': ['terapkan', 'gunakan', 'demonstrasikan', 'ilustrasikan', 'hitung', 'selesaikan'],
            'analyzing': ['analisis', 'mengapa', 'bagaimana', 'klasifikasikan', 'bandingkan', 'kontras', 'sebab', 'akibat'],
        }
        
        logging.info("Indonesian QA system initialization complete!")

    def detect_bloom_level(self, question: str) -> str:
        """Detect Bloom's taxonomy level from the question."""
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
        
        # Return level with highest keyword count
        return max(detected_levels.items(), key=lambda x: x[1])[0]

    def load_pdf(self, pdf_path: str) -> Tuple[str, List[Dict]]:
        """Load and process PDF, maintaining page information for each sentence."""
        logging.info(f"Loading PDF from: {pdf_path}")
        full_text = ""
        metadata = []
        page_text_data = []  # Store text data with page numbers
        
        try:
            # Open PDF
            doc = fitz.open(pdf_path)
            
            # Check for math notation
            math_patterns = [r'\$.*?\$', r'\\\(.*?\\\)', r'\\\[.*?\\\]', r'\\begin\{equation\}.*?\\end\{equation\}']
            
            # Process each page
            for page_num, page in enumerate(doc):
                # Extract text with formatting
                page_text = page.get_text("text")
                
                # Check for math notation
                for pattern in math_patterns:
                    if re.search(pattern, page_text):
                        self.has_math_notation = True
                        logging.info(f"Mathematical notation detected on page {page_num + 1}")
                        metadata.append({
                            "type": "math_notation",
                            "page": page_num + 1,
                            "content": re.findall(pattern, page_text)
                        })
                
                # Clean text
                cleaned_page_text = self.preprocess_text(page_text)
                
                # Store the page text with its page number
                page_text_data.append({
                    "text": cleaned_page_text,
                    "page": page_num + 1
                })
                
                full_text += cleaned_page_text + " "
            
            # Final cleaning for the full text
            full_text = self.preprocess_text(full_text)
            
            # Add page text data to metadata
            metadata.append({
                "type": "page_text_data",
                "data": page_text_data
            })
            
            # Tokenize each page into sentences with page numbers
            sentence_page_mapping = []
            for page_data in page_text_data:
                page_sentences = sent_tokenize(page_data["text"])
                page_num = page_data["page"]
                
                for sentence in page_sentences:
                    if sentence.strip():  # Skip empty sentences
                        sentence_page_mapping.append({
                            "text": sentence.strip(),
                            "page": page_num
                        })
            
            # Add sentence-to-page mapping to metadata
            metadata.append({
                "type": "sentence_page_mapping",
                "mapping": sentence_page_mapping
            })
            
            logging.info(f"Successfully extracted {len(full_text)} characters from PDF")
            logging.info(f"Created page mapping for {len(sentence_page_mapping)} sentences")
            
            return full_text, metadata
            
        except Exception as e:
            logging.error(f"Failed to load PDF: {str(e)}")
            raise

    def preprocess_text(self, text: str) -> str:
        """Preprocess text to improve quality of results."""
        # Clean text
        text = re.sub(r'\n+', ' ', text)  # Remove newlines
        text = re.sub(r'\s+', ' ', text)  # Remove excess whitespace
        
        # Keep important mathematical symbols for formulas
        if self.has_math_notation:
            # Remove strange characters but keep math symbols
            text = re.sub(r'[^\w\s.,?!:;()\[\]{}"\'\-+=/\\*^_]', '', text)
        else:
            # Remove strange characters
            text = re.sub(r'[^\w\s.,?!:;()\[\]{}"\'\-]', '', text)
        
        # Normalize punctuation
        text = re.sub(r'\.+', '.', text)  # Replace multiple dots with single dot
        text = re.sub(r'\s+([.,;:!?])', r'\1', text)  # Remove space before punctuation
        
        return text.strip()

    def chunk_text_with_page_info(self, text: str, metadata: List[Dict]) -> List[Dict]:
        """Split text into chunks while preserving page information."""
        logging.info("Splitting text into chunks with page tracking...")
        
        # Get the sentence-to-page mapping
        sentence_page_mapping = None
        for item in metadata:
            if item['type'] == 'sentence_page_mapping' and 'mapping' in item:
                sentence_page_mapping = item['mapping']
                break
        
        if not sentence_page_mapping:
            logging.warning("No sentence-to-page mapping found. Using default chunking.")
            chunks = self.chunk_text(text)
            return [{"text": chunk, "pages": [1]} for chunk in chunks]  # Default to page 1
        
        # Extract all sentences with their page numbers
        all_sentences = [(item["text"], item["page"]) for item in sentence_page_mapping]
        
        chunks_with_pages = []
        current_chunk_text = ""
        current_chunk_pages = set()
        
        for sentence, page in all_sentences:
            # Add sentence to current chunk if within size limit
            if len(current_chunk_text) + len(sentence) <= self.MAX_CHUNK_SIZE:
                current_chunk_text += " " + sentence if current_chunk_text else sentence
                current_chunk_pages.add(page)
            else:
                # If chunk is large enough, save it and start new chunk
                if current_chunk_text and len(current_chunk_text) >= self.MIN_CHUNK_SIZE:
                    chunks_with_pages.append({
                        "text": current_chunk_text.strip(),
                        "pages": sorted(list(current_chunk_pages))
                    })
                current_chunk_text = sentence
                current_chunk_pages = {page}
        
        # Add final chunk if not empty
        if current_chunk_text and len(current_chunk_text) >= self.MIN_CHUNK_SIZE:
            chunks_with_pages.append({
                "text": current_chunk_text.strip(),
                "pages": sorted(list(current_chunk_pages))
            })
        
        # Add overlap between chunks for better context (while preserving page info)
        overlapped_chunks = []
        for i in range(len(chunks_with_pages)):
            if i < len(chunks_with_pages) - 1:
                # Get current chunk
                current_chunk = chunks_with_pages[i]["text"]
                current_pages = set(chunks_with_pages[i]["pages"])
                
                # Get some sentences from next chunk
                next_chunk = chunks_with_pages[i+1]["text"]
                next_pages = set(chunks_with_pages[i+1]["pages"])
                
                next_sentences = sent_tokenize(next_chunk)
                overlap_text = " ".join(next_sentences[:3]) if len(next_sentences) >= 3 else next_chunk[:self.CHUNK_OVERLAP]
                
                # Combine text and pages
                overlapped_text = current_chunk + " " + overlap_text
                combined_pages = sorted(list(current_pages.union(next_pages)))
                
                overlapped_chunks.append({
                    "text": overlapped_text,
                    "pages": combined_pages
                })
            else:
                overlapped_chunks.append(chunks_with_pages[i])
        
        # Ensure chunks aren't too small by combining when needed
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
        
        logging.info(f"Text split into {len(final_chunks)} chunks with page tracking")
        return final_chunks

    def chunk_text(self, text: str) -> List[str]:
        """
        Legacy method: Split text into chunks with overlap.
        Used as fallback when page information isn't available.
        """
        logging.info("Splitting text into chunks (legacy method)...")
        sentences = sent_tokenize(text)
        chunks = []
        current_chunk = ""
        
        for sentence in sentences:
            # Add sentence to current chunk if within size limit
            if len(current_chunk) + len(sentence) <= self.MAX_CHUNK_SIZE:
                current_chunk += " " + sentence if current_chunk else sentence
            else:
                # If chunk is large enough, save it and start new chunk
                if current_chunk and len(current_chunk) >= self.MIN_CHUNK_SIZE:
                    chunks.append(current_chunk.strip())
                current_chunk = sentence
        
        # Add final chunk if not empty
        if current_chunk and len(current_chunk) >= self.MIN_CHUNK_SIZE:
            chunks.append(current_chunk.strip())
            
        # Add overlap between chunks for better context
        overlapped_chunks = []
        for i in range(len(chunks)):
            if i < len(chunks) - 1:
                # Get some sentences from next chunk
                next_sentences = sent_tokenize(chunks[i+1])
                overlap_text = " ".join(next_sentences[:3]) if len(next_sentences) >= 3 else chunks[i+1][:self.CHUNK_OVERLAP]
                overlapped_chunks.append(chunks[i] + " " + overlap_text)
            else:
                overlapped_chunks.append(chunks[i])
        
        # Make sure chunks aren't too small by combining short chunks
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
        
        logging.info(f"Text split into {len(final_chunks)} chunks")
        return final_chunks

    def get_embeddings(self, texts: List[str]) -> np.ndarray:
        """Generate embeddings for list of texts using embedding model."""
        return self.embedding_model.encode(texts)

    def calculate_relevance(self, question_embedding: np.ndarray, chunk_embeddings: np.ndarray) -> List[float]:
        """Calculate relevance scores between question and each chunk based on cosine similarity."""
        similarity_scores = cosine_similarity([question_embedding], chunk_embeddings)[0]
        return similarity_scores

    def validate_answer(self, question: str, answer: str, context: str) -> Tuple[bool, float]:
        """Validate answer using BERT to measure consistency."""
        try:
            # Encode question + context and question + answer
            inputs_context = self.bert_tokenizer(question, context, return_tensors="pt", truncation=True, max_length=512)
            inputs_answer = self.bert_tokenizer(question, answer, return_tensors="pt", truncation=True, max_length=512)
            
            # Get embeddings
            with torch.no_grad():
                outputs_context = self.bert_model(**inputs_context)
                outputs_answer = self.bert_model(**inputs_answer)
                
            # Get CLS token embeddings
            context_embedding = outputs_context.last_hidden_state[:, 0, :].numpy()
            answer_embedding = outputs_answer.last_hidden_state[:, 0, :].numpy()
            
            # Calculate cosine similarity
            similarity = cosine_similarity(context_embedding, answer_embedding)[0][0]
            
            # Answer is valid if similarity above threshold
            valid = similarity > 0.7  # Increased threshold for stricter validation
            return valid, similarity
        except Exception as e:
            logging.warning(f"Error validating answer: {str(e)}")
            return True, 0.7  # Default fallback

    def format_answer_with_openai(self, answer: str, question: str, page_refs: List[int] = None) -> str:
        """Format the answer using OpenAI API for better readability."""
        if not self.use_openai_for_formatting or not self.openai_api_key:
            return answer
            
        try:
            # Page reference text
            page_ref_text = ""
            if page_refs and len(page_refs) > 0:
                unique_pages = sorted(list(set(page_refs)))
                page_ref_text = f"[Referensi: Halaman {', '.join(map(str, unique_pages))}]"
            
            # Prepare prompt for OpenAI
            prompt = f"""
            Berikut adalah pertanyaan dalam Bahasa Indonesia dan jawaban yang akan diformat ulang.
            
            Pertanyaan: {question}
            
            Jawaban mentah: {answer}
            
            Referensi halaman: {page_ref_text}
            
            Tolong format ulang jawaban tersebut agar lebih mudah dibaca dan dipahami. 
            Pastikan untuk:
            1. Memperbaiki tata bahasa dan ejaan
            2. Menjaga konten asli tetap utuh
            3. Memperbaiki format rumus matematika jika ada
            4. Pastikan jawaban lengkap dan tidak dipotong
            5. Tambahkan struktur yang jelas jika diperlukan (paragraf, dll)
            6. PENTING: Sertakan referensi halaman di akhir jawaban dalam format [Referensi: Halaman X, Y, Z]
            
            Jawaban yang sudah diformat:
            """
            
            # Call OpenAI API
            headers = {
                "Content-Type": "application/json",
                "Authorization": f"Bearer {self.openai_api_key}"
            }
            
            data = {
                "model": "gpt-3.5-turbo",
                "messages": [
                    {"role": "user", "content": prompt}
                ],
                "temperature": 0.3,  # Low temperature for more consistent formatting
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
                # Make sure page reference is included
                if page_ref_text and page_ref_text not in formatted_answer:
                    formatted_answer += f"\n\n{page_ref_text}"
                return formatted_answer
            else:
                logging.warning(f"OpenAI API error: {response.status_code} - {response.text}")
                # Add page reference if not formatted by OpenAI
                if page_ref_text and page_ref_text not in answer:
                    answer += f"\n\n{page_ref_text}"
                return answer
                
        except Exception as e:
            logging.error(f"Error formatting with OpenAI: {str(e)}")
            # Add page reference if formatting fails
            if page_refs and len(page_refs) > 0:
                unique_pages = sorted(list(set(page_refs)))
                page_ref_text = f"[Referensi: Halaman {', '.join(map(str, unique_pages))}]"
                if page_ref_text not in answer:
                    answer += f"\n\n{page_ref_text}"
            return answer

    def find_page_references(self, answer: str, metadata: List[Dict]) -> List[int]:
        """Find page references for the given answer text using improved matching algorithm."""
        page_refs = []
        
        # First try to get sentence-to-page mapping
        sentence_page_mapping = None
        for item in metadata:
            if item['type'] == 'sentence_page_mapping' and 'mapping' in item:
                sentence_page_mapping = item['mapping']
                break
                
        if not sentence_page_mapping:
            logging.warning("No sentence-to-page mapping found in metadata.")
            return page_refs
        
        # Break answer into sentences for more precise matching
        answer_sentences = sent_tokenize(answer)
        
        # For each answer sentence, try to find matching or similar sentences in our mapping
        for ans_sentence in answer_sentences:
            ans_sentence = ans_sentence.strip()
            if len(ans_sentence) < 10:  # Skip very short sentences
                continue
                
            # Prepare answer words for comparison (normalized)
            ans_words = set(word.lower() for word in word_tokenize(ans_sentence) if len(word) > 3)
            if not ans_words:  # Skip if no substantial words
                continue
                
            best_match_score = 0
            best_match_page = None
            
            # For each sentence in our mapping, calculate similarity score
            for mapping in sentence_page_mapping:
                page_sentence = mapping['text'].strip()
                page_num = mapping['page']
                
                # Skip very short sentences from mapping
                if len(page_sentence) < 10:
                    continue
                    
                # Check for exact matches first (highly reliable)
                if ans_sentence in page_sentence or page_sentence in ans_sentence:
                    page_refs.append(page_num)
                    break
                
                # If no exact match, calculate word overlap ratio
                page_words = set(word.lower() for word in word_tokenize(page_sentence) if len(word) > 3)
                if not page_words:
                    continue
                    
                # Calculate Jaccard similarity
                common_words = ans_words.intersection(page_words)
                if not common_words:
                    continue
                    
                total_words = len(ans_words.union(page_words))
                similarity = len(common_words) / total_words if total_words > 0 else 0
                
                # Keep track of best match
                if similarity > best_match_score and similarity >= 0.4:  # Threshold for similarity
                    best_match_score = similarity
                    best_match_page = page_num
            
            # If we found a good match above threshold
            if best_match_page is not None:
                page_refs.append(best_match_page)
        
        # If we still have no references, try fallback method using chunk page info
        if not page_refs:
            logging.info("Using fallback method for page references")
            
            # Try to find matching pages from chunk metadata
            for item in metadata:
                if item['type'] == 'page_text_data' and 'data' in item:
                    page_text_data = item['data']
                    
                    for page_data in page_text_data:
                        page_text = page_data['text'].lower()
                        page_num = page_data['page']
                        
                        # Check if significant parts of the answer appear on this page
                        for ans_sentence in answer_sentences:
                            if len(ans_sentence) > 15:  # Consider only substantial sentences
                                # Look for key phrases (3+ word sequences)
                                ans_words = word_tokenize(ans_sentence.lower())
                                if len(ans_words) >= 3:
                                    for i in range(len(ans_words) - 2):
                                        phrase = " ".join(ans_words[i:i+3])
                                        if len(phrase) >= 10 and phrase in page_text:
                                            page_refs.append(page_num)
                                            break
        
        # If still no references, use general content similarity as last resort
        if not page_refs:
            logging.info("Using content similarity for page references")
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
                
                # Get pages with highest similarity scores
                top_indices = similarities.argsort()[-3:][::-1]  # Top 3 similar pages
                for idx in top_indices:
                    if similarities[idx] > 0.5:  # Minimum similarity threshold
                        page_refs.append(page_nums[idx])
        
        # Return unique, sorted page numbers
        return sorted(list(set(page_refs)))

    def generate_direct_answer(self, question: str, relevant_chunks: List[Dict], bloom_level: str, metadata: List[Dict]) -> Tuple[str, List[int]]:
        """Generate a comprehensive answer from relevant chunks with page references."""
        try:
            # Extract text from chunks
            chunk_texts = [chunk["text"] for chunk in relevant_chunks]
            
            # Combine relevant chunks with size limit
            combined_context = " ".join(chunk_texts)
            if len(combined_context) > 2000:  # Use first 2000 chars for initial pass
                combined_context = combined_context[:2000]
            
            # Use QA pipeline to get initial answer
            qa_result = self.qa_pipe(question=question, context=combined_context)
            initial_answer = qa_result['answer']
            
            # Get additional answers from different chunks to enrich
            additional_answers = []
            for chunk_dict in relevant_chunks[:3]:  # Take 3 most relevant chunks
                try:
                    chunk_text = chunk_dict["text"]
                    result = self.qa_pipe(question=question, context=chunk_text)
                    if result['answer'] not in [initial_answer] + additional_answers:
                        additional_answers.append(result['answer'])
                except:
                    continue
            
            # Combine answers in a coherent way based on bloom level
            if bloom_level in ['remembering', 'understanding']:
                # For basic recall/understanding, keep it simple and direct
                if additional_answers:
                    combined_answer = initial_answer + " " + " ".join(additional_answers)
                else:
                    combined_answer = initial_answer
                    
            elif bloom_level == 'applying':
                # For application questions, include examples if available
                combined_answer = initial_answer
                if additional_answers:
                    combined_answer += " " + additional_answers[0]
                    if len(additional_answers) > 1:
                        combined_answer += " " + additional_answers[1]
                        
            elif bloom_level == 'analyzing':
                # For analysis questions, structure the response by aspects
                parts = [initial_answer] + additional_answers[:2]
                combined_answer = " ".join(parts)
                
            else:
                # For higher-level questions, incorporate multiple perspectives
                combined_answer = initial_answer
                for ans in additional_answers[:2]:
                    combined_answer += " " + ans
            
            # Ensure answer has sufficient substance
            if len(word_tokenize(combined_answer)) < 100:
                combined_answer = self._enhance_answer_content(combined_answer, combined_context)
            
            # Clean up the answer
            combined_answer = self._clean_answer(combined_answer)
            
            # Collect page references from chunks that contributed to the answer
            chunk_pages = []
            for chunk in relevant_chunks:
                if "pages" in chunk:
                    chunk_pages.extend(chunk["pages"])
            
            # Find additional page references for the answer content
            content_page_refs = self.find_page_references(combined_answer, metadata)
            
            # Combine page references from chunks and content matching
            all_page_refs = sorted(list(set(chunk_pages + content_page_refs)))
            
            # Format with OpenAI if enabled (including page references)
            if self.use_openai_for_formatting:
                combined_answer = self.format_answer_with_openai(combined_answer, question, all_page_refs)
            else:
                # Add page references manually if OpenAI formatting is not used
                if all_page_refs:
                    page_ref_text = f"[Referensi: Halaman {', '.join(map(str, all_page_refs))}]"
                    if page_ref_text not in combined_answer:
                        combined_answer += f"\n\n{page_ref_text}"
                
            return combined_answer, all_page_refs
            
        except Exception as e:
            logging.warning(f"Error generating answer: {str(e)}")
            # Fallback to simpler answer
            try:
                chunk_texts = [chunk["text"] for chunk in relevant_chunks[:2]]
                combined_context = " ".join(chunk_texts)
                qa_result = self.qa_pipe(question=question, context=combined_context)
                answer = self._clean_answer(qa_result['answer'])
                
                # Get page references from chunks
                chunk_pages = []
                for chunk in relevant_chunks[:2]:
                    if "pages" in chunk:
                        chunk_pages.extend(chunk["pages"])
                
                # Add page references manually
                if chunk_pages:
                    unique_pages = sorted(list(set(chunk_pages)))
                    page_ref_text = f"[Referensi: Halaman {', '.join(map(str, unique_pages))}]"
                    if page_ref_text not in answer:
                        answer += f"\n\n{page_ref_text}"
                        
                return answer, unique_pages
            except:
                logging.error("Critical failure in answer generation. Using empty fallback.")
                return "Maaf, tidak dapat menemukan jawaban yang tepat.", []  
    
    
    def _enhance_answer_content(self, answer: str, context: str) -> str:
        """Add more content to short answers by finding relevant sentences."""
        # Extract sentences from context
        sentences = sent_tokenize(context)
        
        # Create embedding for answer
        answer_embedding = self.embedding_model.encode(answer)
        
        # Find sentences similar to answer
        sentence_embeddings = self.embedding_model.encode(sentences)
        similarities = cosine_similarity([answer_embedding], sentence_embeddings)[0]
        
        # Get 3-5 most relevant sentences not already in answer
        relevant_sentences = []
        for idx in similarities.argsort()[-10:][::-1]:
            if sentences[idx] not in answer and len(relevant_sentences) < 5:
                relevant_sentences.append(sentences[idx])
        
        # Add relevant content
        enhanced = answer
        for sent in relevant_sentences[:3]:
            if sent not in enhanced:
                enhanced += " " + sent
                
        return enhanced
    
    def _clean_answer(self, answer: str) -> str:
        """Clean and normalize the answer text."""
        # Fix capitalization and punctuation
        if answer and len(answer) > 0:
            answer = answer[0].upper() + answer[1:] if answer else answer
            
        # Ensure proper sentence ending
        if not answer.endswith(('.', '!', '?')):
            answer += '.'
            
        # Preserve mathematical notation if present
        if self.has_math_notation:
            # Special handling for math expressions
            # Preserve common LaTeX-like notations
            pass
        else:
            # Remove unwanted characters
            answer = re.sub(r'[^\w\s.,?!:;()\[\]{}"\'-]', '', answer)
        
        # Fix spacing after punctuation
        answer = re.sub(r'([.,;:!?])([A-Za-z])', r'\1 \2', answer)
        
        # Fix common Indonesian errors
        answer = re.sub(r'\bdi bawah ini\b', 'berikut', answer)
        answer = re.sub(r'\badalah merupakan\b', 'adalah', answer)
        
        return answer

    def answer_question(self, context: str, question: str, metadata: List[Dict] = None, top_k: int = 3) -> List[Dict[str, Any]]:
        logging.info(f"Answering question: '{question}'")
        
        # Check if question relates to formulas
        question_lower = question.lower()
        is_formula_related = any(term in question_lower for term in ['rumus', 'formula', 'persamaan', 'matematika'])
        
        # Detect Bloom's taxonomy level
        bloom_level = self.detect_bloom_level(question)
        logging.info(f"Detected Bloom's taxonomy level: {bloom_level}")
        
        # Check if we need to process special content
        if is_formula_related and metadata:
            logging.info("Question is formula-related, processing formula content")
            # Flag for special handling of formulas
            self.has_math_notation = True
            
            # Extract and add formula content
            formula_texts = []
            for item in metadata:
                if item['type'] == 'math_notation' and 'content' in item:
                    if isinstance(item['content'], list):
                        formula_texts.extend(item['content'])
                    else:
                        formula_texts.append(item['content'])
            
            # Add formula text to context
            if formula_texts:
                context += " " + " ".join(formula_texts)
        
        # Preprocess context and divide into chunks
        processed_context = self.preprocess_text(context)
        chunks = self.chunk_text(processed_context)
        
        # Get embeddings
        question_embedding = self.embedding_model.encode(question)
        chunk_embeddings = self.get_embeddings(chunks)
        
        # Calculate relevance scores
        relevance_scores = self.calculate_relevance(question_embedding, chunk_embeddings)
        
        # Sort chunks by relevance
        sorted_chunks_with_scores = sorted(zip(chunks, relevance_scores), key=lambda x: x[1], reverse=True)
        
        # Take most relevant chunks for answer generation
        top_relevant_chunks = [chunk for chunk, _ in sorted_chunks_with_scores[:5]]
        
        # Generate direct answer from relevant chunks (with page references)
        direct_answer, direct_page_refs = self.generate_direct_answer(question, top_relevant_chunks, bloom_level, metadata)
        
        # Process regular answers from each chunk to get top-k
        results = []
        for i, (chunk, relevance) in enumerate(sorted_chunks_with_scores):
            if i >= min(10, len(chunks)):  # Limit to top 10 chunks
                break
                
            logging.info(f"Evaluating chunk {i+1}/{min(10, len(chunks))} (relevance: {relevance:.4f})")
            
            # Use QA pipeline to get answer from chunk
            try:
                qa_result = self.qa_pipe(question=question, context=chunk)
                answer = qa_result['answer']
                
                # For chunk answers, add context if too short
                if len(word_tokenize(answer)) < 20:
                    # Find sentence from chunk containing answer
                    sentences = sent_tokenize(chunk)
                    for sentence in sentences:
                        if answer in sentence and sentence != answer:
                            answer = sentence
                            break
                
                # Validate answer using BERT
                is_valid, validation_score = self.validate_answer(question, answer, chunk)
                
                # Find page references for this answer
                page_refs = self.find_page_references(answer, metadata)
                
                # Calculate combined score
                qa_score = qa_result['score']
                
                # Give higher weight to longer answers
                length_bonus = min(0.15, 0.008 * len(word_tokenize(answer)))
                
                combined_score = (self.QA_WEIGHT * qa_score) + (self.RETRIEVAL_WEIGHT * relevance) + length_bonus
                
                # Adjust based on validation
                if is_valid:
                    combined_score *= (1.0 + 0.15 * validation_score)
                else:
                    combined_score *= 0.65
                
                # Add result to list
                results.append({
                    'answer': answer,
                    'qa_score': qa_result['score'],
                    'retrieval_score': relevance,
                    'combined_score': combined_score,
                    'chunk': chunk,
                    'is_valid': is_valid,
                    'bloom_level': bloom_level,
                    'page_references': page_refs
                })
                
            except Exception as e:
                logging.warning(f"Error on chunk {i+1}: {str(e)}")
                continue
            
        try:
            qa_result = self.qa_pipe(question=question, context=" ".join(top_relevant_chunks))
            direct_qa_score = qa_result['score']
        except Exception as e:
            logging.warning(f"Error calculating QA score for direct answer: {str(e)}")
            direct_qa_score = 0.0  # Default to 0 if QA pipeline fails

                
        # Add direct answer as top result
        direct_result = {
            'answer': direct_answer,
            'qa_score': direct_qa_score,  # High value for direct answer
            'retrieval_score': max(relevance_scores[:3]) if relevance_scores.size > 0 else 0.5,
            'combined_score': (self.QA_WEIGHT * direct_qa_score) + (self.RETRIEVAL_WEIGHT * max(relevance_scores[:3])),
            'chunk': "combined_relevant_chunks",
            'is_valid': True,
            'bloom_level': bloom_level,
            'is_direct': True,
            'page_references': direct_page_refs
            
        }
        
        # Combine direct result with regular results and select top-k
        all_results = [direct_result] + results
        top_results = sorted(all_results, key=lambda x: x['combined_score'], reverse=True)[:top_k]
        
        logging.info(f"Successfully generated {len(top_results)} best answers")
        return top_results

    def postprocess_answers(self, answers: List[Dict[str, Any]], question: str) -> List[Dict[str, Any]]:

        for i, answer_data in enumerate(answers):
            # Apply cleaning to each answer
            clean_answer = self._clean_answer(answer_data['answer'])
            
            # Apply OpenAI formatting if enabled
            if self.use_openai_for_formatting:
                formatted_answer = self.format_answer_with_openai(clean_answer, question)
                answers[i]['answer'] = formatted_answer
            else:
                answers[i]['answer'] = clean_answer
            
        return answers

    def process_pdf_query(self, pdf_path: str, question: str, top_k: int = 3) -> List[Dict[str, Any]]:

        try:
            # Load and process PDF
            context, metadata = self.load_pdf(pdf_path)
            
            # Get answers
            results = self.answer_question(context, question, metadata, top_k)
            
            # Post-process answers
            final_results = self.postprocess_answers(results, question)
            
            return final_results
        except Exception as e:
            logging.error(f"Error processing PDF query: {str(e)}")
            return []

