# Publicação automática da Gift Lab na Hostinger

O domínio de produção é **https://lojagiftlab.com**. O arquivo `.github/workflows/ci-deploy.yml` realiza automaticamente:

1. instala as dependências em um servidor temporário do GitHub;
2. confere a formatação do PHP;
3. compila o CSS e o JavaScript;
4. executa todos os testes;
5. se tudo passar e o commit estiver na `main`, envia a versão para a Hostinger;
6. executa as migrations e otimiza o Laravel;
7. confirma que o domínio está respondendo.

Pull requests apenas executam os testes. Eles nunca publicam o site.

## 0. Criar o repositório no GitHub

Este projeto ainda não possui um endereço remoto configurado. No GitHub, clique em **New repository**, use um nome como `GiftLab`, escolha **Private** e não marque a criação automática de README, `.gitignore` ou licença.

Na pasta do projeto, substitua `SEU_USUARIO` pelo seu usuário do GitHub e execute:

```bash
git add .
git commit -m "Preparar Gift Lab para produção"
git remote add origin https://github.com/SEU_USUARIO/GiftLab.git
git push -u origin main
```

Confirme antes do commit que o arquivo `.env` não aparece na lista. Ele contém senhas e está configurado para ser ignorado pelo Git.

## 1. Preparar o domínio na Hostinger

No hPanel, adicione o domínio `lojagiftlab.com` ao plano, ative o SSL e force HTTPS. O diretório público (document root) do domínio deve apontar para a pasta `public` do Laravel.

Em **Configuração PHP**, selecione PHP **8.3** (PHP 8.2 também é compatível) e mantenha habilitadas as extensões comuns do Laravel, especialmente `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `fileinfo` e `curl`.

Exemplo de organização (o nome real do usuário será diferente):

```text
/home/u123456789/domains/lojagiftlab.com/giftlab        ← HOSTINGER_DEPLOY_PATH
/home/u123456789/domains/lojagiftlab.com/giftlab/public ← raiz pública do domínio
```

Não aponte o domínio para a raiz `giftlab`, pois isso poderia expor arquivos internos do Laravel.

## 2. Criar o banco de dados

No hPanel, abra **Sites → Gerenciar → Bancos de dados MySQL** e crie:

- um banco de dados;
- um usuário do banco;
- uma senha forte.

Guarde os três dados. Eles serão usados somente no `.env` da hospedagem.

## 3. Ativar e conferir o SSH

No hPanel, abra **Sites → Gerenciar → Avançado → Acesso SSH**. Anote:

- endereço/host SSH;
- porta SSH;
- usuário SSH.

Gere no seu PC uma chave exclusiva para o GitHub Actions:

```bash
ssh-keygen -t ed25519 -C "github-actions-giftlab" -f giftlab_hostinger
```

O comando cria dois arquivos:

- `giftlab_hostinger`: chave privada, que será guardada no GitHub;
- `giftlab_hostinger.pub`: chave pública, que deve ser cadastrada na Hostinger.

Nunca envie a chave privada para o repositório ou para outra pessoa.

## 4. Fazer a primeira preparação do servidor

Entre por SSH e crie a pasta escolhida para a aplicação. Envie temporariamente uma cópia do `.env.example` com o nome `.env` para essa pasta e edite os valores:

```dotenv
APP_NAME="Gift Lab"
APP_ENV=production
APP_KEY=COLE_A_CHAVE_GERADA_AQUI
APP_DEBUG=false
APP_URL=https://lojagiftlab.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=NOME_CRIADO_NO_HOSTINGER
DB_USERNAME=USUARIO_CRIADO_NO_HOSTINGER
DB_PASSWORD=SENHA_CRIADA_NO_HOSTINGER

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Inclua também o token do Melhor Envio e o CEP de origem já usados no projeto. No seu computador, dentro do projeto, gere a chave da aplicação:

```bash
php artisan key:generate --show
```

Copie o resultado completo para `APP_KEY` no `.env` da Hostinger. Essa chave deve permanecer secreta e não pode mudar depois que o site começar a ser usado.

O workflow preserva o `.env`, as imagens enviadas e os arquivos de execução do Laravel em todos os deploys.

## 5. Cadastrar os segredos no GitHub

No repositório do GitHub, abra **Settings → Secrets and variables → Actions → New repository secret** e crie exatamente estes cinco segredos:

| Nome | Conteúdo |
|---|---|
| `HOSTINGER_SSH_HOST` | Host exibido em Acesso SSH no hPanel |
| `HOSTINGER_SSH_PORT` | Porta exibida no hPanel, normalmente `65002` |
| `HOSTINGER_SSH_USER` | Usuário SSH exibido no hPanel |
| `HOSTINGER_SSH_PRIVATE_KEY` | Todo o conteúdo do arquivo `giftlab_hostinger`, incluindo BEGIN e END |
| `HOSTINGER_DEPLOY_PATH` | Caminho absoluto da aplicação, terminando em `/giftlab` |

Não cadastre a senha do banco nesses segredos: ela fica apenas no `.env` da Hostinger.

## 6. Criar o ambiente de produção no GitHub

Abra **Settings → Environments → New environment**, informe `production` e, em **Deployment branches**, permita somente `main`. O workflow já está vinculado a esse ambiente.

## 7. Fazer a primeira publicação

Depois de concluir os passos anteriores, envie este código para a branch `main`. Abra a aba **Actions** no GitHub e acompanhe **Testes e deploy**.

Verde significa que testes e publicação terminaram. Vermelho significa que alguma etapa falhou; clique nela para ver a mensagem. O deploy não acontece se qualquer teste falhar.

Também é possível executar manualmente em **Actions → Testes e deploy → Run workflow**.

## Uso no dia a dia

Depois da configuração inicial, basta:

```bash
git add .
git commit -m "descreva o que mudou"
git push origin main
```

O restante será automático. Não execute `db:seed` na produção, pois ele insere produtos demonstrativos. Para criar o primeiro administrador, entre uma vez por SSH e execute:

```bash
php artisan admin:create
```

## Se o plano não permitir alterar a raiz pública

Pare antes de publicar e consulte o suporte da Hostinger para apontar `lojagiftlab.com` para a pasta `giftlab/public`. Não mova apenas o `index.php` sem ajustar a estrutura, e nunca exponha `.env`, `vendor`, `storage` ou o código da aplicação dentro da área pública.
