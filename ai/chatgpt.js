import readline from "readline";
import fetch from "node-fetch";

const API_KEY = process.env.OPENAI_API_KEY;

if (!API_KEY) {
    console.error("Falta OPENAI_API_KEY");
    process.exit(1);
}

const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout
});

async function ask(prompt) {

    const response = await fetch("https://api.openai.com/v1/responses", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Authorization": `Bearer ${API_KEY}`
        },
        body: JSON.stringify({
            model: "gpt-5",
            input: prompt
        })
    });

    const data = await response.json();
    console.log("\nAI:\n");
    console.log(data.output_text);
}

function loop() {
    rl.question("\nTú: ", async (q) => {
        await ask(q);
        loop();
    });
}

console.log("AI CLI listo.");
loop();