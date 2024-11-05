from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import sys


def main(question, answer):

    # Contoh pertanyaan dan jawaban
    pertanyaan = [question]
    jawaban = [answer]

    # Inisialisasi TF-IDF Vectorizer
    vectorizer = TfidfVectorizer()

    # Transformasi pertanyaan dan jawaban menjadi representasi vektor TF-IDF
    tfidf_matrix_pertanyaan = vectorizer.fit_transform(pertanyaan)
    tfidf_matrix_jawaban = vectorizer.transform(jawaban)

    # Hitung cosine similarity antara setiap pasangan pertanyaan dan jawaban
    similarity_matrix = cosine_similarity(
        tfidf_matrix_pertanyaan, tfidf_matrix_jawaban)

    # Print similarity matrix
    return str(similarity_matrix)


if __name__ == '__main__':
    main(sys.argv[1], sys.argv[2])

