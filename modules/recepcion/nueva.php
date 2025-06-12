<?php
require_once '../../includes/auth.php';
requireLogin();
hasRole('recepcion', true);

$pageTitle = "Nueva Recepción";
include '../../includes/header.php';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar datos
        $requiredFields = ['cliente_id', 'vehiculo_id', 'kilometraje', 'combustible', 'estado_llegada'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("El campo $field es requerido");
            }
        }

        // Insertar recepción
        $stmt = $pdo->prepare("INSERT INTO recepciones
                              (vehiculo_id, usuario_id, kilometraje, combustible, estado_llegada, observaciones)
                              VALUES (?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $_POST['vehiculo_id'],
            $_SESSION['user_id'],
            $_POST['kilometraje'],
            $_POST['combustible'],
            $_POST['estado_llegada'],
            $_POST['observaciones'] ?? null
        ]);

        $recepcionId = $pdo->lastInsertId();

        // Procesar imágenes si hay
        if (!empty($_FILES['imagenes']['name'][0])) {
            $uploadDir = '../../assets/uploads/recepciones/' . $recepcionId . '/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmpName) {
                $fileName = basename($_FILES['imagenes']['name'][$key]);
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($tmpName, $filePath)) {
                    $stmt = $pdo->prepare("INSERT INTO recepcion_imagenes
                                         (recepcion_id, ruta_imagen, descripcion)
                                         VALUES (?, ?, ?)");
                    $stmt->execute([
                        $recepcionId,
                        'assets/uploads/recepciones/' . $recepcionId . '/' . $fileName,
                        $_POST['descripcion_imagen'][$key] ?? null
                    ]);
                }
            }
        }

        // Redirigir a la vista de la recepción
        header("Location: ver.php?id=$recepcionId");
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener clientes para el select
$clientes = $pdo->query("SELECT id, nombre, rut FROM clientes ORDER BY nombre")->fetchAll();
?>

<div class="container">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="content">
        <div class="page-header">
            <h1>Nueva Recepción de Vehículo</h1>
            <a href="index.php" class="btn btn-back">Volver</a>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="form-recepcion">
            <div class="form-section">
                <h2>Datos del Cliente</h2>

                <div class="form-group">
                    <label for="cliente_id">Cliente *</label>
                    <select id="cliente_id" name="cliente_id" required>
                        <option value="">Seleccione un cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= $cliente['id'] ?>" <?= isset($_POST['cliente_id']) && $_POST['cliente_id'] == $cliente['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cliente['nombre']) ?> (<?= htmlspecialchars($cliente['rut']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="vehiculo_id">Vehículo *</label>
                    <select id="vehiculo_id" name="vehiculo_id" required>
                        <option value="">Seleccione un vehículo</option>
                        <?php if (isset($_POST['cliente_id'])):
                            $vehiculos = $pdo->prepare("SELECT id, marca, modelo, patente FROM vehiculos WHERE cliente_id = ?");
                            $vehiculos->execute([$_POST['cliente_id']]);
                            while ($vehiculo = $vehiculos->fetch()):
                        ?>
                        <option value="<?= $vehiculo['id'] ?>" <?= isset($_POST['vehiculo_id']) && $_POST['vehiculo_id'] == $vehiculo['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($vehiculo['marca']) ?> <?= htmlspecialchars($vehiculo['modelo']) ?> (<?= htmlspecialchars($vehiculo['patente']) ?>)
                        </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
            </div>

            <div class="form-section">
                <h2>Datos de la Recepción</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="kilometraje">Kilometraje *</label>
                        <input type="number" id="kilometraje" name="kilometraje" required>
                    </div>

                    <div class="form-group">
                        <label for="combustible">Combustible *</label>
                        <select id="combustible" name="combustible" required>
                            <option value="vacío">Vacío</option>
                            <option value="1/4">1/4</option>
                            <option value="1/2">1/2</option>
                            <option value="3/4">3/4</option>
                            <option value="lleno">Lleno</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="estado_llegada">Estado al Llegar *</label>
                    <textarea id="estado_llegada" name="estado_llegada" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label for="observaciones">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" rows="2"></textarea>
                </div>
            </div>

            <div class="form-section">
                <h2>Imágenes de Recepción</h2>

                <div id="imagenes-container">
                    <div class="imagen-item">
                        <div class="form-group">
                            <label>Imagen</label>
                            <input type="file" name="imagenes[]" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label>Descripción</label>
                            <input type="text" name="descripcion_imagen[]" placeholder="Descripción de la imagen">
                        </div>
                    </div>
                </div>

                <button type="button" id="agregar-imagen" class="btn btn-secondary">Agregar otra imagen</button>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar Recepción</button>
                <button type="reset" class="btn btn-reset">Limpiar</button>
            </div>
        </form>
    </main>
</div>

<script>
// Actualizar vehículos cuando cambia el cliente
document.getElementById('cliente_id').addEventListener('change', function() {
    const clienteId = this.value;
    const vehiculoSelect = document.getElementById('vehiculo_id');

    if (clienteId) {
        fetch(`../../api/vehiculos.php?cliente_id=${clienteId}`)
            .then(response => response.json())
            .then(data => {
                vehiculoSelect.innerHTML = '<option value="">Seleccione un vehículo</option>';
                data.forEach(vehiculo => {
                    const option = document.createElement('option');
                    option.value = vehiculo.id;
                    option.textContent = `${vehiculo.marca} ${vehiculo.modelo} (${vehiculo.patente})`;
                    vehiculoSelect.appendChild(option);
                });
            });
    } else {
        vehiculoSelect.innerHTML = '<option value="">Seleccione un vehículo</option>';
    }
});

// Agregar más campos de imágenes
document.getElementById('agregar-imagen').addEventListener('click', function() {
    const container = document.getElementById('imagenes-container');
    const newItem = document.createElement('div');
    newItem.className = 'imagen-item';
    newItem.innerHTML = `
        <div class="form-group">
            <label>Imagen</label>
            <input type="file" name="imagenes[]" accept="image/*">
        </div>
        <div class="form-group">
            <label>Descripción</label>
            <input type="text" name="descripcion_imagen[]" placeholder="Descripción de la imagen">
        </div>
        <button type="button" class="btn-remove-imagen">×</button>
    `;
    container.appendChild(newItem);

    // Agregar evento para eliminar
    newItem.querySelector('.btn-remove-imagen').addEventListener('click', function() {
        container.removeChild(newItem);
    });
});
</script>

<?php include '../../includes/footer.php'; ?>