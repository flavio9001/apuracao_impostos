<?php
include 'config.php';
$tipo = $_GET['tipo'] ?? 'csv';
$pasta = $_GET['pasta'] ?? './uploads/';

// Reprocessa dados (igual processar.php)
$competencias = []; // Copie lógica de processar.php aqui (foreach glob + parser)

if ($tipo === 'excel') {
    // Composer: composer require phpoffice/phpspreadsheet
    require 'vendor/autoload.php';
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Competência'); $sheet->setCellValue('B1', 'Total Notas'); // etc.
    $linha = 2;
    foreach ($competencias as $mes => $dados) {
        $sheet->setCellValue('A' . $linha, $mes);
        $sheet->setCellValue('B' . $linha, $dados['total_notas']);
        $sheet->setCellValue('C' . $linha, $dados['icms']);
        // ... IPI D, PIS E, etc.
        $linha++;
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="apuracao_impostos.xlsx"');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
} else { // CSV fallback
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="apuracao_impostos.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Competência', 'Total Notas', 'ICMS', 'IPI', 'PIS', 'COFINS', 'IRPJ', 'CSLL']);
    foreach ($competencias as $mes => $dados) {
        fputcsv($output, [$mes, $dados['total_notas'], $dados['icms'], $dados['ipi'], $dados['pis'], $dados['cofins'], $dados['irpj'], $dados['csll']]);
    }
}
?>