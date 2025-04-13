import google.generativeai as genai
import openai
import re
with open('hidden.txt') as file:
    openai.api_key = file.read()


with open('hidden2.txt') as file:
    genai_api_key = file.read()

# with open('hidden3.txt') as file:
#     deepseek_api_key = file.read()
    
    
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
                max_output_tokens=100
            ),
        )
                 
        if response.text:
            # Batasi output maksimal 50 kata
            max_words = 150
            limited_text = " ".join(response.text.split()[:max_words])
            return format_response(limited_text)
        else:
            return "Gemini returned an empty response."
        
    # elif model.lower() == "deepseek":
    #     response = deepseek_api_key.chat.completions.create(
    #         model="deepseek-chat",
    #         messages=[{"role": "system", "content": "You are a helpful assistant"},
    #                   {"role": "user", "content": prompt}],
    #         stream=False
    #     )
    #     return response.choices[0].message.content
        
    else:
        return "Model not supported."

