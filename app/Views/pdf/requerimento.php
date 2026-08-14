<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: "DejaVu Sans", sans-serif; font-size: 11pt; line-height: 1.5; color: #191919; }
</style>
</head>
<body>
<?php echo $corpoHtml; ?>
</body>
</html>
