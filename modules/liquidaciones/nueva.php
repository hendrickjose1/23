<?php
// modules/liquidaciones/nueva.php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
hasRole(['admin', 'supervisor'], true);

$pageTitle = "Liquidación de Trabajo";
include '../../includes/header.php';

// Obtener orden de trabajo
$ordenId = $_GET['id'] ?? null;
if (!$ordenId) {
    header("Location: ../taller/");
    exit();
}

// Obtener datos de la orden
$stmt = $pdo->prepare("SELECT o.*, v.patente, c.nombre as cliente, c.email as cliente_email
                      FROM ordenes_trabajo o
                      JOIN recepciones r ON o.recepcion_id = r.id
                      JOIN vehiculos v ON r.vehiculo_id = v.id
                      JOIN clientes c ON v.cliente_id = c.id
                      WHERE o.id = ?");
$stmt->execute([$ordenId]);
$orden = $stmt->fetch();

if (!$orden) {
    header("Location: ../taller/");
    exit();
}

// Procesar liquidación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Registrar horas de trabajo
        $horas = $_POST['horas'];
        $valorHora = 15000; // Valor por defecto, puedes configurarlo en la base de datos

        // Registrar materiales usados
        $materiales = json_decode($_POST['materiales'], true);
        foreach ($materiales as $material) {
            // Actualizar inventario
            $pdo->prepare("UPDATE materiales SET stock_actual = stock_actual - ? WHERE id = ?")
                ->execute([$material['cantidad'], $material['id']]);

            // Registrar en gastos
            $pdo->prepare("INSERT INTO gastos_taller
                          (orden_trabajo_id, usuario_id, tipo, descripcion, cantidad, precio_unitario, total)
                          VALUES (?, ?, 'material', ?, ?, ?, ?)")
                ->execute([
                    $ordenId,
                    $_SESSION['user_id'],
                    $material['nombre'],
                    $material['cantidad'],
                    $material['precio'],
                    $material['cantidad'] * $material['precio']
                ]);
        }

        // Registrar mano de obra
        $pdo->prepare("INSERT INTO gastos_taller
                      (orden_trabajo_id, usuario_id, tipo, descripcion, cantidad, precio_unitario, total)
                      VALUES (?, ?, 'mano_obra', 'Horas de trabajo', ?, ?, ?)")
            ->execute([
                $ordenId,
                $_SESSION['user_id'],
                $horas,
                $valorHora,
                $horas * $valorHora
            ]);

        // Actualizar estado de la orden
        $pdo->prepare("UPDATE ordenes_trabajo SET estado = 'liquidada' WHERE id = ?")
            ->execute([$ordenId]);

        // Generar PDF de liquidación
        $pdf = generarPDFLiquidacion($ordenId);

        // Enviar por correo
        $asunto = "Liquidación de trabajo - Orden #{$ordenId}";
        $plantilla = obtenerPlantillaCorreo('liquidacion');
        $cuerpo = str_replace(
            ['{{cliente}}', '{{orden_id}}', '{{total}}'],
            [$orden['cliente'], $ordenId, number_format($horas * $valorHora, 0, ',', '.')],
            $plantilla
        );

        enviarCorreo($orden['cliente_email'], $asunto, $cuerpo, [
            ['ruta' => $pdf['ruta'], 'nombre' => $pdf['nombre']]
        ]);

        $pdo->commit();
        $success = "Liquidación generada y enviada correctamente";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al generar liquidación: " . $e->getMessage();
    }
}

// Obtener materiales disponibles
$materiales = $pdo->query("SELECT id, codigo, nombre, precio_unitario, stock_actual
                          FROM materiales
                          WHERE stock_actual > 0
                          ORDER BY nombre")->fetchAll();
?>

<div class="container">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="content">
        <div class="page-header">
            <h1>Liquidación de Orden #<?= $ordenId ?></h1>
            <a href="../taller/ver.php?id=<?= $ordenId ?>" class="btn btn-back">Volver</a>
        </div>

        <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="info-orden">
            <h3>Datos de la Orden</h3>
            <p><strong>Cliente:</strong> <?= htmlspecialchars($orden['cliente']) ?></p>
            <p><strong>Vehículo:</strong> <?= htmlspecialchars($orden['patente']) ?></p>
            <p><strong>Descripción:</strong> <?= htmlspecialchars($orden['descripcion']) ?></p>
        </div>

        <form method="POST" id="form-liquidacion">
            <div class="form-section">
                <h2>Mano de Obra</h2>

                <div class="form-group">
                    <label for="horas">Horas de Trabajo:</label>
                    <input type="number" id="horas" name="horas" min="0.5" step="0.5" value="1" required>
                </div>

                <div class="form-group">
                    <label>Valor por Hora:</label>
                    <input type="text" value="$15,000" readonly>
                </div>
            </div>

            <div class="form-section">
                <h2>Materiales Utilizados</h2>

                <div class="materiales-container">
                    <div class="material-item">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Material</label>
                                <select class="material-select" data-index="0">
                                    <option value="">Seleccione un material</option>
                                    <?php foreach ($materiales as $material): ?>
                                    <option value="<?= $material['id'] ?>"
                                            data-precio="<?= $material['precio_unitario'] ?>"
                                            data-nombre="<?= htmlspecialchars($material['nombre']) ?>">
                                        <?= htmlspecialchars($material['nombre']) ?> (Stock: <?= $material['stock_actual'] ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Cantidad</label>
                                <input type="number" class="material-cantidad" data-index="0" min="0.01" step="0.01" value="1">
                            </div>

                            <div class="form-group">
                                <label>Precio Unitario</label>
                                <input type="text" class="material-precio" data-index="0" readonly>
                            </div>

                            <div class="form-group">
                                <label>Total</label>
                                <input type="text" class="material-total" data-index="0" readonly>
                            </div>

                            <button type="button" class="btn-remove-material" data-index="0">×</button>
                        </div>
                    </div>
                </div>

                <button type="button" id="agregar-material" class="btn btn-secondary">Agregar Material</button>

                <input type="hidden" name="materiales" id="materiales-json" value="[]">
            </div>

            <div class="form-section">
                <h2>Totales</h2>

                <div class="totales-row">
                    <div class="total-label">Total Mano de Obra:</div>
                    <div class="total-value" id="total-mano-obra">$0</div>
                </div>

                <div class="totales-row">
                    <div class="total-label">Total Materiales:</div>
                    <div class="total-value" id="total-materiales">$0</div>
                </div>

                <div class="totales-row">
                    <div class="total-label">Total General:</div>
                    <div class="total-value" id="total-general">$0</div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Generar Liquidación</button>
            </div>
        </form>
    </main>
</div>

<script>
// Variables para manejar materiales
let materiales = [];
let nextMaterialIndex = 1;

// Función para actualizar totales
function actualizarTotales() {
    let totalManobra = parseFloat(document.getElementById('horas').value) * 15000;
    let totalMateriales = 0;

    materiales.forEach(mat => {
        totalMateriales += mat.cantidad * mat.precio;
    });

    document.getElementById('total-mano-obra').textContent = `$${totalManobra.toLocaleString()}`;
    document.getElementById('total-materiales').textContent = `$${totalMateriales.toLocaleString()}`;
    document.getElementById('total-general').textContent = `$${(totalManobra + totalMateriales).toLocaleString()}`;

    // Actualizar campo oculto con los materiales
    document.getElementById('materiales-json').value = JSON.stringify(materiales);
}

// Agregar nuevo material
document.getElementById('agregar-material').addEventListener('click', function() {
    const container = document.querySelector('.materiales-container');
    const newItem = document.createElement('div');
    newItem.className = 'material-item';
    newItem.innerHTML = `
        <div class="form-row">
            <div class="form-group">
                <label>Material</label>
                <select class="material-select" data-index="${nextMaterialIndex}">
                    <option value="">Seleccione un material</option>
                    <?php foreach ($materiales as $material): ?>
                    <option value="<?= $material['id'] ?>"
                            data-precio="<?= $material['precio_unitario'] ?>"
                            data-nombre="<?= htmlspecialchars($material['nombre']) ?>">
                        <?= htmlspecialchars($material['nombre']) ?> (Stock: <?= $material['stock_actual'] ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Cantidad</label>
                <input type="number" class="material-cantidad" data-index="${nextMaterialIndex}" min="0.01" step="0.01" value="1">
            </div>

            <div class="form-group">
                <label>Precio Unitario</label>
                <input type="text" class="material-precio" data-index="${nextMaterialIndex}" readonly>
            </div>

            <div class="form-group">
                <label>Total</label>
                <input type="text" class="material-total" data-index="${nextMaterialIndex}" readonly>
            </div>

            <button type="button" class="btn-remove-material" data-index="${nextMaterialIndex}">×</button>
        </div>
    `;
    container.appendChild(newItem);

    // Configurar eventos para el nuevo material
    const select = newItem.querySelector('.material-select');
    const cantidad = newItem.querySelector('.material-cantidad');
    const precio = newItem.querySelector('.material-precio');
    const total = newItem.querySelector('.material-total');

    function actualizarMaterial() {
        const index = parseInt(select.dataset.index);
        const matId = select.value;
        const matNombre = select.options[select.selectedIndex]?.dataset.nombre || '';
        const matPrecio = parseFloat(select.options[select.selectedIndex]?.dataset.precio || 0);
        const matCantidad = parseFloat(cantidad.value) || 0;

        precio.value = `$${matPrecio.toLocaleString()}`;
        total.value = `$${(matPrecio * matCantidad).toLocaleString()}`;

        // Actualizar array de materiales
        const matIndex = materiales.findIndex(m => m.index === index);
        if (matId && matNombre && matPrecio > 0 && matCantidad > 0) {
            const material = {
                index,
                id: matId,
                nombre: matNombre,
                precio: matPrecio,
                cantidad: matCantidad
            };

            if (matIndex >= 0) {
                materiales[matIndex] = material;
            } else {
                materiales.push(material);
            }
        } else if (matIndex >= 0) {
            materiales.splice(matIndex, 1);
        }

        actualizarTotales();
    }

    select.addEventListener('change', actualizarMaterial);
    cantidad.addEventListener('input', actualizarMaterial);

    // Configurar botón de eliminar
    newItem.querySelector('.btn-remove-material').addEventListener('click', function() {
        const index = parseInt(this.dataset.index);
        materiales = materiales.filter(m => m.index !== index);
        container.removeChild(newItem);
        actualizarTotales();
    });

    nextMaterialIndex++;
});

// Configurar eventos para el primer material
document.querySelector('.material-select').addEventListener('change', function() {
    const precio = this.options[this.selectedIndex]?.dataset.precio || 0;
    document.querySelector('.material-precio').value = `$${parseFloat(precio).toLocaleString()}`;
    actualizarMaterial();
});

document.querySelector('.material-cantidad').addEventListener('input', actualizarMaterial);

function actualizarMaterial() {
    const select = document.querySelector('.material-select');
    const cantidad = document.querySelector('.material-cantidad');
    const precio = document.querySelector('.material-precio');
    const total = document.querySelector('.material-total');

    const matPrecio = parseFloat(select.options[select.selectedIndex]?.dataset.precio || 0);
    const matCantidad = parseFloat(cantidad.value) || 0;

    total.value = `$${(matPrecio * matCantidad).toLocaleString()}`;

    // Actualizar array de materiales
    if (select.value && matCantidad > 0) {
        materiales = [{
            index: 0,
            id: select.value,
            nombre: select.options[select.selectedIndex].dataset.nombre,
            precio: matPrecio,
            cantidad: matCantidad
        }];
    } else {
        materiales = [];
    }

    actualizarTotales();
}

// Configurar eventos para horas de trabajo
document.getElementById('horas').addEventListener('input', actualizarTotales);

// Inicializar totales
actualizarTotales();
</script>

<?php include '../../includes/footer.php'; ?>