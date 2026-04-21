<?php
$page_title = 'Care Chatbot';
$current_page = 'chatbot/index.php';
$extra_head = '<base href="../">';
include __DIR__ . '/../header.php';
require_once __DIR__ . '/chatbot_service.php';
?>

<style>
.chatbot-wrap { padding: 32px 0 56px; }
.chatbot-card {
    background: #fff;
    border: 1px solid rgba(148, 163, 184, .25);
    border-radius: 20px;
    box-shadow: 0 18px 36px rgba(15, 23, 42, .08);
    overflow: hidden;
}
.chatbot-top {
    background: linear-gradient(135deg, #1f3fa8, #2f6fff);
    color: #fff;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.chatbot-top img { width: 36px; height: 36px; object-fit: contain; }
.chatbot-disclaimer {
    margin: 0;
    padding: 14px 18px;
    background: #fff8db;
    border-bottom: 1px solid #f3e5ab;
    font-size: .93rem;
}
.chat-log {
    height: 420px;
    overflow-y: auto;
    padding: 18px;
    background: #f8fafc;
}
.chat-bubble {
    max-width: 85%;
    border-radius: 14px;
    padding: 12px 14px;
    margin-bottom: 12px;
    white-space: pre-wrap;
}
.chat-bubble.user {
    margin-left: auto;
    background: #dbeafe;
    color: #0f172a;
}
.chat-bubble.bot {
    margin-right: auto;
    background: #fff;
    border: 1px solid #e2e8f0;
}
.chat-form { padding: 14px; border-top: 1px solid #e2e8f0; background: #fff; }
.chat-footer-note {
    padding: 10px 16px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    font-size: .86rem;
    color: #475569;
}
</style>

<div class="container chatbot-wrap">
    <div class="chatbot-card">
        <div class="chatbot-top">
            <img src="assets/virtual-chikitsa-logo.svg" alt="Virtual-Chikitsa logo" onerror="this.style.display='none'">
            <div>
                <h1 class="h5 mb-0 fw-bold">Virtual-Chikitsa Assistant</h1>
                <small>Doctor specialty suggestions, medicine info, and slot availability</small>
            </div>
        </div>

        <p class="chatbot-disclaimer">
            <strong><?php echo htmlspecialchars(CHATBOT_DISCLAIMER); ?></strong><br>
            <?php echo htmlspecialchars(CHATBOT_CONSENT); ?>
        </p>

        <div id="chatLog" class="chat-log" aria-live="polite">
            <div class="chat-bubble bot">Hi! Share your symptoms, ask medicine details, or ask for available doctors/appointment slots.<?php echo "\n\n" . htmlspecialchars(CHATBOT_DISCLAIMER); ?></div>
        </div>

        <form id="chatForm" class="chat-form" autocomplete="off">
            <div class="input-group">
                <input id="chatInput" type="text" class="form-control" placeholder="Example: I have cough and fever" required>
                <button class="btn btn-primary" type="submit">Send</button>
            </div>
        </form>

        <div class="chat-footer-note">
            This assistant gives general health information only and is not a substitute for professional diagnosis or treatment.
        </div>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('chatForm');
    const input = document.getElementById('chatInput');
    const log = document.getElementById('chatLog');

    function addBubble(role, text) {
        const div = document.createElement('div');
        div.className = 'chat-bubble ' + role;
        div.textContent = text;
        log.appendChild(div);
        log.scrollTop = log.scrollHeight;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const message = input.value.trim();
        if (!message) {
            return;
        }

        addBubble('user', message);
        input.value = '';

        try {
            const res = await fetch('chatbot/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message })
            });

            const data = await res.json();
            if (!res.ok) {
                addBubble('bot', 'Unable to process your request right now. Please try again.');
                return;
            }

            addBubble('bot', data.answer + '\n\n' + data.disclaimer + '\n' + data.consent);
        } catch (error) {
            addBubble('bot', 'Connection issue. Please try again in a moment.');
        }
    });
})();
</script>

<?php include __DIR__ . '/../footer.php'; ?>
