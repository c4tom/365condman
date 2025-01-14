jQuery(document).ready(function($) {
    // Funções interativas para página administrativa
    function initAdminInteractions() {
        // Exemplo: Tooltip para elementos
        $('[data-tooltip]').hover(
            function() {
                $(this).append('<div class="tooltip">' + $(this).data('tooltip') + '</div>');
            },
            function() {
                $(this).find('.tooltip').remove();
            }
        );

        // Exemplo: Confirmação de ações
        $('.confirm-action').on('click', function(e) {
            if (!confirm($(this).data('confirm-message') || 'Tem certeza?')) {
                e.preventDefault();
            }
        });

        // Exemplo: Atualização dinâmica de estatísticas
        function updateQuickStats() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: '365condman_quick_stats'
                },
                success: function(response) {
                    if (response.success) {
                        $('.dashboard-widget').first().html(response.data.html);
                    }
                }
            });
        }

        // Atualizar estatísticas a cada 5 minutos
        setInterval(updateQuickStats, 5 * 60 * 1000);
    }

    // Inicializar interações
    initAdminInteractions();
});
