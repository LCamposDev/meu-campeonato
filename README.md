# 🏆 Meu Campeonato

API REST para simulação de campeonatos de futebol eliminatórios, desenvolvida em Laravel com PostgreSQL.

---

## 📋 Sobre o Projeto

O **Meu Campeonato** simula campeonatos eliminatórios de futebol de bairro. O sistema gerencia 8 times em um chaveamento que começa nas quartas de final, passa pelas semifinais, disputa do 3º lugar e final.

O placar de cada partida é gerado por um script Python (`teste.py`), simulando uma chamada a um modelo de inteligência artificial externo.

---

## 🛠️ Pré-requisitos

### Com Docker (recomendado)
- [Docker Desktop](https://www.docker.com/products/docker-desktop) 24+
- [Git](https://git-scm.com/)

### Sem Docker
- PHP 8.4+
- Composer 2+
- PostgreSQL 14+
- Python 3+
- Git

---

## 🚀 Instalação e Execução

### ✅ Com Docker (recomendado)

**1. Clone o repositório:**
```bash
git clone https://github.com/LCamposDev/meu-campeonato.git
cd meu-campeonato
```

**2. Copie o arquivo de variáveis de ambiente:**
```bash
cp .env.example .env
```

**3. Configure o `.env`** (as variáveis do banco já vêm preenchidas para o Docker):
```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=meu-campeonato
DB_USERNAME=seu_username
DB_PASSWORD=sua_senha
```

**4. Suba os containers:**
```bash
docker compose up -d --build
```

**5. Gere a chave da aplicação:**
```bash
docker compose exec app php artisan key:generate
```

**6. Rode as migrations:**
```bash
docker compose exec app php artisan migrate
```

**7. Acesse a API:**
```
http://localhost:8000/api/teams
```

Health check: `GET http://localhost:8000/up`

---

### 💻 Sem Docker

**1. Clone o repositório:**
```bash
git clone https://github.com/LCamposDev/meu-campeonato.git
cd meu-campeonato
```

**2. Instale as dependências:**
```bash
composer install
```

**3. Copie o arquivo de variáveis de ambiente:**
```bash
cp .env.example .env
```

**4. Configure o `.env`:**
```env
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=meu-campeonato
DB_USERNAME=postgres
DB_PASSWORD=sua_senha
```

**5. Crie o banco de dados no PostgreSQL:**
```bash
psql -U postgres -c "CREATE DATABASE \"meu-campeonato\";"
```

**6. Gere a chave da aplicação:**
```bash
php artisan key:generate
```

**7. Rode as migrations:**
```bash
php artisan migrate
```

**8. Inicie o servidor:**
```bash
php artisan serve
```

**9. Acesse a API:**
```
http://localhost:8000/api/teams
```

Health check: `GET http://localhost:8000/up`

---

## ⚙️ Variáveis de Ambiente

| Variável | Descrição | Padrão (Docker) |
|----------|-----------|-----------------|
| `APP_NAME` | Nome da aplicação | `MeuCampeonato` |
| `APP_ENV` | Ambiente | `local` |
| `APP_DEBUG` | Modo debug | `true` |
| `DB_CONNECTION` | Driver do banco | `pgsql` |
| `DB_HOST` | Host do banco | `db` |
| `DB_PORT` | Porta do banco | `5432` |
| `DB_DATABASE` | Nome do banco | `meu-campeonato` |
| `DB_USERNAME` | Usuário do banco | `postgres` |
| `DB_PASSWORD` | Senha do banco | `admin` |
| `SCORE_GENERATOR_PYTHON` | Binário do Python | `python3` |
| `SCORE_GENERATOR_SCRIPT` | Caminho do script (vazio = `teste.py` na raiz) | — |
| `SCORE_GENERATOR_FALLBACK` | Placar aleatório se o script falhar | `false` |

---

## 🧪 Rodando os Testes

### Com Docker:
```bash
# Todos os testes
docker compose exec app php artisan test

# Apenas testes unitários
docker compose exec app php artisan test --testsuite=Unit

# Apenas testes de integração
docker compose exec app php artisan test --testsuite=Feature
```

### Sem Docker:
```bash
# Todos os testes
php artisan test

# Apenas testes unitários
php artisan test --testsuite=Unit

# Apenas testes de integração
php artisan test --testsuite=Feature
```

---

## 🐍 Script Python

O placar de cada partida é gerado pelo script `teste.py` localizado na raiz do projeto. Para testá-lo manualmente:

```bash
python3 teste.py
```

Exemplo de output:
```
3
1
```

> O back-end executa esse script automaticamente durante a simulação de cada partida.

---

## 🐳 Comandos Docker Úteis

```bash
# Subir os containers
docker compose up -d

# Parar os containers
docker compose down

# Ver logs da aplicação
docker compose logs app

# Acessar o container da aplicação
docker compose exec app bash

# Rodar migrations
docker compose exec app php artisan migrate

# Reverter migrations
docker compose exec app php artisan migrate:rollback

# Acessar o banco de dados
docker compose exec db psql -U postgres -d meu-campeonato
```

---

## 📡 Endpoints da API

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| `GET` | `/` | Status da API (JSON) |
| `GET` | `/up` | Health check do Laravel |
| `POST` | `/api/teams` | Cadastra um novo time |
| `GET` | `/api/championships` | Lista campeonatos anteriores |
| `POST` | `/api/championships` | Cria um novo campeonato |
| `GET` | `/api/championships/{id}` | Consulta um campeonato |
| `POST` | `/api/championships/{id}/simulate` | Simula o campeonato |

### Importar no Postman

1. Abra o Postman → **Import**
2. Selecione os arquivos em `docs/`:
   - `meu-campeonato.postman_collection.json` — collection com todos os endpoints
   - `meu-campeonato.postman_environment.json` — ambiente local (`http://localhost:8000`)
3. Ative o environment **Meu Campeonato — Local (Docker)**
4. Para testar o fluxo inteiro, use a pasta **Fluxo completo** com o **Collection Runner**

---

## 🏗️ Estrutura do Projeto

```
meu-campeonato/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── TeamController.php
│   │   │   └── ChampionshipController.php
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Models/
│   │   ├── Team.php
│   │   ├── Championship.php
│   │   └── GameMatch.php
│   ├── Enums/
│   ├── Exceptions/
│   └── Services/
│       ├── ChampionshipService.php
│       ├── ScoreGeneratorService.php
│       └── TiebreakResolver.php
├── database/
│   └── migrations/
├── docker/
│   └── nginx/
│       └── nginx.conf
├── routes/
│   ├── api.php
│   └── web.php
├── tests/
│   ├── Unit/
│   └── Feature/
├── teste.py
├── Dockerfile
└── docker-compose.yml
```

---

## 📦 Tecnologias

- **PHP 8.5** + **Laravel 13**
- **PostgreSQL 16**
- **Nginx** (servidor web)
- **Docker** + **Docker Compose**
- **Python 3** (geração de placares)
- **PHPUnit** (testes)

---

## 👨‍💻 Autor

**LCamposDev**  
[github.com/LCamposDev](https://github.com/LCamposDev)