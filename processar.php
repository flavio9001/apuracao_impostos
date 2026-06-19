<?php
header('Content-Type: application/json');
include 'config.php';

$pasta = $_POST['pasta'] ?? './uploads/';
if (!is_dir($pasta)) { echo json_encode(['erro' => 'Pasta inválida: ' . $pasta]); exit; }

$competencias = [];
foreach (glob($pasta . '*.{xml,pdf}', GLOB_BRACE) as $arquivo) {
    $nome = basename($arquivo);
    $ext = pathinfo($arquivo, PATHINFO_EXTENSION);
    $competencia = null;
    $impostos = ['icms' => 0.0, 'ipi' => 0.0, 'pis' => 0.0, 'cofins' => 0.0, 'irpj' => 0.0, 'csll' => 0.0];

    if ($ext === 'xml') {
        $xml = simplexml_load_file($arquivo, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml) {
            $ns = ['nfe' => 'http://www.portalfiscal.inf.br/nfe']; // NF-e padrão
            $xml->registerXPathNamespace('nfe', $ns['nfe']);

            // Competência CORRIGIDA: infNFe/ide/dhEmi (formato YYYY-MM-DDThh:mm)
            $dhEmi = $xml->xpath('//nfe:infNFe/nfe:ide/nfe:dhEmi')[0] ?? null;
            if (!$dhEmi) $dhEmi = $xml->xpath('//nfe:infNFe/nfe:ide/nfe:dEmi')[0] ?? null;
            if ($dhEmi) $competencia = substr((string)$dhEmi, 0, 7);

            // Impostos TOTALS CORRIGIDOS (ICMSTot, IPITot, etc.)
            $vICMS = $xml->xpath('//nfe:infNFe/nfe:total/nfe:ICMSTot/nfe:vICMS')[0] ?? 0; $impostos['icms'] = (float)$vICMS;
            $vIPI = $xml->xpath('//nfe:infNFe/nfe:total/nfe:IPITot/nfe:vIPI')[0] ?? 0; $impostos['ipi'] = (float)$vIPI;
            $vPIS = $xml->xpath('//nfe:infNFe/nfe:total/nfe:PISSTot/nfe:vPIS')[0] ?? 0; $impostos['pis'] = (float)$vPIS;
            $vCOFINS = $xml->xpath('//nfe:infNFe/nfe:total/nfe:COFINSS tot/nfe:vCOFINS')[0] ?? 0; $impostos['cofins'] = (float)$vCOFINS;
            // IRPJ/CSLL: Se não em total, busque em grupos ou adicione custom

            // Registra BD
            $resumo = json_encode(['competencia' => $competencia, 'impostos' => $impostos]);
            $stmt = $pdo->prepare("INSERT INTO leituras (arquivo, resumo, competencia) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE data_lida=NOW()");
            $stmt->execute([$nome, $resumo, $competencia]);
        }
    } elseif ($ext === 'pdf') {
        // PDF básico regex (sem lib)
        $texto = file_get_contents($arquivo);
        if (preg_match('/(\d{4}[-\/]\d{2})/', $texto, $m)) $competencia = $m[1];
        if (preg_match_all('/ICMS[:\s]*[R$]\s*([\d.,]+)/i', $texto, $m)) $impostos['icms'] = str_replace(['.', ','], ['', '.'], $m[1][0]) * 1;
        // Repita regex para IPI: '/IPI[:\s]*[R$]\s*([\d.,]+)/i', etc.
        // BD igual XML
    }

    if ($competencia && !isset($competencias[$competencia])) {
        $competencias[$competencia] = $impostos + ['total_notas' => 0, 'arquivos' => []];
    }
    if ($competencia) {
        foreach ($impostos as $k => $v) if ($v > 0) $competencias[$competencia][$k] += $v;
        $competencias[$competencia]['total_notas']++;
        $competencias[$competencia]['arquivos'][] = $nome;
    }
}
echo json_encode(['competencias' => $competencias]);
?>