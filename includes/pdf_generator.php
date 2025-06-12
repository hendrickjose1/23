<?php
// includes/pdf_generator.php
require_once __DIR__ . '/../vendor/autoload.php';

function generarPDFRecepcion($recepcionId) {
    // Obtener datos de la recepción
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM recepciones WHERE id = ?");
    $stmt->execute([$recepcionId]);
    $recepcion = $stmt->fetch();

    // Obtener datos del vehículo y cliente
    $stmt = $pdo->prepare("SELECT v.*, c.nombre as cliente_nombre, c.email as cliente_email
                          FROM vehiculos v
                          JOIN clientes c ON v.cliente_id = c.id
                          WHERE v.id = ?");
    $stmt->execute([$recepcion['vehiculo_id']]);
    $datos = $stmt->fetch();

    // Generar HTML del PDF
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Recepción - Morismetal</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .header { text-align: center; margin-bottom: 20px; }
            .logo { height: 80px; }
            .titulo { font-size: 18px; font-weight: bold; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            .firma { margin-top: 50px; border-top: 1px solid #000; width: 300px; }
        </style>
    </head>
    <body>
        <div class="header">
            <img src="' . __DIR__ . '/../assets/img/logo_morismetal.png" class="logo">
            <div class="titulo">RECEPCIÓN DE VEHÍCULO</div>
        </div>

        <table>
            <tr><th colspan="2">DATOS DEL CLIENTE</th></tr>
            <tr><td>Nombre:</td><td>' . htmlspecialchars($datos['cliente_nombre']) . '</td></tr>
            <tr><td>Vehículo:</td><td>' . htmlspecialchars($datos['marca'] . ' ' . $datos['modelo']) . '</td></tr>
            <tr><td>Patente:</td><td>' . htmlspecialchars($datos['patente']) . '</td></tr>
        </table>

        <table>
            <tr><th colspan="2">DETALLES DE RECEPCIÓN</th></tr>
            <tr><td>Fecha:</td><td>' . date('d/m/Y H:i', strtotime($recepcion['fecha_recepcion'])) . '</td></tr>
            <tr><td>Kilometraje:</td><td>' . number_format($recepcion['kilometraje'], 0, ',', '.') . ' km</td></tr>
            <tr><td>Combustible:</td><td>' . ucfirst($recepcion['combustible']) . '</td></tr>
            <tr><td>Estado al llegar:</td><td>' . htmlspecialchars($recepcion['estado_llegada']) . '</td></tr>
        </table>

        <div class="firma">
            <p>Firma del cliente:</p>
            <img src="' . $recepcion['firma_cliente'] . '" style="height: 50px;">
        </div>
    </body>
    </html>';

    // Generar PDF
    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Guardar PDF
    $output = $dompdf->output();
    $filename = "recepcion_{$recepcionId}.pdf";
    $filepath = __DIR__ . "/../assets/pdf/{$filename}";
    file_put_contents($filepath, $output);

    return [
        'ruta' => $filepath,
        'nombre' => $filename,
        'email' => $datos['cliente_email']
    ];
}