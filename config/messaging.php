<?php

return [
    /*
     * When a company marks an application Declined (status "rejected"):
     * true keeps the company chat open so the student can ask for feedback;
     * false locks it like Withdrawn (old messages stay readable).
     */
    'declined_keeps_chat_open' => (bool) env('MESSAGING_DECLINED_KEEPS_CHAT_OPEN', false),
];
