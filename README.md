<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# Cine API - Sistema de Reservas de Cinema

Uma API RESTful desenvolvida em **Laravel** para gestão de bilheteira e reservas de assentos em salas de cinema. O projeto foca-se na resolução de problemas clássicos de concorrência (*double booking*) e no ciclo de vida de uma reserva temporária (carrinho de compras).

## Tecnologias Utilizadas

* **PHP 8.x**
* **Laravel 13**
* **SQLite** (Configurado por padrão para facilitar o setup, mas totalmente compatível com MySQL/PostgreSQL)

## Escolhas de Arquitetura e Padrões

Este projeto foi construído seguindo os princípios de um design relacional sólido e boas práticas de desenvolvimento backend:

1. **Prevenção de Race Conditions (Concorrência):**
   * **Problema:** Num sistema de bilheteira, dois utilizadores podem tentar comprar o mesmo lugar exatamente no mesmo milissegundo.
   * **Solução:** Utilização de **Database Transactions** (`DB::transaction`) combinadas com **Pessimistic Locking** (`lockForUpdate()`). Isso garante a atomicidade da operação; a primeira requisição tranca a linha do banco de dados até ser concluída, forçando a segunda requisição a aguardar e, consequentemente, a falhar ao verificar que o assento já não está disponível.

2. **Otimização de Consultas (N+1 Queries):**
   * **Problema:** Renderizar o mapa de uma sala com 100 lugares poderia gerar dezenas de consultas ao banco de dados para verificar o estado de cada assento individualmente.
   * **Solução:** Uso de **Eager Loading** (`with()`) para carregar as relações e manipulação de coleções em memória usando `keyBy()`. A API faz uma única consulta para trazer as reservas ativas e mapeia o estado dos assentos em tempo constante $O(1)$, garantindo tempos de resposta ultrarrápidos.

3. **Ciclo de Vida Autónomo (Task Scheduling):**
   * Para evitar que assentos fiquem presos indefinidamente caso o utilizador abandone a compra, foi implementado um **Console Command** customizado (`reservations:clear-expired`).
   * Este comando é gerido pelo **Laravel Task Scheduler**, rodando em background a cada minuto para libertar assentos "pendentes" cujo tempo limite de 10 minutos tenha expirado.

## Lógica de Negócio

O fluxo de compra de um bilhete segue um modelo de reserva temporária em 3 passos:

1. **Visualização do Mapa (`GET /api/sessoes/{id}/assentos`)**
   * O cliente requisita o mapa de assentos de uma sessão.
   * A API cruza os assentos físicos da sala com as reservas ativas daquela sessão.
   * Retorna uma matriz onde cada assento tem um estado dinâmico: `available` (livre), `pending` (no carrinho de alguém) ou `confirmed` (vendido).

2. **Bloqueio Temporário (`POST /api/reservar`)**
   * O utilizador seleciona um assento.
   * A API valida se o assento pertence à sala correta e se está livre.
   * Se sim, cria uma reserva com estado `pending` e uma data de expiração (`expires_at`) para dali a 10 minutos. O assento passa a aparecer como indisponível para os outros clientes.

3. **Confirmação ou Expiração (`POST /api/reservas/{id}/confirmar`)**
   * **Caminho Feliz:** O utilizador efetua o pagamento dentro dos 10 minutos. A API muda o estado da reserva para `confirmed` e anula a data de expiração. O assento é garantido permanentemente.
   * **Abandono:** Se o tempo passar sem confirmação, o utilizador recebe um erro ao tentar pagar. Paralelamente, o *Scheduler* do servidor identifica a expiração, altera o estado para `cancelled` e o assento volta a ficar `available`.

## Como Executar o Projeto Localmente

**1. Clone o repositório e instale as dependências:**
```bash
git clone https://github.com/CiceroLucas/cine-api.git
cd cine-api
composer install