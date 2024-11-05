from flask import Flask, request, jsonify
import main
import runPDF
import countPDF
import cossine

app = Flask(__name__)


@app.route('/')
def hello_world():
    return 'Hello World'


@app.route('/generate', methods=['POST'])
def generate():
    if 'pdf' in request.files:
        language = request.form['language']
        page = int(request.form['page'])
        response = runPDF.main(request.files['pdf'], language, page)
    else:
        data = request.json
        language = data.get('language')
        topic = data.get('topic')
        response = main.main(topic, language)

    return response


@app.route('/count-page', methods=['POST'])
def count():
    response = countPDF.main(request.files['pdf'])
    return response

@app.route('/cossine-similarity', methods=['POST'])
def cossine_count():
    data = request.json
    question = data.get('question')
    answer = data.get('answer')
    response = cossine.main(question, answer)
    return response

if __name__ == '__main__':
    app.run(host="0.0.0.0",debug=True)
