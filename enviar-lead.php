<?php
/**
 * Recebe os leads do simulador da landing page e encaminha por e-mail.
 * Destino configurado em $DESTINO.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Apenas POST é aceito
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Metodo nao permitido']);
    exit;
}

// >>> Para onde os leads vão:
$DESTINO = 'joaoguilhermerodrigues@hotmail.com';

// Lê o corpo (JSON enviado pelo fetch do site) com fallback para form-encoded
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

// Honeypot anti-spam: campo invisível "website" deve chegar vazio.
// Se um robô preencher, fingimos sucesso e descartamos.
if (!empty($data['website'])) {
    echo json_encode(['ok' => true]);
    exit;
}

// Sanitização: remove quebras de linha (previne injeção de cabeçalho de e-mail) e tags
function limpar($v) {
    if (!is_string($v)) {
        $v = is_scalar($v) ? (string) $v : '';
    }
    $v = str_replace(["\r", "\n", "%0a", "%0d", "%0A", "%0D"], ' ', $v);
    return trim(strip_tags($v));
}

$nome       = limpar($data['nome'] ?? '');
$whatsapp   = limpar($data['whatsapp'] ?? '');
$email      = limpar($data['email'] ?? '');
$tipoBem    = limpar($data['tipoBem'] ?? '');
$tipoPessoa = limpar($data['tipoPessoa'] ?? '');
$credito    = limpar($data['creditoSolicitado'] ?? '');
$utm        = limpar($data['utm'] ?? '');
$origem     = limpar($data['origem'] ?? '');

// Validações básicas
if ($nome === '' || $whatsapp === '' || $email === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Campos obrigatorios ausentes']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'E-mail invalido']);
    exit;
}

// Limites de tamanho (evita abuso)
$nome     = mb_substr($nome, 0, 120);
$whatsapp = mb_substr($whatsapp, 0, 40);
$email    = mb_substr($email, 0, 160);

// Monta a descrição da parcela calculada (quando houver)
$parcela    = $data['parcelaCalculada'] ?? null;
$parcelaTxt = 'Condicao personalizada (sem tabela)';
if (is_array($parcela)) {
    $sem   = limpar($parcela['sem'] ?? '');
    $com   = limpar($parcela['com'] ?? '');
    $prazo = limpar($parcela['prazoMeses'] ?? '');
    $taxa  = limpar($parcela['taxaAdm'] ?? '');
    $red   = limpar($parcela['reducaoPct'] ?? '');
    $parcelaTxt = "Sem oferta: R$ {$sem} | Com reducao: R$ {$com} | Prazo: {$prazo} meses | Taxa adm: {$taxa} | Reducao: {$red}%";
}

$bemLabel = $tipoBem === 'imovel' ? 'Imovel'
          : ($tipoBem === 'automovel' ? 'Automovel'
          : ($tipoBem === 'pesados' ? 'Pesados' : $tipoBem));
$pessoaLabel = $tipoPessoa === 'fisica' ? 'Pessoa Fisica'
             : ($tipoPessoa === 'juridica' ? 'Pessoa Juridica' : $tipoPessoa);

$assunto = "Novo lead Cota Bem - {$nome}";

$corpo  = "Novo lead capturado pelo simulador do site.\n\n";
$corpo .= "Nome: {$nome}\n";
$corpo .= "WhatsApp: {$whatsapp}\n";
$corpo .= "E-mail: {$email}\n";
$corpo .= "Tipo de bem: {$bemLabel}\n";
$corpo .= "Tipo de pessoa: {$pessoaLabel}\n";
$corpo .= "Credito solicitado: R$ {$credito}\n";
$corpo .= "Parcela: {$parcelaTxt}\n";
$corpo .= "Origem: {$origem}\n";
$corpo .= "UTM: {$utm}\n";
$corpo .= "Data/hora: " . date('d/m/Y H:i:s') . "\n";

// Remetente no próprio domínio melhora a entregabilidade
$host = $_SERVER['SERVER_NAME'] ?? 'cotabemnegocios.com.br';
$host = preg_replace('/^www\./', '', $host);
$from = 'no-reply@' . $host;

$headers  = "From: Cota Bem <{$from}>\r\n";
$headers .= "Reply-To: {$nome} <{$email}>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Assunto codificado em UTF-8
$assuntoEnc = '=?UTF-8?B?' . base64_encode($assunto) . '?=';

$enviado = @mail($DESTINO, $assuntoEnc, $corpo, $headers, "-f{$from}");

if ($enviado) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Falha ao enviar o e-mail']);
}
