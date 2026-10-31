(() => {
  const launcher = document.querySelector('#collins-chat-launcher');
  const panel = document.querySelector('#collins-chat-panel');
  if (!launcher || !panel) return;

  const form = panel.querySelector('#collins-chat-form');
  const input = panel.querySelector('#collins-chat-input');
  const messages = panel.querySelector('#collins-chat-messages');
  const status = panel.querySelector('#collins-chat-status');
  const sendButton = panel.querySelector('#collins-chat-send');
  const retryButton = panel.querySelector('#collins-chat-retry');
  const clearButton = panel.querySelector('#collins-chat-clear');
  const closeButton = panel.querySelector('#collins-chat-close');
  const suggestions = panel.querySelectorAll('[data-suggestion]');
  let history = [];
  let busy = false;

  function addMessage(role, text) {
    const item = document.createElement('div');
    item.className = `collins-chat-message collins-chat-message-${role}`;
    item.setAttribute('role', 'listitem');
    const label = document.createElement('span');
    label.className = 'collins-chat-label';
    label.textContent = role === 'assistant' ? 'Collins M Assistant' : 'You';
    const content = document.createElement('p');
    content.textContent = text;
    item.append(label, content);
    messages.append(item);
    messages.scrollTop = messages.scrollHeight;
  }

  function setBusy(value) {
    busy = value;
    sendButton.disabled = value;
    clearButton.disabled = value;
    input.disabled = value;
    panel.setAttribute('aria-busy', String(value));
    if (value) status.textContent = 'Collins M Assistant is replying…';
  }

  async function sendMessage() {
    if (busy) return;
    const message = input.value.trim();
    if (!message) {
      status.textContent = 'Type a message first.';
      input.focus();
      return;
    }
    if (message.length > 4000) {
      status.textContent = 'Please keep messages under 4,000 characters.';
      return;
    }

    retryButton.hidden = true;
    setBusy(true);
    try {
      const response = await fetch('api/chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ message, history: history.slice(-10) })
      });
      let data;
      try { data = await response.json(); }
      catch { throw new Error('The chat service returned an unreadable response.'); }
      if (!response.ok) throw new Error(data.error || 'The chat service is temporarily unavailable.');
      if (typeof data.reply !== 'string' || !data.reply.trim()) throw new Error('The chat service returned an empty reply.');

      addMessage('user', message);
      addMessage('assistant', data.reply.trim());
      history.push({ role: 'user', content: message }, { role: 'assistant', content: data.reply.trim() });
      history = history.slice(-10);
      input.value = '';
      retryButton.hidden = true;
      status.textContent = 'Reply received.';
    } catch (error) {
      status.textContent = `${error.message} Your message is still here; you can retry.`;
      retryButton.hidden = false;
    } finally {
      setBusy(false);
      input.focus();
    }
  }

  function setOpen(open) {
    panel.hidden = !open;
    launcher.setAttribute('aria-expanded', String(open));
    launcher.setAttribute('aria-label', open ? 'Close Collins M Assistant' : 'Open Collins M Assistant');
    if (open) input.focus();
    else launcher.focus();
  }

  launcher.addEventListener('click', () => setOpen(panel.hidden));
  closeButton?.addEventListener('click', () => setOpen(false));

  form.addEventListener('submit', event => {
    event.preventDefault();
    sendMessage();
  });
  input.addEventListener('keydown', event => {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      sendMessage();
    }
  });
  retryButton.addEventListener('click', sendMessage);
  suggestions.forEach(button => button.addEventListener('click', () => {
    if (busy) return;
    input.value = button.dataset.suggestion || '';
    sendMessage();
  }));
  clearButton.addEventListener('click', () => {
    history = [];
    messages.replaceChildren();
    addMessage('assistant', 'Hi! I can help with questions about Collins M phones. Ask me about Samsung or iPhone devices, and I’ll help where I can.');
    status.textContent = 'Chat cleared.';
    input.value = '';
    input.focus();
  });
})();
