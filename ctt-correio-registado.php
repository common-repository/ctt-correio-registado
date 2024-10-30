<?php
/**
 * Plugin Name: CTT Correio Registado
 * Plugin URI: https://wordpress.org/plugins/ctt-correio-registado
 * Description: Associe o número de rastreamento do serviço CTT Correio Registado e permita o acompanhamento da entrega das encomendas. Imprima os dados do cliente diretamente no Talão de Aceitação em papel, poupando tempo e minimizando erros.
 * Version: 1.0.6
 * Author: Pedro Miguel Martins
 * Author URI: https://pedromartins.com/
 * Text Domain: ctt-correio-registado
 * Domain Path: /languages
 * Requires at least: 4.7
 * Requires PHP: 7.0
 * License: GPLv3 or later
 * WC requires at least: 3.0
 * WC-HPOS-Compatible: true
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'CTTCR_Correio_Registado' ) ) :

class CTTCR_Correio_Registado {

    public function __construct() {
        // Load plugin
        $this->init_hooks();
    }

    public function init_hooks() {
        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'cttcr_admin_enqueue_scripts' ) );

        // Add tracking number field to order edit page
        add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'cttcr_admin_shipping_fields' ), 10 );

        // Save tracking number
        add_action( 'woocommerce_before_order_object_save', array( $this, 'cttcr_save_shop_order_tracking_number' ), 10, 1 );

        // Add tracking info to customer completed order email
        add_action( 'woocommerce_email_order_details', array( $this, 'cttcr_email_order_details' ), 100, 4 );

        // Handle AJAX for printing order record
        add_action( 'wp_ajax_cttcr_print_order_record', array( $this, 'cttcr_generate_print_order_record' ) );
        add_action( 'wp_ajax_nopriv_cttcr_print_order_record', array( $this, 'cttcr_generate_print_order_record' ) );

        // AJAX functions for tracking number
        add_action( 'wp_ajax_cttcr_get_order_tracking_number', array( $this, 'cttcr_get_order_tracking_number' ) );
        add_action( 'wp_ajax_cttcr_complete_order_with_tracking', array( $this, 'cttcr_complete_order_with_tracking' ) );

        // Handle AJAX for saving tracking number
        add_action( 'wp_ajax_cttcr_save_tracking_number', array( $this, 'cttcr_save_tracking_number_ajax' ) );
    }

    public function cttcr_admin_enqueue_scripts( $hook ) {
        $screen = get_current_screen();

        $allowed_screens = array( 'woocommerce_page_wc-orders', 'shop_order', 'post' );

        if ( in_array( $screen->id, $allowed_screens, true ) ) {
            wp_enqueue_script(
                'cttcr-admin',
                plugins_url( 'assets/cttcr_admin.js', __FILE__ ),
                array( 'jquery' ),
                filemtime( plugin_dir_path( __FILE__ ) . 'assets/cttcr_admin.js' ),
                true
            );

            wp_enqueue_style(
                'cttcr-admin-css',
                plugins_url( 'assets/cttcr_admin.css', __FILE__ ),
                array(),
                filemtime( plugin_dir_path( __FILE__ ) . 'assets/cttcr_admin.css' )
            );

            wp_localize_script( 'cttcr-admin', 'ajax_object', array(
                'ajax_url'                           => admin_url( 'admin-ajax.php' ),
                'cttcr_nonce'                        => wp_create_nonce( 'cttcr_nonce_action' ),
                'error_message_no_tracking_number'   => esc_html__( 'Por favor, insira o número de rastreamento.', 'ctt-correio-registado' ),
                'error_message_server_communication' => esc_html__( 'Ocorreu um erro na comunicação com o servidor.', 'ctt-correio-registado' ),
            ) );
        }
    }

    public function cttcr_save_tracking_number_ajax() {
        // Verifica o nonce
        if ( ! isset( $_POST['cttcr_nonce'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Falha na verificação de segurança.', 'ctt-correio-registado' ) ) );
		}

		$cttcr_nonce = sanitize_text_field( wp_unslash( $_POST['cttcr_nonce'] ) );

		if ( ! wp_verify_nonce( $cttcr_nonce, 'cttcr_nonce_action' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Falha na verificação de segurança.', 'ctt-correio-registado' ) ) );
		}

        // Valida permissões
        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Permissão negada.', 'ctt-correio-registado' ) ) );
        }

        // Valida dados
        if ( ! isset( $_POST['order_id'], $_POST['tracking_number'] ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Dados inválidos.', 'ctt-correio-registado' ) ) );
        }

        $order_id = intval( $_POST['order_id'] );
        if ( $order_id <= 0 ) {
            wp_send_json_error( array( 'message' => esc_html__( 'ID de pedido inválido.', 'ctt-correio-registado' ) ) );
        }
        $tracking_number = sanitize_text_field( wp_unslash( $_POST['tracking_number'] ) );

        if ( empty( $tracking_number ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'O número de rastreamento não pode estar vazio.', 'ctt-correio-registado' ) ) );
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Pedido não encontrado.', 'ctt-correio-registado' ) ) );
        }

        // Atualiza o número de rastreamento
        $order->update_meta_data( '_cttcr_tracking_number', $tracking_number );
        $order->save();

        wp_send_json_success( array( 'message' => esc_html__( 'Número de rastreamento salvo com sucesso.', 'ctt-correio-registado' ) ) );
    }

    public function cttcr_admin_shipping_fields( $order ) {
		$tracking_number = $order->get_meta( '_cttcr_tracking_number' );
		$tracking_url = 'https://appserver.ctt.pt/CustomerArea/PublicArea_Detail?ObjectCodeInput=' . urlencode( $tracking_number ) . '&SearchInput=' . urlencode( $tracking_number ) . '&IsFromPublicArea=true';

		wp_nonce_field( 'cttcr_save_tracking_number', 'cttcr_tracking_number_nonce' );

		?>
		<div class="cttcr" style="display: block; clear: both; margin: 1em 0;">
			<h3><?php esc_html_e( 'CTT Correio Registado', 'ctt-correio-registado' ); ?>
				<a href="#" id="cttcr_help_icon" style="margin-left: 5px; text-decoration: none;">
					<div class="dashicons dashicons-editor-help"></div>
				</a>
			</h3>
			<p><strong><?php esc_html_e( 'Número de Rastreamento:', 'ctt-correio-registado' ); ?></strong><br/>
				<input type="text" name="_cttcr_tracking_number" id="cttcr_tracking_number" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>" value="<?php echo esc_attr( $tracking_number ); ?>">
				<button id="cttcr_submit_tracking" class="button button-secondary"><?php esc_html_e( 'Guardar', 'ctt-correio-registado' ); ?></button>
			</p>
			<?php if ( ! empty( $tracking_number ) ) : ?>
				<p><a class="button button-secondary" href="<?php echo esc_url( $tracking_url ); ?>" target="_blank"><?php esc_html_e( 'Seguir a encomenda', 'ctt-correio-registado' ); ?></a></p>
			<?php endif; ?>
			<?php
			$order_id = $order->get_id();
			$nonce = wp_create_nonce( 'cttcr_print_order_nonce' );
			echo '<p><a class="button button-primary" href="' . esc_url( add_query_arg( array( 'cttcr_print_order_id' => $order_id, 'cttcr_print_order_nonce' => $nonce ), admin_url( 'admin-ajax.php?action=cttcr_print_order_record' ) ) ) . '" target="_blank">' . esc_html__( 'Imprimir registo', 'ctt-correio-registado' ) . '</a></p>';
			?>
            <div id="cttcr_help_modal" class="cttcr-modal">
                <div class="cttcr-modal-content">
                    <div class="cttcr-close">&times;</div>
                    <h2><?php esc_html_e( 'Ajuda - CTT Correio Registado', 'ctt-correio-registado' ); ?></h2>
                    <p><strong><?php esc_html_e( 'Como funciona?', 'ctt-correio-registado' ); ?></strong><br/>
                    <?php esc_html_e( 'Preencha o Número de Rastreamento e clique em "Atualizar" para guardar. O Número de Rastreamento SÓ é enviado ao cliente quando a encomenda passar para o estado "Concluída".', 'ctt-correio-registado' ); ?></p>
                    <p><strong><?php esc_html_e( 'Onde encontro o número de rastreamento?', 'ctt-correio-registado' ); ?></strong><br/>
                    <?php esc_html_e( 'O Número de Rastreamento encontra-se no respectivo formulário dos CTT, no canto superior direito, junto ao código de barras. Copie sem os espaços entre os números, como neste exemplo: "RL193378648PT".', 'ctt-correio-registado' ); ?></p>

                    <p><strong><?php esc_html_e( 'Configurar a impressão', 'ctt-correio-registado' ); ?></strong><br/>
                    <?php esc_html_e( 'Em Windows, no painel de impressão, configurar "Tamanho do papel" para A5 e retirar opção "Cabeçalhos e rodapés".', 'ctt-correio-registado' ); ?><br/>
                    <?php esc_html_e( 'Em macOS, no diálogo de impressão, selecione "A5" como o tamanho do papel. Nas opções de impressão, desmarque a caixa "Imprimir cabeçalhos e rodapés".', 'ctt-correio-registado' ); ?><br/>
                    <?php esc_html_e( 'Verifique também a orientação do papel na impressora, pois pode variar dependendo da marca e modelo', 'ctt-correio-registado' ); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function cttcr_save_shop_order_tracking_number( $order ) {
        // Verify nonce for security
        if ( ! isset( $_POST['cttcr_tracking_number_nonce'] ) ) {
			return;
		}

		$cttcr_tracking_number_nonce = sanitize_text_field( wp_unslash( $_POST['cttcr_tracking_number_nonce'] ) );

		if ( ! wp_verify_nonce( $cttcr_tracking_number_nonce, 'cttcr_save_tracking_number' ) ) {
			return;
		}

        // Update tracking number
        if ( isset( $_POST['_cttcr_tracking_number'] ) ) {
            $tracking_number = sanitize_text_field( wp_unslash( $_POST['_cttcr_tracking_number'] ) );
            $order->update_meta_data( '_cttcr_tracking_number', $tracking_number );
        }
    }

    public function cttcr_email_order_details( $order, $sent_to_admin, $plain_text, $email ) {
        if ( $email->id === 'customer_completed_order' ) {
            $tracking_number = $order->get_meta( '_cttcr_tracking_number' );

            if ( $tracking_number ) {
                echo '<h2>' . esc_html__( 'Expedição', 'ctt-correio-registado' ) . '</h2>';
                echo '<p><strong>' . esc_html__( 'Operador:', 'ctt-correio-registado' ) . '</strong> ' . esc_html__( 'CTT Correio Registado', 'ctt-correio-registado' ) . '</p>';
                echo '<p><strong>' . esc_html__( 'Número de Rastreamento:', 'ctt-correio-registado' ) . '</strong> ' . esc_html( $tracking_number ) . '</p>';
                $tracking_url = 'https://appserver.ctt.pt/CustomerArea/PublicArea_Detail?ObjectCodeInput=' . urlencode( $tracking_number ) . '&SearchInput=' . urlencode( $tracking_number ) . '&IsFromPublicArea=true';
                echo '<p><a href="' . esc_url( $tracking_url ) . '" target="_blank">' . esc_html__( 'Clique para seguir a sua encomenda.', 'ctt-correio-registado' ) . '</a></p>';
            }
        }
    }

    public function cttcr_generate_print_order_record() {
    if ( isset( $_GET['cttcr_print_order_id'], $_GET['cttcr_print_order_nonce'] ) ) {
        // Sanitiza o nonce
        $cttcr_print_order_nonce = sanitize_text_field( wp_unslash( $_GET['cttcr_print_order_nonce'] ) );
        
        // Verifica o nonce
        if ( wp_verify_nonce( $cttcr_print_order_nonce, 'cttcr_print_order_nonce' ) ) {
            // Sanitiza e valida o order_id
            $order_id = intval( $_GET['cttcr_print_order_id'] );
            if ( $order_id <= 0 ) {
                echo '<p>' . esc_html__( 'ID de pedido inválido.', 'ctt-correio-registado' ) . '</p>';
                return;
            }

            $order = wc_get_order( $order_id );

             if ( $order ) {
                $current_user_id = get_current_user_id();
                $order_user_id   = $order->get_user_id();

                if ( current_user_can( 'edit_shop_orders' ) || $current_user_id === $order_user_id ) {
                    // Generate the print page
                    ?>
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title><?php esc_html_e( 'Imprimir Formulário CTT Correio Registado', 'ctt-correio-registado' ); ?></title>
                        <style>
                            p { margin-top: 0; margin-bottom: 0; line-height: 10pt; }
                            body { font-family: Arial, sans-serif; font-size: 10pt; margin: 0; }
                            .print-area { width: 148mm; height: 210mm; padding: 0; box-sizing: border-box; position: relative; margin: 0; page-break-after: always; }
                            .field { position: absolute; padding: 0px; }
                            .destinatario { top: 47mm; left: 0mm; }
                            .remetente { top: 84mm; left: 0mm; }
                            .destinatario .address, .remetente .address { margin-top: 6mm; margin-bottom: 4mm; }
                            .postcode { margin-left: 15mm; }
                            .postcode span { display: inline-block; width: 5mm; text-align: center; }
                            .postcode span.second-part:nth-child(5) { margin-left:3mm}
                            .postcode span.second-part:nth-child(7) { margin-right: 2mm}
                            .postcode span.city, .postcode span.country { width: auto; }
                            @page { size: A5; }
                        </style>
                    </head>
                    <body class="print-style">
                    <div class="print-area">
                        <div class="field destinatario">
                            <p class="company-name"><?php echo esc_html( $order->get_shipping_company() ); ?> <?php echo esc_html( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() ); ?></p>
                            <p class="address"><?php echo esc_html( $order->get_shipping_address_1() ); ?> <?php echo esc_html( $order->get_shipping_address_2() ); ?></p>
                            <p class="postcode">
                            <?php
                            $shipping_postcode = $order->get_shipping_postcode();
                            $shipping_country  = $order->get_shipping_country(); // Obtém o país de entrega
                            $shipping_country_name = WC()->countries->countries[ $shipping_country ]; // Nome completo do país
                            if ( $shipping_postcode ) {
                                $digits = str_replace( '-', '', $shipping_postcode ); // Remove o hífen do código postal

                                // Verifica se o código postal tem exatamente 7 dígitos (formato português)
                                if ( strlen( $digits ) === 7 ) {
                                    for ( $i = 0; $i < strlen( $digits ); $i++ ) {
                                        $class = $i < 4 ? 'first-part' : 'second-part'; // Define a classe com base na posição do dígito
                                        echo '<span class="' . esc_attr( $class ) . '">' . esc_html( $digits[ $i ] ) . '</span>';
                                    }
                                } else {
                                    // Se não for o formato português, apenas apresenta cada dígito num span sem classes de formatação específica
                                    for ( $i = 0; $i < strlen( $digits ); $i++ ) {
                                        echo '<span class="digit">' . esc_html( $digits[ $i ] ) . '</span>';
                                    }
                                }
                            }
                            ?>
                            <span class="city"><?php echo esc_html( $order->get_shipping_city() ); ?></span>
                            <span class="country"><?php echo esc_html( $shipping_country_name ); ?></span>
                        </p>

                        </div>
                        <div class="field remetente">
                            <p class="company-name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
                            <p class="address"><?php echo esc_html( get_option( 'woocommerce_store_address' ) ); ?> <?php echo esc_html( get_option( 'woocommerce_store_address_2' ) ); ?></p>
                            <p class="postcode">
                            <?php
                            $country  = get_option( 'woocommerce_default_country' ); // Obtém o país da loja
                            $country_name = WC()->countries->countries[ $country ]; // Nome completo do país
                            $postcode = get_option( 'woocommerce_store_postcode' );
                            if ( $postcode ) {
                                $digits = str_replace( '-', '', $postcode ); // Remove o hífen

                                // Verifica se o código postal tem exatamente 7 dígitos (formato português)
                                if ( strlen( $digits ) === 7 ) {
                                    for ( $i = 0; $i < strlen( $digits ); $i++ ) {
                                        $class = $i < 4 ? 'first-part' : 'second-part'; // Define a classe com base na posição
                                        echo '<span class="' . esc_attr( $class ) . '">' . esc_html( $digits[ $i ] ) . '</span>';
                                    }
                                } else {
                                    // Se não for o formato português, apenas apresenta cada dígito num span sem classes de formatação específica
                                    for ( $i = 0; $i < strlen( $digits ); $i++ ) {
                                        echo '<span class="digit">' . esc_html( $digits[ $i ] ) . '</span>';
                                    }
                                }
                            }
                            ?>
                            <span class="city"><?php echo esc_html( get_option( 'woocommerce_store_city' ) ); ?></span>
                            <span class="country"><?php echo esc_html( $country_name ); ?></span>
                        </p>
                        </div>
                    </div>
                    <script>
                        window.onload = function() {
                            window.print();
                        };
                    </script>
                    </body>
                    </html>
                    <?php
                    // Termina a execução depois de renderizar a página
                    exit;
                } else {
                    echo '<p>' . esc_html__( 'Você não tem permissão para acessar este pedido.', 'ctt-correio-registado' ) . '</p>';
                }
            } else {
                echo '<p>' . esc_html__( 'Pedido inválido.', 'ctt-correio-registado' ) . '</p>';
            }
        } else {
            echo '<p>' . esc_html__( 'Falha na verificação de segurança.', 'ctt-correio-registado' ) . '</p>';
        }
    } else {
        echo '<p>' . esc_html__( 'Pedido inválido.', 'ctt-correio-registado' ) . '</p>';
    }
}

    public function cttcr_get_order_tracking_number() {
        // Verifica o nonce
        if ( ! isset( $_POST['cttcr_nonce'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Falha na verificação de segurança.', 'ctt-correio-registado' ) ) );
		}

		$cttcr_nonce = sanitize_text_field( wp_unslash( $_POST['cttcr_nonce'] ) );

		if ( ! wp_verify_nonce( $cttcr_nonce, 'cttcr_nonce_action' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Falha na verificação de segurança.', 'ctt-correio-registado' ) ) );
		}

        // Valida permissões
        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Você não tem permissão para ver o número de rastreamento.', 'ctt-correio-registado' ) ) );
        }

        // Valida order ID
        if ( ! isset( $_POST['order_id'] ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'ID de pedido inválido.', 'ctt-correio-registado' ) ) );
        }

        $order_id = intval( $_POST['order_id'] );
        if ( $order_id <= 0 ) {
            wp_send_json_error( array( 'message' => esc_html__( 'ID de pedido inválido.', 'ctt-correio-registado' ) ) );
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Pedido não encontrado.', 'ctt-correio-registado' ) ) );
        }

        $tracking_number = $order->get_meta( '_cttcr_tracking_number' );

        if ( $tracking_number ) {
            wp_send_json_success( array( 'tracking_number' => esc_html( $tracking_number ) ) );
        } else {
            wp_send_json_error( array( 'message' => esc_html__( 'Nenhum número de rastreamento encontrado.', 'ctt-correio-registado' ) ) );
        }
    }

    public function cttcr_complete_order_with_tracking() {
        // Verifica o nonce
		if ( ! isset( $_POST['cttcr_nonce'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Falha na verificação de segurança.', 'ctt-correio-registado' ) ) );
		}

		$cttcr_nonce = sanitize_text_field( wp_unslash( $_POST['cttcr_nonce'] ) );

		if ( ! wp_verify_nonce( $cttcr_nonce, 'cttcr_nonce_action' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Falha na verificação de segurança.', 'ctt-correio-registado' ) ) );
		}

        // Valida permissões
        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Permissão negada.', 'ctt-correio-registado' ) ) );
        }

        // Valida dados
        if ( ! isset( $_POST['order_id'], $_POST['tracking_number'] ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Dados inválidos.', 'ctt-correio-registado' ) ) );
        }

        $order_id = intval( $_POST['order_id'] );
        $tracking_number = sanitize_text_field( wp_unslash( $_POST['tracking_number'] ) );

        if ( empty( $tracking_number ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'O número de rastreamento não pode estar vazio.', 'ctt-correio-registado' ) ) );
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Pedido não encontrado.', 'ctt-correio-registado' ) ) );
        }

        // Atualiza o número de rastreamento
        $order->update_meta_data( '_cttcr_tracking_number', $tracking_number );

        // Adiciona uma nota ao pedido e altera o status
        // translators: %s será substituído pelo número de rastreamento
		$note = sprintf( esc_html__( 'Pedido concluído com número de rastreamento: %s', 'ctt-correio-registado' ), esc_html( $tracking_number ) );
        $order->update_status( 'completed', $note );
        $order->save();

        wp_send_json_success( array( 'message' => esc_html__( 'Pedido concluído com sucesso.', 'ctt-correio-registado' ) ) );
    }

} // Fim da classe
endif;

new CTTCR_Correio_Registado();