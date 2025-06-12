<?php
require_once '../../includes/auth.php';
requireLogin();
hasRole('recepcion', true);

$pageTitle = "Recepción de Vehículos";
include '../../includes/header.php';
?>

<div class="container">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="content">
        <div class="page-header">
            <h1>Recepción de Vehículos</h1>
            <a href="nueva.php" class="btn btn-primary">Nueva Recepción</a>
        </div>

        <div class="filtros">
            <form method="GET" class="form-filtros">
                <div class="form-group">
                    <label for="fecha">Fecha:</label>
                    <input type="date" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label for="patente">Patente:</label>
                    <input type="text" id="patente" name="patente" placeholder="Buscar por patente">
                </div>

                <div class="form-group">
                    <label for="cliente">Cliente:</label>
                    <input type="text" id="cliente" name="cliente" placeholder="Buscar por cliente">
                </div>

                <button type="submit" class="btn btn-filter">Filtrar</button>
                <button type="reset" class="btn btn-reset">Limpiar</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-recepciones">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Vehículo</th>
                        <th>Patente</th>
                        <th>Kilometraje</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $query = "SELECT r.id, r.fecha_recepcion, c.nombre as cliente,
                                 v.marca, v.modelo, v.patente, r.kilometraje, r.estado_llegada,
                                 CONCAT(v.marca, ' ', v.modelo) as vehiculo
                                 FROM recepciones r
                                 JOIN vehiculos v ON r.vehiculo_id = v.id
                                 JOIN clientes c ON v.cliente_id = c.id
                                 ORDER BY r.fecha_recepcion DESC
                                 LIMIT 20";

                        $stmt = $pdo->query($query);

                        while ($row = $stmt->fetch()) {
                            echo "<tr>
                                <td>{$row['id']}</td>
                                <td>" . date('d/m/Y H:i', strtotime($row['fecha_recepcion'])) . "</td>
                                <td>{$row['cliente']}</td>
                                <td>{$row['vehiculo']}</td>
                                <td>{$row['patente']}</td>
                                <td>{$row['kilometraje']}</td>
                                <td>" . substr($row['estado_llegada'], 0, 30) . "...</td>
                                <td>
                                    <a href='ver.php?id={$row['id']}' class='btn btn-sm btn-view'>Ver</a>
                                    <a href='orden.php?recepcion_id={$row['id']}' class='btn btn-sm btn-edit'>Orden</a>
                                </td>
                            </tr>";
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='8' class='error'>Error al cargar recepciones: " . $e->getMessage() . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php include '../../includes/footer.php'; ?>