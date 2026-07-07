<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($titulo !== null ? $titulo : 'Sistema de Gestão da Semana de Inovação e do Prêmio de Inovação do TJRR', ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>
<?php if (!empty($_SESSION['flash'])): ?>
    <p style="color:red;"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); ?></p>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
<?php echo $conteudo; ?>
</body>
</html>
