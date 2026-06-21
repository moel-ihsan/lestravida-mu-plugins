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
    var validationMsg = document.getElementById('lvk-tshirt-validation-msg');
    
    if (addToCartBtn && hiddenInput && pillsContainer) {
        addToCartBtn.addEventListener('click', function(e) {
            if (hiddenInput.value === '') {
                e.preventDefault(); // Stop submission
                
                // Tampilkan pesan error
                if (validationMsg) {
                    validationMsg.style.display = 'block';
                }
                
                // Tambahkan animasi shake
                pillsContainer.classList.remove('lvk-error-shake');
                void pillsContainer.offsetWidth; // trigger reflow
                pillsContainer.classList.add('lvk-error-shake');
                
                // Scroll ke opsi baju dengan efek halus
                pillsContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Hilangkan class setelah animasi selesai
                setTimeout(function() {
                    pillsContainer.classList.remove('lvk-error-shake');
                }, 500);
            }
        });
        
        // Sembunyikan pesan error otomatis jika user akhirnya memilih salah satu opsi
        if (pills.length > 0) {
            pills.forEach(function(pill) {
                pill.addEventListener('click', function() {
                    if (validationMsg) {
                        validationMsg.style.display = 'none';
                    }
                });
            });
        }
    }
});
