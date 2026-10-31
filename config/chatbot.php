<?php
declare(strict_types=1);

/* Set COLLINS_M_CHAT_WEBHOOK in the PHP/Apache environment to override this URL. */
const COLLINS_M_CHAT_WEBHOOK = 'http://127.0.0.1:5678/webhook/collins-m-chat';

function collins_m_chat_webhook(): string
{
    $configured = getenv('COLLINS_M_CHAT_WEBHOOK');
    return is_string($configured) && $configured !== '' ? $configured : COLLINS_M_CHAT_WEBHOOK;
}
