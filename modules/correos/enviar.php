<?php
// modules/correos/enviar.php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
hasRole(['admin', 'recepcion'], true);

$pageTitle = "Envío de Documentos";
include '../../includes/header.php';

// Procesar envío
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipoDocumento = $_POST['tipo_documento'];
    $documentoId = $_POST['documento_id'];
    $emailAdicional = $_POST['email_adicional'] ?? null;

    try {
        switch ($tipoDocumento) {
            case 'recepcion':
                $pdf = generarPDFRecepcion($documentoId);
                break;
            case 'cotizacion':
                $pdf = generarPDFCotizacion($documentoId);
                break;
            case 'liquidacion':
                $pdf = generarPDFLiquidacion($documentoId);
                break;
            default:
                throw new Exception("Tipo de documento no válido");
        }

        // Enviar correo
        $asunto = "Documento de Morismetal - " . ucfirst($tipoDocumento);
        $plantilla = obtenerPlantillaCorreo($tipoDocumento);
        $cuerpo = str_replace(
            ['{{cliente}}', '{{documento_id}}', '{{fecha}}'],
            [$_POST['nombre_cliente'], $documentoId, date('d/m/Y')],
            $plantilla
        );

        $destinatarios = [$pdf['email']];
        if ($emailAdicional) {
            $destinatarios[] = $emailAdicional;
        }

        $enviado = false;
        foreach ($destinatarios as $destinatario) {
            if (enviarCorreo($destinatario, $asunto, $cuerpo, [
                ['ruta' => $pdf['ruta'], 'nombre' => $pdf['nombre']]
            ])) {
                $enviado = true;
            }
        }

        if ($enviado) {
            $success = "Documento enviado correctamente";
            // Registrar envío en la base de datos
            $pdo->prepare("INSERT INTO envios_correos
                          (tipo_documento, documento_id, destinatario, fecha_envio, usuario_id)
                          VALUES (?, ?, ?, NOW(), ?)")
                ->execute([$tipoDocumento, $documentoId, implode(', ', $destinatarios), $_SESSION['user_id']]);
        } else {
            throw new Exception("Error al enviar el correo");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener documentos pendientes de envío
$recepciones = $pdo->query("SELECT r.id, v.patente, c.nombre as cliente
                           FROM recepciones r
                           JOIN vehiculos v ON r.vehiculo_id = v.id
                           JOIN clientes c ON v.cliente_id = c.id
                           WHERE r.id NOT IN (SELECT documento_id FROM envios_correos WHERE tipo_documento = 'recepcion')
                           ORDER BY r.fecha_recepcion DESC")->fetchAll();

$cotizaciones = $pdo->query("SELECT c.id, cl.nombre as cliente
                            FROM cotizaciones c
                            JOIN clientes cl ON c.cliente_id = cl.id
                            WHERE c.id NOT IN (SELECT documento_id FROM envios_correos WHERE tipo_documento = 'cotizacion')
                            AND c.estado = 'pendiente'
                            ORDER BY c.fecha_creacion DESC")->fetchAll();
?>

<div class="container">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="content">
        <div class="page-header">
            <h1>Envío de Documentos a Clientes</h1>
        </div>

        <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" data-tab="recepciones">Recepciones</button>
            <button class="tab-btn" data-tab="cotizaciones">Cotizaciones</button>
            <button class="tab-btn" data-tab="liquidaciones">Liquidaciones</button>
        </div>

        <div class="tab-content active" id="recepciones">
            <h2>Recepciones Pendientes de Envío</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Vehículo</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recepciones as $recepcion): ?>
                    <tr>
                        <td><?= $recepcion['id'] ?></td>
                        <td><?= htmlspecialchars($recepcion['cliente']) ?></td>
                        <td><?= htmlspecialchars($recepcion['patente']) ?></td>
                        <td><?= date('d/m/Y', strtotime($recepcion['fecha_recepcion'])) ?></td>
                        <td>
                            <button class="btn btn-sm btn-enviar"
                                    data-tipo="recepcion"
                                    data-id="<?= $recepcion['id'] ?>"
                                    data-cliente="<?= htmlspecialchars($recepcion['cliente']) ?>">
                                Enviar
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-content" id="cotizaciones">
            <h2>Cotizaciones Pendientes de Envío</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cotizaciones as $cotizacion): ?>
                    <tr>
                        <td><?= $cotizacion['id'] ?></td>
                        <td><?= htmlspecialchars($cotizacion['cliente']) ?></td>
                        <td><?= date('d/m/Y', strtotime($cotizacion['fecha_creacion'])) ?></td>
                        <td>
                            <button class="btn btn-sm btn-enviar"
                                    data-tipo="cotizacion"
                                    data-id="<?= $cotizacion['id'] ?>"
                                    data-cliente="<?= htmlspecialchars($cotizacion['cliente']) ?>">
                                Enviar
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-content" id="liquidaciones">
            <h2>Liquidaciones Pendientes de Envío</h2>
            <p>Se mostrarán las liquidaciones generadas pendientes de envío.</p>
        </div>

        <!-- Modal para enviar correo -->
        <div id="modal-envio" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>Enviar Documento</h2>
                <form method="POST" id="form-envio">
                    <input type="hidden" name="tipo_documento" id="tipo-documento">
                    <input type="hidden" name="documento_id" id="documento-id">

                    <div class="form-group">
                        <label>Cliente:</label>
                        <input type="text" id="cliente-nombre" readonly>
                    </div>

                    <div class="form-group">
                        <label for="email_adicional">Email adicional (opcional):</label>
                        <input type="email" name="email_adicional" id="email-adicional">
                    </div>

                    <div class="form-group">
                        <label>Plantilla:</label>
                        <select name="plantilla_id" id="plantilla-id">
                            <?php
                            $plantillas = $pdo->query("SELECT id, nombre FROM plantillas_correos WHERE tipo = 'general'")->fetchAll();
                            foreach ($plantillas as $plantilla):
                            ?>
                            <option value="<?= $plantilla['id'] ?>"><?= htmlspecialchars($plantilla['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Enviar Documento</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
// Manejar tabs
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

        this.classList.add('active');
        document.getElementById(this.dataset.tab).classList.add('active');
    });
});

// Manejar modal de envío
const modal = document.getElementById('modal-envio');
const span = document.querySelector('.close-modal');

document.querySelectorAll('.btn-enviar').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('tipo-documento').value = this.dataset.tipo;
        document.getElementById('documento-id').value = this.dataset.id;
        document.getElementById('cliente-nombre').value = this.dataset.cliente;
        modal.style.display = 'block';
    });
});

span.addEventListener('click', () => modal.style.display = 'none');
window.addEventListener('click', (e) => {
    if (e.target === modal) modal.style.display = 'none';
});
</script>

<?php include '../../includes/footer.php'; ?>