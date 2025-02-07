<?php 
require('fpdf.php'); 
 
$pdf = new FPDF(); 
// Add Thai font 
$pdf->AddFont('THSarabunNew','','THSarabunNew.php');
$pdf->AddFont('THSarabunNew','B','THSarabunNew_b.php');
$pdf->AddPage();
$pdf->SetFont('THSarabunNew','',16);
$pdf->Cell(40, 10, iconv('UTF-8', 'cp874', 'สวัสดี'));
$pdf->SetFont('THSarabunNew','B',16);
$pdf->Cell(40, 10, iconv('UTF-8', 'cp874', 'สวัสดี'));
$pdf->Output();
?>