import openai
import pdfplumber
from reportlab.lib.pagesizes import letter
from reportlab.pdfgen import canvas
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfbase import pdfmetrics
from io import BytesIO

# Registrasi font Noto Sans CJK JP (ganti path dengan path lokasi font di komputer Anda)
pdfmetrics.registerFont(
    TTFont('NotoSansJP', 'Noto_Sans_JP/static/NotoSansJP-Regular.ttf'))

# Fungsi untuk mendapatkan respons dari API OpenAI untuk penerjemahan
with open('hidden.txt') as file:
    openai.api_key = file.read()


def get_api_response_translate(prompt: str) -> str | None:
    text: str | None = None
    try:
        response: dict = openai.chat.completions.create(
            model='gpt-3.5-turbo',
            messages=prompt,
            temperature=0.9,
            top_p=1,
            frequency_penalty=0,
            presence_penalty=0.6,
            stop=['Human:', 'AI:']
        )
        choices: dict = response.choices[0]
        text = choices.message.content
    except Exception as e:
        print('ERROR:', e)
    return str(text)


def get_bot_response_translate(message: str, pl: list[str]) -> str:
    pl.append({"role": "user", "content": message})
    bot_response: str = get_api_response_translate(pl)
    if bot_response:
        return bot_response
    else:
        return 'Something went wrong...'


def translateOpenAI(text, language):
    prompt_list: list[str] = [
        {"role": "system",
            "content": """
                Anda adalah seorang bot yang akan melakukan translate potongan halaman PDF ke dalam bahasa """ + language + """ , pastikan terjemahan relevan dengan topik dan kata - kata disusun dengan padu.
            """
         },
    ]
    response: str = get_bot_response_translate(text, prompt_list)
    return str(response)


def read_pdf(file_path):
    with pdfplumber.open(file_path) as pdf:
        pages_text = []
        for page in pdf.pages:
            pages_text.append(page.extract_text())
    return pages_text

# Fungsi untuk membuat PDF dalam memori dan mengembalikannya sebagai objek BytesIO


def create_pdf_in_memory(translated_pages):
    pdf_buffer = BytesIO()
    c = canvas.Canvas(pdf_buffer, pagesize=letter)
    width, height = letter
    margin = 40
    max_line_width = width - 2 * margin

    for page_text in translated_pages:
        # Menggunakan font yang mendukung karakter Jepang
        c.setFont("NotoSansJP", 10)
        lines = page_text.split("\n")
        y_position = height - margin

        for line in lines:
            if line.strip() == "":
                y_position -= 12
                continue

            words = line.split()
            line_to_draw = ""

            for word in words:
                if c.stringWidth(line_to_draw + word, "NotoSansJP", 10) < max_line_width:
                    line_to_draw += word + " "
                else:
                    c.drawString(margin, y_position, line_to_draw)
                    line_to_draw = word + " "
                    y_position -= 12

            if line_to_draw.strip() != "":
                c.drawString(margin, y_position, line_to_draw)
                y_position -= 12

            if y_position < margin:
                c.showPage()
                y_position = height - margin

        c.showPage()
    c.save()
    pdf_buffer.seek(0)  # Set posisi ke awal agar bisa dibaca dari awal
    return pdf_buffer


def main_translate(file_path: str, language: str):
    # Langkah 1: Baca teks dari setiap halaman PDF
    print("Membaca file PDF...")
    pages_text = read_pdf(file_path)
    # Langkah 2: Terjemahkan setiap halaman
    # pages_text = pages_text[:2]
    translated_pages = []
    print("Menerjemahkan halaman PDF...")
    for page_text in pages_text:
        translated_text = translateOpenAI(page_text, language)
        translated_pages.append(translated_text)
        print(f"Halaman diterjemahkan:\n{translated_text}\n")

    # Langkah 3: Buat PDF terjemahan di memori
    print("Membuat file PDF terjemahan di memori...")
    pdf_buffer = create_pdf_in_memory(translated_pages)
    return pdf_buffer
