<?php
require_once '../../includes/auth.php';
requireLogin();
hasRole(['admin', 'recepcion', 'supervisor'], true);

$pageTitle = "Nueva Cotización";
include '../../includes/header.php';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar datos
        if (empty($_POST['cliente_id'])) {
            throw new Exception("Debe seleccionar un cliente");
        }

        if (empty($_POST['items']) || !is_array($_POST['items'])) {
            throw new Exception("Debe agregar al menos un item a la cotización");
        }

        // Calcular totales
        $subtotal = 0;
        $iva = 0;
        $total = 0;

        foreach ($_POST['items'] as $item) {
            $itemTotal = $item['cantidad'] * $item['precio_unitario'];
            $subtotal += $itemTotal;
        }

        $iva = $subtotal * 0.19; // 19% IVA
        $total = $subtotal + $iva;

        // Insertar cotización
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO cotizaciones
                             (cliente_id, usuario_id, vehiculo_id, validez, estado, subtotal, iva, total, observaciones)
                             VALUES (?, ?, ?, ?, 'pendiente', ?, ?, ?, ?)");

        $stmt->execute([
            $_POST['cliente_id'],
            $_SESSION['user_id'],
            $_POST['vehiculo_id'] ?? null,
            $_POST['validez'] ?? 30,
            $subtotal,
            $iva,
            $total,
            $_POST['observaciones'] ?? null
        ]);

        $cotizacionId = $pdo->lastInsertId();

        // Insertar items
        $stmt = $pdo->prepare("INSERT INTO cotizacion_items
                             (cotizacion_id, tipo, descripcion, cantidad, precio_unitario, total)
                             VALUES (?, ?, ?, ?, ?, ?)");

        foreach ($_POST['items'] as $item) {
            $itemTotal = $item['cantidad'] * $item['precio_unitario'];

            $stmt->execute([
                $cotizacionId,
                $item['tipo'],
                $item['descripcion'],
                $item['cantidad'],
                $item['precio_unitario'],
                $itemTotal
            ]);
        }

        $pdo->commit();

        // Redirigir a la vista de la cotización
        header("Location: ver.php?id=$cotizacionId");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
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
            <h1>Nueva Cotización</h1>
            <a href="index.php" class="btn btn-back">Volver</a>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="form-cotizacion" class="form-cotizacion">
            <div class="form-section">
                <h2>Datos del Cliente</h2>

                <div class="form-row">
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
                        <label for="vehiculo_id">Vehículo</label>
                        <select id="vehiculo_id" name="vehiculo_id">
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

                <div class="form-row">
                    <div class="form-group">
                        <label for="validez">Validez (días)</label>
                        <input type="number" id="validez" name="validez" min="1" value="<?= $_POST['validez'] ?? 30 ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="observaciones">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" rows="2"><?= $_POST['observaciones'] ?? '' ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h2>Items de la Cotización</h2>

                <div id="items-container">
                    <!-- Los items se agregarán aquí dinámicamente -->
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nuevo-item-tipo">Tipo</label>
                        <select id="nuevo-item-tipo" class="nuevo-item">
                            <option value="material">Material</option>
                            <option value="mano_obra">Mano de Obra</option>
                            <option value="servicio">Servicio</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="nuevo-item-descripcion">Descripción</label>
                        <input type="text" id="nuevo-item-descripcion" class="nuevo-item" placeholder="Descripción del item">
                    </div>

                    <div class="form-group">
                        <label for="nuevo-item-cantidad">Cantidad</label>
                        <input type="number" id="nuevo-item-cantidad" class="nuevo-item" min="0.01" step="0.01" value="1">
                    </div>

                    <div class="form-group">
                        <label for="nuevo-item-precio">Precio Unitario</label>
                        <input type="number" id="nuevo-item-precio" class="nuevo-item" min="0" step="1" value="0">
                    </div>

                    <button type="button" id="agregar-item" class="btn btn-secondary">Agregar Item</button>
                </div>
            </div>

            <div class="form-section">
                <h2>Totales</h2>

                <div class="totales-row">
                    <div class="total-label">Subtotal:</div>
                    <div class="total-value" id="subtotal">$0</div>
                </div>

                <div class="totales-row">
                    <div class="total-label">IVA (19%):</div>
                    <div class="total-value" id="iva">$0</div>
                </div>

                <div class="totales-row">
                    <div class="total-label">Total:</div>
                    <div class="total-value" id="total">$0</div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar Cotización</button>
                <button type="button" id="guardar-borrador" class="btn btn-secondary">Guardar Borrador</button>
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

// Variables para manejar los items
let items = [];
let nextItemId = 1;

// Función para actualizar los totales
function actualizarTotales() {
    let subtotal = 0;

    items.forEach(item => {
        subtotal += item.cantidad * item.precio_unitario;
    });

    const iva = subtotal * 0.19;
    const total = subtotal + iva;

    document.getElementById('subtotal').textContent = `$${subtotal.toLocaleString()}`;
    document.getElementById('iva').textContent = `$${iva.toLocaleString()}`;
    document.getElementById('total').textContent = `$${total.toLocaleString()}`;

    // Actualizar campos ocultos del formulario
    document.querySelector('input[name="subtotal"]').value = subtotal;
    document.querySelector('input[name="iva"]').value = iva;
    document.querySelector('input[name="total"]').value = total;
}

// Función para renderizar los items
function renderizarItems() {
    const container = document.getElementById('items-container');
    container.innerHTML = '';

    // Agregar campos ocultos para cada item
    items.forEach((item, index) => {
        const itemDiv = document.createElement('div');
        itemDiv.className = 'cotizacion-item';
        itemDiv.innerHTML = `
            <div class="item-tipo">${item.tipo === 'material' ? 'Material' : item.tipo === 'mano_obra' ? 'Mano Obra' : 'Servicio'}</div>
            <div class="item-descripcion">${item.descripcion}</div>
            <div class="item-cantidad">${item.cantidad}</div>
            <div class="item-precio">$${item.precio_unitario.toLocaleString()}</div>
            <div class="item-total">$${(item.cantidad * item.precio_unitario).toLocaleString()}</div>
            <button type="button" class="btn-remove-item" data-id="${item.id}">×</button>

            <input type="hidden" name="items[${index}][tipo]" value="${item.tipo}">
            <input type="hidden" name="items[${index}][descripcion]" value="${item.descripcion}">
            <input type="hidden" name="items[${index}][cantidad]" value="${item.cantidad}">
            <input type="hidden" name="items[${index}][precio_unitario]" value="${item.precio_unitario}">
        `;
        container.appendChild(itemDiv);
    });

    // Agregar eventos para eliminar items
    document.querySelectorAll('.btn-remove-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = parseInt(this.getAttribute('data-id'));
            items = items.filter(item => item.id !== itemId);
            renderizarItems();
            actualizarTotales();
        });
    });
}

// Agregar un nuevo item
document.getElementById('agregar-item').addEventListener('click', function() {
    const tipo = document.getElementById('nuevo-item-tipo').value;
    const descripcion = document.getElementById('nuevo-item-descripcion').value.trim();
    const cantidad = parseFloat(document.getElementById('nuevo-item-cantidad').value);
    const precio = parseFloat(document.getElementById('nuevo-item-precio').value);

    if (!descripcion) {
        alert('La descripción es requerida');
        return;
    }

    if (isNaN(cantidad) || cantidad <= 0) {
        alert('La cantidad debe ser un número positivo');
        return;
    }

    if (isNaN(precio) || precio < 0) {
        alert('El precio debe ser un número positivo');
        return;
    }

    items.push({
        id: nextItemId++,
        tipo,
        descripcion,
        cantidad,
        precio_unitario: precio
    });

    // Limpiar campos
    document.getElementById('nuevo-item-descripcion').value = '';
    document.getElementById('nuevo-item-cantidad').value = '1';
    document.getElementById('nuevo-item-precio').value = '0';

    renderizarItems();
    actualizarTotales();
});

// Autocompletado para materiales
document.getElementById('nuevo-item-descripcion').addEventListener('input', function() {
    if (document.getElementById('nuevo-item-tipo').value === 'material') {
        const query = this.value.trim();

        if (query.length > 2) {
            fetch(`../../api/materiales.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    // Mostrar sugerencias (implementar UI para esto)
                    console.log(data);
                });
        }
    }
});

// Guardar borrador
document.getElementById('guardar-borrador').addEventListener('click', function() {
    // Implementar lógica para guardar borrador
    alert('Borrador guardado');
});

// Inicializar campos ocultos para totales
const form = document.getElementById('form-cotizacion');
const subtotalInput = document.createElement('input');
subtotalInput.type = 'hidden';
subtotalInput.name = 'subtotal';
subtotalInput.value = '0';
form.appendChild(subtotalInput);

const ivaInput = document.createElement('input');
ivaInput.type = 'hidden';
ivaInput.name = 'iva';
ivaInput.value = '0';
form.appendChild(ivaInput);

const totalInput = document.createElement('input');
totalInput.type = 'hidden';
totalInput.name = 'total';
totalInput.value = '0';
form.appendChild(totalInput);
</script>

<?php include '../../includes/footer.php'; ?>