</main>

<?php if (function_exists('is_logged_in') && is_logged_in()): ?>
  <?php require __DIR__ . '/navbar_bottom.php'; ?>
<?php endif; ?>

<script src="/assets/js/app.js"></script>
</body>
</html>
