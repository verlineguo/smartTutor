
import re
from bs4 import BeautifulSoup
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
from difflib import SequenceMatcher
from sentence_transformers import SentenceTransformer, util
import logging
from Sastrawi.StopWordRemover.StopWordRemoverFactory import StopWordRemoverFactory
from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
import functools

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)

# Load model once at module level
model = SentenceTransformer('paraphrase-multilingual-mpnet-base-v2')

# Initialize stemmer and stopwords once
stop_factory = StopWordRemoverFactory()
stem_factory = StemmerFactory()
stopwords = stop_factory.get_stop_words()
stemmer = stem_factory.create_stemmer()

# Cache function results with lru_cache
@functools.lru_cache(maxsize=128)
def enhanced_id_preprocessor(text):
    text = clean_html(text)    
    text = re.sub(r'\b(a|i|u|e|o)\b', '', text) 
    text = text.lower()  
    text = stemmer.stem(text)
    words = text.split()
    words = [w for w in words if w not in stopwords and len(w) > 2]
    return ' '.join(words)

# Compiled regex patterns for reuse
html_whitespace_pattern = re.compile(r'\s+')
sentence_split_pattern = re.compile(r'(?<=[.!?])\s+')

@functools.lru_cache(maxsize=128)
def clean_html(text):
    # Menggunakan BeautifulSoup untuk membersihkan HTML
    clean_text = BeautifulSoup(text, "html.parser").get_text()
    # Membersihkan whitespace berlebih
    clean_text = html_whitespace_pattern.sub(' ', clean_text).strip()
    return clean_text

def detect_obfuscation_strategies(text, original_text):
    strategies = []
    
    # Bersihkan teks
    clean_text = clean_html(text)
    clean_original = clean_html(original_text)
    
    # 1. Deteksi synonym replacement
    student_words = set(clean_text.lower().split())
    llm_words = set(clean_original.lower().split())
    
    # Calculate these values once
    student_word_count = len(student_words)
    llm_word_count = len(llm_words)
    student_text_len = len(clean_text)
    original_text_len = len(clean_original)
    
    # Jika banyak kata yang berbeda tapi panjang text hampir sama
    word_difference_ratio = len(student_words - llm_words) / llm_word_count if llm_word_count else 0
    length_ratio = abs(student_text_len - original_text_len) / original_text_len if original_text_len else 0
    
    if word_difference_ratio > 0.3 and length_ratio < 0.2:
        strategies.append("synonym_replacement")
    
    # 2. Deteksi word reordering
    elif word_difference_ratio < 0.3 and length_ratio < 0.2:
        # Jika banyak kata yang sama tapi urutan berbeda
        strategies.append("word_reordering")
    
    # 3. Deteksi sentence restructuring
    student_sentences = sentence_split_pattern.split(clean_text)
    llm_sentences = sentence_split_pattern.split(clean_original)
    
    sentence_count_diff = abs(len(student_sentences) - len(llm_sentences))
    if sentence_count_diff > max(len(llm_sentences) * 0.3, 2):
        strategies.append("sentence_restructuring")
    
    # 4. Deteksi insertion of irrelevant content
    if student_text_len > original_text_len * 1.3:
        strategies.append("content_insertion")
    
    # 5. Deteksi summarization/paraphrasing
    elif student_text_len < original_text_len * 0.7:
        strategies.append("summarization")
    
    # Default strategy jika tidak ada yang terdeteksi
    if not strategies:
        strategies.append("direct_copying")
    
    return strategies

def choose_detection_methods(obfuscation_strategies):
    # Initialize with default weights
    weights = {
        "cosine": 0.25,
        "jaccard": 0.25,
        "levenshtein": 0.25,
        "bert": 0.25,
        "ngram": 0.0  # Default tidak digunakan
    }
    
    primary_strategy = obfuscation_strategies[0] if obfuscation_strategies else "direct_copying"
    
    # Use a dictionary to map strategies to weights
    strategy_weights = {
        "synonym_replacement": {
            "bert": 0.45, "cosine": 0.20, "jaccard": 0.15, 
            "levenshtein": 0.10, "ngram": 0.10
        },
        "word_reordering": {
            "jaccard": 0.40, "bert": 0.30, "cosine": 0.20, 
            "levenshtein": 0.05, "ngram": 0.05
        },
        "sentence_restructuring": {
            "bert": 0.40, "ngram": 0.25, "cosine": 0.15, 
            "jaccard": 0.15, "levenshtein": 0.05
        },
        "content_insertion": {
            "ngram": 0.35, "cosine": 0.30, "bert": 0.20, 
            "jaccard": 0.10, "levenshtein": 0.05
        },
        "summarization": {
            "bert": 0.50, "cosine": 0.20, "ngram": 0.15, 
            "jaccard": 0.10, "levenshtein": 0.05
        },
        "direct_copying": {
            "levenshtein": 0.35, "cosine": 0.30, "jaccard": 0.20, 
            "bert": 0.10, "ngram": 0.05
        }
    }
    
    # Update weights if strategy is in our mapping
    if primary_strategy in strategy_weights:
        weights.update(strategy_weights[primary_strategy])
    
    return weights

def set_adaptive_thresholds(obfuscation_strategies):
    # Initialize with default thresholds
    thresholds = {
        "cosine": 0.65,
        "jaccard": 0.60,
        "levenshtein": 0.65,
        "bert": 0.75,
        "ngram": 0.55,
        "overall": 0.65  # Threshold keseluruhan
    }
    
    primary_strategy = obfuscation_strategies[0] if obfuscation_strategies else "direct_copying"
    
    # Use a dictionary to map strategies to thresholds
    strategy_thresholds = {
        "synonym_replacement": {
            "cosine": 0.55, "jaccard": 0.50, "levenshtein": 0.55, 
            "bert": 0.70, "overall": 0.60
        },
        "word_reordering": {
            "cosine": 0.60, "levenshtein": 0.60, "bert": 0.70, 
            "overall": 0.62
        },
        "sentence_restructuring": {
            "cosine": 0.55, "jaccard": 0.55, "levenshtein": 0.55, 
            "bert": 0.65, "ngram": 0.50, "overall": 0.58
        },
        "content_insertion": {
            "cosine": 0.50, "jaccard": 0.45, "levenshtein": 0.45, 
            "bert": 0.60, "ngram": 0.50, "overall": 0.55
        },
        "summarization": {
            "cosine": 0.50, "jaccard": 0.45, "levenshtein": 0.45, 
            "bert": 0.65, "ngram": 0.45, "overall": 0.55
        },
        "direct_copying": {
            "cosine": 0.70, "jaccard": 0.65, "levenshtein": 0.70, 
            "bert": 0.80, "ngram": 0.65, "overall": 0.70
        }
    }
    
    # Update thresholds if strategy is in our mapping
    if primary_strategy in strategy_thresholds:
        thresholds.update(strategy_thresholds[primary_strategy])
    
    return thresholds

# Precomputed empty set for ngram optimization
EMPTY_SET = set()

def calculate_similarities(text1, text2, weights, preprocessed=False):
    # Skip computation if text is empty or weights are zero
    if not text1 or not text2:
        return {}, 0
        
    # Preprocess jika diperlukan
    if not preprocessed:
        processed_text1 = enhanced_id_preprocessor(text1)
        processed_text2 = enhanced_id_preprocessor(text2)
    else:
        processed_text1 = text1
        processed_text2 = text2
    
    # Initialize only the needed similarity methods based on weights
    similarities = {}
    weighted_sum = 0
    
    # 1. Cosine Similarity (TF-IDF)
    if weights["cosine"] > 0:
        try:
            vectorizer = TfidfVectorizer()
            tfidf_matrix = vectorizer.fit_transform([processed_text1, processed_text2])
            cos_sim = cosine_similarity(tfidf_matrix[0], tfidf_matrix[1])[0][0]
            similarities["cosine"] = cos_sim
            weighted_sum += cos_sim * weights["cosine"]
        except:
            similarities["cosine"] = 0
    
    # 2. Jaccard Similarity - only compute if needed
    if weights["jaccard"] > 0:
        set1 = set(processed_text1.split())
        set2 = set(processed_text2.split())
        
        if set1 and set2:  # Only compute if both sets have elements
            intersection = len(set1 & set2)
            union = len(set1 | set2)
            jaccard = intersection / union if union > 0 else 0
            similarities["jaccard"] = jaccard
            weighted_sum += jaccard * weights["jaccard"]
        else:
            similarities["jaccard"] = 0
    
    # 3. Levenshtein Similarity - only compute if needed
    if weights["levenshtein"] > 0:
        lev_sim = SequenceMatcher(None, processed_text1, processed_text2).ratio()
        similarities["levenshtein"] = lev_sim
        weighted_sum += lev_sim * weights["levenshtein"]
    
    # 4. BERT Similarity - only compute if needed
    if weights["bert"] > 0:
        try:
            emb1 = model.encode(processed_text1, convert_to_tensor=True)
            emb2 = model.encode(processed_text2, convert_to_tensor=True)
            bert_sim = util.pytorch_cos_sim(emb1, emb2).item()
            similarities["bert"] = bert_sim
            weighted_sum += bert_sim * weights["bert"]
        except:
            similarities["bert"] = 0
    
    # 5. N-gram Similarity - only compute if needed
    if weights["ngram"] > 0:
        ngram_sim = calculate_ngram_similarity(processed_text1, processed_text2, n=3)
        similarities["ngram"] = ngram_sim
        weighted_sum += ngram_sim * weights["ngram"]
    
    return similarities, weighted_sum

def calculate_ngram_similarity(text1, text2, n=3):
    # Quick return for empty texts
    words1 = text1.split()
    words2 = text2.split()
    
    if len(words1) < n or len(words2) < n:
        return 0
    
    # Optimize ngram creation with one-time allocation
    ngrams1 = set(' '.join(words1[i:i+n]) for i in range(len(words1)-n+1))
    ngrams2 = set(' '.join(words2[i:i+n]) for i in range(len(words2)-n+1))
    
    intersection = len(ngrams1 & ngrams2)
    union = len(ngrams1 | ngrams2)
    
    return intersection / union if union > 0 else 0

def adaptive_plagiarism_detection(student_answer, llm_answer):
    # Early return if inputs are empty
    if not student_answer or not llm_answer:
        return {
            "overall_percentage": 0,
            "overall_similarities": {},
            "detected_strategies": ["direct_copying"],
            "method_weights": choose_detection_methods(["direct_copying"]),
            "thresholds": set_adaptive_thresholds(["direct_copying"]),
            "sentence_results": []
        }
    
    # Bersihkan HTML - do this once
    student_clean = clean_html(student_answer)
    llm_clean = clean_html(llm_answer)
    
    # Logging only if necessary
    if logging.getLogger().level <= logging.INFO:
        logging.info(f"Student Answer: {student_clean[:100]}...")
        logging.info(f"LLM Answer: {llm_clean[:100]}...")
    
    # 1. Deteksi strategi obfuscation
    obfuscation_strategies = detect_obfuscation_strategies(student_clean, llm_clean)
    
    # 2. Pilih metode deteksi yang optimal
    method_weights = choose_detection_methods(obfuscation_strategies)
    
    # 3. Set threshold adaptif
    thresholds = set_adaptive_thresholds(obfuscation_strategies)
    
    # 4. Preprocess teks untuk language-specific analysis (bahasa Indonesia)
    processed_student = enhanced_id_preprocessor(student_clean)
    processed_llm = enhanced_id_preprocessor(llm_clean)
    
    # Compute overall similarities once
    overall_similarities, _ = calculate_similarities(
        processed_student,
        processed_llm,
        method_weights,
        preprocessed=True
    )
    
    # 5. Analisis similarity berbasis kalimat - only process significant sentences
    student_sentences = sentence_split_pattern.split(student_clean)
    llm_sentences = sentence_split_pattern.split(llm_clean)
    
    # Filter short sentences once
    significant_student_sentences = [s for s in student_sentences if len(s.split()) >= 5]
    significant_llm_sentences = [s for s in llm_sentences if len(s.split()) >= 5]
    
    sentence_results = []
    overall_matched_chars = 0
    total_chars = len(student_clean)
    
    # Only process if we have content to compare
    if significant_student_sentences and significant_llm_sentences:
        for student_sent in significant_student_sentences:
            best_match = None
            best_score = 0
            best_similarities = None
            
            # For each significant LLM sentence, calculate similarity
            for llm_sent in significant_llm_sentences:
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
                
            # Only add non-zero results
            if best_score > 0:
                sentence_results.append({
                    "student_text": student_sent,
                    "best_match": best_match,
                    "weighted_score": best_score,
                    "individual_scores": best_similarities if best_similarities else {},
                    "is_plagiarized": is_plagiarized
                })
    
    # Calculate overall percentage only once at the end        
    overall_percentage = (overall_matched_chars / total_chars) * 100 if total_chars > 0 else 0

    return {
        "overall_percentage": overall_percentage,
        "overall_similarities": overall_similarities,
        "detected_strategies": obfuscation_strategies,
        "method_weights": method_weights,
        "thresholds": thresholds,
        "sentence_results": sentence_results
    }