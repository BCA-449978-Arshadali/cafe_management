<style>
/* ===================== FOOTER ===================== */
.footer {
    background: linear-gradient(135deg, #0a0a0a, #111);
    border-top: 1px solid rgba(255,123,0,0.1);
    padding: 64px 0 0;
    font-family: 'Poppins', sans-serif;
}
.footer .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}
.footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 40px;
    margin-bottom: 48px;
}

/* Brand Column */
.footer-brand .logo {
    display: flex; align-items: center; gap: 12px; margin-bottom: 16px;
}
.footer-brand .logo img {
    width: 48px; height: 48px; border-radius: 50%;
    border: 2px solid #ff7b00; object-fit: cover;
}
.footer-brand .logo span { font-size: 20px; font-weight: 800; color: #fff; }
.footer-brand > p { color: #666; font-size: 13px; line-height: 1.7; margin-bottom: 20px; }
.footer-social { display: flex; gap: 10px; flex-wrap: wrap; }
.footer-social a {
    width: 38px; height: 38px; border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.1); color: #666;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; transition: 0.2s; text-decoration: none;
}
.footer-social a:hover { background: #ff7b00; border-color: #ff7b00; color: #fff; }

/* Columns */
.footer-col h4 {
    font-size: 14px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 1px; color: #ff7b00; margin-bottom: 20px;
}
.footer-col a {
    display: block; color: #666; font-size: 13px;
    padding: 5px 0; transition: 0.2s; text-decoration: none;
}
.footer-col a:hover { color: #ff7b00; padding-left: 6px; }

/* Contact Items */
.footer-contact-item {
    display: flex; gap: 10px; align-items: flex-start;
    color: #666; font-size: 13px; margin-bottom: 12px; line-height: 1.5;
}
.footer-contact-item i { color: #ff7b00; margin-top: 2px; flex-shrink: 0; width: 14px; text-align: center; }
.footer-contact-item a { color: #666; text-decoration: none; transition: 0.2s; padding: 0; }
.footer-contact-item a:hover { color: #ff7b00; }

/* Hours */
.footer-hours { color: #666; font-size: 13px; line-height: 1.9; }
.footer-hours strong { color: #aaa; display: block; }

/* Bottom Bar */
.footer-bottom {
    border-top: 1px solid rgba(255,255,255,0.05);
    padding: 20px 0;
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 10px;
}
.footer-bottom p   { color: #555; font-size: 12px; }
.footer-bottom p a { color: #ff7b00; text-decoration: none; }
.footer-bottom-links { display: flex; gap: 16px; flex-wrap: wrap; }
.footer-bottom-links a {
    color: #555; font-size: 12px; text-decoration: none; transition: 0.2s;
}
.footer-bottom-links a:hover { color: #ff7b00; }
.footer-credit {
    width: 100%; text-align: center;
    color: rgba(255,255,255,0.2); font-size: 12px;
    padding-bottom: 16px; margin-top: 4px;
}
.footer-credit a { color: #ff7b00; font-weight: 600; text-decoration: none; }

/* Responsive */
@media (max-width: 768px) {
    .footer { padding: 48px 0 0; }
    .footer-grid { gap: 28px; }
    .footer-bottom { flex-direction: column; align-items: flex-start; gap: 8px; }
    .footer-bottom-links { gap: 12px; }
}
@media (max-width: 480px) {
    .footer-brand .logo span { font-size: 18px; }
    .footer-col h4 { margin-bottom: 14px; }
}
</style>

<footer class="footer">
    <div class="container">
        <div class="footer-grid">

            <!-- Brand -->
            <div class="footer-brand">
                <div class="logo">
                    <img src="../assets/images/droppers-logo.png" alt="Droppers Café">
                    <span>Droppers Café</span>
                </div>
                <p>Your favourite café in Amnour, Bihar. Fresh food, great vibes, and memories that last forever.</p>
                <div class="footer-social">
                    <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/dropperscafe" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="https://wa.me/917004810081" target="_blank" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    <a href="tel:+917004810081" title="Call Us"><i class="fa fa-phone"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-col">
                <h4>Quick Links</h4>
                <a href="/cafe_management/customer/index.php">🏠 Home</a>
                <a href="/cafe_management/customer/menu.php">🍽️ Menu</a>
                <a href="/cafe_management/customer/book_table.php">🪑 Book a Table</a>
                <a href="/cafe_management/customer/cart.php">🛒 Cart</a>
                <a href="/cafe_management/customer/track_order.php">📍 Track Order</a>
                <a href="/cafe_management/customer/feedback.php">⭐ Feedback</a>
                <a href="/cafe_management/customer/about.php">ℹ️ About Us</a>
            </div>

            <!-- Contact -->
            <div class="footer-col">
                <h4>Contact Us</h4>
                <div class="footer-contact-item">
                    <i class="fa fa-map-marker-alt"></i>
                    <span>Bheldi Road, Amnour, Saran, Bihar — 841424</span>
                </div>
                <div class="footer-contact-item">
                    <i class="fa fa-phone-alt"></i>
                    <span><a href="tel:+917004810081">+91 70048 10081</a></span>
                </div>
                <div class="footer-contact-item">
                    <i class="fab fa-whatsapp" style="color:#25D366;"></i>
                    <span><a href="https://wa.me/917004810081" target="_blank">Chat on WhatsApp</a></span>
                </div>
                <div class="footer-contact-item">
                    <i class="fa fa-envelope"></i>
                    <!-- ✅ FIX: Cloudflare obfuscated email hataya, proper mailto link lagaya -->
                    <span><a href="mailto:dropperscafe.auth@gmail.com">dropperscafe.auth@gmail.com</a></span>
                </div>
            </div>

            <!-- Opening Hours + Policies -->
            <div class="footer-col">
                <h4>Opening Hours</h4>
                <div class="footer-hours">
                    <strong>Monday – Saturday</strong>
                    10:00 AM – 11:00 PM
                    <br><br>
                    <strong>Sunday</strong>
                    11:00 AM – 10:00 PM
                </div>
                <br>

                <div class="footer-col" style="padding:0; margin-top:4px;">
                    <h4 style="margin-bottom:12px;">Policies</h4>

                    <?php
                    // ✅ FIX: Ek hi baar DB query karo aur result array mein store karo
                    $footer_policies = [];
                    if (isset($conn)) {
                        $fp_res = mysqli_query($conn, "SELECT policy_key, title, icon FROM site_policies ORDER BY id ASC");
                        if ($fp_res && mysqli_num_rows($fp_res) > 0) {
                            while ($fp = mysqli_fetch_assoc($fp_res)) {
                                $footer_policies[] = $fp;
                            }
                        }
                    }

                    if (!empty($footer_policies)):
                        foreach ($footer_policies as $fp):
                    ?>
                        <!-- ✅ FIX: policy_key bhi htmlspecialchars se sanitize kiya -->
                        <a href="<?= BASE_URL ?>/customer/policy.php?type=<?= htmlspecialchars($fp['policy_key']) ?>">
                            <?= $fp['icon'] ?> <?= htmlspecialchars($fp['title']) ?>
                        </a>
                    <?php endforeach; else: ?>
                        <a href="<?= BASE_URL ?>/customer/policy.php?type=return_policy">↩️ Return Policy</a>
                        <a href="<?= BASE_URL ?>/customer/policy.php?type=refund_policy">💰 Refund Policy</a>
                        <a href="<?= BASE_URL ?>/customer/policy.php?type=privacy_policy">🔒 Privacy Policy</a>
                        <a href="<?= BASE_URL ?>/customer/policy.php?type=disclaimer">⚠️ Disclaimer</a>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /.footer-grid -->

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <p>© <?= date('Y') ?> <a href="/cafe_management/customer/index.php">Droppers Café &amp; Resto</a>. All rights reserved.</p>
            <div class="footer-bottom-links">
                <?php
                // ✅ FIX: Double query hataya — pehle se store $footer_policies use karo
                if (!empty($footer_policies)):
                    foreach ($footer_policies as $fb):
                ?>
                    <a href="<?= BASE_URL ?>/customer/policy.php?type=<?= htmlspecialchars($fb['policy_key']) ?>">
                        <?= htmlspecialchars($fb['title']) ?>
                    </a>
                <?php endforeach; else: ?>
                    <a href="<?= BASE_URL ?>/customer/policy.php?type=privacy_policy">Privacy Policy</a>
                    <a href="<?= BASE_URL ?>/customer/policy.php?type=refund_policy">Refund Policy</a>
                    <a href="<?= BASE_URL ?>/customer/policy.php?type=return_policy">Return Policy</a>
                    <a href="<?= BASE_URL ?>/customer/policy.php?type=disclaimer">Disclaimer</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- ✅ FIX: Footer credit text complete kiya -->
        <div class="footer-credit">
            Designed &amp; Developed with ❤️ by <a href="#" target="_blank">Droppers Tech Team</a>
        </div>

    </div><!-- /.container -->
</footer>

<!-- ✅ FIX: Script tag footer ke BOTTOM pe rakha (top se hataya) -->
<script src="../assets/js/custom-modal.js"></script>