document.addEventListener('DOMContentLoaded', () => {
    // 1. Auto-dismiss alerts after 4 seconds for a cleaner UI
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            
            setTimeout(() => {
                alert.style.display = 'none';
                alert.remove();
            }, 500); // Wait for transition to finish
        }, 4000);
    });

    // 2. Add subtle hover effect (scale) to product cards
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-5px)';
            card.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
            card.style.boxShadow = '0 4px 6px -1px rgba(0,0,0,0.05)';
        });
    });

    // 3. Confirm before deleting/removing items
    const deleteLinks = document.querySelectorAll('a[href*="delete="], a[href*="remove="]');
    deleteLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            if (!confirm('Are you sure you want to perform this action?')) {
                e.preventDefault();
            }
        });
    });

    // 4. Client-side form validation for quantity inputs in cart
    const qtyInputs = document.querySelectorAll('input[type="number"][name^="quantity"]');
    qtyInputs.forEach(input => {
        input.addEventListener('change', (e) => {
            if (e.target.value < 1) {
                alert('Quantity must be at least 1');
                e.target.value = 1;
            }
        });
    });
});
