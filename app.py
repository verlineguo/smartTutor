from flask import Flask, request, jsonify
import json
import mainPDF
import mainNoun
import translatePDF
import tfidf
import cosine

app = Flask(__name__)
app.config['MAX_CONTENT_LENGTH'] = 200 * 1024 * 1024


@app.route('/')
def test():
    return 'Hello World'


@app.route('/translate', methods=['POST'])
def count():
    language = request.form['language']
    response = translatePDF.main_translate(request.files['pdf'], language)
    return response


@app.route('/tfidf', methods=['POST'])
def count_tfidf():
    language = request.form['language']
    pdf = request.files['pdf']
    result = tfidf.main_count_tfidf(pdf, language)
    return jsonify(json.loads(result))


@app.route('/generate', methods=['POST'])
def generate():
    language = request.form.get('language') or request.json.get('language')
    pdf = request.files.get('pdf')
    tfidf_data = request.form.get('tfidf_data')
    if tfidf_data:
        tfidf_data = json.loads(tfidf_data)
    if pdf:
        # Panggil fungsi untuk menghasilkan pertanyaan dari PDF dan TF-IDF data
        response = mainPDF.generate_questions_from_pdf(
            pdf, tfidf_data, language=language
        )
    else:
        # If no PDF, use topic-based generation (assuming topic details provided)
        data = request.json
        topic = data.get('topic')
        response = mainNoun.main(topic, language)

    return jsonify(json.loads(response))


@app.route('/cosine_similarity', methods=['POST'])
def calculate_similarity():
    data = request.json
    user_answer = data.get('user_answer')
    actual_answer = data.get('actual_answer')

    if not user_answer or not actual_answer:
        return jsonify({"error": "Both user_answer and actual_answer are required"}), 400

    # Panggil fungsi cosine similarity
    similarity_score = cosine.main(user_answer, actual_answer)

    return jsonify({"similarity_score": similarity_score})


if __name__ == '__main__':
    app.run(host="0.0.0.0", debug=True)
