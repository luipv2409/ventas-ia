<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test IA — Fase 3</title>
    <style>
        body { font-family: sans-serif; max-width: 700px; margin: 40px auto; padding: 0 20px; }
        textarea { width: 100%; padding: 10px; font-size: 15px; border: 1px solid #ccc; border-radius: 6px; }
        button { margin-top: 10px; padding: 10px 24px; background: #4f46e5; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 15px; }
        button:hover { background: #4338ca; }
        #respuesta { margin-top: 20px; padding: 15px; background: #f0fdf4; border-left: 4px solid #22c55e; border-radius: 6px; white-space: pre-wrap; display: none; }
        #error { margin-top: 20px; padding: 15px; background: #fef2f2; border-left: 4px solid #ef4444; border-radius: 6px; display: none; }
        #loader { display: none; color: #6b7280; margin-top: 10px; }
    </style>
</head>
<body>
    <h2>🧠 Test de IA</h2>
    <p>Prueba el asistente con preguntas como:<br>
       <em>"¿Cuánto cuesta el Galaxy A55?"</em> o
       <em>"¿Qué auriculares tienen?"</em>
    </p>

    <textarea id="pregunta" rows="3" placeholder="Escribe tu pregunta aquí..."></textarea>
    <br>
    <button onclick="enviarPregunta()">Preguntar a la IA ✨</button>
    <p id="loader">⏳ Consultando a la IA...</p>
    <div id="respuesta"></div>
    <div id="error"></div>

    <script>
    async function enviarPregunta() {
        const pregunta = document.getElementById('pregunta').value.trim();
        if (!pregunta) { alert('Escribe una pregunta.'); return; }

        document.getElementById('loader').style.display = 'block';
        document.getElementById('respuesta').style.display = 'none';
        document.getElementById('error').style.display = 'none';

        try {
            const res = await fetch('api/ia_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ pregunta })
            });
            const data = await res.json();

            if (data.ok) {
                document.getElementById('respuesta').textContent = '🤖 IA (' + data.proveedor + '):\n\n' + data.respuesta;
                document.getElementById('respuesta').style.display = 'block';
            } else {
                document.getElementById('error').textContent = '❌ Error: ' + (data.error || 'Error desconocido');
                document.getElementById('error').style.display = 'block';
            }
        } catch (e) {
            document.getElementById('error').textContent = '❌ Error de red: ' + e.message;
            document.getElementById('error').style.display = 'block';
        } finally {
            document.getElementById('loader').style.display = 'none';
        }
    }

    // Enviar con Enter (Ctrl+Enter)
    document.getElementById('pregunta').addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && e.ctrlKey) enviarPregunta();
    });
    </script>
</body>
</html>
