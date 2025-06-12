<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/auth.php';
requireLogin();

$user = currentUser();
$dashboard_error_message = '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Morismetal</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="content">
            <?php
            try {
                // --- Start of Core Dashboard Logic ---
            ?>
            <h1>Bienvenido, <?= htmlspecialchars($user['nombre']) ?></h1>

            <div class="dashboard-cards">
                <?php
                // Example: Fetching data for cards - this is where a PDOException might occur
                // For now, using static data as in the original file.
                // if (function_that_fetches_card_data_throws_exception()) {
                //     throw new PDOException("Failed to load card data");
                // }
                ?>
                <div class="card">
                    <h3>Recepciones Hoy</h3>
                    <p class="big-number">5</p>
                    <a href="modules/recepcion/" class="btn-link">Ver todas</a>
                </div>

                <div class="card">
                    <h3>Órdenes en Proceso</h3>
                    <p class="big-number">12</p>
                    <a href="modules/taller/" class="btn-link">Ver todas</a>
                </div>

                <div class="card">
                    <h3>Cotizaciones Pendientes</h3>
                    <p class="big-number">3</p>
                    <a href="modules/cotizaciones/" class="btn-link">Ver todas</a>
                </div>

                <div class="card">
                    <h3>Ingresos del Mes</h3>
                    <p class="big-number">$4,250,000</p>
                    <a href="modules/reportes/" class="btn-link">Ver reporte</a>
                </div>
            </div>

            <div class="recent-activity">
                <h2>Actividad Reciente</h2>
                <?php
                // Example: Fetching data for activity table - this is where a PDOException might occur
                // For now, using static data as in the original file.
                // if (function_that_fetches_activity_data_throws_exception()) {
                //     throw new Exception("Failed to load recent activity");
                // }
                ?>
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>2023-11-15 14:30</td>
                            <td>Recepción</td>
                            <td>Camioneta Ford Ranger</td>
                            <td>Juan Pérez</td>
                        </tr>
                        <tr>
                            <td>2023-11-15 13:45</td>
                            <td>Factura</td>
                            <td>Factura #1234 emitida</td>
                            <td>María González</td>
                        </tr>
                        <tr>
                            <td>2023-11-15 11:20</td>
                            <td>Compra</td>
                            <td>Materiales comprados a Proveedor XYZ</td>
                            <td>Carlos López</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php
                // --- End of Core Dashboard Logic ---
            } catch (PDOException $e) {
                error_log("Dashboard Database Error: " . $e->getMessage());
                $dashboard_error_message = "A database error occurred while loading the dashboard. Please try again later.";
            } catch (Exception $e) {
                error_log("Dashboard General Error: " . $e->getMessage());
                $dashboard_error_message = "An unexpected error occurred while loading the dashboard. Please try again later.";
            }

            // Display error message if any
            if (!empty($dashboard_error_message)) {
                echo '<div class="alert alert-danger">' . htmlspecialchars($dashboard_error_message) . '</div>';
            }
            ?>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>