<?php
// modules/correos/plantillas.php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
hasRole(['admin'], true);

$pageTitle = "Plantillas de Correo";
include '../../includes/header.php';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'crear') {
            $pdo->prepare("INSERT INTO plantillas_correos
                          (nombre, tipo, asunto, contenido)
                          VALUES (?, ?, ?, ?)")
                ->execute([
                    $_POST['nombre'],
                    $_POST['tipo'],
                    $_POST['asunto'],
                    $_POST['contenido']
                ]);
            $success = "Plantilla creada correctamente";
        } elseif ($action === 'editar') {
            $pdo->prepare("UPDATE plantillas_correos
                          SET nombre = ?, tipo = ?, asunto = ?, contenido = ?
                          WHERE id = ?")
                ->execute([
                    $_POST['nombre'],
                    $_POST['tipo'],
                    $_POST['asunto'],
                    $_POST['contenido'],
                    $_POST['id']
                ]);
            $success = "Plantilla actualizada correctamente";
        } elseif ($action === 'eliminar') {
            $pdo->prepare("DELETE FROM plantillas_correos WHERE id = ?")
                ->execute([$_POST['id']]);
            $success = "Plantilla eliminada correctamente";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener plantillas
$plantillas = $pdo->query("SELECT * FROM plantillas_correos ORDER BY tipo, nombre")->fetchAll();
?>

<div class="container">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="content">
        <div class="page-header">
            <h1>Plantillas de Correo</h1>
            <button id="btn-nueva-plantilla" class="btn btn-primary">Nueva Plantilla</button>
        </div>

        <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" data-tab="todas">Todas</button>
            <button class="tab-btn" data-tab="general">Generales</button>
            <button class="tab-btn" data-tab="cotizacion">Cotizaciones</button>
            <button class="tab-btn" data-tab="recordatorio">Recordatorios</button>
            <button class="tab-btn" data-tab="liquidacion">Liquidaciones</button>
        </div>

        <div class="tab-content active" id="todas">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Asunto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plantillas as $plantilla): ?>
                    <tr>
                        <td><?= htmlspecialchars($plantilla['nombre']) ?></td>
                        <td><?= ucfirst($plantilla['tipo']) ?></td>
                        <td><?= htmlspecialchars($plantilla['asunto']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-editar"
                                    data-id="<?= $plantilla['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($plantilla['nombre']) ?>"
                                    data-tipo="<?= $plantilla['tipo'] ?>"
                                    data-asunto="<?= htmlspecialchars($plantilla['asunto']) ?>"
                                    data-contenido="<?= htmlspecialchars($plantilla['contenido']) ?>">
                                Editar
                            </button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="eliminar">
                                <input type="hidden" name="id" value="<?= $plantilla['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger"
                                        onclick="return confirm('¿Eliminar esta plantilla?')">
                                    Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal para editar/crear plantilla -->
        <div id="modal-plantilla" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2 id="modal-titulo">Nueva Plantilla</h2>
                <form method="POST" id="form-plantilla">
                    <input type="hidden" name="action" id="form-action" value="crear">
                    <input type="hidden" name="id" id="plantilla-id">

                    <div class="form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>

                    <div class="form-group">
                        <label for="tipo">Tipo:</label>
                        <select id="tipo" name="tipo" required>
                            <option value="general">General</option>
                            <option value="cotizacion">Cotización</option>
                            <option value="recordatorio">Recordatorio</option>
                            <option value="liquidacion">Liquidación</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="asunto">Asunto:</label>
                        <input type="text" id="asunto" name="asunto" required>
                    </div>

                    <div class="form-group">
                        <label for="contenido">Contenido (HTML):</label>
                        <textarea id="contenido" name="contenido" rows="10" required></textarea>
                        <small>Variables disponibles: {{cliente}}, {{documento_id}}, {{fecha}}, {{validez}}, {{total}}</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Guardar Plantilla</button>
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

        if (this.dataset.tab === 'todas') {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.add('active'));
        } else {
            document.getElementById(this.dataset.tab).classList.add('active');
        }
    });
});

// Manejar modal de plantilla
const modal = document.getElementById('modal-plantilla');
const span = document.querySelector('.close-modal');

document.getElementById('btn-nueva-plantilla').addEventListener('click', function() {
    document.getElementById('modal-titulo').textContent = 'Nueva Plantilla';
    document.getElementById('form-action').value = 'crear';
    document.getElementById('plantilla-id').value = '';
    document.getElementById('form-plantilla').reset();
    modal.style.display = 'block';
});

document.querySelectorAll('.btn-editar').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('modal-titulo').textContent = 'Editar Plantilla';
        document.getElementById('form-action').value = 'editar';
        document.getElementById('plantilla-id').value = this.dataset.id;
        document.getElementById('nombre').value = this.dataset.nombre;
        document.getElementById('tipo').value = this.dataset.tipo;
        document.getElementById('asunto').value = this.dataset.asunto;
        document.getElementById('contenido').value = this.dataset.contenido;
        modal.style.display = 'block';
    });
});

span.addEventListener('click', () => modal.style.display = 'none');
window.addEventListener('click', (e) => {
    if (e.target === modal) modal.style.display = 'none';
});
</script>

<?php include '../../includes/footer.php'; ?>