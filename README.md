# Feedback Kazza

Aplicacao web para coleta de feedback de workshops da Kazza.

O projeto tem um frontend estatico com formulario de avaliacao e uma API PHP simples para gravar as respostas em banco MySQL.

## O que a aplicacao faz

- Exibe um formulario de feedback com 3 avaliacoes de 1 a 5.
- Coleta nome, telefone, email, sugestao livre e aceite para receber novidades.
- Captura o parametro `evento` pela URL, por exemplo `?evento=casacor2025`.
- Envia os dados para uma API PHP.
- A API valida os dados e grava na tabela `feedback_workshop`.
- A API tambem pode listar os nomes do evento do dia para o telao de agradecimento.
- Depois do envio com sucesso, redireciona para `agradecimento.html`.

## Estrutura principal

- `index.html`: pagina principal do formulario.
- `style.css`: estilos visuais e responsividade.
- `script.js`: logica do formulario, mascara de telefone, validacao e envio.
- `agradecimento.html`: pagina exibida apos envio com sucesso.
- `api/feedback.php`: endpoint backend que recebe o JSON e grava no MySQL.
- `api/nomes.php`: endpoint backend que retorna os nomes do evento atual para o telao.
- `api/config.example.php`: modelo de configuracao do banco e CORS.

## Configuracao da API

Crie no servidor o arquivo:

```text
api/config.php
```

Use `api/config.example.php` como base e preencha:

- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `ALLOWED_ORIGINS`

O arquivo `api/config.php` nao deve ser enviado ao GitHub porque contem credenciais reais.

## Banco de dados

A API espera uma tabela MySQL chamada `feedback_workshop` com campos compativeis com:

- `id`
- `nome`
- `telefone`
- `email`
- `data`
- `resposta1`
- `resposta2`
- `resposta3`
- `resposta4`
- `evento`
- `receber_novidades`

## Deploy

O frontend pode ficar em hospedagem estatica, como Netlify.

A pasta `api/` precisa ficar em um servidor com PHP e acesso ao MySQL.

Se o frontend e a API ficarem em dominios diferentes, ajuste `ALLOWED_ORIGINS` em `api/config.php`.

## Observacoes de seguranca

- Nao versionar `api/config.php`.
- Usar HTTPS no endpoint da API.
- Usar usuario MySQL com permissao minima necessaria.
- Restringir `ALLOWED_ORIGINS` ao dominio real do site.
