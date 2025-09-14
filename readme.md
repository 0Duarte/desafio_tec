# Banking

![CI](https://github.com/0Duarte/desafio_tec/actions/workflows/ci.yml/badge.svg)
![PHPStan](https://img.shields.io/badge/Code%20Analysis-PHPStan-blue)
![Laravel](https://img.shields.io/badge/Laravel-12-red)
![PHP](https://img.shields.io/badge/PHP-8.2-blue)
![NGINX](https://img.shields.io/badge/NGINX-1.25-green)


Projeto Laravel para **transferência de saldo entre usuários**, com validações de negócio, notificações e testes automatizados.

---

## 1. Visão Geral

Este projeto implementa um serviço de **transferência entre carteiras de usuários**,

🚨Foi pautado com pensando em práticas (em menor escala) que seguem padrões do setor Financeiro, como o armazenamento dos
valores como tipo Integer em centavos (utilizado por REDE Itau, PagSeguro...) para dar integridade aos valores.

🚨 Registrar logs e manter todas as tentativas de transações registradas (Auditorias, argumento jurídico). 

Regras de negócio:

- Usuários do tipo **lojista** não podem realizar transferências.
- Transferência só é permitida se o **saldo do pagador** for suficiente.
- Status da transferência registrado como `completed` ou `failed`.
- Notificações enviadas ao recebedor após a transferência.


Tecnologias utilizadas:

- Laravel 12
- PHP 8.2
- MySQL / SQLite
- PHPUnit
- GitHub Actions para CI/CD

- Filas e Jobs
- Utilizado o suporte a queue:database do Laravel.

Melhorias futuras são abordadas no último tópico da documentação.
---

## 2. Instalação

### Pré-requisitos

- Docker

```bash

# Gere o ambiente com o docker compose
docker compose up -d --build
```

### Passos

```bash
# Clone o repositório e entre na pasta
git clone https://github.com/0Duarte/desafio_tec.git
cd banking

# Instale as dependências PHP
composer install

# Copie o arquivo de ambiente
cp .env.example .env

# Gere a chave da aplicação
php artisan key:generate

# Rode as migrations e seeders
php artisan migrate
php artisan db:seed
```

## 3. Configuração de Banco de Dados

Por padrão, o desenvolvimento pode usar MySQL. Exemplo de `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=secret
```

Para testes, usamos **SQLite em memória** (rápido e isolado). Crie/ajuste `.env.testing`:

```
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

## 4. Testes

Rodar a suíte de testes:

```bash
php artisan test --env--testing
```

## 5. Análise estática

Rode o Larastan (phpStan):
Sua escolha se deu por familiaridade e uso do dia a dia

```bash
composer analyse
```

## 6. Arquitetura

O projeto segue uma arquitetura **modular e orientada a serviços**, usando padrões comuns do Laravel.

### Componentes principais

- **Controllers**  
  Recebem as requisições HTTP e transformam os dados em DTOs (`TransferRequestDTO`).  
  São responsáveis apenas por **orquestrar o fluxo**, delegando a lógica de negócio para os serviços.

- **Services (Service Layer)**  
  - `TransferService` é o coração da aplicação.  
  - Responsável por:
    - Validar regras de negócio (saldo do pagador, tipo de usuário).
    - Atualizar saldo das carteiras.
    - Registrar status da transferência (`completed` ou `failed`).
    - Notificar o recebedor da transferência.
  - Integrar com serviços externos (`AuthorizationExternalService`).

- **Repositories**  
  Abstraem o acesso ao banco de dados:
  - `WalletRepository` lida com leitura e atualização de carteiras.
  - `TransferRepository` cria, finaliza e atualiza transferências.
  - Essa camada facilita **testes unitários** e mantém o Service Layer focado na lógica de negócio.

- **DTOs (Data Transfer Objects)**  
  - `TransferRequestDTO` encapsula os dados de uma transferência (payer, payee, valor).  
  - Permite validação e conversão de tipos antes de chegar ao Service Layer.

- **Notifications**  
  - `TransferCompleted` Um dos componentes que mais se beneficia das classes do Laravel, extende a classe de notificações nativa, que dispara os canais setados (sms, email) além de usar a trait ShouldQueue, enviando diretamente para a queue. O que permite adicionar retry e garantir que seja novamente enviado mesmo que o serviço esteja indisponível no momento.

### Fluxo de uma transferência

1. O **controller** recebe a requisição e cria um `TransferRequestDTO`.
2. O **TransferService** inicia a transferência (`initTransfer`).
3. Valida regras de negócio (saldo, tipo de usuário).
4. Atualiza o saldo das carteiras via `WalletRepository`.
5. Autoriza externamente a transferência (`AuthorizationExternalService`).
6. Finaliza a transferência (`finalizeTransfer`) ou marca como `failed` em caso de erro.
7. Dispara notificação ao recebedor.
8. Toda operação ocorre dentro de uma **transação do banco de dados** para garantir atomicidade.

### Padrões aplicados

- **Service Layer:** concentra lógica de negócio.  
- **Repository Pattern:** abstrai acesso ao banco.  
- **DTOs:** encapsulam e validam dados.  
- **QUEUE / JOBS:** notificações disparadas após eventos.  
- **Transações (DB::transaction):** garantem consistência de dados.
- **SOLID** utilizado como base
- **Clean Code** utilizado como base

## 7. CI (GitHub Actions)

O workflow `/.github/workflows/ci.yml` executa:
- Testes Automatizados
- Análise estática
---

## 8 Melhorias Futuras

- 1️⃣ **Bloqueio de conta após várias tentativas não autorizadas**  
  Reforçar a segurança implementando um mecanismo de bloqueio temporário ou permanente em caso de múltiplas tentativas inválidas de transferência.

- 2️⃣ **Bloqueio de cashout em horários restritos**  
  Restringir saques e transferências em determinados horários (ex.: fora do expediente bancário), aumentando a segurança contra fraudes.

- 3️⃣ **Registro de notificações enviadas**  
  Garantir que o sistema registre se a notificação ao recebedor foi enviada com sucesso, permitindo rastreabilidade e auditoria.

- 4️⃣ **Utilizar um banco NoSQL para logs**  
  Separar os registros de logs em um banco **não relacional** (ex: MongoDB ou DynamoDB) aproveitando o **esquema aberto**, que facilita armazenar diferentes formatos de eventos (transferências, falhas, notificações).  
  Isso permite **alta performance na escrita**, **consultas flexíveis** e não sobrecarrega o banco relacional usado nas transações principais.

- 5️⃣ **Laravel Octane + Swoole**  
  Otimizar a performance da aplicação utilizando Octane e Swoole, que permitem manter processos PHP rodando de forma persistente e assíncrona. Isso reduz a latência e acelera operações que hoje exigem boot completo do framework, como:
  - Chamadas ao serviço externo de autorização.  
  - Processamento de múltiplas requisições simultâneas.  
  Octane/Swoole pode ser utilizado **isoladamente** como ganho de performance imediato.

- 6️⃣ **Fila de processamento de transferências (mensageria)**  
  Adotar RabbitMQ, Kafka ou AWS SQS para processar transferências e notificações de forma assíncrona e distribuída.  
  Benefícios:
  - Garantia de processamento confiável (retry automático em falhas).  
  - Escalabilidade horizontal para lidar com alto volume de transações.  
  - Melhor desacoplamento entre serviços.

- 7️⃣ **Combinação Octane + Mensageria**  
  Embora funcionem bem de forma independente, Octane/Swoole e mensageria podem ser combinados:  
  - Octane melhora a performance do servidor de aplicação.  
  - Mensageria garante resiliência e consistência no processamento assíncrono.  
  Isso cria uma arquitetura robusta, performática e preparada para alta escala.
