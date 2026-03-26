    </main>
    <footer>
        <div class="footer-content"
            style="text-align:center; display:flex; flex-direction:column; align-items:center; padding:2.5rem; 
background: linear-gradient(135deg, #1e3c72, #2a5298); 
border-top-left-radius:25px; border-top-right-radius:25px; 
box-shadow:0 -10px 25px rgba(0,0,0,0.25); 
position:relative; overflow:hidden;">

            <!-- glow top line -->
            <div style="position:absolute;top:0;left:50%;transform:translateX(-50%);width:80%;height:2px;background:linear-gradient(90deg,transparent,white,transparent);opacity:0.4;"></div>

            <p style="margin-bottom:1rem;color:white;font-size:0.95rem;opacity:0.9;">
                &copy; <?php echo date('Y'); ?> MyShop. Designed with ❤️ for a modern web.
            </p>

            <!-- payment box upgraded -->
            <div style="display:flex;gap:1rem;align-items:center;justify-content:center;
        background:rgba(255,255,255,0.15);
        padding:0.7rem 1.2rem;
        border-radius:12px;
        backdrop-filter:blur(10px);
        box-shadow:0 8px 20px rgba(0,0,0,0.15);
        transition:0.3s ease;">

                <img src="images/bank-card.png"
                    style="height:28px; object-fit:contain; filter:brightness(1.1);" />

                <img src="images/bank-card2.png"
                    style="height:28px; object-fit:contain; filter:brightness(1.1);" />
            </div>

        </div>
    </footer>
    <script src="js/script.js"></script>
    </body>

    </html>