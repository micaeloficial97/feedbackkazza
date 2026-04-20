<?php
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

if ($method !== 'GET') {
    respond(405, ['ok' => false, 'message' => 'Metodo nao permitido.']);
}

$evento = trim((string) ($_GET['evento'] ?? ''));
if ($evento === '') {
    respond(422, ['ok' => false, 'message' => 'Evento obrigatorio.']);
}

if (mb_strlen($evento) > 120) {
    respond(422, ['ok' => false, 'message' => 'Evento excedeu o tamanho permitido.']);
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

$inicioDia = date('Y-m-d 00:00:00');
$fimDia = date('Y-m-d 00:00:00', strtotime('+1 day'));

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
    $mysqli->set_charset($dbCharset);

    $sql = 'SELECT nome
        FROM feedback_workshop
        WHERE LOWER(evento) = LOWER(?)
          AND data >= ?
          AND data < ?
        ORDER BY data ASC
        LIMIT 500';

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('sss', $evento, $inicioDia, $fimDia);
    $stmt->execute();

    $result = $stmt->get_result();
    $nomes = [];
    while ($row = $result->fetch_assoc()) {
        $nome = trim((string) ($row['nome'] ?? ''));
        if ($nome !== '') {
            $nomes[] = ['nome' => $nome];
        }
    }

    respond(200, [
        'ok' => true,
        'data' => $nomes,
    ]);
} catch (mysqli_sql_exception $exception) {
    error_log('nomes.php SQL error: ' . $exception->getMessage());
    respond(500, [
        'ok' => false,
        'message' => 'Falha ao buscar nomes no banco.',
        'debug' => $appDebug ? $exception->getMessage() : null,
    ]);
} catch (Throwable $exception) {
    error_log('nomes.php general error: ' . $exception->getMessage());
    respond(500, [
        'ok' => false,
        'message' => 'Erro interno ao buscar nomes.',
        'debug' => $appDebug ? $exception->getMessage() : null,
    ]);
}
