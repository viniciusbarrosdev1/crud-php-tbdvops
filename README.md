# CRUD de Clientes com PHP, MySQL e Docker Compose

Aplicação web simples de cadastro de clientes, feita em PHP puro (padrão MVC) com banco
MySQL, rodando inteiramente em containers orquestrados pelo Docker Compose. Não é preciso
ter PHP nem MySQL instalados na máquina — só Docker.

A entidade escolhida foi **Cliente**, com os campos:

| Campo           | Tipo                | Observação                                      |
|-----------------|---------------------|-------------------------------------------------|
| `id`            | VARCHAR(32), PK     | Identificador único gerado pela aplicação        |
| `nome`          | VARCHAR(100)        | Obrigatório, mínimo de 3 caracteres              |
| `cpf`           | VARCHAR(14)         | Obrigatório, validado pelos dígitos verificadores|
| `descricao`     | TEXT                | Opcional, até 500 caracteres                     |
| `data_cadastro` | DATETIME            | Preenchido pelo banco no momento do INSERT       |

O que a aplicação faz:

- **Listagem** de todos os clientes, do mais recente para o mais antigo, com filtro por nome ou CPF.
- **Cadastro** por formulário, enviado via POST.
- **Edição** por formulário já preenchido com os dados do registro.
- **Exclusão** com caixa de confirmação antes de apagar.

Além do CRUD, o formulário valida os dados no servidor: nome com tamanho mínimo, CPF com
os dois dígitos verificadores conferidos, e limite de caracteres na descrição. Quando algo está
errado, a página volta com as mensagens e mantém o que o usuário já tinha digitado.

## 1. Pré-requisitos

- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/) (já vem junto no Docker Desktop e
  nas versões atuais do Docker Engine, como plugin `docker compose`)

Nada além disso. PHP, Composer, Apache e MySQL ficam todos dentro dos containers.

## 2. Como executar

**1. Clone o repositório e entre na pasta**

```bash
git clone https://github.com/viniciusbarrosdev1/crud-php-tbdvops.git
cd crud-php-tbdvops
```

**2. Suba os containers**

```bash
docker compose up -d
```

Na primeira execução o Docker vai construir a imagem do PHP a partir do `Dockerfile` e baixar
as imagens do MySQL e do phpMyAdmin, o que leva alguns minutos. Nas próximas vezes é
quase instantâneo.

**3. Acesse a aplicação**

- Aplicação: <http://localhost:8080>
- phpMyAdmin (para ver o banco por dentro): <http://localhost:8081> — usuário `root`, senha `root`

**4. Para derrubar o ambiente**

```bash
docker compose down          # para os containers, mantém os dados do banco
docker compose down -v       # para os containers e apaga o volume, zerando o banco
```

### Como a tabela é criada

Não há script SQL para rodar na mão. A criação é automática, em duas etapas:

1. O serviço `mysql` recebe a variável `MYSQL_DATABASE: clientes_db`, e a própria imagem oficial
   do MySQL cria esse banco vazio na primeira inicialização do container.
2. A tabela `clientes` é criada pelo código PHP. Em `src/Config/Database.php`, o método
   `getConnection()` abre a conexão PDO e, logo em seguida, executa um
   `CREATE TABLE IF NOT EXISTS clientes (...)`. Como é `IF NOT EXISTS`, isso roda a cada
   requisição sem causar erro nem apagar dados: na primeira vez cria a tabela, nas seguintes
   não faz nada.

Ou seja, basta abrir <http://localhost:8080> uma vez e o banco já está pronto para uso.

Vale um detalhe sobre a ordem de subida: o `depends_on` garante que o container do MySQL
comece antes do container da aplicação, mas não garante que o MySQL já esteja aceitando
conexões — ele demora alguns segundos para inicializar. Para resolver isso sem usar
`healthcheck` (proibido no trabalho), a própria conexão PDO em `Database.php` tenta conectar
até 10 vezes, com 2 segundos de intervalo entre as tentativas. Na prática, a primeira
requisição pode demorar um pouco e depois tudo flui normalmente.

## 3. Estrutura do projeto

```
.
├── docker-compose.yml     # Orquestração dos três containers
├── Dockerfile             # Imagem da aplicação: PHP 8.2 + Apache + extensões PDO
└── src/                   # Código da aplicação (montado dentro do container)
    ├── index.php          # Ponto de entrada: recebe a ação, valida e escolhe a view
    ├── helpers.php        # Funções de apoio das views (escape, ícones, datas, CPF)
    ├── Config/
    │   └── Database.php   # Conexão PDO e criação automática da tabela
    ├── Model/
    │   └── Cliente.php    # Consultas SQL do CRUD, todas com prepared statements
    ├── Controller/
    │   └── ClienteController.php  # Faz a ponte entre a requisição e o Model
    ├── View/
    │   ├── listar.php     # Página de listagem
    │   ├── form.php       # Formulário de cadastro e de edição
    │   └── partials/      # Cabeçalho e rodapé reaproveitados pelas páginas
    └── public/assets/
        └── app.css        # Estilos da aplicação
```

## 4. Explicação do docker-compose.yml

O arquivo está comentado linha a linha. Abaixo, a explicação em texto de cada parte.

### Serviço `app` — a aplicação PHP

É o container que o navegador acessa. Diferente dos outros dois, ele não usa uma imagem
pronta direto do Docker Hub: usa `build: .`, ou seja, o Docker monta a imagem a partir do
`Dockerfile` do projeto. Isso foi necessário porque a imagem `php:8.2-apache` não vem com as
extensões `pdo` e `pdo_mysql` habilitadas, e sem elas o PHP não conversa com o MySQL. O
`Dockerfile` parte da imagem oficial e apenas instala essas extensões.

A porta `80` do Apache dentro do container é publicada na porta `8080` da máquina, por isso o
acesso é em `localhost:8080`.

O volume `./src:/var/www/html` espelha a pasta de código do projeto dentro do container. Isso
serve para duas coisas: durante o desenvolvimento, qualquer arquivo editado já aparece no site
sem precisar reconstruir a imagem; e faz com que o `index.php` fique exatamente na raiz que o
Apache serve.

### Serviço `mysql` — o banco de dados

Usa a imagem oficial `mysql:8.0`. A versão foi fixada de propósito, em vez de usar `latest`, para
que o projeto continue subindo igual daqui a alguns meses.

Duas variáveis de ambiente configuram o banco na primeira inicialização: `MYSQL_ROOT_PASSWORD`
define a senha do root e `MYSQL_DATABASE` já cria o banco `clientes_db` vazio.

O ponto mais importante aqui é o volume `dados_mysql:/var/lib/mysql`. `/var/lib/mysql` é onde o
MySQL guarda os arquivos do banco; ao apontar um volume nomeado para esse caminho, os
dados passam a viver fora do container. Assim, `docker compose down` seguido de
`docker compose up -d` devolve o banco com todos os clientes cadastrados. Sem esse volume,
tudo seria perdido junto com o container.

A porta `3306` também foi publicada na máquina, o que é opcional para a aplicação funcionar,
mas permite conectar por um cliente externo como Workbench ou DBeaver para conferir os dados.

### Serviço `phpmyadmin` — administração do banco pelo navegador

Não é exigido pelo trabalho, mas facilita muito a demonstração: dá para mostrar as linhas
entrando e saindo da tabela em tempo real enquanto o CRUD é usado. Roda em `localhost:8081`
porque a `8080` já está ocupada pela aplicação. As variáveis `PMA_HOST`, `PMA_USER` e
`PMA_PASSWORD` dizem qual servidor administrar e já fazem o login automático.

### Variáveis de ambiente

Conforme o enunciado, nenhuma configuração fica em arquivo `.env` — está tudo na seção
`environment` do próprio compose. O serviço `app` recebe quatro variáveis, lidas pelo PHP com
`getenv()` em `src/Config/Database.php` para montar a string de conexão do PDO:

| Variável      | Valor         | Para que serve                                             |
|---------------|---------------|------------------------------------------------------------|
| `DB_HOST`     | `mysql`       | Endereço do banco — é o nome do serviço, não um IP          |
| `DB_USER`     | `root`        | Usuário usado na conexão                                    |
| `DB_PASSWORD` | `root`        | Senha desse usuário                                         |
| `DB_NAME`     | `clientes_db` | Banco que a aplicação utiliza                               |

Repare que `DB_HOST` é `mysql`, o mesmo nome dado ao serviço do banco. Nenhum endereço IP
aparece no código.

### A rede `clientes_net`

Foi criada uma rede bridge personalizada e os três containers foram ligados a ela. É ela que
permite o `DB_HOST: mysql` funcionar: dentro de uma rede personalizada, o Docker mantém um
servidor DNS interno que resolve o nome de cada serviço para o IP do container correspondente.
Como esses IPs mudam a cada subida, depender deles seria inviável — usar o nome do serviço
deixa a configuração estável.

A rede também isola o tráfego: os containers conversam entre si por ela, e do lado de fora só é
possível alcançar o que foi explicitamente publicado em `ports`.

## 5. Pontos interessantes observados

**1. O `depends_on` garante ordem, não prontidão.** Foi o problema que mais consumiu tempo.
Subindo tudo pela primeira vez, a aplicação quebrava com erro de conexão mesmo com o
`depends_on` configurado. O motivo é que ele só espera o container do MySQL *iniciar*, não o
MySQL terminar de subir e aceitar conexões — e a inicialização do banco leva alguns segundos.
Como `healthcheck` não é permitido no trabalho, resolvemos dentro do próprio PHP: a conexão
tenta até 10 vezes com 2 segundos de intervalo, e só desiste depois disso.

**2. Nome de serviço vale mais que IP.** Ao criar a rede personalizada, passamos a poder usar
`DB_HOST: mysql` em vez de um endereço. Os IPs dos containers mudam a cada `up`, então
qualquer configuração baseada em IP quebraria na próxima subida. Com o DNS interno do
Docker, a configuração não muda nunca.

**3. Volume nomeado é o que separa dado de container.** Testamos na prática: cadastramos
clientes, rodamos `docker compose down`, subimos de novo e os dados continuavam lá. Depois
rodamos `docker compose down -v` e o banco voltou vazio. Ficou claro que container é
descartável e que a persistência é responsabilidade do volume, não da imagem.

**4. Imagem pronta nem sempre basta.** Começamos usando `image: php:8.2-apache` direto no
compose e o PDO não encontrava o driver do MySQL. A imagem oficial vem enxuta e não habilita
`pdo_mysql` por padrão. Criamos então um `Dockerfile` curto, partindo dessa mesma
imagem e rodando `docker-php-ext-install pdo pdo_mysql`. Foi a decisão que mais mudou a
arquitetura do projeto: trocar `image:` por `build:` no serviço `app`.

**5. Configuração por variável de ambiente deixa o código genérico.** Nenhum host, usuário ou
senha está escrito no PHP — tudo vem de `getenv()`. Para apontar a aplicação para outro banco,
basta editar o compose, sem tocar em uma linha de código.

## 6. Autores

- Vinicius Barros de Oliveira
- Carlos Eduardo Tolentino Faustino dos Santos
