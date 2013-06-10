# CampTix PagSeguro #
**Contributors:** claudiosanches, rafaelfunchal  
**Donate link:** http://claudiosmweb.com/doacoes/  
**Tags:** camptix, pagseguro  
**Requires at least:** 3.4  
**Tested up to:** 3.5.1  
**Stable tag:** 1.5.3  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Adds PagSeguro gateway to the CampTix plugin

## Description ##

### Add PagSeguro gateway to CampTix ###

This plugin adds PagSeguro gateway to [CampTix](wordpress.org/extend/plugins/camptix/).

Please notice that CampTix must be installed and active.

### Descrição em Português: ###

Adicione o PagSeguro como método de pagamento em seu [CampTix](wordpress.org/extend/plugins/camptix/).

[PagSeguro](https://pagseguro.uol.com.br/) é um método de pagamento brasileiro desenvolvido pela UOL.

O plugin CampTix PagSeguro foi desenvolvido sem nenhum incentivo do PagSeguro ou da UOL. Nenhum dos desenvolvedores deste plugin possuem vínculos com estas duas empresas.

Este plugin desenvolvido a partir da [documentação oficial do PagSeguro](https://pagseguro.uol.com.br/v2/guia-de-integracao/visao-geral.html).

### Instalação: ###

Confira o nosso guia de instalação e configuração do PagSeguro na aba [Installation](http://wordpress.org/extend/plugins/camptix-pagseguro/installation/).

### Dúvidas? ###

Você pode esclarecer suas dúvidas usando:

* A nossa sessão de [FAQ](http://wordpress.org/extend/plugins/camptix-pagseguro/faq/).
* Criando um tópico no repositório do plugin no [GitHub](https://github.com/claudiosmweb/camptix-pagseguro).
* Criando um tópico no [fórum de ajuda do WordPress](http://wordpress.org/support/plugin/camptix-pagseguro) (apenas em inglês).

## Installation ##

* Upload plugin files to your plugins folder, or install using WordPress built-in Add New Plugin installer;
* Activate the plugin;
* Navigate to CampTix -> Setup -> Payment, active the PagSeguro and fill in your PagSeguro Email and Token.

### Instalação e configuração em Português: ###

### Instalação do plugin: ###

* Envie os arquivos do plugin para a pasta wp-content/plugins, ou instale usando o instalador de plugins do WordPress;
* Ative o plugin.

### Requerimentos: ###

É necessário possuir uma conta no [PagSeguro](http://pagseguro.uol.com.br/) e ter instalado o [CampTix](wordpress.org/extend/plugins/camptix/).

### Configurações no PagSeguro: ###

* Gere um token em [Minha conta > Integrações > Token de Segurança](https://pagseguro.uol.com.br/integracao/token-de-seguranca.jhtml);
* Ative os pagamentos via API indo até [Minha conta > Integrações > Pagamentos via API](https://pagseguro.uol.com.br/integracao/pagamentos-via-api.jhtml).

Com estas configurações a sua conta no PagSeguro estará pronta para receber os pagamentos de sua loja.

### Configurações do Plugin: ###

Com o plugin instalado acesse o admin do WordPress e entre em `CampTix > Setup > Payment > PagSeguro`.

Habilite o PagSeguro e adicione o seu e-mail e o token cadastrados no PagSeguro.

**Atenção: O PagSeguro não faz redirecionamentos ou retorno de dados se você estiver em localhost!**

## Frequently Asked Questions ##

### What is the plugin license? ###

* This plugin is released under a GPL license.

### What is needed to use this plugin? ###

* [CampTix](wordpress.org/extend/plugins/camptix/) installed and active;
* Only one account on [PagSeguro](http://pagseguro.uol.com.br/).

### FAQ em Português: ###

### Qual é a licença do plugin? ###

Este plugin esta licenciado como GPL.

### O que eu preciso para utilizar este plugin? ###

* Ter instalado o plugin CampTix;
* Possuir uma conta no PagSeguro;
* Gerar um token de segurança no PagSeguro;
* Habilitar os pagamentos via API.

### Como funciona o PagSeguro? ###

* Saiba mais em [PagSeguro - Como funciona](https://pagseguro.uol.com.br/para_seu_negocio/como_funciona.jhtml).
* Ou acesse a [FAQ do PagSeguro](https://pagseguro.uol.com.br/atendimento/perguntas_frequentes.jhtml).

### PagSeguro recebe pagamentos de quais países? ###

No momento o PagSeguro recebe pagamentos apenas do Brasil.

### Quais são os meios de pagamento que o plugin aceita? ###

São aceitos todos os meios de pagamentos que o PagSeguro disponibiliza.
Entretanto você precisa ativa-los na sua conta no PagSeguro.

Confira os meios de pagamento em [PagSeguro - Meios de Pagamento e Parcelamento](https://pagseguro.uol.com.br/para_voce/meios_de_pagamento_e_parcelamento.jhtml#rmcl).

### Quais são as taxas de transações que o PagSeguro cobra? ###

Para estas informações consulte: [PagSeguro - Taxas e Tarifas](https://pagseguro.uol.com.br/taxas_e_tarifas.jhtml).

### Quais são os limites de recebimento do PagSeguro? ###

Consulte: [PagSeguro - Tabela de Limites](https://pagseguro.uol.com.br/account/limits.jhtml).

### Como que plugin faz integração com PagSeguro? ###

Fazemos a integração baseada na documentação oficial do PagSeguro que pode ser encontrada em "[Guia de integração - PagSeguro](https://pagseguro.uol.com.br/v2/guia-de-integracao/visao-geral.html)".

### Porque o PagSeguro não redireciona depois do pagamento quando estou em Localhost? ###

O PagSeguro não aceitar URLs de localhost.

Para testar o retorno automático de dados é necessário publicar o site utilizando um domínio.

### Mais dúvidas relacionadas ao funcionamento do plugin? ###

Entre em contato [clicando aqui](http://claudiosmweb.com/contato/).

## Screenshots ##

### 1. Settings page ###
![1. Settings page](http://s.wordpress.org/extend/plugins/camptix-pagseguro/screenshot-1.png)


## Changelog ##

### 1.5.3 - 09/06/2013 ###

* Fixed the logs.

### 1.5.2 - 30/05/2013 ###

* Fixed the payment_notify method.

### 1.5.1 - 29/05/2013 ###

* Fixed the get_payment_status method.

### 1.5.0 - 28/05/2013 ###

* Added the plugin settings page url in plugins page.
* Fixed the translation.

### 1.4.0 - 23/05/2013 ###

* Reorganizing some files, refactoring, adding some additional checks, optimizing lookups (thanks Konstantin Kovshenin).
* Improved again the payment notify.

### 1.3.1 - 15/05/2013 ###

* Improved the payment_notify method.

### 1.3.0 ###

* Created the screenshot.
* Fixed issue with PagSeguro URLs in localhost.

### 1.2.0 ###

* Added payment notify.
* Added pt_BR translation.

### 1.1.0 ###

* Added payment return.

### 1.0.0 ###

* Initial version.

## Upgrade Notice ##

### 1.5.3 ###

* Fixed the logs.

## License ##

CampTix PagSeguro is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

CampTix PagSeguro is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with CampTix PagSeguro. If not, see <http://www.gnu.org/licenses/>.
