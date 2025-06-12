            </main> <!-- Cierre del main-content -->
        </div> <!-- Cierre del main-container -->

        <footer class="main-footer">
            <div class="footer-content">
                <div class="footer-left">
                    <p>&copy; <?= date('Y') ?> Morismetal. Todos los derechos reservados.</p>
                </div>
                <div class="footer-right">
                    <p>Versión <?= e($config['version'] ?? '1.0.0') ?></p>
                </div>
            </div>
        </footer>

        <!-- Scripts globales -->
        <script src="<?= baseUrl('assets/js/main.js') ?>"></script>
        <?php if (isset($customScripts)): ?>
            <?php foreach ($customScripts as $script): ?>
                <script src="<?= baseUrl("assets/js/$script") ?>"></script>
            <?php endforeach; ?>
        <?php endif; ?>
    </body>
</html>