<?php
require_once '../../includes/auth.php';
requireLogin();

$pageTitle = "Órdenes de Trabajo";
include '../../includes/header.php';

// Obtener estado filtro
$estado = $_GET['estado'] ?? 'en_proceso';
?>

<div class="container">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="content">
        <div class="page-header">
            <h1>Órdenes de Trabajo</h1>
            <div class="actions">
                <a href="nueva.php" class="btn btn-primary">Nueva Orden</a>
            </div>
        </div>

        <div class="filtros">
            <div class="estado-filtros">
                <a href="?estado=pendiente" class="<?= $estado === 'pendiente' ? 'active' : '' ?>">Pendientes</a>
                <a href="?estado=en_proceso" class="<?= $estado === 'en_proceso' ? 'active' : '' ?>">En Proceso</a>
                <a href="?estado=terminado" class="<?= $estado === 'terminado' ? 'active' : '' ?>">Terminados</a>
                <a href="?estado=entregado" class="<?= $estado === 'entregado' ? 'active' : '' ?>">Entregados</a>
                <a href="?estado=facturado" class="<?= $estado === 'facturado' ? 'active' : '' ?>">Facturados</a>
                <a href="?" class="<?= empty($_GET['estado']) ? 'active' : '' ?>">Todos</a>
            </div>

            <form method="GET" class="form-filtros">
                <div class="form-group">
                    <label for="busqueda">Buscar:</label>
                    <input type="text" id="busqueda" name="busqueda" placeholder="Patente, cliente o descripción">
                </div>
                <button type="submit" class="btn btn-filter">Buscar</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-ordenes">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Vehículo</th>
                        <th>Cliente</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $query = "SELECT o.id, o.fecha_creacion, o.estado, o.descripcion,
                                 CONCAT(v.marca, ' ', v.modelo) as vehiculo, v.patente,
                                 c.nombre as cliente
                                 FROM ordenes_trabajo o
                                 JOIN recepciones r ON o.recepcion_id = r.id
                                 JOIN vehiculos v ON r.vehiculo_id = v.id
                                 JOIN clientes c ON v.cliente_id = c.id";

                        $params = [];

                        if (!empty($estado)) {
                            $query .= " WHERE o.estado = ?";
                            $params[] = $estado;
                        }

                        if (!empty($_GET['busqueda'])) {
                            $query .= (strpos($query, 'WHERE') === false ? " WHERE " : " AND ";
                            $query .= "(v.patente LIKE ? OR c.nombre LIKE ? OR o.descripcion LIKE ?)";
                            $searchTerm = "%{$_GET['busqueda']}%";
                            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
                        }

                        $query .= " ORDER BY o.fecha_creacion DESC LIMIT 50";

                        $stmt = $pdo->prepare($query);
                        $stmt->execute($params);

                        while ($row = $stmt->fetch()) {
                            $estadoClass = str_replace('_', '-', $row['estado']);
                            echo "<tr>
                                <td>{$row['id']}</td>
                                <td>" . date('d/m/Y', strtotime($row['fecha_creacion'])) . "</td>
                                <td>{$row['vehiculo']} ({$row['patente']})</td>
                                <td>{$row['cliente']}</td>
                                <td>" . substr($row['descripcion'], 0, 50) . "...</td>
                                <td><span class='estado {$estadoClass}'>" . ucfirst(str_replace('_', ' ', $row['estado'])) . "</span></td>
                                <td>
                                    <a href='ver.php?id={$row['id']}' class='btn btn-sm btn-view'>Ver</a>
                                    <a href='editar.php?id={$row['id']}' class='btn btn-sm btn-edit'>Editar</a>
                                </td>
                            </tr>";
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='7' class='error'>Error al cargar órdenes: " . $e->getMessage() . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php include '../../includes/footer.php'; ?>