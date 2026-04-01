<?php

return [
    'subscribe' => [
    ],
    'listen' => [
        // 确认 UserCallbackRecord 事件绑定了对应的监听器
        'UserCallbackRecord' => [
            \app\listener\UserCallbackRecordListener::class, // 替换为你实际的监听器命名空间
        ],
    ],
];
