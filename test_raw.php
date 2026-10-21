<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Test Raw IA</title></head>
<body>
<h2>🔬 Respuesta RAW de ia_handler.php</h2>
<p>Si aparece HTML de error PHP aquí abajo, lo veremos claramente:</p>
<pre id="output" style="background:#f3f4f6;padding:15px;border-radius:8px;"></pre>
<script>
fetch('api/ia_handler.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ pregunta: '¿Cuánto cuesta el Galaxy A55?' })
})
.then(res => res.text())  // texto plano, no JSON — para ver el error exacto
.then(txt => {
    document.getElementById('output').textContent = txt;
})
.catch(err => {
    document.getElementById('output').textContent = 'Error de red: ' + err.message;
});
</script>
</body>
</html>
