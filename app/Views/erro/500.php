<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Erro</title>
</head>
<body style="font-family:sans-serif;text-align:center;margin-top:80px;">
<h1>Ocorreu um erro inesperado</h1>
<p>Nossa equipe já foi notificada. Tente novamente em instantes.</p>
<p><a href="<?php echo url('home/index'); ?>">Voltar ao início</a></p>
</body>
</html>
