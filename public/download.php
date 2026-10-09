<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$account = user(); if (!$account) { redirect('login.php'); }
$id = filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
$type = $_GET['type'] ?? 'document';
if ($type === 'report') {
    $property = query('SELECT * FROM properties WHERE id=? AND user_id=?',[$id,$account['id']])[0] ?? null;
    if (!$property) { http_response_code(404); exit('Report not found'); }
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="helio-demo-energy-report.csv"');
    $out = fopen('php://output','w'); fputcsv($out,['DEMO DATA — not real telemetry']); fputcsv($out,['Property',$property['name']]); fputcsv($out,['Hour','Generation kWh','Consumption kWh']);
    foreach ([0,0,0,0,0,0.1,0.4,0.9,1.7,2.8,3.6,4.2,4.5,4.1,3.2,2.5,1.7,0.8,0.3,0,0,0,0,0] as $hour=>$value) { fputcsv($out,[sprintf('%02d:00',$hour),round($value*$property['capacity']/8.4,2),round(0.55+(($hour*7)%11)/10,2)]); } fclose($out); exit;
}
if ($type === 'invoice') {
    $doc = query('SELECT * FROM invoices WHERE id=? AND user_id=?',[$id,$account['id']])[0] ?? null;
    if (!$doc) { http_response_code(404); exit('Invoice not found'); }
    $title = 'Invoice HEL-'.str_pad((string)$doc['id'],4,'0',STR_PAD_LEFT);
    $content = $doc['title']."\nAmount: ".money((float)$doc['amount'])."\nDue date: ".$doc['due_date']."\nStatus: ".$doc['status']."\n\nSample invoice. No payment is processed by this demo.";
} else {
    $doc = query('SELECT * FROM documents WHERE id=? AND user_id=?',[$id,$account['id']])[0] ?? null;
    if (!$doc) { http_response_code(404); exit('Document not found'); }
    $title = $doc['title']; $content = $doc['content'];
}
header('Content-Type: text/plain; charset=utf-8'); header('Content-Disposition: attachment; filename="helio-'.$type.'-'.(int)$id.'.txt"');
echo "HELIO ENERGY\n".$title."\nPrepared for: ".$account['name']."\n\n".$content."\n\nDEMO DOCUMENT — provided for portal demonstration.\n";
