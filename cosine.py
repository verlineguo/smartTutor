import openai
import numpy as np
from sklearn.metrics.pairwise import cosine_similarity

# Fungsi untuk mendapatkan embedding dari teks

with open('hidden.txt') as file:
    openai.api_key = file.read()


def get_embedding(text, model="text-embedding-ada-002"):
    if not isinstance(text, str):
        text = str(text)
    text = text.replace("\n", " ")
    response = openai.embeddings.create(
        input=[text],
        model=model
    )
    return response.data[0].embedding

# Fungsi untuk menghitung cosine similarity antara dua embedding


def calculate_cosine_similarity(embedding1, embedding2):
    # Reshape the embeddings to 2D arrays
    embedding1 = np.array(embedding1).reshape(1, -1)
    embedding2 = np.array(embedding2).reshape(1, -1)
    # Calculate cosine similarity
    similarity = cosine_similarity(embedding1, embedding2)
    return similarity[0][0]

# Fungsi main untuk mengatur alur program


def main(user_answer, actual_answer):
    # Dapatkan embedding dari kedua jawaban
    user_embedding = get_embedding(user_answer)
    actual_embedding = get_embedding(actual_answer)

    # Hitung cosine similarity
    similarity_score = calculate_cosine_similarity(
        user_embedding, actual_embedding)

    # Cetak hasil similarity
    return similarity_score