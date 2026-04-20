<?php

return [
    'DB_HOST' => 'mysql.seudominio.com.br',
    'DB_PORT' => '3306',
    'DB_NAME' => 'nome_do_banco',
    'DB_USER' => 'usuario_do_banco',
    'DB_PASS' => 'senha_do_banco',
    'DB_CHARSET' => 'utf8mb4',

    // Separe multiplas origens por virgula.
    // Exemplo com producao e teste local:
    // https://feedbackkazza.netlify.app,http://127.0.0.1:5500,http://localhost:5500
    'ALLOWED_ORIGINS' => 'https://feedbackkazza.netlify.app',
];
