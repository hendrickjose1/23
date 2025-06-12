<?php
require_once '../../includes/auth.php';
requireLogin();
hasRole(['admin', 'supervisor'], true);

$pageTitle = "Gestión de Inventario";
include '../../includes/header.php';
?>

<div class="container">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="content">
        <div class="page-header">
            <h1>Gestión de Inventario</h1>
            <div class="actions">
                <a href="nuevo.php" class="btn btn-primary">Nuevo Material</a>
                <a href="compras/nueva.php" class="btn btn-secondary">Nueva Compra</a>
            </div>
        </div>

        <div class="filtros">
            <form method="GET" class="form-filtros">
                <div class="form-group">
                    <label for="busqueda">Buscar:</label>
                    <input type="text" id="busqueda" name="busqueda" placeholder="Código o nombre del material">
                </div>

                <div class="form-group">
                    <label for="categoria">Categoría:</label>
                    <select id="categoria" name="categoria">
                        <option value="">Todas</option>
                        <option value="fierro">Fierro</option>
                        <option value="pintura">Pintura</option>
                        <option value="herramienta">Herramientas</option>
                        <option value="repuesto">Repuestos</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="stock">Stock:</label>
                    <select id="stock" name="stock">
                        <option value="">Todos</option>
                        <option value="bajo">Bajo stock</option>
                        <option value="agotado">Agotado</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-filter">Filtrar</button>
                <button type="reset" class="btn btn-reset">Limpiar</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-inventario">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Unidad</th>
                        <th>Stock</th>
                        <th>Stock Mín.</th>
                        <th>Precio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $query = "SELECT * FROM materiales WHERE activo = 1";
                        $params = [];

                        if (!empty($_GET['busqueda'])) {
                            $query .= " AND (codigo LIKE ? OR nombre LIKE ?)";
                            $searchTerm = "%{$_GET['busqueda']}%";
                            $params[] = $searchTerm;
                            $params[] = $searchTerm;
                        }

                        if (!empty($_GET['categoria'])) {
                            $query .= " AND categoria = ?";
                            $params[] = $_GET['categoria'];
                        }

                        if (!empty($_GET['stock'])) {
                            if ($_GET['stock'] === 'bajo') {
                                $query .= " AND stock_actual <= stock_minimo AND stock_actual > 0";
                            } elseif ($_GET['stock'] === 'agotado') {
                                $query .= " AND stock_actual <= 0";
                            }
                        }

                        $query .= " ORDER BY nombre";

                        $stmt = $pdo->prepare($query);
                        $stmt->execute($params);

                        while ($row = $stmt->fetch()) {
                            $stockClass = '';
                            if ($row['stock_actual'] <= 0) {
                                $stockClass = 'stock-agotado';
                            } elseif ($row['stock_actual'] <= $row['stock_minimo']) {
                                $stockClass = 'stock-bajo';
                            }

                            echo "<tr>
                                <td>{$row['codigo']}</td>
                                <td>{$row['nombre']}</td>
                                <td>" . substr($row['descripcion'], 0, 30) . "...</td>
                                <td>{$row['unidad_medida']}</td>
                                <td class='{$stockClass}'>{$row['stock_actual']}</td>
                                <td>{$row['stock_minimo']}</td>
                                <td>$" . number_format($row['precio_unitario'], 2) . "</td>
                                <td>
                                    <a href='editar.php?id={$row['id']}' class='btn btn-sm btn-edit'>Editar</a>
                                    <a href='movimientos.php?id={$row['id']}' class='btn btn-sm btn-history'>Movimientos</a>
                                </td>
                            </tr>";
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='8' class='error'>Error al cargar inventario: " . $e->getMessage() . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="inventario-stats">
            <div class="stat-card">
                <h3>Materiales con stock bajo</h3>
                <p class="stat-number">12</p>
            </div>

            <div class="stat-card">
                <h3>Materiales agotados</h3>
                <p class="stat-number">5</p>
            </div>

            <div class="stat-card">
                <h3>Valor total inventario</h3>
                <p class="stat-number">$4,250,000</p>
            </div>
        </div>
    </main>
</div>

<?php include '../../includes/footer.php'; ?>