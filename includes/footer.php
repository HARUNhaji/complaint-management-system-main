    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Common scripts can go here
        <?php if (isset($page_scripts)): ?>
            <?php echo $page_scripts; ?>
        <?php endif; ?>
    </script>
</body>
</html>