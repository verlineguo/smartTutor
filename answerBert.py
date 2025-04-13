import pdfplumber
import re
import nltk
from nltk.tokenize import sent_tokenize, word_tokenize
from nltk.corpus import stopwords
from transformers import AutoTokenizer, AutoModelForQuestionAnswering, pipeline
from sentence_transformers import SentenceTransformer, CrossEncoder
import numpy as np
from sklearn.metrics.pairwise import cosine_similarity
import torch

try:
    nltk.data.find('tokenizers/punkt')
except LookupError:
    nltk.download('punkt', quiet=True)
    
try:
    nltk.data.find('corpora/stopwords')
except LookupError:
    nltk.download('stopwords', quiet=True)


class BERTQuestionAnsweringSystem:
    def __init__(self, 
                 qa_model_name='bert-large-uncased-whole-word-masking-finetuned-squad',  
                 embedding_model_name='all-MiniLM-L6-v2',  
                 cross_encoder_name='cross-encoder/ms-marco-MiniLM-L-6-v2'):  
        """
        Initialize an enhanced QA system with better retrieval and extraction capabilities
        """
        try:
            # QA pipeline with better settings
            self.tokenizer = AutoTokenizer.from_pretrained(qa_model_name)
            self.model = AutoModelForQuestionAnswering.from_pretrained(qa_model_name)
            self.qa_pipeline = pipeline(
                'question-answering', 
                model=self.model, 
                tokenizer=self.tokenizer,
                handle_impossible_answer=True,  # Better handling of unanswerable questions
                max_answer_length=100
            )
            
            # Embedding model for semantic search - multilingual
            self.embedding_model = SentenceTransformer(embedding_model_name)
            
            # Cross-encoder for answer reranking
            self.cross_encoder = CrossEncoder(cross_encoder_name)
            
            # Stopwords for text cleaning - support both English and Indonesian
            self.stopwords = set(stopwords.words('english'))
            
            # Add Indonesian stopwords
            # self.indonesian_stopwords = {
            #     'ada', 'adalah', 'adanya', 'adapun', 'agak', 'agaknya', 'agar', 'akan', 'akankah', 
            #     'akhir', 'akhiri', 'akhirnya', 'aku', 'akulah', 'amat', 'amatlah', 'anda', 'andalah', 
            #     'antar', 'antara', 'antaranya', 'apa', 'apaan', 'apabila', 'apakah', 'apalagi', 'apatah', 
            #     'artinya', 'asal', 'asalkan', 'atas', 'atau', 'ataukah', 'ataupun', 'awal', 'awalnya', 
            #     'bagai', 'bagaikan', 'bagaimana', 'bagaimanakah', 'bagaimanapun', 'bagi', 'bagian', 
            #     'bahkan', 'bahwa', 'bahwasanya', 'baik', 'bakal', 'bakalan', 'balik', 'banyak', 'bapak', 
            #     'baru', 'bawah', 'beberapa', 'begini', 'beginian', 'beginikah', 'beginilah', 'begitu', 
            #     'begitukah', 'begitulah', 'begitupun', 'belakang', 'belakangan', 'belum', 'belumlah', 
            #     'benar', 'benarkah', 'benarlah', 'berada', 'berakhir', 'berakhirlah', 'berakhirnya', 
            #     'berapa', 'berapakah', 'berapalah', 'berapapun', 'berarti', 'berawal', 'berbagai', 
            #     'berdatangan', 'beri', 'berikan', 'berikut', 'berikutnya', 'berjumlah', 'berkali-kali', 
            #     'berkata', 'berkehendak', 'berkeinginan', 'berkenaan', 'berlainan', 'berlalu', 'berlangsung', 
            #     'berlebihan', 'bermacam', 'bermacam-macam', 'bermaksud', 'bermula', 'bersama', 'bersama-sama', 
            #     'bersiap', 'bersiap-siap', 'bertanya', 'bertanya-tanya', 'berturut', 'berturut-turut', 
            #     'bertutur', 'berujar', 'berupa', 'besar', 'betul', 'betulkah', 'biasa', 'biasanya', 'bila', 
            #     'bilakah', 'bisa', 'bisakah', 'boleh', 'bolehkah', 'bolehlah', 'buat', 'bukan', 'bukankah', 
            #     'bukanlah', 'bukannya', 'bulan', 'bung', 'cara', 'caranya', 'cukup', 'cukupkah', 'cukuplah', 
            #     'cuma', 'dahulu', 'dalam', 'dan', 'dapat', 'dari', 'daripada', 'datang', 'dekat', 'demi', 
            #     'demikian', 'demikianlah', 'dengan', 'depan', 'di', 'dia', 'diakhiri', 'diakhirinya', 
            #     'dialah', 'diantara', 'diantaranya', 'diberi', 'diberikan', 'diberikannya', 'dibuat', 
            #     'dibuatnya', 'didapat', 'didatangkan', 'digunakan', 'diibaratkan', 'diibaratkannya', 
            #     'diingat', 'diingatkan', 'diinginkan', 'dijawab', 'dijelaskan', 'dijelaskannya', 'dikarenakan',
            #     'dikatakan', 'dikatakannya', 'dikerjakan', 'diketahui', 'diketahuinya', 'dikira', 'dilakukan', 
            #     'dilalui', 'dilihat', 'dimaksud', 'dimaksudkan', 'dimaksudkannya', 'dimaksudnya', 'diminta', 
            #     'dimintai', 'dimisalkan', 'dimulai', 'dimulailah', 'dimulainya', 'dimungkinkan', 'dini', 
            #     'dipastikan', 'diperbuat', 'diperbuatnya', 'dipergunakan', 'diperkirakan', 'diperlihatkan', 
            #     'diperlukan', 'diperlukannya', 'dipersoalkan', 'dipertanyakan', 'dipunyai', 'diri', 'dirinya', 
            #     'disampaikan', 'disebut', 'disebutkan', 'disebutkannya', 'disini', 'disinilah', 'ditambahkan', 
            #     'ditandaskan', 'ditanya', 'ditanyai', 'ditanyakan', 'ditegaskan', 'ditujukan', 'ditunjuk', 
            #     'ditunjuki', 'ditunjukkan', 'ditunjukkannya', 'ditunjuknya', 'dituturkan', 'dituturkannya', 
            #     'diucapkan', 'diucapkannya', 'diungkapkan', 'dong', 'dua', 'dulu', 'empat', 'enggak', 
            #     'enggaknya', 'entah', 'entahlah', 'guna', 'gunakan', 'hal', 'hampir', 'hanya', 'hanyalah', 
            #     'hari', 'harus', 'haruslah', 'harusnya', 'hendak', 'hendaklah', 'hendaknya', 'hingga', 'ia',
            #     'ialah', 'ibarat', 'ibaratkan', 'ibaratnya', 'ibu', 'ikut', 'ingat', 'ingat-ingat', 'ingin', 
            #     'inginkah', 'inginkan', 'ini', 'inikah', 'inilah', 'itu', 'itukah', 'itulah', 'jadi', 'jadilah',
            #     'jadinya', 'jangan', 'jangankan', 'janganlah', 'jauh', 'jawab', 'jawaban', 'jawabnya', 'jelas', 
            #     'jelaskan', 'jelaslah', 'jelasnya', 'jika', 'jikalau', 'juga', 'jumlah', 'jumlahnya', 'justru',
            #     'kala', 'kalau', 'kalaulah', 'kalaupun', 'kalian', 'kami', 'kamilah', 'kamu', 'kamulah', 'kan',
            #     'kapan', 'kapankah', 'kapanpun', 'karena', 'karenanya', 'kasus', 'kata', 'katakan', 'katakanlah',
            #     'katanya', 'ke', 'keadaan', 'kebetulan', 'kecil', 'kedua', 'keduanya', 'keinginan', 'kelamaan',
            #     'kelihatan', 'kelihatannya', 'kelima', 'keluar', 'kembali', 'kemudian', 'kemungkinan', 
            #     'kemungkinannya', 'kenapa', 'kepada', 'kepadanya', 'kesampaian', 'keseluruhan', 'keseluruhannya',
            #     'keterlaluan', 'ketika', 'khususnya', 'kini', 'kinilah', 'kira', 'kira-kira', 'kiranya', 
            #     'kita', 'kitalah', 'kok', 'kurang', 'lagi', 'lagian', 'lah', 'lain', 'lainnya', 'lalu', 'lama',
            #     'lamanya', 'lanjut', 'lanjutnya', 'lebih', 'lewat', 'lima', 'luar', 'macam', 'maka', 'makanya',
            #     'makin', 'malah', 'malahan', 'mampu', 'mampukah', 'mana', 'manakala', 'manalagi', 'masa', 
            #     'masalah', 'masalahnya', 'masih', 'masihkah', 'masing', 'masing-masing', 'mau', 'maupun', 
            #     'melainkan', 'melakukan', 'melalui', 'melihat', 'melihatnya', 'memang', 'memastikan', 
            #     'memberi', 'memberikan', 'membuat', 'memerlukan', 'memihak', 'meminta', 'memintakan', 
            #     'memisalkan', 'memperbuat', 'mempergunakan', 'memperkirakan', 'memperlihatkan', 'mempersiapkan',
            #     'mempersoalkan', 'mempertanyakan', 'mempunyai', 'memulai', 'memungkinkan', 'menaiki', 
            #     'menambahkan', 'menandaskan', 'menanti', 'menanti-nanti', 'menantikan', 'menanya', 'menanyai',
            #     'menanyakan', 'mendapat', 'mendapatkan', 'mendatang', 'mendatangi', 'mendatangkan', 'menegaskan',
            #     'mengakhiri', 'mengapa', 'mengatakan', 'mengatakannya', 'mengenai', 'mengerjakan', 'mengetahui',
            #     'menggunakan', 'menghendaki', 'mengibaratkan', 'mengibaratkannya', 'mengingat', 'mengingatkan',
            #     'menginginkan', 'mengira', 'mengucapkan', 'mengucapkannya', 'mengungkapkan', 'menjadi', 
            #     'menjawab', 'menjelaskan', 'menuju', 'menunjuk', 'menunjuki', 'menunjukkan', 'menunjuknya',
            #     'menurut', 'menuturkan', 'menyampaikan', 'menyangkut', 'menyatakan', 'menyebutkan', 'merasa',
            #     'mereka', 'merekalah', 'merupakan', 'meski', 'meskipun', 'meyakini', 'meyakinkan', 'minta',
            #     'mirip', 'misal', 'misalkan', 'misalnya', 'mula', 'mulai', 'mulailah', 'mulanya', 'mungkin',
            #     'mungkinkah', 'nah', 'naik', 'namun', 'nanti', 'nantinya', 'nyaris', 'nyatanya', 'oleh',
            #     'olehnya', 'pada', 'padahal', 'padanya', 'pak', 'paling', 'panjang', 'pantas', 'para', 'pasti',
            #     'pastilah', 'penting', 'pentingnya', 'per', 'percuma', 'perlu', 'perlukah', 'perlunya',
            #     'pernah', 'persoalan', 'pertama', 'pertama-tama', 'pertanyaan', 'pertanyakan', 'pihak',
            #     'pihaknya', 'pukul', 'pula', 'pun', 'punya', 'rasa', 'rasanya', 'rata', 'rupanya', 'saat',
            #     'saatnya', 'saja', 'sajalah', 'saling', 'sama', 'sama-sama', 'sambil', 'sampai', 'sampai-sampai',
            #     'sampaikan', 'sana', 'sangat', 'sangatlah', 'satu', 'saya', 'sayalah', 'se', 'sebab', 'sebabnya',
            #     'sebagai', 'sebagaimana', 'sebagainya', 'sebagian', 'sebaik', 'sebaik-baiknya', 'sebaiknya',
            #     'sebaliknya', 'sebanyak', 'sebegini', 'sebegitu', 'sebelum', 'sebelumnya', 'sebenarnya',
            #     'seberapa', 'sebesar', 'sebetulnya', 'sebisanya', 'sebuah', 'sebut', 'sebutlah', 'sebutnya',
            #     'secara', 'secukupnya', 'sedang', 'sedangkan', 'sedemikian', 'sedikit', 'sedikitnya', 'seenaknya',
            #     'segala', 'segalanya', 'segera', 'seharusnya', 'sehingga', 'seingat', 'sejak', 'sejauh',
            #     'sejenak', 'sejumlah', 'sekadar', 'sekadarnya', 'sekali', 'sekali-kali', 'sekalian', 'sekaligus',
            #     'sekalipun', 'sekarang', 'sekarang', 'sekecil', 'seketika', 'sekiranya', 'sekitar', 'sekitarnya',
            #     'sekurang-kurangnya', 'sekurangnya', 'sela', 'selain', 'selaku', 'selalu', 'selama', 'selama-lamanya',
            #     'selamanya', 'selanjutnya', 'seluruh', 'seluruhnya', 'semacam', 'semakin', 'semampu', 'semampunya',
            #     'semasa', 'semasih', 'semata', 'semata-mata', 'semaunya', 'sementara', 'semisal', 'semisalnya',
            #     'sempat', 'semua', 'semuanya', 'semula', 'sendiri', 'sendirian', 'sendirinya', 'seolah',
            #     'seolah-olah', 'seorang', 'sepanjang', 'sepantasnya', 'sepantasnyalah', 'seperlunya',
            #     'seperti', 'sepertinya', 'sepihak', 'sering', 'seringnya', 'serta', 'serupa', 'sesaat',
            #     'sesama', 'sesampai', 'sesegera', 'sesekali', 'seseorang', 'sesuatu', 'sesuatunya',
            #     'sesudah', 'sesudahnya', 'setelah', 'setempat', 'setengah', 'seterusnya', 'setiap', 'setiba',
            #     'setibanya', 'setidak-tidaknya', 'setidaknya', 'setinggi', 'seusai', 'sewaktu', 'siap',
            #     'siapa', 'siapakah', 'siapapun', 'sini', 'sinilah', 'soal', 'soalnya', 'suatu', 'sudah',
            #     'sudahkah', 'sudahlah', 'supaya', 'tadi', 'tadinya', 'tahu', 'tahun', 'tak', 'tambah',
            #     'tambahnya', 'tampak', 'tampaknya', 'tandas', 'tandasnya', 'tanpa', 'tanya', 'tanyakan',
            #     'tanyanya', 'tapi', 'tegas', 'tegasnya', 'telah', 'tempat', 'tengah', 'tentang', 'tentu',
            #     'tentulah', 'tentunya', 'tepat', 'terakhir', 'terasa', 'terbanyak', 'terdahulu', 'terdapat',
            #     'terdiri', 'terhadap', 'terhadapnya', 'teringat', 'teringat-ingat', 'terjadi', 'terjadilah',
            #     'terjadinya', 'terkira', 'terlalu', 'terlebih', 'terlihat', 'termasuk', 'ternyata', 'tersampaikan',
            #     'tersebut', 'tersebutlah', 'tertentu', 'tertuju', 'terus', 'terutama', 'tetap', 'tetapi',
            #     'tiap', 'tiba', 'tiba-tiba', 'tidak', 'tidakkah', 'tidaklah', 'tiga', 'tinggi', 'toh',
            #     'tunjuk', 'turut', 'tutur', 'tuturnya', 'ucap', 'ucapnya', 'ujar', 'ujarnya', 'umum',
            #     'umumnya', 'ungkap', 'ungkapnya', 'untuk', 'usah', 'usai', 'waduh', 'wah', 'wahai',
            #     'waktu', 'waktunya', 'walau', 'walaupun', 'wong', 'yaitu', 'yakin', 'yakni', 'yang'
            # }
            
            self.indonesian_stopwords = {
            'ada', 'adalah', 'adanya', 'adapun', 'agak', 'akan', 'aku', 'saya',
            'akulah', 'anda', 'ini', 'itu', 'dan', 'yang', 'di', 'ke', 'pada',
            'untuk', 'dari', 'dengan', 'tidak', 'kita', 'mereka', 'kami',
            'atau', 'tetapi', 'jika', 'maka', 'oleh', 'sebagai', 'karena',
            'ketika', 'saat', 'tentang', 'sampai', 'hingga', 'hanya', 'juga'
        }
            self.stopwords.update(self.indonesian_stopwords)
            
            # Store last question for reference
            self.last_question = ""
            
        except Exception as e:
            print(f"Error initializing models: {e}")
            print("Make sure you have installed transformers, torch, and sentence-transformers")

    def read_pdf(self, file_path: str) -> str:
        """
        Enhanced PDF reader with better text extraction and layout preservation
        """
        try:
            document = ''
            with pdfplumber.open(file_path) as pdf:
                for page in pdf.pages:
                    # Try to extract text with custom settings for better results
                    text = page.extract_text(x_tolerance=3, y_tolerance=3)
                    if text:
                        # Better paragraph handling
                        lines = text.split('\n')
                        current_paragraph = []
                        
                        for line in lines:
                            line = line.strip()
                            if not line:  # Empty line indicates paragraph break
                                if current_paragraph:
                                    document += ' '.join(current_paragraph) + "\n\n"
                                    current_paragraph = []
                            else:
                                # Check if this line is likely a continuation of previous
                                if current_paragraph and not line[0].isupper() and not line[0].isdigit():
                                    current_paragraph.append(line)
                                else:
                                    # If previous paragraph exists, add it
                                    if current_paragraph:
                                        document += ' '.join(current_paragraph) + "\n\n"
                                    # Start new paragraph
                                    current_paragraph = [line]
                        
                        # Add final paragraph if exists
                        if current_paragraph:
                            document += ' '.join(current_paragraph) + "\n\n"

            if not document:
                raise ValueError("No text was extracted from the PDF")
            
            # Apply enhanced PDF cleanup
            document = self.fix_pdf_extraction_issues(document)
            return document
        except Exception as e:
            print(f"Error reading PDF: {e}")
            return ""

    def fix_pdf_extraction_issues(self, text):
        """
        Advanced PDF extraction fixes with better handling of various issues
        """
        # Fix CamelCase and hyphenation
        text = re.sub(r'([a-z])([A-Z])', r'\1 \2', text)
        
        # Fix missing spaces after punctuation
        text = re.sub(r'([.,;:!?\)])([a-zA-Z0-9])', r'\1 \2', text)
        text = re.sub(r'([a-zA-Z0-9])(\()', r'\1 \2', text)
        
        # Fix spaces around citations and references like [6]
        text = re.sub(r'(\w)(\[\d+\])', r'\1 \2', text)
        text = re.sub(r'(\[\d+\])(\w)', r'\1 \2', text)
        
        # Fix hyphenated words at line breaks that should be joined
        text = re.sub(r'(\w+)-\s*\n\s*(\w+)', r'\1\2', text)
        
        # Fix split words (common OCR issue)
        text = re.sub(r'(\w+)\s+(?=[a-z]{1,3}\s+[A-Z])', r'\1', text)
        
        # Handle bulleted lists better
        text = re.sub(r'(\n\s*[•·-]\s*)', r'\n• ', text)
        
        # Handle page numbers and headers/footers
        text = re.sub(r'\n\s*\d+\s*\n', r'\n', text)
        
        # Fix tables (basic approach)
        text = re.sub(r'(\S)\s{3,}(\S)', r'\1 | \2', text)
        
        # Remove excessive whitespace
        text = re.sub(r'\s+', ' ', text)
        
        # Fix common PDF extraction errors for Indonesian
        text = text.replace(' nya ', ' -nya ')
        text = text.replace(' lah ', ' -lah ')
        text = text.replace(' pun ', ' -pun ')
        
        # Fix common spacing issues around punctuation
        text = text.replace(' .', '.')
        text = text.replace(' ,', ',')
        text = text.replace(' :', ':')
        text = text.replace(' ;', ';')
        text = text.replace('( ', '(')
        text = text.replace(' )', ')')
        
        return text.strip()

    def preprocess_text(self, text: str) -> str:
        """
        Enhanced text preprocessing with better content preservation
        """
        # Keep more special characters that might be important for context
        text = re.sub(r'[^\w\s\.,;:!?\'"\(\)\[\]\-–—]', ' ', text)
        
        # Fix CamelCase issues (words without spaces)
        text = re.sub(r'([a-z])([A-Z])', r'\1 \2', text)
        
        # Better paragraph handling - preserve section breaks
        paragraphs = re.split(r'\n\s*\n', text)
        processed_paragraphs = []
        
        for para in paragraphs:
            # Keep section headers (usually short lines followed by empty lines)
            if len(para.strip()) < 100 and para.strip().isupper():
                processed_paragraphs.append(para.strip())
                continue
                
            # Clean paragraph
            clean_para = re.sub(r'\s+', ' ', para).strip()
            if clean_para:
                processed_paragraphs.append(clean_para)
                
        return '\n\n'.join(processed_paragraphs)
    
    def create_semantic_chunks(self, text: str, chunk_size: int = 450, overlap: int = 200) -> list:
        """
        Create larger chunks with more context and better semantic meaning preservation
        """
        # Split into paragraphs first to preserve structure
        paragraphs = text.split('\n\n')
        chunks = []
        current_chunk = []
        current_size = 0
        
        for paragraph in paragraphs:
            paragraph = paragraph.strip()
            if not paragraph:
                continue
                
            # Check if it's a section header
            is_header = len(paragraph) < 100 and (paragraph.isupper() or 
                                              paragraph.endswith(':') or 
                                              not paragraph.endswith('.'))
            
            # Get approximate size (word count)
            paragraph_size = len(paragraph.split())
            
            # Handle section headers separately to preserve document structure
            if is_header:
                # If we have content in the current chunk, save it before the new section
                if current_chunk:
                    chunks.append('\n\n'.join(current_chunk))
                    current_chunk = []
                    current_size = 0
                
                current_chunk.append(paragraph)
                current_size = paragraph_size
                continue
            
            # If adding this whole paragraph keeps us under chunk_size, add it all
            if current_size + paragraph_size <= chunk_size:
                current_chunk.append(paragraph)
                current_size += paragraph_size
            else:
                # Process sentence by sentence
                sentences = sent_tokenize(paragraph)
                
                # If a single sentence is too long, we need to handle it specially
                if len(sentences) == 1 and paragraph_size > chunk_size:
                    if current_chunk:
                        chunks.append('\n\n'.join(current_chunk))
                    # Split very long sentence into chunks (last resort)
                    words = paragraph.split()
                    for i in range(0, len(words), chunk_size):
                        chunks.append(' '.join(words[i:i+chunk_size]))
                    current_chunk = []
                    current_size = 0
                    continue
                
                for sentence in sentences:
                    sentence_size = len(sentence.split())
                    
                    # If adding this sentence exceeds chunk size and we already have content
                    if current_size + sentence_size > chunk_size and current_chunk:
                        # Join current chunk and add to chunks
                        chunks.append('\n\n'.join(current_chunk))
                        
                        # For overlap, keep some paragraphs from the end
                        # that contain approximately 'overlap' words
                        words_so_far = 0
                        overlap_start = len(current_chunk)
                        
                        for i in range(len(current_chunk)-1, -1, -1):
                            para_words = len(current_chunk[i].split())
                            words_so_far += para_words
                            if words_so_far >= overlap:
                                overlap_start = i
                                break
                        
                        current_chunk = current_chunk[overlap_start:]
                        current_size = sum(len(p.split()) for p in current_chunk)
                    
                    # Add current sentence
                    if not current_chunk:
                        current_chunk = [sentence]
                    else:
                        if sentence_size > chunk_size//2:  # If sentence is very large
                            current_chunk.append(sentence)  # Add as separate paragraph
                        else:
                            # Try to maintain paragraph structure
                            if current_chunk[-1].endswith(('.', '!', '?')):
                                current_chunk.append(sentence)
                            else:
                                current_chunk[-1] = current_chunk[-1] + ' ' + sentence
                    current_size += sentence_size
        
        # Add the last chunk if it has content
        if current_chunk:
            chunks.append('\n\n'.join(current_chunk))
            
        return chunks
    
    def retrieve_relevant_chunks(self, question: str, chunks: list, top_k: int = 8) -> list:
        """
        Enhanced retrieval with better semantic search
        """
        # Clean and enhance question
        processed_question = self.enhance_question(question)
        
        # Encode the question and chunks
        question_embedding = self.embedding_model.encode([processed_question])[0]
        chunk_embeddings = self.embedding_model.encode(chunks)
        
        # Calculate similarities
        similarities = cosine_similarity([question_embedding], chunk_embeddings)[0]
        
        # Get top-k chunks
        top_indices = np.argsort(similarities)[-top_k:][::-1]
        
        # Return relevant chunks with scores
        return [{"chunk": chunks[i], "relevance": float(similarities[i])} for i in top_indices]
    
    def enhance_question(self, question: str) -> str:
        """
        Enhance question to improve retrieval
        """
        # Clean the question
        question = question.strip()
        
        # Add directive for better answers if not present
        directives = ["berikan", "jelaskan", "explain", "give", "provide", "describe"]
        if not any(question.lower().startswith(d) for d in directives) and not question.endswith('?'):
            # Add question mark if missing
            if not question.endswith('?'):
                question += '?'
            
            # Detect language and add appropriate directive
            if any(w in question.lower() for w in self.indonesian_stopwords):
                question = f"Jelaskan secara lengkap: {question}"
            else:
                question = f"Explain thoroughly: {question}"
        
        return question
    
    def extract_full_answer(self, context: str, question: str, answer_span: str) -> str:
        """
        Extract a more complete answer with better context preservation
        """
        # First check if the answer is already a complete sentence or paragraph
        if answer_span.strip().endswith(('.', '!', '?')) and len(answer_span.split()) > 10:
            # Check if it starts with lowercase and no capital letter
            if answer_span[0].islower() and not any(c.isupper() for c in answer_span):
                # Probably incomplete - find containing sentence
                pass
            else:
                # It's likely complete
                return answer_span
            
        # Try to find the complete sentence(s) containing the answer
        sentences = sent_tokenize(context)
        
        # Find sentences containing the answer_span
        containing_sentences = [s for s in sentences if answer_span in s]
        
        if containing_sentences:
            # If it's an essay question, we might want to include surrounding sentences
            # Check if question suggests an essay answer (look for keywords)
            essay_keywords = ['explain', 'describe', 'discuss', 'elaborate', 'analyze', 'why',
                              'compare', 'contrast', 'evaluate', 'jelaskan', 'terangkan', 'mengapa',
                              'bahas', 'bandingkan', 'analisis', 'bagaimana', 'how']
            
            is_essay_question = any(keyword in question.lower() for keyword in essay_keywords)
            
            # For essay questions, include more context
            if is_essay_question:
                # Find the index of the first containing sentence
                for i, sentence in enumerate(sentences):
                    if answer_span in sentence:
                        # Include sentences before and after for context
                        # Adjust the range based on the complexity of the question
                        question_complexity = sum(1 for k in essay_keywords if k in question.lower())
                        context_range = min(5, 2 + question_complexity)  # More context for complex questions
                        
                        start_idx = max(0, i - context_range)
                        end_idx = min(len(sentences), i + context_range + 1)
                        
                        return ' '.join(sentences[start_idx:end_idx])
            
            # For non-essay questions or fallback
            return ' '.join(containing_sentences)
        
        # If we can't find the sentence, try to extract a paragraph
        paragraphs = context.split('\n\n')
        for paragraph in paragraphs:
            if answer_span in paragraph:
                # Check if the paragraph is too long
                para_sentences = sent_tokenize(paragraph)
                if len(para_sentences) > 12:  # Increased threshold for better context
                    # Find the sentence with the answer and include some context
                    for i, sentence in enumerate(para_sentences):
                        if answer_span in sentence:
                            start_idx = max(0, i - 3)
                            end_idx = min(len(para_sentences), i + 4)
                            return ' '.join(para_sentences[start_idx:end_idx])
                return paragraph
        
        # If all else fails, return the original answer span
        return answer_span
    
    def validate_answer(self, answer: str, question: str) -> bool:
        """
        Validate if an answer is complete and good quality
        """
        # Skip validation for very short questions - they might have short answers
        if len(question.split()) < 5:
            return True
            
        # Check if answer is too short (adjusted for complexity)
        question_words = len(question.split())
        min_answer_words = min(3, question_words // 3)
        
        # Too short (basic heuristic)
        if len(answer.split()) < min_answer_words:
            return False
            
        # Check if answer ends abruptly (no ending punctuation)
        if not answer.strip().endswith(('.', '!', '?', '"', '\'', ')', ']', ':')):
            return False
            
        # Check if answer starts with conjunctions or other words suggesting incompleteness
        starting_words = ['dan', 'atau', 'tetapi', 'namun', 'karena', 'sebab', 'sehingga', 
                          'and', 'or', 'but', 'however', 'because', 'therefore', 'thus',
                          'although', 'meskipun', 'walaupun', 'since', 'so', 'for', 'nor', 'yet']
                          
        # First word check (but allow for quoted text)
        first_word = answer.lower().strip().split()[0] if answer.strip() else ""
        if first_word and first_word[0] not in "\"'([{" and any(first_word == word for word in starting_words):
            return False
            
        return True
    
    def format_answer_text(self, text: str) -> str:
        """
        Format answer text for better readability
        """        
        # Fix CamelCase (words without spaces)
        text = re.sub(r'([a-z])([A-Z])', r'\1 \2', text)
        
        # Fix missing spaces after punctuation
        text = re.sub(r'([.,;:!?])([a-zA-Z])', r'\1 \2', text)
        
        # Fix common spacing issues
        text = text.replace(' .', '.').replace(' ,', ',').replace(' :', ':')
        text = text.replace(' ;', ';').replace('( ', '(').replace(' )', ')')
        
        # Handle quotes more consistently
        text = re.sub(r'([\'"])(\s+)', r'\1', text)  # No space after opening quote
        text = re.sub(r'(\s+)([\'"])', r'\2', text)  # No space before closing quote
        
        # Normalize whitespace
        text = re.sub(r'\s+', ' ', text).strip()
        
        # Make first letter uppercase if it's a complete sentence
        if text and text[0].isalpha() and text[0].islower() and text[-1] in '.!?':
            text = text[0].upper() + text[1:]
            
        return text
    
    def rerank_results(self, results, question):
        """
        Rerank results using cross-encoder
        """
        if not results or len(results) <= 1:
            return results
            
        # Prepare pairs for cross-encoder
        pairs = [(question, result['answer']) for result in results]
        
        # Get cross-encoder scores
        rerank_scores = self.cross_encoder.predict(pairs)
        
        # Update scores
        for i, result in enumerate(results):
            # Balance between retrieval confidence and answer quality
            old_score = result['combined_score']
            cross_encoder_score = float(rerank_scores[i])
            
            # Combine scores (weighted average)
            result['reranker_score'] = cross_encoder_score
            result['final_score'] = 0.5 * old_score + 0.5 * cross_encoder_score
            
        # Sort by final score
        results.sort(key=lambda x: x['final_score'], reverse=True)
        
        return results
    
    def answer_question(self, context: str, question: str, top_k: int = 3) -> list:
        """
        Answer a question given a context, with improved ranking and extraction
        """
        if not context:
            print("Context is empty. Cannot answer the question.")
            return []
            
        # Store current question
        self.last_question = question
        
        # Preprocess the context
        processed_context = self.preprocess_text(context)
        
        # Create semantic chunks with larger size for better context
        chunks = self.create_semantic_chunks(processed_context, chunk_size=450, overlap=200)
        
        # Retrieve relevant chunks with improved scoring
        relevant_chunks = self.retrieve_relevant_chunks(question, chunks, top_k=min(8, len(chunks)))
        
        # Get answers from each relevant chunk
        all_results = []
        for chunk_info in relevant_chunks:
            chunk = chunk_info["chunk"]
            relevance = chunk_info["relevance"]
            
            try:
                # Handle overly long chunks by splitting if necessary
                if len(chunk.split()) > 500:  # If chunk is very large
                    subchunks = self.create_semantic_chunks(chunk, chunk_size=400, overlap=100)
                    # Use the most relevant subchunk
                    subchunk_scores = cosine_similarity(
                        [self.embedding_model.encode([question])[0]], 
                        self.embedding_model.encode(subchunks)
                    )[0]
                    best_subchunk_idx = np.argmax(subchunk_scores)
                    chunk = subchunks[best_subchunk_idx]
                
                # Get answer from QA pipeline with better handling of impossible answers
                qa_result = self.qa_pipeline({
                    'question': question,
                    'context': chunk,
                })
                
                # If confidence is too low, might be impossible question
                if qa_result['score'] < 0.1:
                    answer = "No answer found in the given context."
                    is_valid = False
                    combined_score = 0.1
                else:
                    # Extract a more complete answer
                    full_answer = self.extract_full_answer(chunk, question, qa_result['answer'])
                    
                    # Format the answer for better readability
                    formatted_answer = self.format_answer_text(full_answer)
                    
                    # Validate the answer
                    is_valid = self.validate_answer(formatted_answer, question)
                    
                    # Adjust score based on answer quality
                    answer_quality = 1.0 if is_valid else 0.5
                    
                    # Combine QA confidence with retrieval relevance and answer quality
                    combined_score = 0.5 * qa_result['score'] + 0.4 * relevance + 0.1 * answer_quality
                    
                    answer = formatted_answer
                
                all_results.append({
                    'answer': answer,
                    'original_answer': qa_result['answer'],
                    'qa_score': qa_result['score'],
                    'retrieval_score': relevance,
                    'combined_score': combined_score,
                    'chunk': chunk,
                    'is_valid': is_valid
                })
                
            except Exception as e:
                print(f"Error processing chunk: {e}")
        
        # Additional reranking with cross-encoder for better results
        all_results = self.rerank_results(all_results, question)
        
        # Filter out invalid answers if we have enough valid ones
        valid_results = [r for r in all_results if r['is_valid']]
        
        if len(valid_results) >= top_k:
            return valid_results[:top_k]
        else:
            # Fall back to all results if not enough valid ones
            return all_results[:top_k]
    
    def process_pdf_question(self, pdf_path: str, question: str, top_k: int = 3):
        """
        Process a question for a specific PDF file
        """
        # Read the PDF with improved extraction
        context = self.read_pdf(pdf_path)
        
        if not context:
            print("Failed to extract text from PDF.")
            return []
            
        # Get answers
        return self.answer_question(context, question, top_k)
    
    def print_answers(self, results: list):
        """
        Print answers in a reader-friendly format
        """
        if not results:
            print("No answers found.")
            return
            
        print("\n===== ANSWERS =====\n")
        for i, result in enumerate(results):
            score_key = 'final_score' if 'final_score' in result else 'combined_score'
            print(f"Answer {i+1} (Score: {result[score_key]:.2f}):")
            print("-" * 50)
            print(result['answer'])
            print("\n" + "-" * 50)
            
        # Print best answer with context
        best = results[0]
        print("\nBEST ANSWER:")
        print("=" * 50)
        print(best['answer'])
        print("\nFrom context:")
        print("-" * 50)
        highlighted = self.get_context_with_highlights(self.format_answer_text(best['chunk']), best['original_answer'])
        print(highlighted)
        print("=" * 50)
    
    def get_context_with_highlights(self, chunk: str, answer: str) -> str:
        """
        Return context with highlighted answer for better readability
        """
        # Format the answer for better matching
        formatted_answer = self.format_answer_text(answer)
        
        if formatted_answer in chunk:
            highlighted = chunk.replace(formatted_answer, f"**{formatted_answer}**")
            return highlighted
        
        # Try with original answer if formatted doesn't match
        if answer in chunk:
            highlighted = chunk.replace(answer, f"**{answer}**")
            return highlighted
            
        # If exact match fails, try to find the approximate position
        words = answer.split()
        if len(words) >= 3:
            # Use first and last words as anchors
            first_word = words[0]
            last_word = words[-1]
            
            start_pos = chunk.find(first_word)
            end_pos = chunk.find(last_word, start_pos) + len(last_word)
            
            if start_pos >= 0 and end_pos > start_pos:
                approx_context = chunk[:start_pos] + f"**{chunk[start_pos:end_pos]}**" + chunk[end_pos:]
                return approx_context
            
        # Return the original if highlighting fails
        return chunk



