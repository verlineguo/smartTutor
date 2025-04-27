import google.generativeai as genai
import openai
import re
from together import Together



with open('hidden.txt') as file:
    openai.api_key = file.read()


with open('hidden2.txt') as file:
    genai_api_key = file.read()

with open('hidden3.txt') as file:
    llama_api_key = file.read()
    
    
genai.configure(api_key=genai_api_key)

def format_response(text):
    """
    Post-process LLM response to ensure consistent formatting for web display
    """
    # Remove any excessive whitespace
    text = re.sub(r'\s{3,}', '\n\n', text)
    
    # Ensure proper list formatting
    text = re.sub(r'(?m)^[ \t]*•[ \t]*(.+)$', r'* \1', text)  # Convert • bullets to * bullets
    text = re.sub(r'(?m)^[ \t]*\*(?!\*)[ \t]*(.+)$', r'* \1', text)  # Fix spacing after * bullets
    text = re.sub(r'(?m)^[ \t]*(\d+)\.[ \t]*(.+)$', r'\1. \2', text)  # Fix numbered lists
    
    # Ensure consistent heading formatting
    text = re.sub(r'(?m)^(#+)([^ #])', r'\1 \2', text)  # Add space after # in headings
    
    # Ensure paragraphs are separated by blank lines
    text = re.sub(r'(?m)(\w+[.!?])[ \t]*\n(?=\w)', r'\1\n\n', text)
    
    # Make sure there are no triple+ newlines (compress to double)
    text = re.sub(r'\n{3,}', '\n\n', text)
    
    # Fix any inconsistent indentation
    lines = text.split('\n')
    cleaned_lines = [line.strip() for line in lines]
    text = '\n'.join(cleaned_lines)
    
    return text

def get_llm_response(model, prompt, temp=0.7, top_p=0.7, freq_penalty=0.5, pres_penalty=0.5):
    formatting_instructions = """
    Please format your response using the following guidelines:
    - Use proper markdown formatting for lists and headings
    - Use consistent bullet points or numbering for lists
    - Structure information hierarchically with clear headings
    - Separate paragraphs with blank lines
    """
    enhanced_prompt = formatting_instructions + "\n\n" + prompt

    if model.lower() == "openai":
        response = openai.chat.completions.create(
            model='gpt-3.5-turbo',
            messages=[
                {"role": "system", "content": enhanced_prompt},
            ],
            temperature=temp,
            # max_tokens=300,
            top_p=top_p,
            frequency_penalty=freq_penalty,
            presence_penalty=pres_penalty,
            stop=['Human:', 'AI:']
        )
        choices: dict = response.choices[0]
        text = choices.message.content
        return format_response(text)
    elif model.lower() == "gemini":
        model = genai.GenerativeModel("gemini-2.0-flash")
        response = model.generate_content(
            contents=[
                {"role": "user", "parts": f"{enhanced_prompt}"}
            ],
            generation_config=genai.types.GenerationConfig(
                temperature=temp,
                top_p=top_p,
            ),
        )
        try:
            text = response.text
        except AttributeError:
            text = response.candidates[0].content.parts[0].text
        
        return format_response(text)
        
    elif model.lower() == "llama":
        client = Together(api_key=llama_api_key)

        response = client.chat.completions.create(
            model="meta-llama/Llama-4-Scout-17B-16E-Instruct",
            messages=[{"role": "user", "content": enhanced_prompt}]
        )
        text = response.choices[0].message.content
        return format_response(text)

        
        
    else:
        return "Model not supported."

