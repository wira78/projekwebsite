// Inisialisasi variabel
let deliveryCost = 0;

const calculatePrice = () => {
    // Ambil nilai input tanggal
    const tglRental = new Date(document.querySelector('[name="tgl_rental"]').value);
    const tglKembali = new Date(document.querySelector('[name="tgl_kembali"]').value);
    
    // Validasi input
    if (!tglRental || !tglKembali || tglRental > tglKembali) {
        document.getElementById('harga-sewa').textContent = 'Rp 0';
        document.getElementById('grand-total').textContent = 'Rp 0';
        return;
    }

    // Hitung selisih hari
    const diffTime = tglKembali - tglRental;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    // Hitung total harga
    const totalPrice = (basePricePerDay * diffDays) + deliveryCost;
    
    // Update tampilan
    document.getElementById('harga-sewa').textContent = 
        `Rp ${(basePricePerDay * diffDays).toLocaleString('id-ID')}`;
    
    document.getElementById('grand-total').textContent = 
        `Rp ${totalPrice.toLocaleString('id-ID')}`;
}

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    // Hitung awal
    calculatePrice();

    // Event untuk perubahan tanggal
    document.querySelectorAll('[name="tgl_rental"], [name="tgl_kembali"]').forEach(input => {
        input.addEventListener('change', calculatePrice);
    });

    // Event untuk metode pengiriman
    document.querySelectorAll('input[name="delivery"]').forEach(radio => {
        radio.addEventListener('change', () => {
            deliveryCost = radio.value === 'delivery' ? 150000 : 0;
            document.getElementById('delivery-cost').textContent = 
                deliveryCost > 0 ? `Rp ${deliveryCost.toLocaleString('id-ID')}` : 'Gratis';
            calculatePrice();
        });
    });
});