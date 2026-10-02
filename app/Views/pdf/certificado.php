<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 59: folha do certificado, em HTML para o Dompdf. A4 em paisagem, que
 * e' o formato de certificado da escola.
 *
 * A view NAO acrescenta titulo, logotipo nem assinatura: o conteudo inteiro
 * e' o texto que o Administrador escreveu, com as palavras chave ja'
 * substituidas por CertificadoTextoService. Ele pode inserir imagem pelo
 * editor rico, e CertificadoPdfService converte o endereco dela em caminho de
 * arquivo antes de chegar aqui.
 *
 * O unico elemento proprio do sistema e' a faixa de conferencia no pe' da
 * folha, com o codigo e o endereco da pagina publica: sem ela o documento
 * nao tem como ser conferido por quem o recebe.
 */
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 0; }
    body { margin: 0; padding: 0; font-family: "DejaVu Sans", sans-serif; font-size: 12pt; line-height: 1.6; color: #191919; }
    .folha { position: relative; width: 297mm; height: 210mm; }
    .arte { position: absolute; top: 0; left: 0; width: 297mm; height: 210mm; }
    .corpo { position: absolute; top: 24mm; left: 26mm; width: 245mm; }
    .corpo p { margin: 0 0 10pt 0; }
    .conferencia { position: absolute; bottom: 10mm; left: 26mm; width: 245mm; font-size: 8pt; color: #4a4a4a; }
</style>
</head>
<body>
<?php
/* A cor da folha entra pelo atributo do elemento, e não pela folha de
   estilos acima, porque ela é escolhida pelo Administrador a cada
   certificado. Formato fora do esperado vira branco, nunca uma cor
   inventada. */
$corFolha = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $fundoCor) === 1 ? $fundoCor : '#ffffff';
?>
<div class="folha" style="background-color: <?php echo $corFolha; ?>;">
    <?php if (!empty($fundoCaminho)): ?>
        <img class="arte" src="<?php echo htmlspecialchars($fundoCaminho, ENT_QUOTES, 'UTF-8'); ?>" alt="">
    <?php endif; ?>
    <div class="corpo"><?php echo $corpoHtml; ?></div>
    <div class="conferencia">
        Código de conferência <?php echo htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?>.
        Confira a autenticidade em <?php echo htmlspecialchars($enderecoConferencia, ENT_QUOTES, 'UTF-8'); ?>
    </div>
</div>
</body>
</html>
