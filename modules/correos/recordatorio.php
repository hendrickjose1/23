<?php
// modules/correos/recordatorios.php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
hasRole(['admin', 'recepcion', 'supervisor'], true);

$pageTitle = "Recordatorios de Cotizaciones";
include '../../includes/header.php';

// Obtener cotizaciones pendientes
$cotizaciones = $pdo->query("SELECT c.id, c.fecha_creacion, cl.nombre as cliente, cl.email,
                            DATEDIFF(NOW(), c.fecha_creacion) as dias_transcurridos
                            FROM cotizaciones c
                            JOIN clientes cl ON c.cliente_id = cl.id
                            WHERE c.estado = 'pendiente'
                            AND c.fecha_creacion < DATE_SUB(NOW(), INTERVAL 3 DAY) -- Más de 3 días sin respuesta
                            ORDER BY c.fecha_creacion")->fetchAll();

// Procesar envío de recordatorios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cotizacionId = $_POST['cotizacion_id'];
    $plantillaId = $_POST['plantilla_id'];

    try {
        // Obtener datos de la cotización
        $stmt = $pdo->prepare("SELECT c.*, cl.nombre as cliente, cl.email
                              FROM cotizaciones c
                              JOIN clientes cl ON c.cliente_id = cl.id
                              WHERE c.id = ?");
        $stmt->execute([$cotizacionId]);
        $cotizacion = $stmt->fetch();

        if (!$cotizacion) {
            throw new Exception("Cotización no encontrada");
        }

        // Obtener plantilla
        $plantilla = $pdo->prepare("SELECT asunto, contenido FROM plantillas_correos WHERE id = ?")->execute([$plantillaId])->fetch();

        if (!$plantilla) {
            throw new Exception("Plantilla no encontrada");
        }

        // Generar PDF de cotización
        $pdf = generarPDFCotizacion($cotizacionId);

        // Personalizar plantilla
        $cuerpo = str_replace(
            ['{{cliente}}', '{{cotizacion_id}}', '{{fecha}}', '{{validez}}'],
            [
                $cotizacion['cliente'],
                $cotizacionId,
                date('d/m/Y', strtotime($cotizacion['fecha_creacion'])),
                $cotizacion['validez']
            ],
            $plantilla['contenido']
        );

        // Enviar correo
        if (enviarCorreo($cotizacion['email'], $plantilla['asunto'], $cuerpo, [
            ['ruta' => $pdf['ruta'], 'nombre' => $pdf['nombre']]
        ])) {
            // Registrar el recordatorio
            $pdo->prepare("INSERT INTO recordatorios_cotizaciones
                          (cotizacion_id, usuario_id, fecha_envio, plantilla_id)
                          VALUES (?, ?, NOW(), ?)")
                ->execute([$cotizacionId, $_SESSION['user_id'], $plantillaId]);

            $success = "Recordatorio enviado correctamente";
        } else {
            throw new Exception("Error al enviar el correo");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="container">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="content">
        <div class="page-header">
            <h1>Recordatorios de Cotizaciones</h1>
        </div>

        <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="info-box">
            <p>Se muestran las cotizaciones pendientes de aprobación con más de 3 días de antigüedad.</p>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Días</th>
                    <th>Último Recordatorio</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cotizaciones as $cotizacion):
                    // Obtener último recordatorio
                    $recordatorio = $pdo->prepare("SELECT fecha_envio FROM recordatorios_cotizaciones
                                                 WHERE cotizacion_id = ?
                                                 ORDER BY fecha_envio DESC LIMIT 1")
                        ->execute([$cotizacion['id']])->fetch();
                ?>
                <tr>
                    <td><?= $cotizacion['id'] ?></td>
                    <td><?= htmlspecialchars($cotizacion['cliente']) ?></td>
                    <td><?= date('d/m/Y', strtotime($cotizacion['fecha_creacion'])) ?></td>
                    <td><?= $cotizacion['dias_transcurridos'] ?></td>
                    <td><?= $recordatorio ? date('d/m/Y', strtotime($recordatorio['fecha_envio'])) : 'Nunca' ?></td>
                    <td>
                        <button class="btn btn-sm btn-enviar-recordatorio"
                                data-id="<?= $cotizacion['id'] ?>"
                                data-cliente="<?= htmlspecialchars($cotizacion['cliente']) ?>">
                            Enviar Recordatorio
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Modal para enviar recordatorio -->
        <div id="modal-recordatorio" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>Enviar Recordatorio</h2>
                <form method="POST" id="form-recordatorio">
                    <input type="hidden" name="cotizacion_id" id="cotizacion-id">

                    <div class="form-group">
                        <label>Cliente:</label>
                        <input type="text" id="cliente-nombre" readonly>
                    </div>

                    <div class="form-group">
                        <label>Plantilla:</label>
                        <select name="plantilla_id" id="plantilla-id">
                            <?php
                            $plantillas = $pdo->query("SELECT id, nombre FROM plantillas_correos WHERE tipo = 'recordatorio'")->fetchAll();
                            foreach ($plantillas as $plantilla):
                            ?>
                            <option value="<?= $plantilla['id'] ?>"><?= htmlspecialchars($plantilla['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Enviar Recordatorio</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
// Manejar modal de recordatorio
const modal = document.getElementById('modal-recordatorio');
const span = document.querySelector('.close-modal');

document.querySelectorAll('.btn-enviar-recordatorio').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('cotizacion-id').value = this.dataset.id;
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