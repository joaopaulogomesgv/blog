=== Blog Automático com IA ===
Contributors: blogautomatico
Tags: ai, blog, automatic, elementor, seo, openai, gemini, grok, deepseek, content generation
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.3.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cria posts de blog automaticamente usando IA (OpenAI ChatGPT, Google Gemini, xAI Grok e DeepSeek) com templates Elementor Pro e otimização SEO.

== Description ==

O **Blog Automático com IA** transforma suas ideias em artigos completos de blog. Você pode colar 100+ ideias de uma vez só e ativar o piloto automático para postar 1 ou 2 artigos por dia sem qualquer intervenção manual!

* Suporte Multi-IA: OpenAI (GPT-4o), Google Gemini (Gemini 2.5 Flash / Pro), xAI (Grok 3) e DeepSeek (Chat / Reasoner)
* Fila em Massa: Cole até centenas de temas de uma única vez
* Piloto Automático: Define 1 ou 2 posts diários com agendamento autônomo e janelas de horário
* Imagens em Destaque geradas via DALL-E ou Grok Image
* Elementor Pro: Layouts responsivos modernos injetados automaticamente
* SEO Integrado: Preenchimento automático de meta title, meta description e Schema Markup (FAQPage e Article)
* Dashboard analítico com consumo de tokens e histórico completo
* **NOVO: Treinamento de IA** — Personalize a escrita para que seja 100% indetectável como IA

== Installation ==

1. Desinstale qualquer versão anterior caso já tenha instalado no WordPress
2. Faça o upload do arquivo `blog-automatico.zip` através do painel do WordPress em 'Plugins > Adicionar Novo > Enviar Plugin'
3. Ative o plugin
4. Vá até 'Blog Automático > Configurações', selecione seu provedor preferido e insira sua API Key
5. Vá em 'Fila de Conteúdo', cole seus assuntos e ative o Piloto Automático!

== Changelog ==

= 1.3.2 =
* Botão de exclusão (lixeira) adicionado na tabela de Últimas Gerações no Dashboard
* Botão de exclusão individual adicionado no Histórico de Artigos Gerados
* Opção de limpar falhas em massa ou limpar histórico completo
* Endpoints AJAX seguros (ba_delete_log e ba_clear_logs) com confirmação e animação sem recarregar a página

= 1.3.1 =
* Correção de sobrecarga ("High Demand" / HTTP 503 e 429) no Google Gemini com retry exponencial automático
* Sistema de Fallback Automático entre modelos Gemini (tenta automaticamente modelos alternativos caso o selecionado sofra pico de demanda)
* Inclusão do Gemini 2.5 Flash como modelo recomendado e mais estável da família Google
* Tratamento e mensagens amigáveis em português para instabilidades temporárias de servidores

= 1.3.0 =
* Nova aba "Treinamento de IA" para personalização completa da escrita
* Persona do escritor: defina a identidade, experiência e tom de voz que a IA deve adotar
* Nível de humanização configurável (leve, moderado, alto) com variação de frases e parágrafos
* Campo de textos de referência: cole seus textos reais para a IA copiar seu estilo pessoal
* Lista de palavras/expressões proibidas (ex: "vale ressaltar", "é importante destacar") para eliminar marcas de IA
* Lista de expressões preferidas para tornar o texto mais pessoal e natural
* Regras personalizadas livres para instruções específicas do usuário
* Sistema anti-detecção: evita padrões previsíveis de IA (estrutura uniforme, conectivos excessivos, introduções genéricas)
* Integração total com o gerador de conteúdo — todas as configurações são injetadas nos prompts automaticamente

= 1.2.0 =
* Layout 100% fluido e adaptado para telas amplas, monitores Full HD, 2K, 4K e ultrawide: remoção da trava de largura fixa de 1280px.
* As seções de configurações, piloto automático, opções de artigos e tabelas da fila agora aproveitam a largura total da área útil do navegador.
* Calibração de padding e grid para proporcionar máxima ergonomia visual sem espaços vazios laterais.

= 1.1.9 =
* Atualização dos modelos Google Gemini para Gemini 3.8 Flash e Gemini 3.1 Pro Preview (substituindo modelos legados descontinuados pelo Google).
* Fallback automático para gemini-3.8-flash caso o modelo salvo anteriormente na base fosse gemini-2.0-flash.
* Suporte a teste de conexão em tempo real utilizando os valores digitados no formulário.
* Aumento da cota de tokens no teste de conexão para compatibilidade com o processamento de raciocínio (thinking) dos modelos recentes do Gemini.
* Parsing aprimorado para capturar texto em múltiplos blocos de resposta da API do Google.

= 1.1.8 =
* Correção dos ícones de setas nos campos select: novo chevron moderno, sutil e proporcional com viewBox 24x24 e background-size fixo de 14px
* Removidas distorções visuais e setas ampliadas que apareciam em resoluções com zoom/DPI scaling do Windows
* Calibração de alinhamento vertical e proporção dos dashicons nos botões de ação

= 1.1.7 =
* Nova paleta de botões e destaques na cor `#B4D443` com contraste WCAG perfeito (texto escuro, ícones nítidos e hover suave)
* Blindagem total dos alertas com texto branco límpido sobre fundo escuro sólido e links destacados em `#B4D443`
* Fim definitivo de avisos esbranquiçados ou ilegíveis

= 1.1.6 =
* Correção definitiva de visibilidade e contraste dos avisos: remoção de classes conflitantes do WordPress core
* Avisos agora utilizam fundo escuro sólido de alto contraste com texto perfeitamente nítido e legível

= 1.1.5 =
* Correção de layout: campo de ideias principal agora ocupa 100% da largura com altura ampla e protagonismo
* Novo grid responsivo de 4 colunas para as opções do artigo
* Campo de fila de 100+ ideias expandido com largura total

= 1.1.4 =
* Visual modo noturno sóbrio e profissional: remoção completa de cores roxas e gradientes
* Fontes e tipografia calibradas para proporções nativas do WordPress
* Botões no azul clássico do WordPress e switch funcional em verde/cinza
* Banners e inputs limpos e discretos

= 1.1.3 =
* Adicionados botões com links diretos oficiais para gerar e copiar a API Key de cada IA (Google AI Studio, OpenAI, xAI Grok e DeepSeek)
* Reformulação total do switch do Piloto Automático em card isolado sem conflitos com o WordPress
* Blindagem de inputs e selects escuros impedindo que o WordPress force fundo branco
* Design refinado de alta legibilidade

= 1.1.2 =
* Experiência visual Full Immersion: eliminação total das bordas e faixas brancas do WordPress Admin
* Glassmorphism refinado, novos gradientes de profundidade e iluminação neon sutil
* Botões com elevação dinâmica e microinterações

= 1.1.1 =
* Correção de contraste e tema escuro no WordPress Admin
* Envelopamento do painel administrativo em canvas dark moderno
* Ajuste de visibilidade nos avisos (notices) e banners de automação

= 1.1.0 =
* Adicionado suporte a múltiplos provedores de IA: Google Gemini, xAI (Grok), DeepSeek e OpenAI
* Adicionado sistema de Piloto Automático para publicação autônoma diária (1, 2 ou mais posts por dia)
* Adicionada nova tela 'Fila de Conteúdo' com importação em massa de 100+ tópicos (bulk add)
* Redesign visual completo da interface administrativa (Design System Dark moderno e polido, Dashicons oficiais)
* Sincronização dinâmica de modelos por provedor via AJAX
* Suporte a janelas de horário configuráveis para publicações automáticas

= 1.0.0 =
* Lançamento inicial
* Geração de conteúdo via GPT-4o
* Geração de imagens via DALL-E 3
* Integração com Elementor Pro
* Suporte a Yoast SEO e RankMath
* Schema markup automático (FAQ + Article)
