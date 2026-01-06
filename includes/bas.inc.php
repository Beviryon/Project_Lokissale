<?php
/**
 * Zone 5 : Bas de page (bas.inc.php)
 * Contient les liens du footer
 */
?>
    </main>
    
    <!-- Zone 5 : Pied de page -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-links">
                <a href="<?php echo SITE_URL; ?>/pages/mentions.php">Mentions légales</a>
                <span class="footer-separator">|</span>
                <a href="<?php echo SITE_URL; ?>/pages/cgv.php">C.G.V.</a>
                <span class="footer-separator">|</span>
                <a href="<?php echo SITE_URL; ?>/pages/plan.php">Plan du site</a>
                <span class="footer-separator">|</span>
                <a href="javascript:window.print()">Imprimer la page</a>
                <span class="footer-separator">|</span>
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo SITE_URL; ?>/actions/newsletter-subscribe.php">Newsletter</a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/auth/connexion.php">S'inscrire à la newsletter</a>
                <?php endif; ?>
                <span class="footer-separator">|</span>
                <a href="<?php echo SITE_URL; ?>/pages/contact.php">Contact</a>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript commun (toujours chargé) -->
    <script src="<?php echo SITE_URL; ?>/assets/js/common.js"></script>
    
    <!-- JavaScript spécifique à la page (si défini) -->
    <?php if (isset($pageJS)): ?>
        <script src="<?php echo SITE_URL; ?>/assets/js/<?php echo htmlspecialchars($pageJS); ?>"></script>
    <?php endif; ?>
</body>
</html>