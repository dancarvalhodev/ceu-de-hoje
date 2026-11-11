# Deploy — passo a passo para colocar o Céu de Hoje no ar

Roteiro do zero, numa VM nova da Oracle, em três partes: o servidor (passos 1 a 8), o primeiro
deploy (passos 9 a 15) e o dia a dia (rollback e cuidados). Ao final, um push na `master` publica
sozinho em `https://ceudehoje.danielcarvalho.dev`.

**Já pronto antes deste roteiro:** o subdomínio `ceudehoje` na Squarespace e o domínio
`ceudehoje.danielcarvalho.dev` verificado no Resend. Aqui eles só são reaproveitados.

O porquê de cada peça está em `.claude/arch-explain/deploy.md`. Aqui fica só o que fazer, na
ordem. Rode **um comando por vez**.

> Os nomes de menus e botões são os do painel da Oracle em inglês e podem mudar. Se algum não
> bater, procure pelo conceito (por exemplo, "regra de entrada" em vez de "Ingress Rule").

## Parte 1 — O servidor

| # | Passo | Onde | Tempo |
|---|---|---|---|
| 1 | Conta na Oracle e proteções de custo | Navegador | 15 min, mais a aprovação |
| 2 | Gerar as chaves SSH | Seu computador | 2 min |
| 3 | Criar a rede (VCN) | Painel da Oracle | 5 min |
| 4 | Abrir as portas 80 e 443 | Painel da Oracle | 5 min |
| 5 | Criar a VM, com o cloud-init | Painel da Oracle | 10 min |
| 6 | Conferir a VM | SSH | 5 min |
| 7 | Apontar o subdomínio para o IP da VM | Squarespace | 2 min, mais a propagação |
| 8 | Conferir o DNS | Seu computador | 1 min |

Você vai precisar de: um cartão de crédito (a Oracle exige para validar a conta, mesmo no plano
gratuito), o acesso à Squarespace e ao Resend, e este repositório no computador.

### 1. Conta na Oracle e proteções de custo

1. Acesse `https://www.oracle.com/cloud/free/` e crie a conta (se já tem uma, entre nela e pule
   para o item 4).
2. Na **Home Region**, pare e pense: **ela não muda depois**, e os recursos gratuitos só existem
   nela. Para o Brasil: `Brazil East (Sao Paulo)` ou `Brazil Southeast (Vinhedo)`.
3. Conclua a validação do cartão e espere o e-mail de conta ativa.
4. **Opcional, recomendado:** converter para **Pay As You Go** (**Billing & Cost Management →
   Upgrade and Manage Payment**). Contas pagas têm prioridade nas máquinas ARM e não têm a VM
   recolhida por ociosidade. Continua sem custo dentro do Always Free; a Oracle faz uma
   pré-autorização temporária no cartão, que ela mesma libera.
5. **Orçamento com alerta:** **Billing & Cost Management → Budgets**, valor de **1 dólar**, aviso
   por e-mail. Não impede cobrança, mas avisa no primeiro centavo.

**Os limites do Always Free que importam aqui** (somando todas as VMs ARM da conta):

| Recurso | Limite gratuito | Esta VM |
|---|---|---|
| Máquina ARM (Ampere A1) | 4 OCPUs e 24 GB de memória | 1 OCPU e 6 GB |
| Disco (boot e block volume) | 200 GB | cerca de 47 GB, o padrão |
| Tráfego de saída | 10 TB por mês | uma fração mínima |

Onde dá para ser cobrado sem perceber: escolher um shape sem a etiqueta **Always
Free-eligible**, passar dos limites somando outras VMs, aumentar o disco ou acumular discos de
instâncias apagadas (ao apagar uma instância, marque apagar o boot volume junto).

### 2. Gerar as chaves SSH

São duas chaves, uma para cada usuário da VM:

| Chave | Usuário na VM | Quem usa |
|---|---|---|
| A sua (`~/.ssh/id_ed25519`) | `ubuntu` | Você, para administrar |
| A de deploy, nova | `deploy` | O GitHub Actions, para publicar |

Se ainda não tem a pessoal (`ls ~/.ssh/id_ed25519.pub` dá erro):

```
ssh-keygen -t ed25519
```

A de deploy, sem senha, porque é o Actions que usa:

```
ssh-keygen -t ed25519 -f ~/.ssh/ceu-deploy -C "deploy ceu-de-hoje" -N ""
```

- `~/.ssh/ceu-deploy`: a **privada**. Só sai do seu computador para os Secrets do GitHub.
- `~/.ssh/ceu-deploy.pub`: a **pública**. Vai para a VM no passo 5.

### 3. Criar a rede (VCN)

A VM precisa de uma **sub-rede pública** para receber um IP público. O assistente monta tudo
(sub-rede, saída para a internet e rota):

1. **Networking → Virtual cloud networks → Start VCN Wizard**.
2. **Create VCN with Internet Connectivity**, nome `ceu-de-hoje`, valores padrão, conclua.

Crie a rede **antes** da VM. A sub-rede de uma instância não pode ser trocada depois, e sem
internet o cloud-init falha na única vez em que roda.

### 4. Abrir as portas 80 e 443 na Security List

A Oracle tem dois firewalls: o de dentro da VM (aberto pelo cloud-init) e este, de fora.

1. **Networking → Virtual cloud networks → `ceu-de-hoje`** → a sub-rede com "public" no nome →
   **Security Lists** → **Default Security List**.
2. **Add Ingress Rules**, três regras, todas com **Source Type** `CIDR` e **Source CIDR**
   `0.0.0.0/0`. Deixe **Source Port Range** em branco.

| IP Protocol | Destination Port Range | Para quê |
|---|---|---|
| TCP | `80` | Redirecionamento para HTTPS e emissão do certificado |
| TCP | `443` | HTTPS |
| UDP | `443` | HTTP/3 |

A regra da porta 22 (SSH) já existe; não a apague.

### 5. Criar a VM

**Compute → Instances → Create instance.**

- **Nome:** `ceu-de-hoje`.
- **Image and shape** (botão **Edit**):
  - **Image:** `Canonical Ubuntu` 24.04. O `cloud-init.yaml` foi escrito para Ubuntu.
  - **Shape:** em **Ampere**, `VM.Standard.A1.Flex`, com **1 OCPU e 6 GB**. Confira a etiqueta
    **Always Free-eligible**.
- **Networking:** **Select existing virtual cloud network** → `ceu-de-hoje`; **Select existing
  subnet** → a que tem "public" no nome. Marque **Assign a public IPv4 address**. Se essa opção
  estiver bloqueada, a sub-rede escolhida é a privada.
- **Add SSH keys:** **Paste public keys**, com a **sua** chave pública
  (`cat ~/.ssh/id_ed25519.pub`).
- **Boot volume:** padrão.

**O cloud-init** (campo escondido):

1. **Show advanced options → Management → Initialization script → Paste cloud-init script**.
2. Cole o conteúdo inteiro de `infra/cloud-init.yaml`.
3. **No texto colado** (não no arquivo do repositório), troque `REPLACE_WITH_THE_DEPLOY_PUBLIC_KEY`
   pelo conteúdo de `~/.ssh/ceu-deploy.pub`, mantendo o traço e os espaços do começo:

   ```yaml
         - ssh-ed25519 AAAA...resto-da-chave deploy ceu-de-hoje
   ```

Antes de clicar em **Create**, salve esse texto (já com a chave) num arquivo fora do repositório:
se a criação falhar, é só colar de novo.

Clique em **Create**. Quando ficar **Running**, anote o **Public IP address**. Daqui em diante ele
aparece como `<IP>`.

**Se aparecer "Out of capacity":** não é erro de configuração; falta máquina ARM livre na região.
Em ordem: tire o fault domain, se escolheu um; tente de novo em outros horários (a VCN continua
lá); converta a conta para Pay As You Go (passo 1), que é o que mais resolve.

**O cloud-init só roda nesta primeira inicialização.** Se esqueceu de colar ou de trocar a chave,
o mais simples é apagar a instância (**Terminate**, marcando apagar o boot volume) e criar de novo.

### 6. Conferir a VM

O cloud-init leva alguns minutos depois do **Running**. Entre como `ubuntu`:

```
ssh ubuntu@<IP>
```

```
cloud-init status --wait --long
```

O esperado é `status: done` **sem** `degraded`. Depois, um por vez:

| Comando | Resultado esperado |
|---|---|
| `docker --version` | Mostra a versão |
| `docker compose version` | Mostra a versão |
| `id deploy` | Os grupos incluem `docker` |
| `sudo iptables -L INPUT -n --line-numbers` | As primeiras linhas liberam 80 e 443 |
| `ls -ld /srv/ceu-de-hoje` | Existe e é do usuário `deploy` |

Saia (`exit`) e teste o usuário de deploy, do seu computador:

```
ssh -i ~/.ssh/ceu-deploy deploy@<IP> docker ps
```

Deve mostrar uma lista vazia, sem pedir senha e sem erro de permissão.

#### Se o cloud-init não foi aplicado

Com `degraded done` e avisos como *"Failed loading yaml blob"*, o texto colado tinha um caractere
fora do ASCII e **nada** foi executado. Não precisa apagar a VM; faça à mão, como `ubuntu`, um
comando por vez:

```
sudo apt-get update
sudo apt-get -y upgrade
sudo groupadd docker
sudo useradd --create-home --shell /bin/bash --groups docker deploy
sudo passwd --lock deploy
sudo mkdir /home/deploy/.ssh
sudo nano /home/deploy/.ssh/authorized_keys
```

No `nano`, cole o conteúdo de `~/.ssh/ceu-deploy.pub` numa linha só, salve e saia. Depois:

```
sudo chmod 700 /home/deploy/.ssh
sudo chmod 600 /home/deploy/.ssh/authorized_keys
sudo chown -R deploy:deploy /home/deploy/.ssh
sudo iptables -I INPUT -p tcp -m multiport --dports 80,443 -j ACCEPT
sudo iptables -I INPUT -p udp --dport 443 -j ACCEPT
sudo netfilter-persistent save
curl -fsSL https://get.docker.com | sudo sh
sudo mkdir -p /srv/ceu-de-hoje
sudo chown deploy:deploy /srv/ceu-de-hoje
```

Se o `groupadd` disser que o grupo já existe, siga em frente. Ao terminar, refaça a tabela acima.

### 7. Apontar o subdomínio para o IP da VM

O registro `ceudehoje` já existe na Squarespace; falta apontá-lo para a VM nova. Em **Domains →
danielcarvalho.dev → DNS → Custom records**, edite o registro com **Host** `ceudehoje`:

| Host | Tipo | Dados |
|---|---|---|
| `ceudehoje` | A | `<IP>` |

Se ele estiver como **CNAME** em vez de **A**, apague e crie como **A**. Na Squarespace o Host é só
`ceudehoje`, sem o `.danielcarvalho.dev`.

**Não mexa nos registros do Resend** (`send.ceudehoje`, `resend._domainkey.ceudehoje` e, se
houver, `_dmarc.ceudehoje`): eles são do e-mail e continuam valendo.

**Sobre o IP:** o IP público padrão é "efêmero": fica o mesmo enquanto a instância existir, mas
muda se ela for apagada e recriada. Nesse caso, atualize o registro e os secrets do GitHub (passos
11 e 12). Para um IP que sobreviva à recriação: **Networking → IP Management → Reserved Public
IPs**.

### 8. Conferir o DNS

Do seu computador:

```
dig +short ceudehoje.danielcarvalho.dev
```

**Só siga para o primeiro deploy quando responder `<IP>`.** O Caddy pede o certificado assim que
sobe; se o DNS ainda não aponta para a VM, o pedido falha, e falhas seguidas fazem a Let's Encrypt
bloquear novas tentativas por um tempo.

## Parte 2 — O primeiro deploy

| # | Passo | Onde |
|---|---|---|
| 9 | Gerar a `APP_KEY` e a senha do banco | Seu computador |
| 10 | Criar o `.env` na VM | VM, por SSH |
| 11 | Copiar a identidade da VM | Seu computador |
| 12 | Cadastrar os três secrets | GitHub |
| 13 | Conferir que o repositório é público | GitHub |
| 14 | Push e imagens públicas | Seu computador e GitHub |
| 15 | Conferência final | Seu computador e VM |

### 9. Gerar a `APP_KEY` e a senha do banco

Uma `APP_KEY` nova, só de produção (não reaproveite a de dev). Na raiz do projeto:

```
just artisan key:generate --show
```

O `--show` só imprime a chave (`base64:...`), sem mexer no seu `.env`. Guarde-a num gerenciador de
senhas: trocar a `APP_KEY` depois desloga todo mundo.

A senha do MySQL:

```
openssl rand -hex 24
```

E tenha à mão a **chave de API do Resend** (`re_...`). Se não guardou a que já existe, crie outra
em **API Keys → Create API Key**, com permissão **Sending access** restrita ao domínio
`ceudehoje.danielcarvalho.dev`; ela aparece uma vez só.

### 10. Criar o `.env` na VM

```
ssh -i ~/.ssh/ceu-deploy deploy@<IP>
nano /srv/ceu-de-hoje/.env
```

Conteúdo, trocando os três valores entre `< >`:

```
APP_NAME="Céu de Hoje"
APP_ENV=production
APP_KEY=<a do passo 9>
APP_DEBUG=false
APP_URL=https://ceudehoje.danielcarvalho.dev

LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=ceu_de_hoje
DB_USERNAME=ceu_de_hoje
DB_PASSWORD=<a senha do passo 9>

REDIS_HOST=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

MAIL_MAILER=resend
RESEND_API_KEY=<a chave re_... do Resend>
MAIL_FROM_ADDRESS=alerta@ceudehoje.danielcarvalho.dev
MAIL_FROM_NAME="Céu de Hoje"
```

Compare com a seção de produção do `.env.example` do repositório: se ele tiver alguma variável a
mais, copie também. `DB_HOST` e `REDIS_HOST` são os nomes dos serviços no `compose.prod.yaml`. O
MySQL cria o banco e o usuário com esses valores na primeira subida; depois disso, mudar a senha
aqui não muda a do banco.

Feche o arquivo para os outros usuários da máquina:

```
chmod 600 /srv/ceu-de-hoje/.env
```

### 11. Copiar a identidade da VM

O GitHub guarda a "impressão digital" da VM para ter certeza de que fala com ela, e não com alguém
se passando por ela. No seu computador:

```
ssh-keyscan -t ed25519 <IP>
```

Copie a linha que começa com o IP (a que começa com `#` é comentário e não entra).

### 12. Cadastrar os três secrets no GitHub

No repositório `ceu-de-hoje`: **Settings → Secrets and variables → Actions → New repository
secret**. Se já existirem de uma tentativa anterior, edite com os valores novos.

| Nome | Valor | De onde vem |
|---|---|---|
| `SSH_HOST` | Só o IP, sem `deploy@` | Página da instância |
| `SSH_KEY` | O conteúdo inteiro da chave **privada** de deploy | `cat ~/.ssh/ceu-deploy` |
| `SSH_KNOWN_HOSTS` | A linha do passo 11 | `ssh-keyscan` |

No `SSH_KEY`, copie da linha `-----BEGIN OPENSSH PRIVATE KEY-----` até
`-----END OPENSSH PRIVATE KEY-----`, inclusive. É o arquivo **sem** `.pub`. Depois de salvo, o
GitHub não mostra mais o valor.

### 13. Conferir que o repositório é público

**Settings → General → Danger Zone**. O job das imagens usa o runner ARM (`ubuntu-24.04-arm`),
gratuito só em repositórios públicos; num privado, ele fica parado em **Queued**. Nada de segredo
vai para o repositório: o `.env` só existe na VM.

### 14. Push e imagens públicas

1. Faça o commit e o `git push` na `master`. O push é o gatilho do deploy.
2. Na aba **Actions**, abra a execução do workflow **Deploy**:

   | Job | O que faz | Tempo aproximado |
   |---|---|---|
   | tests | Pint, Larastan e Pest | 1 a 2 min |
   | images | Constrói e envia as duas imagens ao GHCR | 5 a 10 min |
   | publish | Na VM: baixa, migra e sobe | 1 a 2 min |

3. **No primeiro deploy, o `publish` falha** com `denied`, `unauthorized` ou `pull access denied`.
   É esperado: no GHCR toda imagem nasce privada, e a VM não tem senha para baixar imagem privada.
   Para abrir:
   1. No GitHub, sua foto → **Your profile** → aba **Packages**.
   2. `ceu-de-hoje-php` → **Package settings** → no fim da página, **Change visibility → Public**.
   3. O mesmo para `ceu-de-hoje-caddy`.
   4. Volte à execução em **Actions** e clique em **Re-run failed jobs**.

   Isso é feito uma vez só; as próximas versões já nascem públicas.

### 15. Conferência final

Do seu computador:

| Comando | Resultado esperado |
|---|---|
| `curl -I https://ceudehoje.danielcarvalho.dev` | `HTTP/2 200` |
| `curl -I https://ceudehoje.danielcarvalho.dev/up` | `HTTP/2 200` |
| `curl -I http://ceudehoje.danielcarvalho.dev` | `308`, com `location: https://...` |

No navegador: cadastre-se, cadastre um local e abra a página do céu.

Na VM, os serviços:

```
ssh -i ~/.ssh/ceu-deploy deploy@<IP>
cd /srv/ceu-de-hoje
docker compose -p ceu-prod -f compose.prod.yaml ps
```

Os seis (`caddy`, `php`, `queue`, `scheduler`, `mysql`, `redis`) devem estar `Up`, e os que têm
healthcheck, `(healthy)`.

**E-mail de teste**, ainda na VM (troque o endereço pelo seu):

```
docker compose -p ceu-prod -f compose.prod.yaml exec php php artisan tinker --execute='Mail::raw("Teste do Céu de Hoje", fn ($m) => $m->to("voce@exemplo.com")->subject("Teste"));'
```

Deve chegar em alguns segundos. No painel do Resend, **Emails** mostra o envio.

**O alerta de verdade:** dispare o job à mão. Ele só manda e-mail se a pontuação do seu local
estiver boa agora, então o silêncio num céu nublado é o comportamento certo:

```
docker compose -p ceu-prod -f compose.prod.yaml exec php php artisan tinker --execute='App\Jobs\CheckSkyJob::dispatch();'
```

```
docker compose -p ceu-prod -f compose.prod.yaml logs --tail 50 queue
```

O `dispatch()` põe o job na fila, e o container `queue` o executa; o log mostra o job `DONE`.

### Daqui em diante

Publicar é fazer o push na `master`. O deploy roda sozinho e leva cerca de dez minutos.

## Parte 3 — Dia a dia

### Voltar para uma versão anterior (rollback)

1. Em **Actions**, abra a execução do **Deploy** anterior à que quebrou e copie o SHA do commit
   (40 caracteres).
2. Na VM:

   ```
   cd /srv/ceu-de-hoje
   IMAGE_TAG=<sha> docker compose -p ceu-prod -f compose.prod.yaml up -d --no-build --wait
   ```

Isso não muda a `master`: o próximo push publica de novo o código quebrado. Depois do rollback,
corrija (ou faça um `git revert`) e publique.

**Rollback não desfaz migração.** A imagem volta, o banco não.

### Cuidados

- **Nunca rode `down --volumes`.** Apagaria o banco e os certificados (a Let's Encrypt limita
  quantos emite por semana para o mesmo domínio). Para derrubar, use só `down`.
- **Mudou o `.env` da VM?** `docker compose -p ceu-prod -f compose.prod.yaml up -d --force-recreate`.
- **O cloud-init não roda de novo.** Mudança numa VM que já existe é feita à mão, por SSH; atualize
  o `infra/cloud-init.yaml` também, para a próxima máquina nascer igual.
- **A chave `~/.ssh/ceu-deploy`** dá controle da VM. Fora do seu computador, só nos Secrets do
  GitHub.
- **Cost Analysis** na primeira semana: confira que tudo aparece como zero.
- **Sem backup do MySQL** por enquanto (fora do MVP).

## Se algo não funcionar

| Sintoma | Causa provável | Onde olhar |
|---|---|---|
| A opção do IP público está bloqueada ao criar a VM | A sub-rede escolhida é privada | Passo 5, "Networking" |
| "Out of capacity" ao criar a VM | Falta máquina ARM livre na região | Passo 5 |
| `ssh ubuntu@...` não conecta | IP errado, chave pessoal errada ou VM sem IP público | Página da instância; passo 5 |
| `ssh deploy@...` pede senha ou recusa | A chave de deploy não foi trocada no cloud-init | `sudo cat /home/deploy/.ssh/authorized_keys`, como `ubuntu` |
| `cloud-init status` mostra `error` | Um comando do arquivo falhou | `/var/log/cloud-init-output.log` |
| `cloud-init status` mostra `degraded done` | Caractere fora do ASCII no texto colado | Passo 6, "Se o cloud-init não foi aplicado" |
| `curl` fica parado e dá timeout | Um dos dois firewalls está fechado | Passo 4 e `sudo iptables -L INPUT -n` |
| `curl` reclama do certificado | O Caddy não conseguiu emitir: DNS ainda não apontava, ou porta 80 fechada | Passo 8, passo 4 e `logs caddy` |
| Job `images` parado em **Queued** | Repositório privado | Passo 13 |
| `publish` falha com `denied` ou `unauthorized` | Imagens ainda privadas no GHCR | Passo 14 |
| `publish` falha com `Permission denied (publickey)` | `SSH_KEY` incompleto ou é a pública | Passo 12 |
| `publish` falha com `Host key verification failed` | `SSH_KNOWN_HOSTS` não bate (VM recriada ou IP trocado) | Passos 11 e 12 |
| `publish` falha no `migrate` com `Access denied` | Senha do MySQL mudou no `.env` depois da primeira subida | Passo 10 |
| Página 500 | Erro do Laravel | `logs php` (o log vai para `stderr`) |
| Links do e-mail com `http://` ou `localhost` | `APP_URL` errado | Passo 10 e `up -d --force-recreate` |
| E-mail não chega | Chave errada ou remetente fora do domínio verificado | Painel do Resend (**Emails**) e `logs queue` |
| Nenhum alerta às 20h | `scheduler` ou `queue` parados | `ps` e `logs scheduler` |

Nos comandos de log acima, o prefixo completo é
`docker compose -p ceu-prod -f compose.prod.yaml`, rodado em `/srv/ceu-de-hoje`.
