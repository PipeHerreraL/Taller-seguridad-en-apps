/**
 * public/assets/js/chat.js
 * Lógica del lado del cliente para el widget del mini-chat virtual.
 * Se comunica con el endpoint /chat mediante solicitudes HTTP POST asíncronas.
 */
document.addEventListener('DOMContentLoaded', () => {
    const chatWidget = document.getElementById('clinica-chat-widget');
    if (!chatWidget) return;

    const toggleBtn = document.getElementById('chat-toggle-btn');
    const closeBtn = document.getElementById('chat-close-btn');
    const panel = document.getElementById('chat-panel');
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-input');
    const chatBody = document.getElementById('chat-body');
    const typingIndicator = document.getElementById('chat-typing-indicator');

    // 1. Alternar apertura y cierre del panel del chat
    toggleBtn.addEventListener('click', () => {
        panel.classList.toggle('chat-hidden');
        if (!panel.classList.contains('chat-hidden')) {
            input.focus();
            scrollToBottom();
        }
    });

    closeBtn.addEventListener('click', () => {
        panel.classList.add('chat-hidden');
    });

    // Cerrar si se presiona la tecla Escape dentro del panel
    panel.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            panel.classList.add('chat-hidden');
            toggleBtn.focus();
        }
    });

    // 2. Procesar el envío del formulario del chat
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const query = input.value.trim();
        if (!query) return;

        // Limpiar el input
        input.value = '';

        // Añadir mensaje del usuario a la vista
        appendMessage(query, 'user');
        scrollToBottom();

        // Mostrar indicador de escritura
        showTypingIndicator();

        try {
            const response = await fetch('chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: query })
            });

            if (!response.ok) {
                const errData = await response.json().catch(() => ({}));
                throw new Error(errData.error || `HTTP ${response.status}`);
            }

            const data = await response.json();
            hideTypingIndicator();

            if (data.response) {
                appendMessage(data.response, 'bot');
            } else {
                appendMessage('No he recibido una respuesta válida.', 'bot');
            }

        } catch (error) {
            hideTypingIndicator();
            appendMessage(`Error: ${error.message || 'No se pudo contactar al asistente. Inténtalo de nuevo.'}`, 'bot error');
        }

        scrollToBottom();
    });

    /**
     * Muestra la burbuja del mensaje en el chat
     */
    function appendMessage(text, sender) {
        const bubble = document.createElement('div');
        bubble.className = `chat-message ${sender}`;

        // Escapar texto para mitigar XSS de forma estricta
        const escaped = escapeHTML(text);

        // Renderizar un markdown simplificado para **negritas**, saltos de línea y viñetas
        const formatted = formatMarkdown(escaped);

        bubble.innerHTML = formatted;
        chatBody.appendChild(bubble);
    }

    /**
     * Escapa caracteres HTML
     */
    function escapeHTML(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Reemplaza patrones de markdown básicos de forma segura
     */
    function formatMarkdown(str) {
        let text = str;

        // Reemplazar saltos de línea por <br>
        text = text.replace(/\n/g, '<br>');

        // Reemplazar **negrita** por <strong>negrita</strong>
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

        // Reemplazar `código` por <code>código</code>
        text = text.replace(/`(.*?)`/g, '<code>$1</code>');

        // Reemplazar viñetas básicas como '👤 ' o '🩸 ' o '🧬 ' u otros emojis al inicio de línea
        // O viñetas clásicas '- ' o '* '
        text = text.replace(/(?:^|<br>)-\s(.*?)(?=$|<br>)/g, '$&').replace(/(?:^|<br>)\*\s(.*?)(?=$|<br>)/g, '$&');

        return text;
    }

    function showTypingIndicator() {
        typingIndicator.classList.remove('chat-hidden');
        scrollToBottom();
    }

    function hideTypingIndicator() {
        typingIndicator.classList.add('chat-hidden');
    }

    function scrollToBottom() {
        chatBody.scrollTop = chatBody.scrollHeight;
    }
});
