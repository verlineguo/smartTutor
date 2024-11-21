import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
import json
import nltk
from nltk.corpus import stopwords
from janome.tokenizer import Tokenizer
import openai
import numpy as np
from sklearn.metrics.pairwise import cosine_similarity

with open('hidden.txt') as file:
    openai.api_key = file.read()

# Download stopwords
nltk.download('stopwords')


def read_pdf_with_plumber(file_path):
    import pdfplumber
    text = ""
    with pdfplumber.open(file_path) as pdf:
        for page in pdf.pages:
            text += page.extract_text() + "\n"
    return text


def clean_text(text, language):
    import re
    if language.lower() != 'japanese':
        text = re.sub(r'\s+', ' ', text)
        text = re.sub(r'[^a-zA-Z\s]', '', text)
    return text.lower()


def tokenize_japanese(text):
    t = Tokenizer()
    tokens = [token.surface for token in t.tokenize(text)]
    return ' '.join(tokens)


def get_embeddings(text, model="text-embedding-ada-002"):
    if not isinstance(text, str):
        text = str(text)
    text = text.replace("\n", " ")
    response = openai.embeddings.create(
        input=[text],
        model=model
    )
    return response.data[0].embedding


def calculate_cosine_similarity(ngram_embedding, chunk_embeddings):
    similarities = cosine_similarity([ngram_embedding], chunk_embeddings)
    return np.mean(similarities)


def main_count_tfidf(file_path, language):
    # Define stopwords based on language
    if language.lower() == "indonesia":
        stopwords_lang = stopwords.words('indonesian')
    elif language.lower() == "english":
        stopwords_lang = stopwords.words('english')
    elif language.lower() == "japanese":
        stopwords_lang = []
    else:
        raise ValueError(
            "Bahasa tidak didukung. Pilih 'indonesia', 'japanese', atau 'english'.")

    document_text = read_pdf_with_plumber(file_path)
    document_text_cleaned = clean_text(document_text, language)

    if language.lower() == "japanese":
        document_text_cleaned = tokenize_japanese(document_text_cleaned)

    vectorizer = TfidfVectorizer(ngram_range=(1, 3), stop_words=stopwords_lang)
    tfidf_matrix = vectorizer.fit_transform([document_text_cleaned])
    feature_names = vectorizer.get_feature_names_out()
    dense = tfidf_matrix.todense()
    denselist = dense.tolist()[0]

    df = pd.DataFrame({'N-gram': feature_names, 'TF-IDF Score': denselist})
    df['N-gram Length'] = df['N-gram'].apply(lambda x: len(x.split()))

    df_unigram = df[df['N-gram Length'] ==
                    1].sort_values(by='TF-IDF Score', ascending=False).head(10)
    df_bigram = df[df['N-gram Length'] ==
                   2].sort_values(by='TF-IDF Score', ascending=False).head(10)
    df_trigram = df[df['N-gram Length'] ==
                    3].sort_values(by='TF-IDF Score', ascending=False).head(10)

    top_ngrams = pd.concat([df_unigram, df_bigram, df_trigram])[
        'N-gram'].tolist()

    # Generate embeddings for each n-gram
    ngram_embeddings = {ngram: get_embeddings(ngram) for ngram in top_ngrams}

    # Chunk the document (approximate 2000 characters per chunk)
    chunk_size = 2000
    document_chunks = [document_text_cleaned[i:i + chunk_size]
                       for i in range(0, len(document_text_cleaned), chunk_size)]

    # Generate embeddings for each document chunk
    chunk_embeddings = [get_embeddings(chunk) for chunk in document_chunks]

    # Calculate cosine similarity between each n-gram and the document chunks
    similarity_results = {}
    for ngram, ngram_embedding in ngram_embeddings.items():
        similarity_score = calculate_cosine_similarity(
            ngram_embedding, chunk_embeddings)
        similarity_results[ngram] = similarity_score

    result = {
        "unigram": df_unigram.to_dict(orient="records"),
        "bigram": df_bigram.to_dict(orient="records"),
        "trigram": df_trigram.to_dict(orient="records"),
        "cosine_similarity": similarity_results
    }

    return json.dumps(result, indent=4)
