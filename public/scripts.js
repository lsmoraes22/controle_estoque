document.addEventListener('DOMContentLoaded', function () {
    $('#show-create-form').on('click', function() {
        $('#table-container').hide();
        $('#form-container').show();
    });

    $(document).on('click', '.edit-user', function() {
        $('#table-container').hide();
        $('#form-container').show();
    });

    // Supondo que você tenha um método para terminar a edição
    window.livewire.on('userUpdated', function() {
        $('#form-container').hide();
        $('#table-container').show();
    });

    window.livewire.on('userCreated', function() {
        $('#form-container').hide();
        $('#table-container').show();
    });

    // Mostrar tabela ao iniciar
    $('#table-container').show();
    $('#form-container').hide();
});
