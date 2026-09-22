# Gift Lab

Catálogo online responsivo da Gift Lab. Visitantes descobrem produtos e seguem para canais externos de venda; não há carrinho, checkout ou conta de comprador.

## Stack

- PHP 8.2+ e Laravel 12; extensões GD com WebP (imagens), BCMath, PDO, mbstring, fileinfo e XML
- Blade, Tailwind CSS 4, Alpine.js e Vite
- MySQL 8+ (SQLite pode ser usado em testes)
- Laravel Storage, migrations, seeders, Form Requests e middleware de autorização

## Instalação

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Configure `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD` no `.env`, depois execute:

```bash
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan admin:create
php artisan serve
```

O site estará em `http://127.0.0.1:8000`. A administração fica em `/admin` e exige um administrador. O link público Admin aparece somente para administradores já autenticados.

## Desenvolvimento

```bash
composer run dev
```

O comando inicia servidor, fila, logs e Vite. Para trabalhar separadamente, use `php artisan serve` e `npm run dev`.

## Catálogo e administração

O catálogo oferece busca (incluindo descrição completa), filtros de preço, condição, promoção e disponibilidade, categorias, favoritos locais, compartilhamento, conjuntos e links externos. O painel gerencia produtos, atributos, variações, categorias hierárquicas, canais, FAQ, banners, páginas institucionais, mensagens e configurações de marca/Home/contato/SEO.

O Financeiro registra vendas, fiados com vários itens, contas parceladas, extrato e metas. A visão geral apresenta saldo acumulado (tudo recebido, com frete e taxas, menos tudo pago) e total a pagar, além dos indicadores mensais. Fiados só entram no saldo e nas metas após recebimento; reaberturas, edições e exclusões são refletidas na próxima consulta. Cálculos usam centavos inteiros. Estoque continua manual; não há integração bancária.

Os registros criados por `db:seed` são demonstrações fictícias, com canais de exemplo identificados e links em example.com. O seed preserva registros existentes e nunca cria senha administrativa. Não executar seed como procedimento de atualização da loja.

## Preços e segurança

Valores monetários são persistidos como `DECIMAL`. Preços públicos ficam nos anúncios de cada canal; cards mostram o menor preço ativo. Preço anterior opcional permite promoção por canal. Campos globais antigos de preço/desconto permanecem somente por compatibilidade. Custo é privado. O admin usa autenticação, sessão, CSRF, rate limiting, validação e middleware de autorização.

Links externos usam `noopener noreferrer`. O formulário de contato possui honeypot, validação, CSRF e limite de requisições.

Mensagens ficam no painel Conteúdo → Mensagens. Para notificações por e-mail, configurar SMTP/remetente no `.env` e ativar a opção em Configurações. Falhas de e-mail não apagam a mensagem. Vídeos e miniaturas do YouTube dependem da permissão de mídia externa, configurável no rodapé.

Uploads novos geram versões WebP de detalhe, catálogo e miniatura. Para otimizar imagens anteriores, executar opcionalmente `php artisan images:optimize`; o comando preserva originais como recuperação. GD/WebP e acesso ao disco público são necessários.

## Testes e qualidade

```bash
php artisan test
vendor/bin/pint --test
npm run build
```

Os testes cobrem catálogo, acesso, preços/canais, saldo acumulado e mudanças financeiras, metas, fiados, conjuntos, conteúdo, uploads, hierarquia, consentimento no HTML e falhas de frete. PHPUnit usa SQLite em memória. No Windows com GD não habilitado no php.ini, pode-se validar com `php -d extension=gd vendor/phpunit/phpunit/phpunit`; para uploads web, habilitar a extensão também no servidor PHP.

## Publicação

Em produção configure `APP_ENV=production`, `APP_DEBUG=false`, URL, MySQL, mail e cookies seguros. Aponte o servidor web para `/public` e execute:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

Garanta escrita em `storage/` e `bootstrap/cache/`, HTTPS e um processo de backup do banco e dos uploads.

A atualização de 22/09/2026 requer a migration de conteúdo, redirecionamentos, imagens e preço anterior por canal. Confirmar GD/WebP na hospedagem antes de publicar. O workflow já aplica migrations; não publicar apenas as views sem o backend/schema correspondente. Para o layout específico da Hostinger, seguir `DEPLOYMENT.md`.

## Comandos úteis

```bash
php artisan admin:create
php artisan migrate:fresh --seed
php artisan optimize:clear
php artisan route:list
php artisan storage:link
```

## Memória operacional

O diretório local `/docs` contém as referências e `PROJECT_HISTORY.md`. Ele é ignorado pelo Git conforme requisito. Leia o histórico antes de qualquer alteração futura e acrescente uma entrada ao concluir mudanças relevantes.
