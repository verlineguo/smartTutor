from flask import Flask, request, jsonify
import json
import mainPDF
import mainNoun
import translatePDF
import tfidf
import gc
import cosine
from similarity import cosine_similarity_score, jaccard_similarity, bert_similarity, highlight_differences, multi_pattern_search
import os
from answerBert import BERTQuestionAnsweringSystem
from multiLLM import get_llm_response
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
    print("ini lagi di generate :D")
    if tfidf_data:
        tfidf_data = json.loads(tfidf_data)
    if pdf:
        # Panggil fungsi untuk menghasilkan pertanyaan dari PDF dan TF-IDF data
        response = mainPDF.generate_questions_from_pdf(
            pdf, tfidf_data, language=language
        )
    else:
        data = request.json
        topic = data.get('topic')
        response = mainNoun.main(topic, language)
    return jsonify(json.loads(response))


@app.route('/answer_llm', methods=['POST'])
def multi_llm():
    model = request.form.get('model') or request.json.get('model')
    prompt = request.form.get('prompt') or request.json.get('prompt')
    
    if not model or not prompt:
        return jsonify({"error": "Model and prompt are required"}), 400
    try:
        response = get_llm_response(model, prompt)
        return jsonify({
            "model": model,
            "response": response,
            "status": "success"
        })
    except Exception as e:
        return jsonify({
            "error": str(e),
            "status": "error"
        }), 500

@app.route('/cosine_similarity', methods=['POST'])
def calculate_similarity():
    data = request.json
    user_answer = data.get('user_answer')
    actual_answer = data.get('actual_answer')

    if not user_answer or not actual_answer:
        return jsonify({"error": "Both user_answer and actual_answer are required"}), 400

    # Panggil fungsi cosine similarity
    similarity_score = cosine.main(user_answer, actual_answer)
    print(similarity_score)

    return jsonify({"similarity_score": similarity_score})



@app.route('/bert_qa', methods=['POST'])
def bert_qa():
    try:
        if 'pdf' not in request.files:
            return jsonify({'error': 'No PDF file uploaded'}), 400
            
        pdf_file = request.files['pdf']
        question = request.form.get('question')
        
        if not question:
            return jsonify({'error': 'Question is required'}), 400
        
        file_path = os.path.join("uploads", pdf_file.filename)
        os.makedirs("uploads", exist_ok=True)
        pdf_file.save(file_path)
        
        qa_system = BERTQuestionAnsweringSystem()
        document = qa_system.read_pdf(file_path)
        
        if not document:
            return jsonify({'error': 'Failed to extract text from PDF'}), 500
            
        results = qa_system.answer_question(document, question)
        
        formatted_results = []
        for result in results:
            formatted_results.append({
                'answer': result['answer'],
                'combined_score': float(result['combined_score']),
                'qa_score': float(result['qa_score']),
                'retrieval_score': float(result['retrieval_score']),
                'context': qa_system.get_context_with_highlights(result['chunk'], result['answer'])
            })
        
        return jsonify({'answers': formatted_results}), 200
    except MemoryError:
        # Khusus untuk error memory
        gc.collect()
        return jsonify({"error": "Out of memory, please try with smaller PDF"}), 507
    except Exception as e:
        app.logger.error(f"BERT QA Error: {str(e)}")
        return jsonify({"error": "An error occurred processing the request"}), 500

@app.route('/checkPlagiarism', methods=['POST'])
def plagiarism_check():
    data = request.json
    text1 = data.get('user_answer')
    text2 = data.get('ai_answer')

    print(f"text1: {text1}")
    print(f"text2: {text2}")
    
    results = {
        'cosine_similarity': cosine_similarity_score(text1, text2),
        'jaccard_similarity': jaccard_similarity(text1, text2),
        'bert_similarity': bert_similarity(text1, text2),
        'highlighted_text': highlight_differences(text1, text2),
        # 'multi_pattern_matches': multi_pattern_search(text2, list(set(text1.split()) & set(text2.split())))
    }
    
    return jsonify(results)


if __name__ == '__main__':
    app.run(host="0.0.0.0", debug=True)