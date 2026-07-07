<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($titulo !== null ? $titulo : 'Sistema Premio de Inovacao TJRR', ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>
<?php echo $conteudo; ?>
</body>
</html>
