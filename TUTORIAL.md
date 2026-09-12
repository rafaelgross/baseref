# Manual do Usuário — BaseRef

**Plataforma web para Revisões Sistemáticas da Literatura seguindo o protocolo PRISMA 2020**

Versão 1.0 · https://baseref.com.br

---

## Sumário

1. [O que é o BaseRef?](#1-o-que-é-o-baseref)
2. [Primeiros passos](#2-primeiros-passos)
3. [Visão geral do fluxo de trabalho](#3-visão-geral-do-fluxo-de-trabalho)
4. [Projetos](#4-projetos)
5. [Extração PICO](#5-extração-pico)
6. [Importação de referências](#6-importação-de-referências)
7. [Deduplicação](#7-deduplicação)
8. [Triagem (título e resumo)](#8-triagem-título-e-resumo)
9. [Triagem assistida por IA](#9-triagem-assistida-por-ia)
10. [Elegibilidade (texto completo)](#10-elegibilidade-texto-completo)
11. [Concordância entre revisores](#11-concordância-entre-revisores)
12. [Avaliação de qualidade e risco de viés](#12-avaliação-de-qualidade-e-risco-de-viés)
13. [Diagrama PRISMA 2020](#13-diagrama-prisma-2020)
14. [Bibliometria](#14-bibliometria)
15. [Referências formatadas](#15-referências-formatadas)
16. [Exportação](#16-exportação)
17. [Checklist PRISMA 2020](#17-checklist-prisma-2020)
18. [Perguntas frequentes](#18-perguntas-frequentes)
19. [Suporte e como citar](#19-suporte-e-como-citar)

---

## 1. O que é o BaseRef?

O **BaseRef** é uma plataforma web gratuita e de código aberto (licença MIT) que apoia pesquisadores na condução de **Revisões Sistemáticas da Literatura (RSL)** seguindo o protocolo **PRISMA 2020** — o padrão internacional mais adotado para o relato de revisões sistemáticas.

Com o BaseRef você pode:

- Importar referências das principais bases (Scopus, Web of Science, PubMed, Embase, IEEE Xplore e outras)
- Detectar e resolver artigos duplicados automaticamente
- Triar títulos e resumos, com sugestão de inclusão/exclusão assistida por IA
- Avaliar elegibilidade por texto completo com motivos padronizados
- Trabalhar com dois ou mais revisores e medir a concordância (kappa de Cohen)
- Avaliar qualidade metodológica e risco de viés (CASP, JBI ou instrumento próprio)
- Gerar automaticamente o Diagrama de Fluxo PRISMA 2020 e exportá-lo em SVG ou PNG
- Analisar bibliometria com gráficos, mapas e redes de coautoria
- Gerar referências formatadas em ABNT NBR 6023:2018 e APA 7ª edição
- Exportar os dados em CSV, BibTeX ou JSON
- Acompanhar os 27 itens do Checklist PRISMA 2020

Todo o trabalho fica salvo na nuvem e é isolado por usuário: cada pesquisador vê apenas os próprios projetos e aqueles em que foi convidado como colaborador.

---

## 2. Primeiros passos

### Acesso

1. Acesse **https://baseref.com.br**.
2. Clique em **Entrar com Google**.
3. Autorize o acesso à sua conta Google.

Não há cadastro de senha: a autenticação é feita por **Google OAuth 2.0**, e nenhuma senha é armazenada pela plataforma. A sessão expira automaticamente após algumas horas de inatividade — basta entrar novamente.

### A tela do sistema

- **Menu lateral (esquerda)** — seletor de projeto ativo e todas as etapas da revisão, agrupadas em *Visão Geral*, *Processo PRISMA*, *Análise*, *Dados* e *Suporte*.
- **Cabeçalho (topo)** — nome do projeto ativo, seletor de idioma (PT / EN / ES) e alternância entre tema claro e escuro.
- **Área central** — a etapa em que você está trabalhando.

### Ajuda dentro do sistema

No menu lateral, em **Suporte → Ajuda**, você encontra:

- **Tutorial em Vídeo** — abre o vídeo de demonstração da plataforma em uma janela sobre a tela.
- **Tutorial em PDF** — abre este manual.

> **Dica:** o idioma da interface (PT / EN / ES) e o tema claro/escuro ficam salvos no seu navegador e são reaplicados no próximo acesso.

---

## 3. Visão geral do fluxo de trabalho

Uma revisão sistemática no BaseRef segue as etapas abaixo, nesta ordem:

```
[1]  Criar o projeto e definir critérios
      ↓
[2]  Estruturar a pergunta (PICO)
      ↓
[3]  Importar referências (.bib / .ris / .xml / .csv / .txt)
      ↓
[4]  Detectar e resolver duplicatas
      ↓
[5]  Triagem — título e resumo
      ↓
[6]  Elegibilidade — texto completo
      ↓
[7]  Concordância entre revisores (kappa)
      ↓
[8]  Avaliação de qualidade / risco de viés
      ↓
[9]  Diagrama PRISMA 2020
      ↓
[10] Bibliometria
      ↓
[11] Referências e exportação
      ↓
[12] Checklist PRISMA 2020
```

O **Dashboard** mostra, em tempo real, quantos artigos foram identificados, quantas duplicatas foram removidas e quantos artigos estão em cada fase.

> **Dica:** as etapas 7, 8 e 10 são opcionais, mas fortemente recomendadas — a maioria dos periódicos exige o relato de concordância entre revisores e de avaliação de qualidade.

---

## 4. Projetos

Cada projeto é uma revisão independente: possui suas próprias referências, decisões, critérios e colaboradores. Você pode manter várias revisões em andamento ao mesmo tempo sem risco de mistura de dados.

### Criar um projeto

1. Acesse **Projetos** no menu lateral.
2. Clique em **Novo Projeto**.
3. Preencha os campos:
   - **Título** (obrigatório) — o nome da sua revisão.
   - **Descrição** — informações adicionais sobre o estudo.
   - **Objetivo / Questão de Pesquisa** — a pergunta que a revisão pretende responder.
   - **Período de Busca — Início / Fim** — o recorte temporal da busca (ex.: 2013 a 2024).
   - **Critérios de inclusão** e **critérios de exclusão** — escreva-os de forma clara e objetiva.
4. Clique em **Criar**.

> **Importante:** os critérios de inclusão e exclusão não são apenas documentação — são eles que a **triagem assistida por IA** utiliza para sugerir decisões. Quanto mais específicos, melhores as sugestões.

### Selecionar o projeto ativo

No topo do menu lateral há o seletor **Projeto Ativo**. Todas as etapas (importação, triagem, diagrama, bibliometria) sempre se referem ao projeto selecionado ali.

### Colaboradores

Para revisões com mais de um avaliador:

1. Abra o **Dashboard** (ou a área de colaboradores do projeto).
2. Informe o **e-mail do colaborador** e confirme.
3. O colaborador passa a ver o projeto na própria conta.

> **Importante:** o colaborador precisa ter feito login ao menos uma vez no BaseRef com aquele e-mail antes de ser adicionado.

---

## 5. Extração PICO

O framework **PICO** ajuda a estruturar a pergunta de pesquisa e a padronizar a extração de dados dos estudos incluídos.

1. Acesse **Extração PICO** no menu lateral.
2. Selecione o projeto ativo.
3. Para cada artigo, registre:
   - **P — População** — quem foi estudado.
   - **I — Intervenção** — o que foi aplicado, analisado ou proposto.
   - **C — Comparação** — o comparador ou grupo de controle, quando houver.
   - **O — Desfecho (Outcome)** — os resultados medidos.
   - **Contexto** — ambiente, país, setor ou cenário do estudo.

Os dados PICO acompanham o artigo e podem ser conferidos junto com as demais informações do projeto, servindo de base para a síntese dos resultados no manuscrito.

---

## 6. Importação de referências

### Formatos suportados

| Formato | Extensão | Bases compatíveis |
|---------|----------|-------------------|
| BibTeX | `.bib` | Scopus, Web of Science, IEEE Xplore, Mendeley, Zotero |
| RIS | `.ris` | Mendeley, Zotero, EndNote, PubMed, Embase |
| PubMed XML | `.xml` | PubMed (exportação MEDLINE XML) |
| CSV / texto | `.csv`, `.txt` | Exportações tabulares e listas simples |

O tamanho máximo por arquivo é de **50 MB**.

### Como importar

1. Acesse **Importação** no menu lateral.
2. Escolha o formato do arquivo nas abas: **BibTeX**, **RIS**, **PubMed XML** ou **CSV/Texto**.
3. Arraste o arquivo para a área indicada, ou clique nela para selecionar.
4. Preencha o **Nome da Fonte** (ex.: `Scopus 2024`, `WoS — dezembro/2023`).
5. Selecione a **Base de dados** de origem.
6. Informe a **Data da busca** — esse dado é exigido pelo relato PRISMA.
7. Clique em **Importar**.

Ao final, o sistema informa quantos registros foram encontrados, quantos foram importados e se houve erros de leitura em algum registro.

### Como exportar de cada base

**Scopus** — faça a busca, selecione os resultados → `Export` → `BibTeX` → marque todos os campos.

**Web of Science** — selecione os registros → `Export` → `BibTeX` (ou `RIS Format`) → exporte todos os campos.

**PubMed** — faça a busca, selecione os resultados → `Send to` → `File` → formato `PubMed` ou `XML` → `Create file`.

**Embase e demais bases** — exporte em `RIS`.

**Mendeley / Zotero / EndNote** — exporte a pasta desejada em `BibTeX` ou `RIS`.

> **Dica:** importe cada base em um arquivo separado, com nome de fonte próprio. O BaseRef registra a origem de cada referência, e o Diagrama PRISMA precisa dessa informação para relatar quantos registros vieram de cada base.

### Histórico de importações

A própria tela de importação lista os arquivos já processados, com nome, base, data da busca, quantidade de artigos e data em que foram importados.

---

## 7. Deduplicação

Ao importar de várias bases é normal que o mesmo artigo apareça mais de uma vez. Esta etapa identifica e resolve esses casos.

### Como detectar

1. Acesse **Duplicatas** no menu lateral.
2. Clique em **Detectar Duplicatas**.
3. Aguarde o processamento (em bases grandes pode levar alguns segundos).

A detecção usa dois critérios:

- **DOI idêntico** — correspondência exata do identificador.
- **Similaridade de título** — comparação textual, com grau de similaridade exibido para cada par.

### Revisando os pares

Cada par suspeito é exibido lado a lado. Para cada um você pode:

- **Confirmar como duplicata** — o registro secundário é marcado como duplicado e sai das etapas seguintes.
- **Rejeitar** — os dois registros são mantidos como estudos distintos.
- **Confirmar todos os pendentes** — aceita de uma vez todos os pares detectados (use com cautela, especialmente quando a similaridade for baixa).

### Redefinir

O botão **Redefinir** apaga os resultados da deduplicação e permite executar o processo novamente — útil após importar novas bases.

> **Importante:** artigos marcados como duplicata não aparecem na Triagem, na Elegibilidade nem nos gráficos bibliométricos, mas são contabilizados corretamente no Diagrama PRISMA como "registros removidos antes da triagem".

---

## 8. Triagem (título e resumo)

Nesta fase você avalia cada artigo lendo **título e resumo** e decide se ele segue para a avaliação de texto completo.

### Como triar

1. Acesse **Triagem** no menu lateral.
2. Para cada artigo, escolha:
   - **Incluir** — o artigo passa para a Elegibilidade.
   - **Excluir** — informe o **motivo da exclusão** (obrigatório) e, se quiser, uma observação.
   - **Desfazer** — devolve o artigo para a lista de pendentes.
3. A barra no topo mostra o progresso da triagem.

### Recursos da tela

- **Busca** — localize artigos por título ou resumo.
- **Filtros rápidos** — *Pendentes*, *Incluídos*, *Excluídos* e *Todos*.
- **Resumo completo** — clique no título do artigo para ler o resumo integral.
- **Tradução do resumo** — botão que traduz o resumo para português, útil em artigos em outros idiomas.
- **Classificação de relevância** — atribua de 1 a 5 estrelas (*pouco relevante* a *altamente relevante*) para priorizar a leitura do texto completo.
- **Sugestão da IA** — veja a recomendação automática antes de decidir (detalhado na seção seguinte).

> **Dica:** os motivos de exclusão são reaproveitados no Diagrama PRISMA e na exportação. Padronize a lista de motivos no início da revisão e mantenha-a estável até o fim.

---

## 9. Triagem assistida por IA

O BaseRef pode analisar título e resumo de um artigo e sugerir **incluir** ou **excluir**, sempre com base nos **critérios de inclusão e exclusão cadastrados no projeto** e no objetivo da revisão.

### Como usar

1. Preencha, no projeto, o objetivo e os critérios de inclusão e exclusão.
2. Na Triagem, acione a **sugestão da IA** para o artigo.
3. Leia a recomendação e a justificativa apresentada.
4. **A decisão final é sempre sua** — a sugestão não altera o status do artigo automaticamente.

> **Importante:** a sugestão da IA é um apoio à leitura, não uma substituição do revisor. Para fins de publicação, relate no manuscrito que houve apoio de IA na triagem e que todas as decisões foram conferidas por revisores humanos.

---

## 10. Elegibilidade (texto completo)

Os artigos incluídos na triagem avançam para esta fase, na qual você avalia o **texto completo**.

1. Acesse **Elegibilidade** no menu lateral.
2. Obtenha o texto completo do artigo (pelo DOI, pelo portal da revista ou pelo repositório da sua instituição).
3. Escolha:
   - **Incluir** — o artigo integra o corpus final da revisão.
   - **Excluir** — informe o motivo (critério de exclusão de texto completo).

A interface é idêntica à da Triagem, com os mesmos filtros, busca e observações. Os motivos de exclusão registrados aqui aparecem no bloco "textos completos excluídos, com motivos" do Diagrama PRISMA — exatamente como o protocolo exige.

---

## 11. Concordância entre revisores

Revisões sistemáticas de qualidade costumam ser conduzidas por **dois ou mais revisores independentes**. Esta tela mede o grau de acordo entre eles.

### Modos de revisão

- **Revisor único (padrão)** — apenas um avaliador decide; não há cálculo de concordância.
- **Dupla-cega (2 ou mais revisores independentes)** — cada revisor avalia sem ver a decisão do outro.

### Como configurar

1. Acesse **Concordância (κ)** no menu lateral.
2. Selecione o modo **Dupla-cega**.
3. Adicione os revisores pelo e-mail (o revisor precisa já ter feito login no BaseRef).
4. Escolha a fase a analisar: **Triagem (título/resumo)** ou **Elegibilidade (texto completo)**.

### O que a tela mostra

- **Kappa de Cohen (κ)** — índice de concordância entre dois revisores, descontando o acerto ao acaso. O cálculo exige **pelo menos dois revisores com decisões registradas** na fase escolhida.
- **Matriz de concordância** — cruzamento das decisões do Revisor 1 (R1) e do Revisor 2 (R2).
- **Conflitos pendentes** — artigos em que os revisores discordaram, para resolução por consenso ou por um terceiro avaliador.

### Como interpretar o kappa

| Valor de κ | Interpretação usual |
|------------|---------------------|
| < 0,00 | Sem concordância |
| 0,00 – 0,20 | Concordância leve |
| 0,21 – 0,40 | Concordância razoável |
| 0,41 – 0,60 | Concordância moderada |
| 0,61 – 0,80 | Concordância substancial |
| 0,81 – 1,00 | Concordância quase perfeita |

> **Dica:** relate no manuscrito o valor de κ obtido, a fase em que foi calculado e como os conflitos foram resolvidos. É uma exigência frequente dos avaliadores de periódicos.

---

## 12. Avaliação de qualidade e risco de viés

Após definir o corpus final, avalie a qualidade metodológica de cada estudo incluído.

### Escolher o instrumento

1. Acesse **Qualidade/Viés** no menu lateral.
2. Selecione o instrumento:
   - **CASP** — 10 itens (*Critical Appraisal Skills Programme*).
   - **JBI** — 8 itens (*Joanna Briggs Institute*).
   - **Personalizado** — crie suas próprias perguntas com o campo **Nova pergunta...**.
3. Confirme.

> **Importante:** trocar de instrumento **apaga todas as respostas já registradas**. Defina o instrumento antes de começar a avaliar.

### Avaliar os estudos

Para cada artigo incluído, responda item a item. As respostas possíveis são **sim**, **não** e **não claro** (*unclear*), e a tela consolida o total de cada categoria por estudo.

### Exportar

O botão de exportação gera um **CSV** com todas as respostas por artigo e por item — pronto para virar a tabela de avaliação de qualidade do seu artigo ou o material suplementar.

---

## 13. Diagrama PRISMA 2020

O diagrama de fluxo é gerado **automaticamente** a partir dos números reais do projeto — não é preenchido à mão.

### Como visualizar

1. Acesse **Diagrama PRISMA** no menu lateral.
2. O fluxograma é exibido já com os números atuais do projeto.

### Estrutura do diagrama

```
Identificação
  └── Registros identificados por base de dados
  └── Registros removidos antes da triagem (duplicatas)
Triagem
  └── Registros triados
  └── Registros excluídos (com motivos)
Elegibilidade
  └── Textos completos avaliados
  └── Textos completos excluídos (com motivos)
Incluídos
  └── Estudos incluídos na revisão
```

### Idioma

O diagrama acompanha o idioma selecionado no cabeçalho (**PT / EN / ES**) — troque a bandeira e o fluxograma é redesenhado com os rótulos traduzidos, pronto para submissão em periódico nacional ou internacional.

### Exportar

- **SVG** — formato vetorial, editável no Inkscape, Illustrator ou Figma; é a melhor opção para submissão.
- **PNG** — imagem em 150 dpi ou 300 dpi. A exportação em **300 dpi** atende à exigência da maioria dos periódicos.

> **Dica:** se os números não corresponderem ao esperado, verifique a deduplicação e as decisões pendentes de triagem — o diagrama sempre reflete o estado atual dos dados.

---

## 14. Bibliometria

A área de bibliometria analisa visualmente o corpus da revisão.

1. Acesse **Bibliometria** no menu lateral.
2. Os gráficos são carregados automaticamente.

### Análises disponíveis

| # | Análise | O que mostra |
|---|---------|--------------|
| 1 | Publicações por ano | Evolução temporal da produção |
| 2 | Artigos por base de dados | Distribuição por fonte de busca |
| 3 | Top periódicos | Revistas com mais publicações |
| 4 | Top autores | Autores mais produtivos |
| 5 | Top instituições | Afiliações mais frequentes |
| 6 | Top países | Ranking e mapa mundial |
| 7 | Distribuição de citações | Histograma de citações por artigo |
| 8 | Artigos mais citados | Estudos de maior impacto |
| 9 | Nuvem de palavras-chave | Termos mais frequentes |
| 10 | Co-ocorrência de keywords | Rede de termos relacionados |
| 11 | Rede de coautoria | Colaboração entre autores |
| 12 | Áreas temáticas | Classificação por área de conhecimento |

Os gráficos podem ser salvos como imagem para uso direto no manuscrito.

> **Nota:** as análises consideram os artigos **incluídos** na revisão. Metadados ausentes na base de origem (como afiliação ou citações) podem limitar alguns gráficos.

---

## 15. Referências formatadas

O BaseRef gera a lista de referências já formatada a partir dos metadados dos artigos.

1. Acesse **Referências** no menu lateral.
2. Escolha o escopo: **Apenas incluídos** ou **Todos (exceto duplicatas)**.
3. Clique em **Selecionar Artigos** e marque os que deseja.
4. Clique em **Gerar Referências**.
5. Escolha a norma:
   - **ABNT NBR 6023:2018**
   - **APA 7ª edição**
6. Copie a lista pronta para o seu editor de texto.

> **Importante:** confira sempre as referências geradas. A formatação depende da qualidade dos metadados exportados pela base de origem — nomes de autores abreviados ou títulos em caixa alta na origem exigem ajuste manual.

---

## 16. Exportação

1. Acesse **Exportação** no menu lateral.
2. Escolha o **escopo**:
   - **Todos os artigos** — tudo o que foi importado, exceto duplicatas.
   - **Aprovados na triagem** — artigos que passaram para a elegibilidade.
   - **Artigos incluídos** — o corpus final da revisão.
   - **Artigos excluídos** — descartados, com os respectivos motivos.
3. Escolha o formato: **CSV**, **BibTeX** ou **JSON**.

### Quando usar cada formato

| Formato | Ideal para |
|---------|------------|
| CSV | Análises em Excel, Google Sheets, R ou Python; material suplementar |
| BibTeX | Importar no Mendeley, Zotero ou LaTeX |
| JSON | Backup completo dos metadados e integração com outros sistemas |

> **Dica:** exportar os artigos excluídos em CSV, com os motivos, é a forma mais simples de montar o apêndice "estudos excluídos e razões" exigido por muitos periódicos.

---

## 17. Checklist PRISMA 2020

O checklist reúne os **27 itens** que o relato de uma revisão sistemática deve contemplar segundo o PRISMA 2020.

1. Acesse **Checklist PRISMA** no menu lateral.
2. Para cada item, registre a situação e informe a **página ou seção** do seu manuscrito onde ele é atendido (ex.: `p. 5, Seção 2.1`).
3. Use o campo de **comentários** para observações adicionais.
4. Acompanhe a barra **Progresso do Checklist**.

O checklist preenchido costuma ser solicitado como documento de submissão pelos periódicos, junto com o diagrama de fluxo.

---

## 18. Perguntas frequentes

**O BaseRef é gratuito?**
Sim. É 100% gratuito e open source, sob licença MIT.

**Preciso instalar alguma coisa?**
Não. O BaseRef funciona inteiramente no navegador. Seus dados ficam salvos na nuvem, isolados por usuário.

**Posso importar o mesmo arquivo duas vezes?**
Pode, mas o sistema não bloqueia a reimportação. Se isso acontecer, execute novamente a detecção de duplicatas.

**Alterei uma decisão de triagem de um artigo que já estava na elegibilidade. O que acontece?**
O artigo sai do escopo da elegibilidade e todos os números do Diagrama PRISMA são recalculados automaticamente.

**Como faço uma revisão com dois avaliadores independentes?**
Adicione o segundo revisor como colaborador do projeto, ative o modo **Dupla-cega** na tela de Concordância e resolva os conflitos apontados. O kappa de Cohen é calculado automaticamente.

**O Diagrama PRISMA pode ser editado manualmente?**
Não. Os números vêm das decisões registradas no sistema. Para alterá-los, ajuste os dados na triagem, na elegibilidade ou na deduplicação.

**Em que resolução devo exportar o diagrama?**
Prefira **SVG**. Se o periódico exigir imagem rasterizada, use **PNG a 300 dpi**.

**A IA decide sozinha quais artigos entram?**
Não. Ela apenas sugere, com base nos critérios que você cadastrou. A decisão é sempre do revisor.

**Como faço backup dos meus dados?**
Exporte o projeto em **JSON** (contém todos os metadados) e, se quiser, também em CSV e BibTeX. Guarde os arquivos junto com o material da pesquisa.

**Em quais idiomas o sistema funciona?**
Português, inglês e espanhol — inclusive o Diagrama PRISMA.

---

## 19. Suporte e como citar

### Suporte

Dúvidas sobre a plataforma, sobre revisões sistemáticas ou sobre o protocolo PRISMA 2020:
**rafaelgross123@gmail.com**

### Como citar o BaseRef

> GROSS, Rafael; GONÇALVES, Rodrigo Franco. **BaseRef**: plataforma de apoio à pesquisa e gestão de referências bibliográficas. Versão 1.0.0 [software]. Zenodo, 2026. DOI: 10.5281/zenodo.21119457. Disponível em: <https://baseref.com.br/>.

### Referência do protocolo

> PAGE, M. J. et al. The PRISMA 2020 statement: an updated guideline for reporting systematic reviews. **BMJ**, v. 372, n. 71, 2021. DOI: 10.1136/bmj.n71.

---

*BaseRef · Distribuído sob licença MIT · https://baseref.com.br*
