from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
from difflib import SequenceMatcher
from sentence_transformers import SentenceTransformer, util
from ahocorasick import Automaton

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

# Sequence Matching (Highlight Plagiarism)
def highlight_differences(text1, text2):
    matcher = SequenceMatcher(None, text1, text2)
    result = []
    
    for tag, i1, i2, j1, j2 in matcher.get_opcodes():
        if tag == "equal":
            result.append(f"\033[92m{text1[i1:i2]}\033[0m")  # Hijau untuk yang cocok
        elif tag in ["replace", "delete"]:
            result.append(f"\033[91m{text1[i1:i2]}\033[0m")  # Merah untuk perbedaan
        elif tag == "insert":
            result.append(f"\033[94m{text2[j1:j2]}\033[0m")  # Biru untuk tambahan
        
    return "".join(result)



def multi_pattern_search(text, patterns):
    A = Automaton()
    for idx, pattern in enumerate(patterns):
        A.add_word(pattern, (idx, pattern))
    A.make_automaton()
    
    matches = []
    for end_index, (idx, pattern) in A.iter(text):
        start_index = end_index - len(pattern) + 1

        matches.append({
            "pattern": pattern,
            "start_index": start_index,
            "end_index": end_index
        })    
    return matches

def bert_similarity(text1, text2):
    emb1 = model.encode(text1, convert_to_tensor=True)
    emb2 = model.encode(text2, convert_to_tensor=True)
    return util.pytorch_cos_sim(emb1, emb2).item()


