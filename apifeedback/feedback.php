<?php
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

function respond(int $statusCode, array $payload): never
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$config = [];
$configPath = __DIR__ . '/config.php';
if (is_file($configPath)) {
    $loaded = require $configPath;
    if (is_array($loaded)) {
        $config = $loaded;
    }
}

$readConfig = static function (string $key, ?string $default = null) use ($config): ?string {
    $env = getenv($key);
    if ($env !== false && $env !== '') {
        return $env;
    }

    if (array_key_exists($key, $config) && $config[$key] !== '') {
        return (string) $config[$key];
    }

    return $default;
};

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOriginsRaw = (string) $readConfig('ALLOWED_ORIGINS', '');
$allowedOrigins = array_values(array_filter(array_map(
    static fn (string $value): string => rtrim(trim($value), '/'),
    explode(',', $allowedOriginsRaw)
)));

if ($origin !== '') {
    $normalizedOrigin = rtrim($origin, '/');
    // If ALLOWED_ORIGINS is empty, accept any origin during setup.
    if (empty($allowedOrigins) || in_array($normalizedOrigin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $normalizedOrigin);
        header('Vary: Origin');
    } else {
        respond(403, ['ok' => false, 'message' => 'Origem nao permitida por CORS.']);
    }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($method !== 'POST') {
    respond(405, ['ok' => false, 'message' => 'Metodo nao permitido.']);
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || trim($rawBody) === '') {
    respond(400, ['ok' => false, 'message' => 'Corpo da requisicao vazio.']);
}

$data = json_decode($rawBody, true);
if (!is_array($data)) {
    respond(400, ['ok' => false, 'message' => 'JSON invalido.']);
}

$nome = trim((string) ($data['nome'] ?? ''));
$email = trim((string) ($data['email'] ?? ''));
$telefone = trim((string) ($data['telefone'] ?? ''));
$resposta1 = (int) ($data['resposta1'] ?? 0);
$resposta2 = (int) ($data['resposta2'] ?? 0);
$resposta3 = (int) ($data['resposta3'] ?? 0);
$resposta4 = trim((string) ($data['resposta4'] ?? ''));
$receberNovidades = !empty($data['receber_novidades']) ? 1 : 0;
$evento = trim((string) ($data['evento'] ?? 'indefinido'));
$dataRegistro = date('Y-m-d H:i:s');

if ($nome === '' || $email === '' || $telefone === '') {
    respond(422, ['ok' => false, 'message' => 'Nome, email e telefone sao obrigatorios.']);
}

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    respond(422, ['ok' => false, 'message' => 'Email invalido.']);
}

if (
    $resposta1 < 1 || $resposta1 > 5 ||
    $resposta2 < 1 || $resposta2 > 5 ||
    $resposta3 < 1 || $resposta3 > 5
) {
    respond(422, ['ok' => false, 'message' => 'As avaliacoes devem estar entre 1 e 5.']);
}

if (mb_strlen($nome) > 120 || mb_strlen($telefone) > 30 || mb_strlen($evento) > 120) {
    respond(422, ['ok' => false, 'message' => 'Campos excederam o tamanho permitido.']);
}

$dbHost = (string) $readConfig('DB_HOST', '');
$dbPort = (int) $readConfig('DB_PORT', '3306');
$dbName = (string) $readConfig('DB_NAME', '');
$dbUser = (string) $readConfig('DB_USER', '');
$dbPass = (string) $readConfig('DB_PASS', '');
$dbCharset = (string) $readConfig('DB_CHARSET', 'utf8mb4');
$appDebug = filter_var((string) $readConfig('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN);

if ($dbHost === '' || $dbName === '' || $dbUser === '') {
    respond(500, ['ok' => false, 'message' => 'Configuracao de banco incompleta.']);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
    $mysqli->set_charset($dbCharset);

    $sql = 'INSERT INTO feedback_workshop (
        nome, telefone, email, data, resposta1, resposta2, resposta3, resposta4, evento, receber_novidades
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param(
        'ssssiiissi',
        $nome,
        $telefone,
        $email,
        $dataRegistro,
        $resposta1,
        $resposta2,
        $resposta3,
        $resposta4,
        $evento,
        $receberNovidades
    );
    $stmt->execute();

    respond(201, [
        'ok' => true,
        'message' => 'Feedback salvo com sucesso.',
        'id' => $mysqli->insert_id,
    ]);
} catch (mysqli_sql_exception $exception) {
    error_log('feedback.php SQL error: ' . $exception->getMessage());
    respond(500, [
        'ok' => false,
        'message' => 'Falha ao gravar feedback no banco.',
        'debug' => $appDebug ? $exception->getMessage() : null,
    ]);
} catch (Throwable $exception) {
    error_log('feedback.php general error: ' . $exception->getMessage());
    respond(500, [
        'ok' => false,
        'message' => 'Erro interno ao processar feedback.',
        'debug' => $appDebug ? $exception->getMessage() : null,
    ]);
}
