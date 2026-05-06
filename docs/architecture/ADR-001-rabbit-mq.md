# ADR-001: Migração para RabbitMQ para garantir entrega at-least-once

## Data
2026-05-05

## Contexto
O serviço `idler` utilizava Redis como fila para processamento de e-mails. Embora o Redis ofereça alta performance (baixa latência), ele não garante a entrega da mensagem (o e-mail pode ser perdido caso o serviço falhe durante o processamento após o `RPOP`). O requisito atual exige que **nenhum e-mail seja perdido**.

## Decisão
Migrar a infraestrutura de fila de Redis para RabbitMQ.

## Motivação
- Necessidade de garantias de entrega (`ACKs`).
- Necessidade de filas de espera persistentes (`Durable Queues`).
- Suporte a `Dead Letter Queues` (DLQ) para tratamento de falhas.

## Trade-offs
- **Positivos:** Confiabilidade total ("at-least-once" delivery), suporte a DLQ, desacoplamento nativo com o modelo AMQP.
- **Negativos:** Aumento na complexidade operacional e leve incremento na latência devido ao overhead do protocolo AMQP e persistência em disco.

## Consequências
- Implementação de um `IQueueClient` para desacoplar a lógica da biblioteca `amqplib`.
- Necessidade de infraestrutura de RabbitMQ gerenciada (com volumes persistentes).
- Introdução de mecanismos de ACK/NACK no processamento.
