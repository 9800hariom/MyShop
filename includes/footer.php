    </main>
    <style>
        footer {
            background: linear-gradient(135deg, #ff6a00, #eea849);
            color: #fff;
            padding-top: 40px;
            font-family: Arial, sans-serif;
        }

        /* animated ticker */
        .ticker {
            overflow: hidden;
            white-space: nowrap;
            background: rgba(0, 0, 0, 0.15);
            padding: 10px 0;
        }

        .ticker span {
            display: inline-block;
            padding-left: 100%;
            animation: scroll 18s linear infinite;
            font-weight: bold;
        }

        @keyframes scroll {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-100%);
            }
        }

        .footer-container {
            max-width: 1100px;
            margin: auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 30px;
        }

        .footer-box {
            background: rgba(255, 255, 255, 0.12);
            padding: 20px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .footer-box img {
            height: 40px;
            margin-bottom: 10px;
        }

        .payment {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
            margin-top: 10px;
            padding: 20px;

            /* FIX */
            flex-wrap: wrap;
            overflow: hidden;
            max-width: 100%;
            box-sizing: border-box;
        }

        .payment img {
            height: 28px;
            max-width: 100%;
            object-fit: contain;
            display: block;
        }

        .footer-bottom {
            text-align: center;
            padding: 15px;
            font-size: 14px;
            background: rgba(0, 0, 0, 0.2);
        }
    </style>

    <footer>

        <!-- Top Ticker -->
        <div class="ticker">
            <span>
                🚀 Fast Delivery Across Nepal • 💳 Secure Payments • 🔥 Best Deals Every Day • 🛒 New Arrivals Weekly • 📦 Easy Return Policy •
            </span>
        </div>

        <!-- Main Footer -->
        <div class="footer-container">

            <div class="footer-box">

                <p>MyShop - Your trusted online shopping destination for fashion, electronics & more.</p>
            </div>

            <div class="footer-box">
                <h3>Why Choose Us</h3>
                <p>✔ Fast Delivery</p>
                <p>✔ Best Prices</p>
                <p>✔ Secure Checkout</p>
                <p>✔ Easy Returns</p>
            </div>

            <div class="footer-box">
                <h3>Payment Methods</h3>
                <div class="payment">
                    <img src="images/bank-card.png">
                    <img src="images/bank-card2.png">
                </div>
            </div>

        </div>

        <!-- Bottom -->
        <div class="footer-bottom">
            &copy; <?php echo date('Y'); ?> MyShop | Designed with ❤️ for Modern Shopping Experience
        </div>

    </footer>
    <script src="js/script.js"></script>
    </body>

    </html>