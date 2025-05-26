# !pip install bert-score
# !pip install transformers
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity as tfidf_cosine_sim
from bert_score import score as bert_score
import re

# Note:     Gunakan model multilingual untuk bahasa Indonesia/Inggris/Jepang
# Contoh:   bert-base-multilingual-cased

class AnswerEvaluator:
    def __init__(self):
        self.tfidf_vectorizer = TfidfVectorizer()
    
    def clean_text(self, text):
        """Membersihkan teks dari HTML tags dan TinyMCE artifacts"""
        if not text:
            return ""
        
        # Remove HTML tags
        text = re.sub(r'<[^>]+>', '', text)
        
        # Remove HTML entities
        text = re.sub(r'&nbsp;|&amp;|&lt;|&gt;|&quot;|&#39;', ' ', text)
        
        # Remove extra whitespaces
        text = re.sub(r'\s+', ' ', text).strip()
        
        return text

    def calculate_tfidf_similarity(self, ref_answer, user_answer):
        """Menghitung similarity berbasis TF-IDF"""
        clean_ref = self.clean_text(ref_answer)
        clean_user = self.clean_text(user_answer)
        tfidf_matrix = self.tfidf_vectorizer.fit_transform([clean_ref, clean_user])
        return tfidf_cosine_sim(tfidf_matrix[0], tfidf_matrix[1])[0][0]
    
    def calculate_bertscore(self, ref_answer, user_answer):
        """Menghitung BERTScore"""
        clean_ref = self.clean_text(ref_answer)
        clean_user = self.clean_text(user_answer)
        P, R, F1 = bert_score([clean_user], [clean_ref], lang='id')  # Ganti 'id' dengan 'en' untuk English
        return float(F1.mean())
    
    def combined_evaluation(self, ref_answer, user_answer, current_level):
        """Kombinasi TF-IDF dan BERTScore"""
        tfidf_score = self.calculate_tfidf_similarity(ref_answer, user_answer)
        bert_score = self.calculate_bertscore(ref_answer, user_answer)
        
        # Gabungkan dengan weighting (bisa disesuaikan)
        combined = 0.4 * tfidf_score + 0.6 * bert_score
        
        is_correct = combined >= 0.65  # This is a boolean
        is_correct_int = 1 if is_correct else 0
        # return {
        #     "tfidf_score": tfidf_score,
        #     "bert_score": bert_score,
        #     "combined_score": combined,
        #     "is_correct": combined >= 0.7  # Threshold bisa disesuaikan
        # }
        
        return {
        "tfidf_score": float(tfidf_score),  
        "bert_score": float(bert_score), 
        "combined_score": float(combined), 
        "is_correct": is_correct_int,  
        "current_level": current_level,
        "level_progress": {
            "current_streak": 1 if combined >= 0.65 else 0,
            "needed_for_next": 4
        }
    }
