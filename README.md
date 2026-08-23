# Gift Lab

Catálogo online responsivo da Gift Lab. Visitantes descobrem produtos e seguem para canais externos de venda; não há carrinho, checkout ou conta de comprador.

## Stack

- PHP 8.2+ e Laravel 12
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

O site estará em `http://127.0.0.1:8000`. A administração fica em `/admin`; ela não possui link na área pública e exige um usuário criado pelo comando acima.

## Desenvolvimento

```bash
composer run dev
```

O comando inicia servidor, fila, logs e Vite. Para trabalhar separadamente, use `php artisan serve` e `npm run dev`.

## Catálogo e administração

O catálogo público oferece busca, filtros, ordenação, categorias, favoritos via LocalStorage, compartilhamento, produtos relacionados, estados de disponibilidade e links externos configuráveis. O painel contém dashboard, produtos, categorias e configurações essenciais da marca/home.

Os registros criados por `db:seed` são demonstrações fictícias. O seed nunca cria uma senha administrativa.

## Preços e segurança

Valores monetários são persistidos como `DECIMAL`. `final_price` é calculado a partir do preço de venda e desconto; o custo é oculto pelo model e nunca renderizado nas páginas públicas. O admin usa autenticação, sessão, CSRF, rate limiting, Form Requests, validação de upload e middleware de autorização.

Links externos usam `noopener noreferrer`. O formulário de contato possui honeypot, validação, CSRF e limite de requisições.

## Testes e qualidade

```bash
php artisan test
vendor/bin/pint --test
npm run build
```

Os testes cobrem páginas públicas, status publicável, preço/desconto, sigilo do custo, estoque/sob encomenda e controle de acesso administrativo.

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
