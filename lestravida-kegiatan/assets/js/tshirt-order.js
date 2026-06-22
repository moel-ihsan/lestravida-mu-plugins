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
    var customContainer = document.getElementById('lvk-tshirt-custom-size-container');
    var customInput = document.getElementById('lvk-tshirt-custom-size-input');
    var customHidden = document.getElementById('lvk_tshirt_custom_size_hidden');
    
    if (addToCartBtn && hiddenInput && pillsContainer) {
        addToCartBtn.addEventListener('click', function(e) {
            if (hiddenInput.value === '') {
                e.preventDefault();
                
                // Tampilkan pesan error
                validationMsg.style.display = 'block';
                validationMsg.textContent = 'Mohon pilih preferensi Baju Kegiatan terlebih dahulu sebelum melanjutkan ke pendaftaran.';
                
                // Tambahkan animasi shake pada container
                pillsContainer.classList.add('lvk-shake');
                
                // Hapus class shake setelah animasi selesai agar bisa di-trigger lagi nanti
                setTimeout(function() {
                    pillsContainer.classList.remove('lvk-shake');
                }, 400);
                
                // Auto scroll ke bagian opsi baju
                var y = pillsContainer.getBoundingClientRect().top + window.scrollY - 100;
                window.scrollTo({top: y, behavior: 'smooth'});
                
                return;
            }

            if (hiddenInput.value === '> XL' && customInput && customHidden.value.trim() === '') {
                e.preventDefault();
                
                validationMsg.style.display = 'block';
                validationMsg.textContent = 'Mohon sebutkan ukuran spesifik Anda (contoh: XXL atau XXXL).';
                
                customInput.classList.add('lvk-shake');
                setTimeout(function() {
                    customInput.classList.remove('lvk-shake');
                }, 400);
                
                customInput.focus();
                return;
            }
        });
        
        var pills = pillsContainer.querySelectorAll('.lvk-tshirt-pill');
        pills.forEach(function(pill) {
            pill.addEventListener('click', function() {
                var value = this.getAttribute('data-value');
                
                // Hilangkan state active dari semua pill
                pills.forEach(function(p) { p.classList.remove('active'); });
                
                // Set pill yang diklik menjadi active
                this.classList.add('active');
                
                // Simpan value ke hidden input
                hiddenInput.value = value;
                
                // Sembunyikan pesan error jika sudah memilih
                validationMsg.style.display = 'none';

                // Tampilkan custom input jika pilih > XL
                if (value === '> XL') {
                    if (customContainer) customContainer.style.display = 'block';
                } else {
                    if (customContainer) customContainer.style.display = 'none';
                    if (customInput) customInput.value = '';
                    if (customHidden) customHidden.value = '';
                }
            });
        });

        if (customInput && customHidden) {
            customInput.addEventListener('input', function() {
                customHidden.value = this.value;
                if (this.value.trim() !== '') {
                    validationMsg.style.display = 'none';
                }
            });
        }
    }
});
