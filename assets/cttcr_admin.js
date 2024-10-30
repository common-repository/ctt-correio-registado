jQuery(document).ready(function($) {
    // Abre a modal ao clicar no ícone de ajuda
    $('#cttcr_help_icon').on('click', function(event) {
        event.preventDefault();
        $('#cttcr_help_modal').fadeIn(); // Mostra a modal
    });

    // Fecha a modal ao clicar no botão de fechar (X)
    $('.cttcr-close').on('click', function() {
        $('#cttcr_help_modal').fadeOut(); // Fecha a modal
    });

    // Fecha a modal ao clicar fora do conteúdo
    $(window).on('click', function(event) {
        if ($(event.target).is('#cttcr_help_modal')) {
            $('#cttcr_help_modal').fadeOut();
        }
    });

    // Evento de clique no botão de salvar
    $('#cttcr_submit_tracking').on('click', function(event) {
        event.preventDefault();

        var orderId = $('#cttcr_tracking_number').data('order-id');
        var trackingNumber = $('#cttcr_tracking_number').val();

        if (trackingNumber === '') {
            alert(ajax_object.error_message_no_tracking_number);
            return;
        }

        // Requisição AJAX para salvar o número de rastreamento
        $.ajax({
            url: ajax_object.ajax_url,
            method: 'POST',
            data: {
                action: 'cttcr_save_tracking_number',  // Alterado para a ação correta
                order_id: orderId,
                tracking_number: trackingNumber,
                cttcr_nonce: ajax_object.cttcr_nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Atualizar a página ou realizar outras ações se necessário
                } else {
                    alert('Erro: ' + response.data.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert(ajax_object.error_message_server_communication);
            }
        });
    });
});