jQuery(function ($) {

    $('li.inventory_options a')
        .text('Kuota Peserta');

    $('label[for="_manage_stock"]')
        .text('Aktifkan Kuota Peserta');

    $('label[for="_stock"]')
        .text('Jumlah Kuota Peserta');

    $('label[for="_sold_individually"]')
        .text('Batasi 1 Slot per Orang');

    $('p.form-field._stock_field .description')
        .text('Jumlah maksimum peserta kegiatan.');

});