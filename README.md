# Céu de Hoje

[![CI](https://github.com/dancarvalhodev/ceu-de-hoje/actions/workflows/ci.yml/badge.svg)](https://github.com/dancarvalhodev/ceu-de-hoje/actions/workflows/ci.yml)

Diz se o céu está bom para observar agora, no seu local, e avisa por e-mail quando vale a pena
olhar para cima. No ar em **https://ceudehoje.danielcarvalho.dev**.

## O que faz

- **Cadastro de locais:** cada usuário cadastra de onde observa (nome, latitude, longitude e fuso
  horário), digitando ou com o botão "Usar minha localização".
- **Tela "Céu agora":** uma pontuação de observabilidade de 0 a 10 para o local, com a cobertura
  de nuvens do momento e a fase da lua.
- **Alerta diário:** todo dia às 20h (horário de São Paulo), um e-mail para quem tem um local com
  pontuação 7 ou mais.

## Como a pontuação funciona

A pontuação começa em 10 e perde por dois motivos: as **nuvens** tiram na mesma proporção (céu
fechado dá 0), e a **lua** tira até metade (nova não tira nada, cheia tira metade).

```
pontuação = 10 × (1 − nuvens / 100) × (1 − 0,5 × iluminação da lua)
```

Arredondada a uma casa decimal.

| Céu | Lua | Pontuação |
|---|---|---|
| Limpo | Nova | 10 |
| Limpo | Cheia | 5 |
| Metade nublado | Nova | 5 |
| Metade nublado | Cheia | 2,5 |
| Fechado | Qualquer | 0 |

As nuvens vêm da API do Open-Meteo; a iluminação da lua é calculada localmente.

## Stack

- **Laravel 13** com **PHP 8.5**, telas em **Blade**
- **MySQL** e **Redis** (sessão, cache e fila)
- **Caddy**, **Docker Compose** e **just** para os comandos
- **Pest**, **Pint** e **Larastan**
- **GitHub Actions** para CI e deploy, **Oracle Cloud** para hospedar
- **Resend** para os e-mails
- **Open-Meteo** (cobertura de nuvens) e **solaris/php-moon-phase** (fase da lua)

## Rodar localmente

Pré-requisitos: **Docker** (com o Compose v2) e **[just](https://github.com/casey/just)**. Não é
preciso PHP, Composer nem Node no computador: tudo roda nos containers.

```bash
git clone https://github.com/dancarvalhodev/ceu-de-hoje.git
cd ceu-de-hoje
just install
```

O `just install` cria o `.env`, sobe os containers, instala as dependências, gera a chave, roda as
migrações e compila os assets. Depois:

- App: http://localhost
- Mailpit (e-mails de desenvolvimento): http://localhost:8025

`just` sem argumentos lista os outros comandos (`just test`, `just stan`, `just cs`, `just vite`...).

## Deploy

Cada push na `master` dispara o GitHub Actions: os testes rodam, as imagens de produção (PHP e
Caddy, para ARM) são publicadas no GitHub Container Registry, e o workflow entra na VM da Oracle
por SSH para baixar as imagens e subir a versão nova com Docker Compose. A migração roda na subida
do container PHP, e o próprio Caddy do app cuida do HTTPS. O passo a passo para montar o servidor
está em [`docs/deploy.md`](docs/deploy.md).

## Próximos passos

- React com Inertia, começando pela tela de cadastro de local.
- Painel "Céu agora" em React.
- Planetas visíveis à noite, com o melhor horário para cada um.
