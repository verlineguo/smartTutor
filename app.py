from flask import Flask, request, jsonify
import json
import mainPDF
import mainNoun
import translatePDF
import tfidf
import gc
import cosine
from similarity import cosine_similarity_score, jaccard_similarity, bert_similarity
import os
from answerBert import BertAnsweringSystem
from multiLLM import get_llm_response
from evaluation import AnswerEvaluator
from werkzeug.utils import secure_filename
import traceback

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
    print(tfidf_data)
    if tfidf_data:
        tfidf_data = json.loads(tfidf_data)
    if pdf:
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
        # Check if PDF file is uploaded
        if 'pdf' not in request.files:
            return jsonify({'error': 'No PDF file uploaded'}), 400
            
        pdf_file = request.files['pdf']
        question = request.form.get('question')
        
        # Check if question is provided
        if not question:
            return jsonify({'error': 'Question is required'}), 400
        
        # Determine number of results to return (optional parameter)
        try:
            top_k = int(request.form.get('top_k', 3))
        except ValueError:
            top_k = 3
        
        # Save the uploaded PDF
        file_path = os.path.join("uploads", secure_filename(pdf_file.filename))
        pdf_file.save(file_path)
        
        # Initialize the QA system
        qa_system = BertAnsweringSystem()
        
        # Process the query and get answers
        results = qa_system.process_pdf_query(file_path, question, top_k)
        
        if not results:
            return jsonify({'error': 'Failed to generate answers'}), 500
        
        # Format results for API response
        formatted_results = []
        for result in results:
            answer_data = {
                'answer': str(result['answer']),
                'combined_score': float(result['combined_score']),
                'qa_score': float(result['qa_score']),
                'retrieval_score': float(result['retrieval_score']),
                'bloom_level': str(result['bloom_level']),
                'is_valid': bool(result['is_valid'])
            }
            
            # Add context with highlighting if it's not the essay result
            if result.get('chunk') != "combined_relevant_chunks":
                # Highlighting the answer in the context
                chunk = result['chunk']
                answer = result['original_answer']
                highlighted_context = chunk.replace(answer, f"<mark>{answer}</mark>")
                answer_data['context'] = highlighted_context
            else:
                answer_data['is_essay'] = True
            
            formatted_results.append(answer_data)
        
        # Clean up - remove the uploaded file if needed
        # os.remove(file_path)  # Uncomment if you want to delete files after processing
        
        return jsonify({'answers': formatted_results}), 200
        
    except MemoryError:
        # Handle memory errors specifically
        gc.collect()
        return jsonify({"error": "Out of memory, please try with smaller PDF"}), 507
    except Exception as e:
        # Log the full error with traceback
        app.logger.error(f"Indonesian QA Error: {str(e)}")
        app.logger.error(traceback.format_exc())
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
        # 'highlighted_text': highlight_differences(text1, text2),
        # 'multi_pattern_matches': multi_pattern_search(text2, list(set(text1.split()) & set(text2.split())))
    }
    
    return jsonify(results)

@app.route('/evaluate', methods=['POST'])
def evaluate_answer():
    try:
        data = request.json
        evaluator = AnswerEvaluator()
        
        result = evaluator.combined_evaluation(
            data['reference_answer'],
            data['user_answer'],
            data['current_level']
        )
        app.logger.info(f"Evaluation Result: {result}")

        return jsonify(result)
    
    except Exception as e:
        return jsonify({"error": str(e)}), 500


if __name__ == '__main__':
    app.run(host="0.0.0.0", debug=True)