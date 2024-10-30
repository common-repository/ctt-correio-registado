=== CTT Correio Registado ===
Contributors: pedromiguelmartins
Tags: woocommerce, rastreamento, ctt correio registado, tracking, shipping
Requires at least: 4.7
Tested up to: 6.6
Stable tag: 1.0.6
Requires PHP: 7.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
WC requires at least: 3.0
WC tested up to: 8.1.1
Requires Language: pt_PT

Associe o número de rastreamento do CTT Correio Registado e acompanhe a entrega. Imprima os dados no Talão de Aceitação e poupe tempo e evite erros.

== Description ==

O plugin **CTT Correio Registado** integra-se com o WooCommerce, permitindo associar o número de rastreamento do serviço CTT Correio Registado a cada encomenda e facilitar o acompanhamento da entrega tanto para o cliente como para o gestor da loja. Assim que a encomenda é concluída, o número de rastreamento é automaticamente enviado por email ao cliente.

Além disso, o plugin automatiza a impressão dos dados do cliente no Talão de Aceitação em papel, eliminando a necessidade de preenchimento manual e reduzindo o tempo e o risco de erros no processo de envio.

**Principais funcionalidades:**

* Inserção do número de rastreamento diretamente na página de detalhe da encomenda.
* Link automático para rastreamento: 1. no email de "Conclusão" da encomenda para os clientes; 2. e na página de detalhe da encomenda.
* Impressão dos dados do cliente no Talão de Aceitação em papel. Basta colocar a folha na impressora e configurar o tamanho do papel para A5.

== Instalação ==

1. Faça o upload dos ficheiros do plugin para o diretório `/wp-content/plugins/ctt-correio-registado`, ou instale o plugin diretamente através do painel do WordPress.
2. Ative o plugin através do menu "Plugins" no WordPress.
3. Certifique-se de que o WooCommerce está instalado e ativo.
4. O plugin está pronto para uso. Encontrará o campo para inserir o número de rastreamento na página de edição das encomendas.

== Versão PRO ==
* **CTT Correio Registado PRO**: Oferece um painel de expedição de encomendas que permite filtrar as encomendas para mostrar apenas as de CTT Correio Registado e preencher todos os números de rastreamento num único local, facilitando o processo de envio. Além disso, permite a impressão em série dos Talões de Aceitação, poupando tempo e evitando a necessidade de abrir encomenda a encomenda. <a href="https://pedromartins.com/produto/ctt-correio-registado-pro/" target="_blank">Descarregar</a>.


== Instale também ==
* **Imprimir Etiquetas para Envio de Encomendas**: Imprima etiquetas de envio de encomendas diretamente do WooCommerce, com layout otimizado para folhas A4, cada uma com 12 etiquetas de 105 x 48 mm. <a href="https://wordpress.org/plugins/imprimir-etiquetas-para-envio-de-encomendas/" target="_blank">Descarregar</a>.
* **Imprimir Etiquetas para Envio de Encomendas PRO**: Incluí um painel próprio para impressão de múltiplas etiquetas de encomendas de uma só vez e a escolha de vários formatos de etiquetas. <a href="https://pedromartins.com/produto/imprimir-etiquetas-para-envio-de-encomendas-pro/" target="_blank">Descarregar</a>.


==  Frequently Asked Questions ==

= Onde encontro o número de rastreamento? =

O número de rastreamento encontra-se no respetivo Talão de Aceitação em papel dos CTT, no canto superior direito, junto ao código de barras. Copie sem os espaços entre os números, como neste exemplo: "RL193378648PT".

= Como configurar a impressão? =

**No Windows:**

No painel de impressão, configure o "Tamanho do papel" para A5 e desmarque a opção "Cabeçalhos e rodapés". Verifique a orientação da folha na impressora, pois varia conforme a marca e o modelo.

**No macOS:**

No diálogo de impressão, selecione "A5" como o tamanho do papel. Nas opções de impressão, desmarque a caixa "Imprimir cabeçalhos e rodapés". Verifique também a orientação do papel na impressora, pois pode variar dependendo da marca e modelo.

== Screenshots ==

1. **Campo de inserção do número de rastreamento na página de administração da encomenda.**
2. **Exemplo de formulário preenchido automaticamente com os dados do cliente.**

== Changelog ==

= 1.0.6 =
* Atualização do ficheiro readme.txt.

= 1.0.5 =
* Atualização do ficheiro readme.txt.

= 1.0.4 =
* Reposicionar o botão "Imprimir registo" para ficar dentro da div class="cttcr".

= 1.0.3 =
* Adição de botão "Guardar" junto ao campo do Número de Rastreamento. Permite guardar o número sem atualizar estado da encomenda.
* Melhoria à consistência do código.
* Correção do ficheiro JS da requisição AJAX para salvar o número de rastreamento.

= 1.0.2 =
* Correção da informação mostrada no email de Conclusão da encomenda.

= 1.0.1 =
* Correção da descrição
* Correção na impressão do Código Postal

= 1.0 =
* Lançamento inicial do plugin com funcionalidades de rastreamento e impressão de dados.

== Upgrade Notice ==

= 1.0 =
Primeira versão estável. Atualize para obter as funcionalidades de rastreamento de encomendas e impressão automática dos formulários dos CTT.

== Aviso Legal ==

Este plugin não é afiliado, patrocinado, aprovado ou de qualquer forma associado aos CTT – Correios de Portugal, S.A. "CTT", "Correios de Portugal", "Correio Registado" e outras marcas, nomes ou termos relacionados são propriedade dos CTT – Correios de Portugal, S.A. O uso desses termos no contexto deste plugin é meramente descritivo, com o propósito de identificar um serviço e funcionalidade relacionada ao rastreamento de encomendas, sem qualquer vínculo ou aprovação por parte da entidade titular das marcas.

== Notas ==

Este plugin foi testado com as versões mais recentes do WordPress e do WooCommerce para garantir compatibilidade.