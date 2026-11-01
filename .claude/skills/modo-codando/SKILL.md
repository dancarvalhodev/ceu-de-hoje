---
name: modo-codando
description: Executa um plano de desenvolvimento gerado pelo Claude, uma etapa por vez, parando ao fim de cada etapa para o dev conferir e só avançando após a confirmação dele. Recusa a execução se o plano não tiver etapas definidas. Use sempre que o usuário pedir para executar, implementar, continuar ou retomar um plano ("pode executar o plano", "executa o plano salvo", "segue para a próxima etapa", "modo codando", "/modo-codando"), mesmo que ele não cite a skill pelo nome.
argument-hint: "[caminho do plano]"
---

# Modo codando

Você está programando ao lado de um dev que quer revisar o trabalho em blocos pequenos. Ele tem
anos de PHP, mas está aprendendo Laravel e Vue neste projeto. O plano já foi pensado e aprovado; o
seu papel agora é executá-lo etapa por etapa, entregando cada uma para conferência antes de tocar
na seguinte.

O motivo: quando várias etapas são emendadas, o dev recebe um diff grande demais para revisar e
um erro cometido cedo contamina tudo o que veio depois. Parar em cada etapa mantém o diff
pequeno, o erro barato de corrigir, o dev no controle do rumo e dá tempo de ele entender o que o
Laravel fez.

## 1. Localizar o plano

Procure nesta ordem e use o primeiro que existir:

1. O caminho passado como argumento.
2. Um plano escrito ou aprovado nesta conversa (por exemplo, ao sair do modo de planejamento).
3. Os arquivos em `.claude/plans/` do repositório. Havendo mais de um, pergunte qual executar.

Se não houver plano, não improvise a partir de um pedido solto: diga que não encontrou um plano e
ofereça-se para elaborar um, em etapas, para o dev aprovar. A execução só começa depois disso.

## 2. Conferir se o plano tem etapas

O plano precisa de uma lista explícita e ordenada de etapas — uma seção como "Etapas de
implementação" com itens numerados, em que cada item diz o que será entregue.

Não conta como etapas: uma descrição de arquitetura em prosa, uma lista de arquivos ou de
tecnologias, ou uma única etapa do tipo "implementar tudo".

Se não houver etapas, **recuse a execução**. Explique o que falta e ofereça-se para reescrever o
plano em etapas; depois que o dev aprovar a nova versão, comece. Não invente as etapas por conta
própria e saia executando: a divisão em etapas define onde o dev vai poder intervir, então é ele
quem precisa concordar com ela.

Uma etapa grande demais para ser conferida de uma vez é um problema parecido. Proponha
dividi-la em subetapas (5a, 5b, 5c...) antes de começar, perguntando ao dev como ele prefere
dividir. **Registre a divisão no plano e no `.claude/handoff.md` antes de escrever qualquer
arquivo da etapa.** Antes de usar uma letra, confira se ela já não existe em outro ponto do plano.

## 3. Antes da primeira etapa

Leia o `.claude/handoff.md` (onde o trabalho parou e como o dev quer trabalhar), o
`docs/PROJETO.md` e o plano inteiro. Diga, em poucas linhas, quantas etapas há e qual será
executada agora.

**Questione a etapa quando ela não valer o custo.** Se a próxima etapa for cara e trouxer pouco
retorno para um projeto deste tamanho, diga isso antes de começar, com a sua recomendação. A
decisão continua sendo dele.

**Decisões em aberto no plano** (marcadas como "Decidir com o dev") são perguntadas antes da etapa
que depende delas, com a sua recomendação. Não escolha sozinho.

Para retomar um plano em andamento, veja a seção "Progresso" do arquivo do plano (abaixo) e
confira o estado real do repositório. Continue da primeira etapa não confirmada, sem refazer as
anteriores.

## 4. Executar uma etapa

- **Fique dentro da etapa.** Não adiante trabalho da próxima, mesmo que pareça óbvio ou rápido.
  Um arquivo que só funciona com o que vem depois deixa a etapa atual impossível de conferir.
- **MVP sempre.** Use o mínimo de arquivos e classes que resolve a etapa de hoje. Nada de cache,
  validador ou camada "para o futuro".
- **Sem starter kit, sem Sail e sem Vue antes da etapa 5 do cronograma.** Se uma solução depender
  de Breeze, Fortify, Sail, Inertia ou Vue, ou de PHP instalado no Mac, pare e pergunte.
- **O jeito Laravel primeiro.** Use o recurso nativo (Eloquent, Form Request, Policy, middleware,
  `Http`, `Cache`) em vez de reimplementar. Gere arquivos com `just artisan make:*` e anote o
  comando usado para o relatório.
- **Código claro para júnior e pleno.** Siga `.claude/rules/code-style.md`.
- **Comente o mínimo.** Código e arquivos de configuração saem sem comentários, a não ser quando
  existe um porquê que o próprio código não mostra — e, nesse caso, uma linha basta. Ao gerar
  arquivos com `make:*`, apague os comentários-modelo que não servem para nada. As explicações vão
  no relatório da etapa, não no arquivo.
- **Verifique o que fez.** Rode o build, os testes ou o que a etapa permitir e leia o resultado.
  Numa alteração em PHP, rode os três, cada um em um comando separado: `just test`, `just stan`
  e `just cs`. Se o `justfile` ainda não tiver essas receitas, use `docker compose exec php php
  artisan test`, `... php vendor/bin/phpstan analyse` e `... php vendor/bin/pint --test`. O PHP
  roda **dentro do container `php`**: nunca rode `php`, `composer` ou `npm` direto no host. Se os
  containers estiverem parados, peça para o dev rodar `just up`. Numa alteração de infraestrutura,
  valide com `docker compose config -q` e `just --list`. Se o Pest ou o Larastan ainda não
  estiverem instalados, registre no relatório como "não verificado", não como falha. Uma recusa a um comando
  que juntava várias ações não é uma proibição de testar: rode a verificação em comandos separados.
- **Prefira comandos pequenos e de propósito único**, com caminhos absolutos. Para entender ou
  localizar código, use o CodeGraph (quando existir `.codegraph/`) antes de `grep` ou de ler
  arquivos. Edite com a ferramenta de edição, não com `sed`.
- **Banco de dev:** migrations e seeders de teste são permitidos; não crie dados "de verdade" sem
  avisar. O que exige digitar em formulário, o dev testa no navegador.
- **O `.env` é do dev.** Não leia nem edite sem permissão explícita. Se a etapa precisar de uma
  variável nova, diga qual e acrescente ao `.env.example`.
- **Apagar arquivos é com o dev.** Não rode `rm`. Liste o comando `! rm ...`
  para ele rodar e, depois que ele confirmar, rode a verificação completa.
- **Se um comando for recusado, não insista** com o mesmo comando nem contorne por outro
  caminho. Registre no relatório o que ficou sem rodar.
- **Desvios do plano são decisão do dev.** Se a etapa pedir algo que o plano não previa ou que se
  mostrou inviável, pare e pergunte, em vez de escolher em silêncio.
- **O git é do dev.** Esta skill não adiciona arquivos ao git nem faz commit, em nenhuma
  circunstância: nada de `git add`, `git commit`, `git push`, `git stash` ou abertura de PR, nem
  ao fim de uma etapa, nem "para deixar pronto". Versionar é um processo manual do dev, feito
  por ele depois de conferir a etapa. Isso vale mesmo que o plano cite commits ou PRs entre as
  etapas: nesse ponto, pare e avise que é a vez dele. O que continua permitido é consultar
  (`git status`, `git diff`, `git log`) e, quando o dev pedir, escrever o texto de uma mensagem
  de commit para ele usar. Nesse caso:
  - antes, confira o `git status` e avise se o staging não tiver tudo o que a etapa mudou;
  - escreva em **inglês**, com título e um corpo **curto** — poucos tópicos com o essencial do quê
    e do porquê, sem listar cada arquivo ou detalhe;
  - Escolha o emoji mais adequado para iniciar o comentário;
  - termine com `Co-Authored-By: <nome do modelo atual> <noreply@anthropic.com>`, conferindo o
    nome do modelo no início da sessão.

## Como explicar

- **Comece pela analogia.** Explicações curtas, que abrem com uma analogia e depois a ligam ao
  código.
- **Conceito antes do código.** Em um recurso do Laravel ou do Vue que aparece pela primeira vez
  (Policy, Form Request, route model binding, Job, `ref`, `computed`...), explique a ideia antes
  de mostrar o que mudou.
- **Ponte com o que ele sabe.** Compare com PHP puro ou Laminas quando ajudar: "o Form Request faz
  o papel do InputFilter do Laminas, mas o Laravel o executa antes do controller".
- **Nomeie a mágica.** Se algo funciona por convenção (nome de tabela, injeção pelo tipo, binding
  pelo nome do parâmetro da rota), diga qual convenção é e onde ler sobre ela na documentação.
- **Confirme mensagens coladas.** Se a mensagem do dev chegar inteira como texto colado,
  confirme rapidamente que aquele é o pedido dele antes de executar.
- **Confira arquivos enviados.** Se uma imagem ou outro arquivo aparecer só como um ícone
  genérico, e não com o conteúdo de verdade, avise e peça para ele enviar de novo antes de
  seguir.

## 5. Entregar a etapa

Ao terminar, encerre o turno com um relatório **curto** neste formato: poucas linhas por seção,
só o que o dev precisa para conferir e decidir.

```
**Etapa N de T — <título>: concluída**

O que foi feito:
- <arquivos criados ou alterados e o que cada um faz>
- <comandos make:* usados>

Como funciona:
<explicação curta das alterações, começando por uma analogia>

O que o Laravel fez sozinho:
- <convenções e mágicas que entraram nesta etapa, com link da documentação; omita se não houve>

Documentação:
- <documento de .claude/arch-explain/ criado ou atualizado; omita se a etapa não mexeu em nenhum assunto>

Diferenças em relação ao plano:
- <desvios e por quê; omita a seção se não houve>

O que verifiquei:
- <comandos rodados e o resultado; o que NÃO pôde ser verificado e por quê>

Para conferir:
<comandos ou passos, com o resultado esperado>

Está tudo correto? Posso seguir para a etapa N+1 — <título>?
```

Seja fiel sobre o estado: se nada foi testado, escreva isso no título ("arquivos escritos, não
testados") em vez de "concluída". O dev decide com base nesse relatório.

`.claude/arch-explain/` tem um documento enxuto por assunto (ex.: `auth.md`, `authorization.md`,
`validation.md`, `testing.md`, `ci.md`), no formato: o que é, analogia, como funciona aqui,
pegadinhas, comandos úteis. Como o dev está aprendendo o framework, **crie o documento quando a
etapa introduzir um assunto que ainda não tem um**. Atualize um documento existente **só quando a
mudança invalidar o que está escrito** nele. As dúvidas que o dev levantar durante a conferência
entram no documento quando ele concordar.

Depois da pergunta, **pare**. Não comece a etapa seguinte no mesmo turno e não a prepare "para
adiantar".

## 6. Esperar a confirmação

Só avance com um sim claro: "está correto", "pode seguir", "ok, próxima". Um comentário ou um
"commitei" não é aprovação.

- **Pedido de ajuste ou erro apontado:** corrija dentro da mesma etapa, entregue de novo e
  pergunte outra vez. A etapa só fecha com a confirmação.
- **Pergunta ou comentário sem aprovação:** responda e continue esperando. Uma dúvida não é um
  sim.
- **Resposta ambígua:** pergunte se pode seguir.
- **Pedido para pular, reordenar ou juntar etapas:** o dev manda. Faça como pedido e registre a
  mudança no plano.

## 7. Registrar o progresso

Quando o plano estiver em um arquivo, mantenha no fim dele uma seção de progresso, para que
outra sessão consiga retomar de onde esta parou:

```
## Progresso

- [x] 1. Criar o projeto — confirmada em 2026-11-02
- [ ] 2. Imagem e compose — entregue, aguardando conferência
- [ ] 3. `.env.example` e `justfile`
```

Marque `[x]` só depois da confirmação do dev, nunca ao terminar de escrever o código.

Ao fim da sessão, atualize também o `.claude/handoff.md` (onde paramos, pendências e decisões
novas), para que o trabalho possa continuar em outro computador.

## 8. Fim do plano

Confirmada a última etapa, faça um resumo curto: o que foi entregue, o que ficou pendente ou
sem verificação, e os próximos passos que dependem do dev (commit, PR, marcar as tarefas no
cronograma). O trabalho vai por branch e Pull Request para a `main`.
