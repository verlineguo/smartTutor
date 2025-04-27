from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
from difflib import SequenceMatcher
from sentence_transformers import SentenceTransformer, util
# from ahocorasick import Automaton

# BERT Model untuk Word Embeddings
model = SentenceTransformer('all-MiniLM-L6-v2')

#Cosine Similarity (TF-IDF)
def cosine_similarity_score(text1, text2):
    vectorizer = TfidfVectorizer()
    tfidf_matrix = vectorizer.fit_transform([text1, text2])
    return cosine_similarity(tfidf_matrix[0], tfidf_matrix[1])[0][0]

# Jaccard Similarity
def jaccard_similarity(text1, text2):
    set1, set2 = set(text1.split()), set(text2.split())
    return len(set1 & set2) / len(set1 | set2)

def bert_similarity(text1, text2):
    emb1 = model.encode(text1, convert_to_tensor=True)
    emb2 = model.encode(text2, convert_to_tensor=True)
    return util.pytorch_cos_sim(emb1, emb2).item()


