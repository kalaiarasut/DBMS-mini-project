<?php
session_start();
require_once '../config/database.php';
require_once 'fpdf/fpdf.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    die('Unauthorized');
}

if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    die('Invalid order ID');
}
$order_id = intval($_GET['order_id']);

// Fetch order
$sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);
if (!$order) {
    die('Order not found.');
}

// Fetch order items
$item_sql = "SELECT oi.*, j.name FROM order_items oi JOIN jewellery j ON oi.jewellery_id = j.id WHERE oi.order_id = ?";
$item_stmt = mysqli_prepare($conn, $item_sql);
mysqli_stmt_bind_param($item_stmt, "i", $order_id);
mysqli_stmt_execute($item_stmt);
$item_result = mysqli_stmt_get_result($item_stmt);
$items = [];
while ($row = mysqli_fetch_assoc($item_result)) {
    $items[] = $row;
}

// Generate PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Jewellery Shop Invoice',0,1,'C');
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,'Order ID: '.$order['id'],0,1);
$pdf->Cell(0,8,'Order Date: '.$order['created_at'],0,1);
$pdf->Cell(0,8,'Status: '.ucfirst($order['status']),0,1);
$pdf->Ln(4);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(80,8,'Item',1);
$pdf->Cell(30,8,'Price',1);
$pdf->Cell(20,8,'Qty',1);
$pdf->Cell(40,8,'Total',1);
$pdf->Ln();
$pdf->SetFont('Arial','',12);
foreach ($items as $item) {
    $pdf->Cell(80,8,$item['name'],1);
    $pdf->Cell(30,8,'Rs. '.number_format($item['price'],2),1);
    $pdf->Cell(20,8,$item['quantity'],1);
    $pdf->Cell(40,8,'Rs. '.number_format($item['price']*$item['quantity'],2),1);
    $pdf->Ln();
}
$pdf->SetFont('Arial','B',12);
$pdf->Cell(130,8,'Total',1);
$pdf->Cell(40,8,'Rs. '.number_format($order['total_amount'],2),1);
$pdf->Ln(15);
$pdf->SetFont('Arial','I',10);
$pdf->Cell(0,8,'Thank you for shopping with us!',0,1,'C');

$pdf->Output('D', 'Invoice_Order_'.$order['id'].'.pdf');
exit; 