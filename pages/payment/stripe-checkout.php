<?php
/**
 * Lọc Phim - Trang thanh toán Stripe
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán VIP - Lọc Phim</title>
    <meta name="description" content="Thanh toán nâng cấp tài khoản VIP Lọc Phim">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #e50914;
            --secondary-color: #b81d24;
            --background-color: #141414;
            --text-color: #ffffff;
            --light-gray: #f1f3f5;
            --gray: #adb5bd;
            --dark-gray: #343a40;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --border-radius: 4px;
            --box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        a {
            color: var(--text-color);
            text-decoration: none;
        }
        
        a:hover {
            color: var(--primary-color);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .header {
            background-color: rgba(0, 0, 0, 0.9);
            padding: 15px 0;
        }
        
        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .main {
            flex: 1;
            padding: 40px 0;
        }
        
        .page-title {
            font-size: 2rem;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .checkout-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: var(--border-radius);
            padding: 30px;
        }
        
        .checkout-info {
            margin-bottom: 30px;
        }
        
        .checkout-info h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            border-bottom: 1px solid var(--dark-gray);
            padding-bottom: 10px;
        }
        
        .checkout-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .checkout-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.2rem;
            font-weight: bold;
            margin-top: 15px;
            border-top: 1px solid var(--dark-gray);
            padding-top: 15px;
        }
        
        #payment-element {
            margin-bottom: 24px;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 12px 15px;
            font-size: 1rem;
            font-weight: 500;
            text-align: center;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-disabled {
            background-color: var(--gray);
            color: white;
            cursor: not-allowed;
        }
        
        #payment-message {
            color: var(--danger-color);
            margin-top: 15px;
            text-align: center;
        }
        
        .footer {
            background-color: rgba(0, 0, 0, 0.9);
            padding: 20px 0;
            margin-top: 40px;
            text-align: center;
            color: var(--gray);
        }
        
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
            vertical-align: middle;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .checkout-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-inner">
                <a href="/" class="logo">Lọc Phim</a>
            </div>
        </div>
    </header>
    
    <main class="main">
        <div class="container">
            <h1 class="page-title">Thanh toán nâng cấp VIP</h1>
            
            <div class="checkout-container">
                <div class="checkout-info">
                    <h3>Thông tin thanh toán</h3>
                    <div class="checkout-details">
                        <span>Gói:</span>
                        <span><?php echo htmlspecialchars($package['name']); ?></span>
                    </div>
                    <div class="checkout-details">
                        <span>Thời hạn:</span>
                        <span><?php echo htmlspecialchars($package['duration']); ?> ngày</span>
                    </div>
                    <div class="checkout-total">
                        <span>Tổng thanh toán:</span>
                        <span><?php echo number_format($package['price'], 0, ',', '.'); ?>đ</span>
                    </div>
                </div>
                
                <form id="payment-form">
                    <div id="payment-element">
                        <!-- Stripe.js sẽ chèn form thanh toán vào đây -->
                        <div style="text-align: center; margin: 20px 0;">
                            <div class="spinner"></div>
                            <span>Đang tải thông tin thanh toán...</span>
                        </div>
                    </div>
                    <button id="submit" class="btn btn-primary">
                        <div class="spinner" id="spinner" style="display: none;"></div>
                        <span id="button-text">Thanh toán ngay</span>
                    </button>
                    <div id="payment-message"></div>
                </form>
            </div>
        </div>
    </main>
    
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Lọc Phim. Tất cả quyền được bảo lưu.</p>
        </div>
    </footer>
    
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        // Khởi tạo Stripe
        const stripe = Stripe('<?php echo STRIPE_PUBLIC_KEY; ?>');
        
        // Tạo Stripe Elements
        const elements = stripe.elements({
            clientSecret: '<?php echo $clientSecret; ?>'
        });
        
        // Tạo payment element
        const paymentElement = elements.create('payment');
        paymentElement.mount('#payment-element');
        
        // Xử lý sự kiện submit form
        const form = document.getElementById('payment-form');
        const submitButton = document.getElementById('submit');
        const spinner = document.getElementById('spinner');
        const buttonText = document.getElementById('button-text');
        const paymentMessage = document.getElementById('payment-message');
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Vô hiệu hóa nút thanh toán
            setLoading(true);
            
            // Xác nhận thanh toán
            const { error } = await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: '<?php echo url('thanh-toan/ket-qua?payment_intent={PAYMENT_INTENT_ID}'); ?>',
                },
            });
            
            // Xử lý lỗi nếu có
            if (error) {
                showMessage(error.message);
                setLoading(false);
            }
            // Nếu không có lỗi, stripe.confirmPayment sẽ tự chuyển hướng
        });
        
        // Helper function để hiển thị spinner loading
        function setLoading(isLoading) {
            if (isLoading) {
                submitButton.disabled = true;
                submitButton.classList.add('btn-disabled');
                spinner.style.display = 'inline-block';
                buttonText.textContent = 'Đang xử lý...';
            } else {
                submitButton.disabled = false;
                submitButton.classList.remove('btn-disabled');
                spinner.style.display = 'none';
                buttonText.textContent = 'Thanh toán ngay';
            }
        }
        
        // Helper function để hiển thị thông báo lỗi
        function showMessage(message) {
            paymentMessage.textContent = message;
            setTimeout(() => {
                paymentMessage.textContent = '';
            }, 5000);
        }
    </script>
</body>
</html>