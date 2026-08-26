# Tunisia Platform

A microservices platform for a Tunisia-based booking/provider marketplace, with an AI service powered by locally-hosted LLMs (Ollama) for chat, classification and recommendations.

> Backend services live in this repo. The Angular back-office admin app is published separately: [tunisia-platform-back-office](#).

## Architecture

```
                        ┌─────────────┐
                        │    Nginx    │   API Gateway
                        │  (gateway)  │
                        └──────┬──────┘
           ┌───────────────────┼───────────────────┐
           │                   │                    │
   ┌───────▼──────┐   ┌────────▼───────┐   ┌────────▼───────┐
   │ auth-service │   │ booking-service│   │provider-service│
   │   Laravel    │   │    Laravel     │   │    Laravel     │
   └───────┬──────┘   └────────┬───────┘   └────────┬───────┘
           │                   │                    │
           │          ┌────────▼───────┐            │
           └──────────►   ai-service   ◄────────────┘
                      │    Laravel     │
                      └────────┬───────┘
                               │
                      ┌────────▼───────┐
                      │  Ollama (LLM)  │  chat / classify / recommend
                      │ mistral:7b     │  llama3.1:8b
                      └────────────────┘

Shared infrastructure: PostgreSQL/PostGIS · Redis · RabbitMQ · Elasticsearch + Kibana
```

## Services

| Service | Stack | Responsibility |
|---|---|---|
| `auth-service` | Laravel + Sanctum | Authentication, token issuance, shared identity for all services |
| `booking-service` | Laravel | Booking lifecycle, scheduling |
| `provider-service` | Laravel + PostGIS | Provider/listing management, geolocation search |
| `ai-service` | Laravel | Orchestrates local LLM calls (Ollama) for chat, request classification and recommendations, calling into `provider-service` and `booking-service` for context |
| `nginx` | Nginx | API gateway, single entrypoint routing to each service |

## Why this design

- **Service-per-domain**: auth, booking and provider concerns are isolated, independently deployable Laravel apps sharing a Postgres cluster via separate databases.
- **AI as its own service**: LLM orchestration (prompting, model selection per task — chat vs. classify vs. recommend) is isolated from business services, called over HTTP rather than embedded, so business services stay LLM-agnostic.
- **Local-first LLMs**: Ollama runs models on the host (`mistral:7b`, `llama3.1:8b`) rather than calling a third-party API — no per-request cost, no data leaving the infrastructure, swappable with a hosted provider (Claude, OpenAI) behind the same `ai-service` interface.
- **Async messaging via RabbitMQ**: inter-service events (e.g. a booking created) are queued rather than synchronously chained, so services degrade independently under load.
- **Search via Elasticsearch/Kibana**: provider/listing search offloaded from Postgres, with Kibana for operational visibility into indexed data.

## Running locally

```bash
docker compose up -d
```

Each service exposes its own port (see `docker-compose.yml`); Nginx fronts them on `:80`. Copy each service's `.env.example` to `.env` before first run — `.env` files are gitignored and never committed.

## Stack

Laravel · PostgreSQL/PostGIS · Redis · RabbitMQ · Elasticsearch · Kibana · Ollama · Docker Compose · Nginx
