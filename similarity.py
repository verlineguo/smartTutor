import re
from bs4 import BeautifulSoup
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
from difflib import SequenceMatcher
from sentence_transformers import SentenceTransformer, util
import numpy as np
from html import escape
import logging
import nltk
from nltk.corpus import stopwords
from nltk.tokenize import word_tokenize
from nltk.stem import WordNetLemmatizer
from Sastrawi.StopWordRemover.StopWordRemoverFactory import StopWordRemoverFactory
from Sastrawi.Stemmer.StemmerFactory import StemmerFactory

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)

model = SentenceTransformer('paraphrase-multilingual-MiniLM-L12-v2')

def enhanced_id_preprocessor(text):
    text = clean_html(text)    
    text = re.sub(r'\b(a|i|u|e|o)\b', '', text) 
    text = text.lower()  
    stop_factory = StopWordRemoverFactory()
    stem_factory = StemmerFactory()
    stopwords = stop_factory.get_stop_words()
    stemmer = stem_factory.create_stemmer()
    text = stemmer.stem(text)
    words = text.split()
    words = [w for w in words if w not in stopwords and len(w) > 2]
    return ' '.join(words)

# BERT Model untuk Word Embeddings

# Fungsi untuk membersihkan tag HTML
def clean_html(text):
    # Menggunakan BeautifulSoup untuk membersihkan HTML
    clean_text = BeautifulSoup(text, "html.parser").get_text()
    # Membersihkan whitespace berlebih
    clean_text = re.sub(r'\s+', ' ', clean_text).strip()
    return clean_text

# Fungsi untuk deteksi obfuscation strategies (strategi pengaburan)
def detect_obfuscation_strategies(text, original_text):
    strategies = []
    
    # Bersihkan teks
    clean_text = clean_html(text)
    clean_original = clean_html(original_text)
    
    # 1. Deteksi synonym replacement
    student_words = set(clean_text.lower().split())
    llm_words = set(clean_original.lower().split())
    
    # Jika banyak kata yang berbeda tapi panjang text hampir sama
    word_difference_ratio = len(student_words - llm_words) / len(llm_words) if llm_words else 0
    length_ratio = abs(len(clean_text) - len(clean_original)) / len(clean_original) if clean_original else 0
    
    if word_difference_ratio > 0.3 and length_ratio < 0.2:
        strategies.append("synonym_replacement")
    
    # 2. Deteksi word reordering
    if word_difference_ratio < 0.3 and length_ratio < 0.2:
        # Jika banyak kata yang sama tapi urutan berbeda
        strategies.append("word_reordering")
    
    # 3. Deteksi sentence restructuring
    student_sentences = re.split(r'(?<=[.!?])\s+', clean_text)
    llm_sentences = re.split(r'(?<=[.!?])\s+', clean_original)
    
    if abs(len(student_sentences) - len(llm_sentences)) > max(len(llm_sentences) * 0.3, 2):
        strategies.append("sentence_restructuring")
    
    # 4. Deteksi insertion of irrelevant content
    if len(clean_text) > len(clean_original) * 1.3:
        strategies.append("content_insertion")
    
    # 5. Deteksi summarization/paraphrasing
    if len(clean_text) < len(clean_original) * 0.7:
        strategies.append("summarization")
    
    # Default strategy jika tidak ada yang terdeteksi
    if not strategies:
        strategies.append("direct_copying")
    
    return strategies

# Fungsi untuk memilih metode deteksi yang optimal berdasarkan strategi obfuscation
def choose_detection_methods(obfuscation_strategies):
    # Set bobot default
    weights = {
        "cosine": 0.25,
        "jaccard": 0.25,
        "levenshtein": 0.25,
        "bert": 0.25,
        "ngram": 0.0  # Default tidak digunakan
    }
    
    # Sesuaikan bobot berdasarkan strategi obfuscation
    if "synonym_replacement" in obfuscation_strategies:
        # Untuk penggantian sinonim, BERT lebih efektif
        weights["bert"] = 0.45
        weights["cosine"] = 0.20
        weights["jaccard"] = 0.15
        weights["levenshtein"] = 0.10
        weights["ngram"] = 0.10
    
    elif "word_reordering" in obfuscation_strategies:
        # Untuk pengurutan kata, Jaccard bagus karena tidak mempedulikan urutan
        weights["jaccard"] = 0.40
        weights["bert"] = 0.30
        weights["cosine"] = 0.20
        weights["levenshtein"] = 0.05
        weights["ngram"] = 0.05
    
    elif "sentence_restructuring" in obfuscation_strategies:
        # Untuk restrukturisasi kalimat, kombinasikan BERT dengan N-gram
        weights["bert"] = 0.40
        weights["ngram"] = 0.25
        weights["cosine"] = 0.15
        weights["jaccard"] = 0.15
        weights["levenshtein"] = 0.05
    
    elif "content_insertion" in obfuscation_strategies:
        # Untuk insersi konten, prioritaskan n-gram dan cosine
        weights["ngram"] = 0.35
        weights["cosine"] = 0.30
        weights["bert"] = 0.20
        weights["jaccard"] = 0.10
        weights["levenshtein"] = 0.05
    
    elif "summarization" in obfuscation_strategies:
        # Untuk ringkasan/parafrase, BERT sangat penting
        weights["bert"] = 0.50
        weights["cosine"] = 0.20
        weights["ngram"] = 0.15
        weights["jaccard"] = 0.10
        weights["levenshtein"] = 0.05
    
    elif "direct_copying" in obfuscation_strategies:
        # Untuk penyalinan langsung, metode leksikal lebih akurat
        weights["levenshtein"] = 0.35
        weights["cosine"] = 0.30
        weights["jaccard"] = 0.20
        weights["bert"] = 0.10
        weights["ngram"] = 0.05
    
    return weights

# Fungsi untuk menentukan threshold adaptif berdasarkan strategi obfuscation
def set_adaptive_thresholds(obfuscation_strategies):
    # Set default thresholds
    thresholds = {
        "cosine": 0.65,
        "jaccard": 0.60,
        "levenshtein": 0.65,
        "bert": 0.75,
        "ngram": 0.55,
        "overall": 0.65  # Threshold keseluruhan
    }
    
    # Sesuaikan threshold berdasarkan strategi obfuscation
    if "synonym_replacement" in obfuscation_strategies:
        # Penggantian sinonim sulit terdeteksi dengan metode leksikal, turunkan threshold
        thresholds["cosine"] = 0.55
        thresholds["jaccard"] = 0.50
        thresholds["levenshtein"] = 0.55
        thresholds["bert"] = 0.70  # BERT tetap tinggi
        thresholds["overall"] = 0.60
    
    elif "word_reordering" in obfuscation_strategies:
        # Word reordering: Jaccard tetap sama, sesuaikan yang lain
        thresholds["cosine"] = 0.60
        thresholds["levenshtein"] = 0.60
        thresholds["bert"] = 0.70
        thresholds["overall"] = 0.62
    
    elif "sentence_restructuring" in obfuscation_strategies:
        # Restrukturisasi kalimat lebih sulit terdeteksi
        thresholds["cosine"] = 0.55
        thresholds["jaccard"] = 0.55
        thresholds["levenshtein"] = 0.55
        thresholds["bert"] = 0.65
        thresholds["ngram"] = 0.50
        thresholds["overall"] = 0.58
    
    elif "content_insertion" in obfuscation_strategies:
        # Insersi konten: turunkan threshold secara signifikan
        thresholds["cosine"] = 0.50
        thresholds["jaccard"] = 0.45
        thresholds["levenshtein"] = 0.45
        thresholds["bert"] = 0.60
        thresholds["ngram"] = 0.50
        thresholds["overall"] = 0.55
    
    elif "summarization" in obfuscation_strategies:
        # Ringkasan/parafrase sulit terdeteksi, gunakan threshold rendah
        thresholds["cosine"] = 0.50
        thresholds["jaccard"] = 0.45
        thresholds["levenshtein"] = 0.45
        thresholds["bert"] = 0.65
        thresholds["ngram"] = 0.45
        thresholds["overall"] = 0.55
    
    elif "direct_copying" in obfuscation_strategies:
        # Penyalinan langsung: tingkatkan threshold
        thresholds["cosine"] = 0.70
        thresholds["jaccard"] = 0.65
        thresholds["levenshtein"] = 0.70
        thresholds["bert"] = 0.80
        thresholds["ngram"] = 0.65
        thresholds["overall"] = 0.70
    
    return thresholds


# Fungsi untuk perhitungan similarity dengan berbagai metode
def calculate_similarities(text1, text2, weights, preprocessed=False):
    # Preprocess jika diperlukan
    if not preprocessed:
        processed_text1 = enhanced_id_preprocessor(text1)
        processed_text2 = enhanced_id_preprocessor(text2)
    else:
        processed_text1 = text1
        processed_text2 = text2
    
    # Hitung similarity dengan berbagai metode
    similarities = {}
    
    # 1. Cosine Similarity (TF-IDF)
    if weights["cosine"] > 0:
        try:
            vectorizer = TfidfVectorizer()
            tfidf_matrix = vectorizer.fit_transform([processed_text1, processed_text2])
            similarities["cosine"] = cosine_similarity(tfidf_matrix[0], tfidf_matrix[1])[0][0]
        except:
            similarities["cosine"] = 0
    else:
        similarities["cosine"] = 0
    
    # 2. Jaccard Similarity
    if weights["jaccard"] > 0:
        set1 = set(processed_text1.split())
        set2 = set(processed_text2.split())
        intersection = len(set1 & set2)
        union = len(set1 | set2)
        similarities["jaccard"] = intersection / union if union > 0 else 0
    else:
        similarities["jaccard"] = 0
    
    # 3. Levenshtein Similarity
    if weights["levenshtein"] > 0:
        similarities["levenshtein"] = SequenceMatcher(None, processed_text1, processed_text2).ratio()
    else:
        similarities["levenshtein"] = 0
    
    # 4. BERT Similarity
    if weights["bert"] > 0:
        try:
            emb1 = model.encode(processed_text1, convert_to_tensor=True)
            emb2 = model.encode(processed_text2, convert_to_tensor=True)
            similarities["bert"] = util.pytorch_cos_sim(emb1, emb2).item()
        except:
            similarities["bert"] = 0
    else:
        similarities["bert"] = 0
    
    # 5. N-gram Similarity
    if weights["ngram"] > 0:
        similarities["ngram"] = calculate_ngram_similarity(processed_text1, processed_text2, n=3)
    else:
        similarities["ngram"] = 0
    
    # Hitung weighted average
    weighted_avg = sum(similarities[method] * weights[method] for method in weights.keys())
    
    return similarities, weighted_avg

def calculate_ngram_similarity(text1, text2, n=3):
    # Fungsi untuk membuat n-gram dari teks
    def create_ngrams(text, n):
        words = text.split()
        if len(words) < n:
            return set()
        return set(' '.join(words[i:i+n]) for i in range(len(words)-n+1))
    
    ngrams1 = create_ngrams(text1, n)
    ngrams2 = create_ngrams(text2, n)
    
    intersection = len(ngrams1 & ngrams2)
    union = len(ngrams1 | ngrams2)
    
    if union == 0:
        return 0
    
    return intersection / union

# Fungsi utama untuk deteksi plagiarisme adaptif
def adaptive_plagiarism_detection(student_answer, llm_answer):
    # Bersihkan HTML
    student_clean = clean_html(student_answer)
    llm_clean = clean_html(llm_answer)
    logging.info(f"Student Answer: {student_clean}")
    logging.info(f"LLM Answer: {llm_clean}")
    
    # 1. Deteksi strategi obfuscation
    obfuscation_strategies = detect_obfuscation_strategies(student_clean, llm_clean)
    
    # 2. Pilih metode deteksi yang optimal
    method_weights = choose_detection_methods(obfuscation_strategies)
    
    # 3. Set threshold adaptif
    thresholds = set_adaptive_thresholds(obfuscation_strategies)
    
    # 4. Preprocess teks untuk language-specific analysis (bahasa Indonesia)
    processed_student = enhanced_id_preprocessor(student_clean)
    processed_llm = enhanced_id_preprocessor(llm_answer)
    
    overall_similarities, _ = calculate_similarities(
        processed_student,
        processed_llm,
        method_weights,
        preprocessed=True
    )
    
    # 5. Analisis similarity berbasis kalimat
    student_sentences = re.split(r'(?<=[.!?])\s+', student_clean)
    llm_sentences = re.split(r'(?<=[.!?])\s+', llm_clean)
    
    sentence_results = []
    overall_matched_chars = 0
    total_chars = len(student_clean)
    
    for i, student_sent in enumerate(student_sentences):
        if len(student_sent.split()) < 5:  # Abaikan kalimat pendek
            continue
        
        best_match = None
        best_score = 0
        best_similarities = None
        
        for llm_sent in llm_sentences:
            if len(llm_sent.split()) < 5:
                continue
                
            # Hitung similarity dengan berbagai metode
            similarities, weighted_score = calculate_similarities(
                student_sent, llm_sent, method_weights, preprocessed=False
            )
            
            if weighted_score > best_score:
                best_score = weighted_score
                best_match = llm_sent
                best_similarities = similarities
    
        is_plagiarized = int(best_score > thresholds["overall"])        
        if is_plagiarized:
            overall_matched_chars += len(student_sent)
            
        sentence_results.append({
            "student_text": student_sent,
            "best_match": best_match,
            "weighted_score": best_score,
            "individual_scores": best_similarities if best_similarities else {},
            "is_plagiarized": is_plagiarized
        })
        
    overall_percentage = (overall_matched_chars / total_chars) * 100 if total_chars > 0 else 0

    

    return {
        "overall_percentage": overall_percentage,
        "overall_similarities": overall_similarities,
        "detected_strategies": obfuscation_strategies,
        "method_weights": method_weights,
        "thresholds": thresholds,
        "sentence_results": sentence_results
    }

