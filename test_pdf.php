<?php
require_once __DIR__ . '/vendor/autoload.php';

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
$html .= '<style>';
$html .= '@page { margin: 30px 25px 40px 25px; }';
$html .= 'body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #2c3e50; margin: 0; padding: 0; }';
$html .= '.report-header { background-color: #009c3b; color: #ffffff; padding: 18px 25px 14px 25px; margin: -30px -25px 0 -25px; }';
$html .= '.report-header h1 { margin: 0 0 4px 0; font-size: 17px; font-weight: bold; color: #ffffff; }';
$html .= '.report-header .report-sub { font-size: 8.5px; color: #b6f2cc; }';
$html .= '.report-content { padding: 15px 0 0 0; }';
$html .= 'table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }';
$html .= 'table td, table th { text-align: left; padding: 6px 10px; font-size: 9.5px; vertical-align: top; border-bottom: 1px solid #e5e8e8; }';
$html .= 'table th { background-color: #e8f8ee; font-weight: bold; width: 35%; color: #007a2f; border-right: 1px solid #c8e6d0; }';
$html .= 'table td { background-color: #ffffff; color: #2c3e50; }';
$html .= '.row-even td { background-color: #f8f9f9; }';
$html .= '.section-title { font-size: 11px; font-weight: bold; color: #007a2f; margin: 18px 0 6px 0; padding: 5px 10px; background-color: #e8f8ee; border-left: 3px solid #009c3b; }';
$html .= '.report-footer { margin-top: 25px; text-align: center; font-size: 7.5px; color: #aab7b8; border-top: 1px solid #eaecee; padding-top: 8px; }';
$html .= '</style></head><body>';

$logo_path = __DIR__ . '/assets/img/LOGO_branca.svg';
$logo_img = '';
if (file_exists($logo_path)) {
    $logo_data = base64_encode(file_get_contents($logo_path));
    $logo_img = 'data:image/svg+xml;base64,' . $logo_data;
}

$html .= '<div class="report-header">';
$html .= '<table style="width:100%; border:none; margin:0; padding:0; margin-bottom:0;">';
$html .= '<tr>';
$html .= '<td style="border:none; padding:0; background:transparent; vertical-align:middle; width:160px;">';
if ($logo_img) {
    $html .= '<img src="' . $logo_img . '" style="width: 140px; height: auto;" />';
}
$html .= '</td>';
$html .= '<td style="border:none; padding:0; background:transparent; vertical-align:middle; text-align:right;">';
$html .= '<h1 style="color:#ffffff">Consulta CPF</h1>';
$html .= '<span class="report-sub">Selo Brasil Consultas &bull; ' . date('d/m/Y H:i:s') . '</span>';
$html .= '</td>';
$html .= '</tr>';
$html .= '</table>';
$html .= '</div>';

$html .= '<div class="report-content">';
$html .= '<table>';
$html .= '<tr><th>Nome</th><td>JOAO DA SILVA</td></tr>';
$html .= '<tr class="row-even"><th>CPF</th><td>123.456.789-01</td></tr>';
$html .= '<tr><th>Data Nascimento</th><td>15/05/1990</td></tr>';
$html .= '</table>';
$html .= '</div>';
$html .= '<div class="report-footer">Documento gerado automaticamente pelo sistema Selo Brasil Consultas &mdash; Uso interno e confidencial</div>';
$html .= '</body></html>';

$dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$output = $dompdf->output();

$file = __DIR__ . '/test_output.pdf';
file_put_contents($file, $output);
echo "PDF generated successfully\n";
