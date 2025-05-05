import google.generativeai as genai
import openai
from together import Together


with open('hidden.txt') as file:
    openai.api_key = file.read()


with open('hidden2.txt') as file:
    genai_api_key = file.read()

with open('hidden3.txt') as file:
    llama_api_key = file.read()
    
    
genai.configure(api_key=genai_api_key)


def get_llm_response(model, prompt, temp=0.7, top_p=0.7, freq_penalty=0.5, pres_penalty=0.5):
    

    if model.lower() == "openai":
        response = openai.chat.completions.create(
            model='gpt-3.5-turbo',
            messages=[
                {"role": "user", "content": prompt},
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
        return text
    elif model.lower() == "gemini":
        model = genai.GenerativeModel("gemini-2.0-flash")
        response = model.generate_content(
            contents=[
                {"role": "user", "parts": prompt}
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
        
        return text
        
    elif model.lower() == "llama":
        client = Together(api_key=llama_api_key)

        response = client.chat.completions.create(
            model="meta-llama/Llama-4-Scout-17B-16E-Instruct",
            messages=[{"role": "user", "content": prompt}]
        )
        text = response.choices[0].message.content
        return text
         
    else:
        return "Model not supported."
