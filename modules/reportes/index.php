<?php
require_once '../../includes/auth.php';
requireLogin();
hasRole(['admin', 'supervisor'], true);

$pageTitle = "Reportes y Análisis";
include '../../includes/header.php';

// Obtener parámetros de fechas
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
?>

<div class="container">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="content">
        <div class="page-header">
            <h1>Reportes y Análisis</h1>
        </div>

        <div class="filtros-reportes">
            <form method="GET" class="form-filtros">
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_inicio">Fecha Inicio:</label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= $fechaInicio ?>">
                    </div>

                    <div class="form-group">
                        <label for="fecha_fin">Fecha Fin:</label>
                        <input type="date" id="fecha_fin" name="fecha_fin" value="<?= $fechaFin ?>">
                    </div>

                    <div class="form-group">
                        <label for="tipo_reporte">Tipo de Reporte:</label>
                        <select id="tipo_reporte" name="tipo_reporte">
                            <option value="financiero">Financiero</option>
                            <option value="taller">Taller</option>
                            <option value="inventario">Inventario</option>
                            <option value="ventas">Ventas</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-filter">Generar Reporte</button>
                </div>
            </form>
        </div>

        <div class="reporte-container">
            <div class="reporte-header">
                <h2>Reporte Financiero: <?= date('d/m/Y', strtotime($fechaInicio)) ?> al <?= date('d/m/Y', strtotime($fechaFin)) ?></h2>
                <div class="reporte-actions">
                    <button class="btn btn-export">Exportar a Excel</button>
                    <button class="btn btn-print">Imprimir</button>
                </div>
            </div>

            <div class="reporte-financiero">
                <div class="financiero-stats">
                    <div class="stat-card">
                        <h3>Ingresos Totales</h3>
                        <p class="stat-number">$4,250,000</p>
                    </div>

                    <div class="stat-card">
                        <h3>Gastos Totales</h3>
                        <p class="stat-number">$2,150,000</p>
                    </div>

                    <div class="stat-card">
                        <h3>Utilidad Neta</h3>
                        <p class="stat-number">$2,100,000</p>
                    </div>

                    <div class="stat-card">
                        <h3>Margen de Ganancia</h3>
                        <p class="stat-number">49.4%</p>
                    </div>
                </div>

                <div class="grafico-container">
                    <canvas id="grafico-financiero" width="400" height="200"></canvas>
                </div>

                <div class="tabla-reporte">
                    <h3>Detalle de Ingresos y Gastos</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th>Ingresos</th>
                                <th>Gastos</th>
                                <th>Utilidad</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Reparaciones</td>
                                <td>$3,200,000</td>
                                <td>$1,500,000</td>
                                <td>$1,700,000</td>
                                <td>53.1%</td>
                            </tr>
                            <tr>
                                <td>Mantenimientos</td>
                                <td>$750,000</td>
                                <td>$400,000</td>
                                <td>$350,000</td>
                                <td>46.7%</td>
                            </tr>
                            <tr>
                                <td>Venta de Materiales</td>
                                <td>$300,000</td>
                                <td>$250,000</td>
                                <td>$50,000</td>
                                <td>16.7%</td>
                            </tr>
                            <tr class="total-row">
                                <td>Total</td>
                                <td>$4,250,000</td>
                                <td>$2,150,000</td>
                                <td>$2,100,000</td>
                                <td>49.4%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="grafico-container">
                    <canvas id="grafico-categorias" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Gráfico financiero
const ctxFinanciero = document.getElementById('grafico-financiero').getContext('2d');
const graficoFinanciero = new Chart(ctxFinanciero, {
    type: 'bar',
    data: {
        labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'],
        datasets: [
            {
                label: 'Ingresos',
                data: [1200000, 950000, 1100000, 1000000],
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            },
            {
                label: 'Gastos',
                data: [600000, 450000, 550000, 550000],
                backgroundColor: 'rgba(255, 99, 132, 0.5)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Gráfico por categorías
const ctxCategorias = document.getElementById('grafico-categorias').getContext('2d');
const graficoCategorias = new Chart(ctxCategorias, {
    type: 'pie',
    data: {
        labels: ['Reparaciones', 'Mantenimientos', 'Venta Materiales'],
        datasets: [{
            data: [3200000, 750000, 300000],
            backgroundColor: [
                'rgba(255, 99, 132, 0.5)',
                'rgba(54, 162, 235, 0.5)',
                'rgba(255, 206, 86, 0.5)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true
    }
});
</script>

<?php include '../../includes/footer.php'; ?>