</main>
    
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-paw me-2"></i><?php echo APP_NAME; ?></h5>
                    <p>Connecting pet owners with reliable pet care services.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo APP_URL; ?>" class="text-white">Home</a></li>
                        <li><a href="<?php echo APP_URL; ?>/services" class="text-white">Services</a></li>
                        <li><a href="<?php echo APP_URL; ?>/educational" class="text-white">Pet Care Tips</a></li>
                        <li><a href="<?php echo APP_URL; ?>/about.php" class="text-white">About Us</a></li>
                        <li><a href="<?php echo APP_URL; ?>/contact.php" class="text-white">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <address>
                        <i class="fas fa-map-marker-alt me-2"></i> 123 Pet Street, Petville<br>
                        <i class="fas fa-phone me-2"></i> (123) 456-7890<br>
                        <i class="fas fa-envelope me-2"></i> info@petcareplatform.com
                    </address>
                    <div class="social-icons mt-3">
                        <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>