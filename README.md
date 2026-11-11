# Transporte

Sistema de gestión para una empresa de transporte de cargas: guías, flota, clientes, cuentas corrientes, caja y facturación interna.

## Stack

- **Laravel 13** + **Livewire 4** (componentes de archivo único, prefijo `⚡`) + **Flux UI**
- **Tailwind CSS v4** (configuración CSS-first en `resources/css/app.css`, sin `tailwind.config.js`)
- **Vite** con fuentes autohospedadas (Instrument Sans, Geist Mono)
- **Fortify** para login, 2FA y passkeys

## Módulos

| Grupo | Módulos |
|---|---|
| Principal | Dashboard |
| Operación | Guías, Transportes, Fleteros, Productos, Clientes |
| Finanzas | Caja, Cobranzas, Órdenes de pago, Cheques, Cuentas bancarias |
| Sistema (solo admin) | Usuarios, Auditoría, Configuración |

Tres roles: **administrador**, **administrativo**, **usuario de carga**.

## Puesta en marcha local

```bash
composer setup   # instala dependencias, copia .env, genera key, migra, compila assets
composer dev      # servidor + queue listener + vite, todo en paralelo
```

## Calidad

```bash
composer lint         # Pint (lint)
composer lint:check   # Pint en modo chequeo (no modifica nada)
composer types:check  # PHPStan
composer test          # suite completa (Pest/PHPUnit)
composer ci:check      # lo mismo que corre en GitHub Actions
```

## Entornos

| Entorno | Dónde |
|---|---|
| Local | Laragon, `http://transporte.test` |
| Producción | `https://transporte.jjsoftware.click` |

El despliegue a producción es manual (FTP), sin CD automático todavía.

---

Privado — desarrollado para uso interno del cliente. No distribuir.
