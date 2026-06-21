document.addEventListener('DOMContentLoaded', function() {
    var pills = document.querySelectorAll('.lvk-tshirt-pill');
    var hiddenInput = document.getElementById('lvk_tshirt_size_hidden');
    
    if (pills.length > 0) {
        pills.forEach(function(pill) {
            pill.addEventListener('click', function() {
                pills.forEach(function(p) {
                    p.classList.remove('active');
                });
                
                this.classList.add('active');
                
                if (hiddenInput) {
                    hiddenInput.value = this.getAttribute('data-value');
                }
            });
        });
    }

    var btnChart = document.getElementById('lvk-show-size-chart');
    var chartContainer = document.getElementById('lvk-size-chart-container');
    
    if (btnChart && chartContainer) {
        btnChart.addEventListener('click', function(e) {
            e.preventDefault();
            if (chartContainer.style.display === 'none' || chartContainer.style.display === '') {
                chartContainer.style.display = 'block';
                btnChart.innerText = 'Tutup Desain & Size Chart';
            } else {
                chartContainer.style.display = 'none';
                btnChart.innerText = 'Lihat Desain & Size Chart';
            }
        });
    }

    var addToCartBtn = document.querySelector('.single_add_to_cart_button');
    var pillsContainer = document.getElementById('lvk-tshirt-pills-container');
    
    if (addToCartBtn && hiddenInput && pillsContainer) {
        addToCartBtn.addEventListener('click', function(e) {
            if (hiddenInput.value === '') {
                e.preventDefault(); // Stop submission
                
                // Scroll to the options
                pillsContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Flash visual warning
                var originalBorder = pillsContainer.style.border;
                pillsContainer.style.border = '2px solid #ef4444';
                pillsContainer.style.padding = '8px';
                pillsContainer.style.borderRadius = '8px';
                
                setTimeout(function() {
                    pillsContainer.style.border = originalBorder;
                    pillsContainer.style.padding = '0';
                }, 2500);
                
                // Show alert
                alert('Penting: Silakan pilih opsi Order Baju Kepanitiaan (atau klik "Tidak Pesan") terlebih dahulu sebelum mendaftar.');
            }
        });
    }
});
